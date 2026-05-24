<?php

namespace App\Events;

use App\Models\Fixture;

class MatchScoreUpdated
{
    public function __construct(public readonly Fixture $fixture) {}
}
