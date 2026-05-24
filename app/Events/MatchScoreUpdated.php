<?php

namespace App\Events;

use App\Models\Fixture;
use Illuminate\Foundation\Events\Dispatchable;

class MatchScoreUpdated
{
    use Dispatchable;

    public function __construct(public readonly Fixture $fixture) {}
}
