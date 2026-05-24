<?php

namespace App\Providers;

use App\Events\MatchScoreUpdated;
use App\Events\RoundFinalized;
use App\Events\TournamentFinalized;
use App\Listeners\CalculateClassifierPoints;
use App\Listeners\CalculateMatchPoints;
use App\Listeners\CalculateSpecialPredictions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Event::listen(MatchScoreUpdated::class, CalculateMatchPoints::class);
        Event::listen(RoundFinalized::class, CalculateClassifierPoints::class);
        Event::listen(TournamentFinalized::class, CalculateSpecialPredictions::class);
    }
}
