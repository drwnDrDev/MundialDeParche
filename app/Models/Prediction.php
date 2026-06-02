<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'match_id',
        'predicted_home',
        'predicted_away',
        'predicted_winner_id',
        'pts_exact',
        'pts_result',
        'total_points',
        'calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'calculated_at' => 'datetime',
            'predicted_home'       => 'integer',
            'predicted_away'       => 'integer',
            'predicted_winner_id'  => 'integer',
            'pts_exact' => 'integer',
            'pts_result' => 'integer',
            'total_points' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fixture(): BelongsTo
    {
        return $this->belongsTo(Fixture::class, 'match_id');
    }
}
