<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class RankingController extends Controller
{
    public function index(): Response
    {
        $avatarColors = ['yel', 'teal', 'red', 'cream'];

        $position = 0;
        $lastPts  = null;
        $counter  = 0;

        $users = User::where('is_active', true)
            ->where('role', 'user')
            ->orderByDesc('total_points')
            ->select(['id', 'name', 'total_points'])
            ->get()
            ->map(function ($user) use (&$position, &$lastPts, &$counter, $avatarColors) {
                $counter++;
                if ($user->total_points !== $lastPts) {
                    $position = $counter;
                    $lastPts  = $user->total_points;
                }
                return [
                    'id'          => $user->id,
                    'name'        => $user->name,
                    'total_points'=> $user->total_points,
                    'position'    => $position,
                    'avatarColor' => $avatarColors[$user->id % 4],
                    'delta'       => '+0',
                ];
            });

        $activated = User::where('is_activated', true)->where('role', 'user')->count();
        $total     = $activated * 50000;

        $fmt = fn ($n) => number_format($n / 1000, 0, '.', '.') . 'K';

        return Inertia::render('Ranking', [
            'users' => $users,
            'pozo'  => [
                'total'   => $fmt($total) ?: '0K',
                'players' => $activated,
                'prize1'  => $fmt((int) ($total * 0.70)),
                'prize2'  => $fmt((int) ($total * 0.30)),
            ],
        ]);
    }
}
