<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CoinTransaction;
use App\Models\Fixture;
use App\Models\Prediction;
use App\Models\PredictionSubmission;
use App\Models\Round;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Users/Index', [
            'users'  => User::where('role', 'user')->orderBy('name')->get(),
            'rounds' => Round::orderBy('order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'role'      => 'user',
            'is_active' => true,
        ]);

        return back()->with('status', "Usuario '{$data['name']}' creado.");
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('status', $user->fresh()->is_active ? 'Usuario activado.' : 'Usuario desactivado.');
    }

    private const COINS_PER_ACTIVATION = 50;

    public function activatePot(User $user): RedirectResponse
    {
        if ($user->is_activated) {
            return back()->with('status', 'El usuario ya está activado en el pozo.');
        }

        $user->update([
            'is_activated'  => true,
            'coins_balance' => $user->coins_balance + self::COINS_PER_ACTIVATION,
        ]);

        CoinTransaction::create([
            'user_id' => $user->id,
            'type'    => 'credit',
            'amount'  => self::COINS_PER_ACTIVATION,
            'concept' => 'Activación al pozo del torneo',
        ]);

        return back()->with('status', "Usuario '{$user->name}' activado en el pozo (+50 coins).");
    }

    public function deactivatePot(User $user): RedirectResponse
    {
        if (! $user->is_activated) {
            return back()->with('status', 'El usuario no está activado en el pozo.');
        }

        $user->update([
            'is_activated'  => false,
            'coins_balance' => max(0, $user->coins_balance - self::COINS_PER_ACTIVATION),
        ]);

        CoinTransaction::create([
            'user_id' => $user->id,
            'type'    => 'debit',
            'amount'  => self::COINS_PER_ACTIVATION,
            'concept' => 'Baja del pozo del torneo',
        ]);

        return back()->with('status', "Usuario '{$user->name}' dado de baja del pozo (-50 coins).");
    }

    public function reopenPredictions(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'round_id' => ['required', 'exists:rounds,id'],
        ]);

        PredictionSubmission::where('user_id', $user->id)
            ->where('round_id', $data['round_id'])
            ->update(['status' => 'draft', 'submitted_at' => null]);

        return back()->with('status', "Predicciones de '{$user->name}' reabiertas.");
    }

    public function predictions(User $user): Response
    {
        $rounds = Round::orderBy('order')->get();

        $fixturesByRound = Fixture::with(['homeTeam', 'awayTeam', 'group'])
            ->whereIn('round_id', $rounds->pluck('id'))
            ->orderBy('match_number')
            ->get()
            ->groupBy('round_id');

        $predictions = Prediction::where('user_id', $user->id)
            ->get()
            ->keyBy('match_id');

        $submissions = PredictionSubmission::where('user_id', $user->id)
            ->get()
            ->keyBy('round_id');

        return Inertia::render('Admin/Users/Predictions', [
            'targetUser'  => $user->only(['id', 'name', 'email', 'total_points', 'coins_balance', 'is_activated']),
            'rounds'      => $rounds,
            'fixtures'    => $fixturesByRound,
            'predictions' => $predictions,
            'submissions' => $submissions,
        ]);
    }
}
