<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TournamentResult extends Model
{
    protected $fillable = [
        'champion_team_id',
        'runner_up_team_id',
        'top_scorer_player_id',
    ];
}
