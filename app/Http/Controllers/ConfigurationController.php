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
}
