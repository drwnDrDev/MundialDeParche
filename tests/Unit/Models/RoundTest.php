<?php

use App\Models\Round;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('has point configuration', function () {
    $round = Round::factory()->create([
        'points_exact' => 3,
        'points_result' => 1,
        'points_classifier' => 2,
    ]);

    expect($round->points_exact)->toBe(3)
        ->and($round->points_result)->toBe(1)
        ->and($round->points_classifier)->toBe(2);
});

it('is closed by default', function () {
    $round = Round::factory()->create();

    expect($round->is_open)->toBeFalse()
        ->and($round->is_locked)->toBeFalse();
});
