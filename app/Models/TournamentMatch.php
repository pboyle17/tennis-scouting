<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TournamentMatch extends Model
{
    protected $fillable = ['tournament_draw_id', 'utr_result_id', 'played_on', 'round_code', 'round_name', 'outcome', 'completion_type'];

    protected $casts = [
        'played_on' => 'date',
    ];

    public function draw()
    {
        return $this->belongsTo(TournamentDraw::class, 'tournament_draw_id');
    }

    public function matchPlayers()
    {
        return $this->hasMany(TournamentMatchPlayer::class);
    }

    public function sets()
    {
        return $this->hasMany(TournamentMatchSet::class)->orderBy('set_number');
    }

    /**
     * Order of the round within a draw: round robin ("Round 1", "Round 2", ...) first,
     * then the main bracket, then the consolation bracket (C-QF-Q, C-QF, C-SF, C-F).
     */
    public function roundRank(): int
    {
        $code = $this->round_code;
        if ($code === null) {
            return 0;
        }
        if (preg_match('/^Round (\d+)$/', $code, $m)) {
            return min((int) $m[1], 19);
        }

        $bracket = ['R128' => 0, 'R64' => 1, 'R32' => 2, 'R16' => 3, 'QF-Q' => 4, 'QF' => 5, 'SF' => 6, 'F' => 7];
        $consolation = str_starts_with($code, 'C-');
        $stage = $bracket[$consolation ? substr($code, 2) : $code] ?? 8;

        return ($consolation ? 40 : 20) + $stage;
    }

    public function isWalkover(): bool
    {
        return $this->completion_type === 'winnerdidnotstart' || $this->outcome === 'walkover';
    }

    /**
     * Score from the winner's side, e.g. "7-6(7) 0-6 1-0(8)".
     */
    public function scoreText(): string
    {
        return $this->sets->map(function ($set) {
            $text = "{$set->winner_score}-{$set->loser_score}";
            if ($set->loser_tiebreak !== null) {
                $text .= "({$set->loser_tiebreak})";
            }
            return $text;
        })->implode(' ');
    }
}
