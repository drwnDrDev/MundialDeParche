<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Round extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'order',
        'is_open',
        'is_locked',
        'points_exact',
        'points_result',
        'points_classifier',
    ];

    protected function casts(): array
    {
        return [
            'is_open' => 'boolean',
            'is_locked' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function fixtures(): HasMany
    {
        return $this->hasMany(Fixture::class);
    }

    public function predictionSubmissions(): HasMany
    {
        return $this->hasMany(PredictionSubmission::class);
    }
}
