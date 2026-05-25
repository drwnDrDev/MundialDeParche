<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class RankingController extends Controller
{
    public function index(): Response
    {
        $users = User::where('is_active', true)
            ->orderByDesc('total_points')
            ->select(['id', 'name', 'avatar', 'total_points'])
            ->get()
            ->values()
            ->map(fn ($user, $index) => array_merge($user->toArray(), ['position' => $index + 1]));

        return Inertia::render('Ranking', [
            'users' => $users,
        ]);
    }
}
