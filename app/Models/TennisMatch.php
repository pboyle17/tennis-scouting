<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TennisMatch extends Model
{
    protected $fillable = [
        'league_id',
        'home_team_id',
        'away_team_id',
        'location',
        'start_time',
        'home_score',
        'away_score',
        'external_id',
        'tennis_record_match_link'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'home_score' => 'integer',
        'away_score' => 'integer',
    ];

    /**
     * Get the league that the match belongs to
     */
    public function league()
    {
        return $this->belongsTo(League::class);
    }

    /**
     * Get the home team
     */
    public function homeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    /**
     * Get the away team
     */
    public function awayTeam()
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    /**
     * Get the courts for this match
     */
    public function courts()
    {
        return $this->hasMany(Court::class);
    }

    /**
     * Determine the winning side ('home' or 'away'), or null if unplayed or still tied.
     * Ties on courts won are broken by total sets won, then total games won
     * (match tiebreaks are stored as a 1-0 set, so they count as one game).
     */
    public function winningSide(): ?string
    {
        if ($this->home_score === null || $this->away_score === null) return null;
        if ($this->home_score === 0 && $this->away_score === 0) return null;

        if ($this->home_score !== $this->away_score) {
            return $this->home_score > $this->away_score ? 'home' : 'away';
        }

        $tiebreak = $this->tiebreakTotals();
        if ($tiebreak['home_sets'] !== $tiebreak['away_sets']) {
            return $tiebreak['home_sets'] > $tiebreak['away_sets'] ? 'home' : 'away';
        }
        if ($tiebreak['home_games'] !== $tiebreak['away_games']) {
            return $tiebreak['home_games'] > $tiebreak['away_games'] ? 'home' : 'away';
        }

        return null;
    }

    /**
     * How a tied court score was decided: 'sets', 'games', or null if not decided by tiebreak.
     */
    public function tiebreakMethod(): ?string
    {
        if ($this->home_score === null || $this->home_score !== $this->away_score || $this->home_score === 0) {
            return null;
        }

        $tiebreak = $this->tiebreakTotals();
        if ($tiebreak['home_sets'] !== $tiebreak['away_sets']) return 'sets';
        if ($tiebreak['home_games'] !== $tiebreak['away_games']) return 'games';

        return null;
    }

    /**
     * Total sets (from court scores) and games (from court sets) for each side.
     */
    public function tiebreakTotals(): array
    {
        $totals = ['home_sets' => 0, 'away_sets' => 0, 'home_games' => 0, 'away_games' => 0];

        foreach ($this->courts as $court) {
            $totals['home_sets'] += (int) $court->home_score;
            $totals['away_sets'] += (int) $court->away_score;
            foreach ($court->courtSets as $set) {
                $totals['home_games'] += (int) $set->home_score;
                $totals['away_games'] += (int) $set->away_score;
            }
        }

        return $totals;
    }

    public function homeWon(): bool
    {
        return $this->winningSide() === 'home';
    }

    public function awayWon(): bool
    {
        return $this->winningSide() === 'away';
    }
}
