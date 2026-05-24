<?php

use App\Models\PredictionSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('prediction_submission has pts_classifier column', function () {
    $sub = PredictionSubmission::factory()->submitted()->create(['pts_classifier' => 6]);
    expect($sub->fresh()->pts_classifier)->toBe(6);
});
