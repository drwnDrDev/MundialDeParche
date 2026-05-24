<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'is_activated',
        'coins_balance',
        'total_points',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_activated' => 'boolean',
            'coins_balance' => 'integer',
            'total_points' => 'integer',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class);
    }

    public function predictionSubmissions(): HasMany
    {
        return $this->hasMany(PredictionSubmission::class);
    }

    public function specialPrediction(): HasOne
    {
        return $this->hasOne(SpecialPrediction::class);
    }

    public function coinTransactions(): HasMany
    {
        return $this->hasMany(CoinTransaction::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
