<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fixture extends Model
{
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = [
        'round_id',
        'group_id',
        'match_number',
        'match_date',
        'home_team_id',
        'away_team_id',
        'home_placeholder',
        'away_placeholder',
        'home_score',
        'away_score',
        'winner_team_id',
        'winner_feeds_match_id',
        'winner_feeds_slot',
        'went_to_extra_time',
        'status',
        'venue',
    ];

    protected function casts(): array
    {
        return [
            'match_date' => 'datetime',
            'went_to_extra_time' => 'boolean',
            'home_score' => 'integer',
            'away_score' => 'integer',
            'winner_team_id' => 'integer',
            'winner_feeds_match_id' => 'integer',
            'home_team_id' => 'integer',
            'away_team_id' => 'integer',
        ];
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function winnerTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'winner_team_id');
    }

    public function winnerFeedsMatch(): BelongsTo
    {
        return $this->belongsTo(Fixture::class, 'winner_feeds_match_id');
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class, 'match_id');
    }

    public function isGroupStage(): bool
    {
        return $this->group_id !== null;
    }

    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }

    public function isLive(): bool
    {
        return $this->status === 'in_progress';
    }
}
