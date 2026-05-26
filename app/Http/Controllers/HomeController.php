<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Models\Prediction;
use App\Models\Round;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $colors = ['yel', 'teal', 'red', 'cream'];
        $avatarColor = $colors[$user->id % 4];

        $position = User::where('is_active', true)
            ->where('total_points', '>', $user->total_points)
            ->count() + 1;
        $totalActive = User::where('is_active', true)->count();

        // Featured match: live first, then next scheduled
        $featured = Fixture::where('status', 'in_progress')
            ->with(['homeTeam', 'awayTeam', 'group'])
            ->first()
            ?? Fixture::where('status', 'scheduled')
               ->where('match_date', '>=', now())
               ->with(['homeTeam', 'awayTeam', 'group'])
               ->orderBy('match_date')
               ->first();

        $featuredData = null;
        if ($featured) {
            $myPrediction = Prediction::where('user_id', $user->id)
                ->where('match_id', $featured->id)
                ->first();

            $isWinnerCorrect = false;
            if ($myPrediction && $featured->isLive()) {
                $ph = $myPrediction->predicted_home;
                $pa = $myPrediction->predicted_away;
                $sh = $featured->home_score ?? 0;
                $sa = $featured->away_score ?? 0;
                $isWinnerCorrect = ($ph > $pa && $sh > $sa)
                    || ($ph < $pa && $sh < $sa)
                    || ($ph === $pa && $sh === $sa);
            }

            $featuredData = [
                'id'              => $featured->id,
                'status'          => $featured->isLive() ? 'live' : 'upcoming',
                'teamA'           => $featured->homeTeam?->name ?? $featured->home_placeholder,
                'teamB'           => $featured->awayTeam?->name ?? $featured->away_placeholder,
                'codeA'           => $featured->homeTeam?->fifa_code ?? '???',
                'codeB'           => $featured->awayTeam?->fifa_code ?? '???',
                'flagUrlA'        => $featured->homeTeam?->flag_url,
                'flagUrlB'        => $featured->awayTeam?->flag_url,
                'scoreA'          => $featured->home_score,
                'scoreB'          => $featured->away_score,
                'group'           => $featured->group?->name,
                'venue'           => $featured->venue,
                'matchDate'       => $featured->match_date?->toIso8601String(),
                'myPick'          => $myPrediction
                    ? "{$myPrediction->predicted_home}-{$myPrediction->predicted_away}"
                    : null,
                'myPts'           => $myPrediction
                    ? ($myPrediction->pts_exact ?? 0) + ($myPrediction->pts_result ?? 0)
                    : null,
                'isWinnerCorrect' => $isWinnerCorrect,
            ];
        }

        // Stats
        $acertados = Prediction::where('user_id', $user->id)
            ->where('pts_result', '>', 0)
            ->count();

        // Phase — currently open round
        $openRound = Round::where('is_open', true)->first();
        $phaseData = null;
        if ($openRound) {
            $totalFixtures   = $openRound->fixtures()->count();
            $myPredictions   = Prediction::where('user_id', $user->id)
                ->whereHas('fixture', fn ($q) => $q->where('round_id', $openRound->id))
                ->count();
            $closeDate = $openRound->fixtures()->max('match_date');

            $phaseData = [
                'name'      => $openRound->name,
                'missing'   => max(0, $totalFixtures - $myPredictions),
                'closeDate' => $closeDate,
            ];
        }

        // Next bets: up to 5 upcoming/live predictions
        $nextBets = Prediction::where('user_id', $user->id)
            ->whereHas('fixture', fn ($q) => $q
                ->whereIn('status', ['scheduled', 'in_progress'])
                ->orderBy('match_date')
            )
            ->with(['fixture.homeTeam', 'fixture.awayTeam', 'fixture.round'])
            ->get()
            ->sortBy('fixture.match_date')
            ->take(5)
            ->map(function ($pred) {
                $f   = $pred->fixture;
                $now = now();
                $d   = $f->match_date;

                if ($f->status === 'in_progress') {
                    $timeLabel = 'EN VIVO';
                } else {
                    $diffMins = (int) $now->diffInMinutes($d, false);
                    if ($diffMins < 60) {
                        $timeLabel = "EN {$diffMins}M";
                    } elseif ($diffMins < 1440) {
                        $hours = (int) ceil($diffMins / 60);
                        $timeLabel = "EN {$hours}H";
                    } elseif ($d->isTomorrow()) {
                        $timeLabel = 'MAÑ ' . $d->format('H') . 'H';
                    } else {
                        $days = ['DOM', 'LUN', 'MAR', 'MIÉ', 'JUE', 'VIE', 'SÁB'];
                        $timeLabel = $days[$d->dayOfWeek] . ' ' . $d->format('H') . 'H';
                    }
                }

                return [
                    'teamA'    => $f->homeTeam?->name ?? $f->home_placeholder,
                    'teamB'    => $f->awayTeam?->name ?? $f->away_placeholder,
                    'codeA'    => $f->homeTeam?->fifa_code ?? '???',
                    'codeB'    => $f->awayTeam?->fifa_code ?? '???',
                    'flagUrlA' => $f->homeTeam?->flag_url,
                    'flagUrlB' => $f->awayTeam?->flag_url,
                    'pick'     => "{$pred->predicted_home}-{$pred->predicted_away}",
                    'pts'      => $f->round?->points_exact ?? 0,
                    'time'     => $timeLabel,
                    'hot'      => $f->status === 'in_progress',
                ];
            })
            ->values();

        return Inertia::render('Home', [
            'user'     => [
                'name'        => $user->name,
                'totalPoints' => $user->total_points,
                'position'    => $position,
                'totalActive' => $totalActive,
                'isActivated' => (bool) $user->is_activated,
                'avatarColor' => $avatarColor,
            ],
            'featured' => $featuredData,
            'stats'    => [
                'position'    => $position,
                'totalActive' => $totalActive,
                'acertados'   => $acertados,
            ],
            'phase'    => $phaseData,
            'nextBets' => $nextBets,
        ]);
    }
}
