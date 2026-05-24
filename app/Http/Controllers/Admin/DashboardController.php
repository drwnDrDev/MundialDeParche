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
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'teams'    => Team::count(),
                'fixtures' => Fixture::count(),
                'rounds'   => Round::count(),
                'users'    => User::where('role', 'user')->count(),
                'pot'      => User::where('is_activated', true)->count() * self::COINS_PER_ACTIVATION,
            ],
        ]);
    }
}
