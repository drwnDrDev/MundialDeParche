<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fixture;
use App\Models\Round;
use App\Models\Team;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const COINS_PER_ACTIVATION = 50;

    public function index(): Response
    {
        $activeRound = Round::where('is_open', true)->first();

        $pendingFixtures = $activeRound
            ? Fixture::where('round_id', $activeRound->id)
                ->whereNull('home_score')
                ->count()
            : 0;

        $recentlyUpdated = Fixture::with(['homeTeam', 'awayTeam', 'round'])
            ->whereNotNull('home_score')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(fn ($f) => [
                'match_number' => $f->match_number,
                'home'         => $f->home_team?->fifa_code ?? $f->home_placeholder ?? '?',
                'away'         => $f->away_team?->fifa_code ?? $f->away_placeholder ?? '?',
                'home_score'   => $f->home_score,
                'away_score'   => $f->away_score,
                'status'       => $f->status,
                'round_name'   => $f->round?->name,
            ]);

        $notActivated = User::where('role', 'user')
            ->where('is_activated', false)
            ->where('is_active', true)
            ->count();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'teams'        => Team::count(),
                'fixtures'     => Fixture::count(),
                'users'        => User::where('role', 'user')->count(),
                'pot'          => User::where('is_activated', true)->count() * self::COINS_PER_ACTIVATION,
                'notActivated' => $notActivated,
            ],
            'activeRound'     => $activeRound?->only(['id', 'name', 'slug', 'is_open', 'is_locked']),
            'pendingFixtures' => $pendingFixtures,
            'recentlyUpdated' => $recentlyUpdated,
        ]);
    }
}
