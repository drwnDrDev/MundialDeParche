<?php

use App\Models\CoinTransaction;
use App\Models\Message;
use App\Models\Prediction;
use App\Models\PredictionSubmission;
use App\Models\SpecialPrediction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('has required fields for the quinela', function () {
    $user = User::factory()->create([
        'role' => 'user',
        'is_active' => true,
        'is_activated' => false,
        'coins_balance' => 0,
        'total_points' => 0,
    ]);

    expect($user->role)->toBe('user')
        ->and($user->is_active)->toBeTrue()
        ->and($user->is_activated)->toBeFalse()
        ->and($user->coins_balance)->toBe(0)
        ->and($user->total_points)->toBe(0);
});

it('can be admin', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    expect($admin->isAdmin())->toBeTrue();
});

it('is not admin by default', function () {
    $user = User::factory()->create(['role' => 'user']);

    expect($user->isAdmin())->toBeFalse();
});

it('has many predictions', function () {
    $user = User::factory()->create();
    Prediction::factory()->count(3)->create(['user_id' => $user->id]);

    expect($user->predictions)->toHaveCount(3);
});

it('has many prediction submissions', function () {
    $user = User::factory()->create();
    PredictionSubmission::factory()->count(2)->create(['user_id' => $user->id]);

    expect($user->predictionSubmissions)->toHaveCount(2);
});

it('has one special prediction', function () {
    $user = User::factory()->create();
    SpecialPrediction::factory()->create(['user_id' => $user->id]);

    expect($user->specialPrediction)->toBeInstanceOf(SpecialPrediction::class);
});

it('has many coin transactions', function () {
    $user = User::factory()->create();
    CoinTransaction::factory()->count(2)->create(['user_id' => $user->id]);

    expect($user->coinTransactions)->toHaveCount(2);
});

it('has many messages', function () {
    $user = User::factory()->create();
    Message::factory()->count(3)->create(['user_id' => $user->id]);

    expect($user->messages)->toHaveCount(3);
});
