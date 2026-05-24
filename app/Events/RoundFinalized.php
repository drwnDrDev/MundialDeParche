<?php

namespace App\Events;

use App\Models\Round;

class RoundFinalized
{
    public function __construct(public readonly Round $round) {}
}
