<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Configuration;

class ConfigurationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
      $configurations = Configuration::all();
      return view('configurations.index', compact('configurations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('configurations.create');
    }

  public function store(Request $request)
  {
      $request->validate(['jwt' => 'required|string']);
      Configuration::create($request->all());
      return redirect()->route('configurations.index')->with('success', 'Configuration created.');
  }

  public function show(Configuration $configuration)
  {
      return view('configurations.show', compact('configuration'));
  }

  public function edit(Configuration $configuration)
  {
      return view('configurations.edit', compact('configuration'));
  }

  public function update(Request $request, Configuration $configuration)
  {
      $request->validate(['jwt' => 'required|string']);
      $configuration->update($request->all());
      return redirect()->route('configurations.index')->with('success', 'Configuration updated.');
  }

  public function destroy(Configuration $configuration)
  {
      $configuration->delete();
      return redirect()->route('configurations.index')->with('success', 'Configuration deleted.');
  }

  /**
   * Get database connection details from DATABASE_URL or default config
   */
  private function parseConnectionDetails()
  {
      // Try to get DATABASE_URL from environment
      $databaseUrl = env('DATABASE_URL');

      // If no DATABASE_URL, use default Laravel config
      if (!$databaseUrl) {
          return [
              'host' => config('database.connections.pgsql.host'),
              'port' => config('database.connections.pgsql.port'),
              'database' => config('database.connections.pgsql.database'),
              'username' => config('database.connections.pgsql.username'),
              'password' => config('database.connections.pgsql.password'),
          ];
      }

      // Parse DATABASE_URL format: postgres://username:password@host:port/database
      $url = parse_url($databaseUrl);

      return [
          'host' => $url['host'] ?? 'localhost',
          'port' => $url['port'] ?? '5432',
          'database' => ltrim($url['path'] ?? '', '/'),
          'username' => $url['user'] ?? '',
          'password' => $url['pass'] ?? '',
      ];
  }

  /**
   * Backup database and upload to S3
   */
  public function backupDatabase()
  {
      try {
          // Get database connection details
          $db = $this->parseConnectionDetails();
          $dbHost = $db['host'];
          $dbPort = $db['port'];
          $dbName = $db['database'];
          $dbUser = $db['username'];
          $dbPassword = $db['password'];

          // Create filename with environment, database name, and timestamp
          $env = app()->environment();
          $timestamp = now()->format('Y-m-d_His');
          $filename = "{$env}_backup_{$dbName}_{$timestamp}.sql";
          $localPath = storage_path("app/backups/{$filename}");

          // Ensure backup directory exists
          if (!file_exists(storage_path('app/backups'))) {
              mkdir(storage_path('app/backups'), 0755, true);
          }

          // Set PGPASSWORD environment variable
          putenv("PGPASSWORD={$dbPassword}");

          // Create database dump using pg_dump
          $command = sprintf(
              'pg_dump -h %s -p %s -U %s -d %s -F c -f %s 2>&1',
              escapeshellarg($dbHost),
              escapeshellarg($dbPort),
              escapeshellarg($dbUser),
              escapeshellarg($dbName),
              escapeshellarg($localPath)
          );

          exec($command, $output, $returnCode);

          // Clear PGPASSWORD
          putenv('PGPASSWORD');

          if ($returnCode !== 0) {
              \Log::error('Database backup failed', [
                  'command' => $command,
                  'output' => $output,
                  'return_code' => $returnCode
              ]);
              return redirect()->route('configurations.index')->with('error', 'Database backup failed: ' . implode("\n", $output));
          }

          // Upload to S3
          $s3Path = "backups/{$filename}";
          \Storage::disk('s3')->put($s3Path, file_get_contents($localPath));

          // Delete local backup file
          unlink($localPath);

          $message = "Database backup uploaded successfully to S3: {$filename}";
          \Log::info($message);

          return redirect()->route('configurations.index')->with('success', $message);

      } catch (\Exception $e) {
          \Log::error('Database backup error', [
              'error' => $e->getMessage(),
              'trace' => $e->getTraceAsString()
          ]);

          return redirect()->route('configurations.index')->with('error', 'Database backup failed: ' . $e->getMessage());
      }
  }

  /**
   * List all available backups from S3
   */
  public function listBackups()
  {
      try {
          // List all backup files in S3
          $files = \Storage::disk('s3')->files('backups');

          if (empty($files)) {
              return redirect()->route('configurations.index')->with('error', 'No backup files found in S3.');
          }

          // Sort files by name (which includes timestamp) to get newest first
          rsort($files);

          $backups = [];
          foreach ($files as $file) {
              $filename = basename($file);

              // Parse timestamp from filename (format: env_backup_dbname_Y-m-d_His.sql)
              preg_match('/.*?_backup_.*?_(\d{4}-\d{2}-\d{2}_\d{6})/', $filename, $matches);
              $dateStr = 'Unknown';
              if (isset($matches[1])) {
                  $date = \DateTime::createFromFormat('Y-m-d_His', $matches[1]);
                  if ($date) {
                      $dateStr = $date->format('M d, Y g:i A');
                  }
              }

              // Get file size
              $size = \Storage::disk('s3')->size($file);
              $sizeFormatted = $this->formatBytes($size);

              $backups[] = [
                  'path' => $file,
                  'filename' => $filename,
                  'date' => $dateStr,
                  'size' => $sizeFormatted,
              ];
          }

          return redirect()->route('configurations.index')->with('backups', $backups);

      } catch (\Exception $e) {
          \Log::error('Failed to list backups', [
              'error' => $e->getMessage(),
              'trace' => $e->getTraceAsString()
          ]);

          return redirect()->route('configurations.index')->with('error', 'Failed to list backups: ' . $e->getMessage());
      }
  }

  /**
   * Format bytes to human readable format
   */
  private function formatBytes($bytes, $precision = 2)
  {
      $units = ['B', 'KB', 'MB', 'GB', 'TB'];

      for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
          $bytes /= 1024;
      }

      return round($bytes, $precision) . ' ' . $units[$i];
  }

  /**
   * Restore database from selected S3 backup
   */
  public function restoreDatabase(Request $request)
  {
      try {
          $backupFile = $request->input('filename');

          if (!$backupFile) {
              return redirect()->route('configurations.index')->with('error', 'No backup file specified.');
          }

          \Log::info('Restoring database from backup', ['file' => $backupFile]);

          // Get database connection details
          $db = $this->parseConnectionDetails();
          $dbHost = $db['host'];
          $dbPort = $db['port'];
          $dbName = $db['database'];
          $dbUser = $db['username'];
          $dbPassword = $db['password'];

          // Create local path for downloaded backup
          $filename = basename($backupFile);
          $localPath = storage_path("app/backups/{$filename}");

          // Ensure backup directory exists
          if (!file_exists(storage_path('app/backups'))) {
              mkdir(storage_path('app/backups'), 0755, true);
          }

          // Download backup from S3
          $backupContent = \Storage::disk('s3')->get($backupFile);
          file_put_contents($localPath, $backupContent);

          \Log::info('Downloaded backup from S3', ['local_path' => $localPath]);

          // Drop all connections to the database
          \DB::disconnect('pgsql');

          // Set PGPASSWORD environment variable
          putenv("PGPASSWORD={$dbPassword}");

          // Terminate existing connections (requires superuser or database owner privileges)
          $terminateCommand = sprintf(
              "psql -h %s -p %s -U %s -d postgres -c \"SELECT pg_terminate_backend(pg_stat_activity.pid) FROM pg_stat_activity WHERE pg_stat_activity.datname = %s AND pid <> pg_backend_pid();\" 2>&1",
              escapeshellarg($dbHost),
              escapeshellarg($dbPort),
              escapeshellarg($dbUser),
              escapeshellarg($dbName)
          );
          exec($terminateCommand, $terminateOutput, $terminateReturnCode);

          // Generate table of contents from backup, filtering out rackets and string_jobs tables
          // This preserves equipment data during restores
          $tocPath = storage_path('app/backups/toc.txt');
          $filteredTocPath = storage_path('app/backups/toc_filtered.txt');

          // Get list of objects in backup
          $listCommand = sprintf(
              'pg_restore -l %s 2>&1',
              escapeshellarg($localPath)
          );
          exec($listCommand, $tocOutput, $listReturnCode);

          // Filter out rackets and string_jobs tables
          $filteredToc = array_filter($tocOutput, function($line) {
              // Exclude lines that reference rackets or string_jobs tables
              return !preg_match('/\brackets\b/', $line) && !preg_match('/\bstring_jobs\b/', $line);
          });
          file_put_contents($filteredTocPath, implode("\n", $filteredToc));

          \Log::info('Generated filtered TOC for restore', [
              'original_lines' => count($tocOutput),
              'filtered_lines' => count($filteredToc)
          ]);

          // Restore database using filtered list
          // --clean: clean (drop) database objects before recreating
          // --if-exists: use IF EXISTS when dropping objects
          // --no-owner: skip restoration of object ownership
          // --no-privileges: skip restoration of access privileges (ACL)
          // -L: use filtered list to exclude rackets and string_jobs tables
          $restoreCommand = sprintf(
              'pg_restore -h %s -p %s -U %s -d %s --clean --if-exists --no-owner --no-privileges -L %s -v %s 2>&1',
              escapeshellarg($dbHost),
              escapeshellarg($dbPort),
              escapeshellarg($dbUser),
              escapeshellarg($dbName),
              escapeshellarg($filteredTocPath),
              escapeshellarg($localPath)
          );

          exec($restoreCommand, $restoreOutput, $restoreReturnCode);

          // Clean up TOC file
          if (file_exists($filteredTocPath)) {
              unlink($filteredTocPath);
          }

          // Clear PGPASSWORD
          putenv('PGPASSWORD');

          // Delete local backup file
          unlink($localPath);

          // Reconnect to database
          \DB::purge('pgsql');
          \DB::reconnect('pgsql');

          if ($restoreReturnCode !== 0) {
              \Log::error('Database restore failed', [
                  'command' => $restoreCommand,
                  'output' => $restoreOutput,
                  'return_code' => $restoreReturnCode
              ]);
              return redirect()->route('configurations.index')->with('error', 'Database restore encountered issues. Check logs for details.');
          }

          $message = "Database successfully restored from S3 backup: {$filename}";
          \Log::info($message);

          return redirect()->route('configurations.index')->with('success', $message);

      } catch (\Exception $e) {
          \Log::error('Database restore error', [
              'error' => $e->getMessage(),
              'trace' => $e->getTraceAsString()
          ]);

          // Try to reconnect to database
          try {
              \DB::purge('pgsql');
              \DB::reconnect('pgsql');
          } catch (\Exception $reconnectError) {
              \Log::error('Failed to reconnect to database', ['error' => $reconnectError->getMessage()]);
          }

          return redirect()->route('configurations.index')->with('error', 'Database restore failed: ' . $e->getMessage());
      }
  }
}
