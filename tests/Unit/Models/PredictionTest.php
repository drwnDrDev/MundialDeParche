<?php

use App\Models\Fixture;
use App\Models\Prediction;
use App\Models\PredictionSubmission;
use App\Models\Round;
use App\Models\SpecialPrediction;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to a user and a fixture', function () {
    $user = User::factory()->create();
    $fixture = Fixture::factory()->create();
    $prediction = Prediction::factory()->create([
        'user_id' => $user->id,
        'match_id' => $fixture->id,
    ]);

    expect($prediction->user->id)->toBe($user->id)
        ->and($prediction->fixture->id)->toBe($fixture->id);
});

it('starts with zero points', function () {
    $prediction = Prediction::factory()->create();

    expect($prediction->pts_exact)->toBe(0)
        ->and($prediction->pts_result)->toBe(0)
        ->and($prediction->pts_classifier)->toBe(0)
        ->and($prediction->total_points)->toBe(0);
});

it('prediction submission has correct statuses', function () {
    $submission = PredictionSubmission::factory()->create(['status' => 'draft']);

    expect($submission->status)->toBe('draft');
});

it('special prediction belongs to user', function () {
    $user = User::factory()->create();
    $sp = SpecialPrediction::factory()->create(['user_id' => $user->id]);

    expect($sp->user->id)->toBe($user->id);
});

it('special prediction starts with zero points', function () {
    $sp = SpecialPrediction::factory()->create();

    expect($sp->pts_champion)->toBe(0)
        ->and($sp->pts_runner_up)->toBe(0)
        ->and($sp->pts_top_scorer)->toBe(0);
});
