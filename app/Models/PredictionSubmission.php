<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PredictionSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'round_id', 'status', 'submitted_at',
        'pts_classifier', 'predicted_classifiers',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at'          => 'datetime',
            'predicted_classifiers' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }
}
