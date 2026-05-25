<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FixtureController;
use App\Http\Controllers\Admin\PlayerController;
use App\Http\Controllers\Admin\RoundController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\TournamentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SpecialPredictionController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin'       => Route::has('login'),
        'canRegister'    => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion'     => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/chat', [ChatController::class, 'index'])->name('chat');
    Route::post('/chat/messages', [ChatController::class, 'store'])->name('chat.store');
});

Route::middleware(['auth'])->prefix('predictions')->name('predictions.')->group(function () {
    Route::get('/', [PredictionController::class, 'index'])->name('index');
    Route::get('/special', [SpecialPredictionController::class, 'show'])->name('special');
    Route::post('/special', [SpecialPredictionController::class, 'save'])->name('special.save');
    Route::get('/{round}', [PredictionController::class, 'show'])->name('show');
    Route::post('/{round}/save', [PredictionController::class, 'save'])->name('save');
    Route::post('/{round}/submit', [PredictionController::class, 'submit'])->name('submit');
});

require __DIR__.'/auth.php';

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Rounds
    Route::get('rounds', [RoundController::class, 'index'])->name('rounds.index');
    Route::post('rounds/{round}/open', [RoundController::class, 'open'])->name('rounds.open');
    Route::post('rounds/{round}/lock', [RoundController::class, 'lock'])->name('rounds.lock');
    Route::post('rounds/{round}/finalize', [RoundController::class, 'finalize'])->name('rounds.finalize');

    // Teams
    Route::get('teams', [TeamController::class, 'index'])->name('teams.index');
    Route::get('teams/{team}/edit', [TeamController::class, 'edit'])->name('teams.edit');
    Route::patch('teams/{team}', [TeamController::class, 'update'])->name('teams.update');

    // Fixtures
    Route::get('fixtures', [FixtureController::class, 'index'])->name('fixtures.index');
    Route::get('fixtures/create', [FixtureController::class, 'create'])->name('fixtures.create');
    Route::post('fixtures', [FixtureController::class, 'store'])->name('fixtures.store');
    Route::get('fixtures/{fixture}/edit', [FixtureController::class, 'edit'])->name('fixtures.edit');
    Route::patch('fixtures/{fixture}', [FixtureController::class, 'update'])->name('fixtures.update');
    Route::delete('fixtures/{fixture}', [FixtureController::class, 'destroy'])->name('fixtures.destroy');

    // Players
    Route::get('players', [PlayerController::class, 'index'])->name('players.index');
    Route::post('players', [PlayerController::class, 'store'])->name('players.store');
    Route::patch('players/{player}', [PlayerController::class, 'update'])->name('players.update');
    Route::delete('players/{player}', [PlayerController::class, 'destroy'])->name('players.destroy');

    // Users
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::post('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
    Route::post('users/{user}/activate-pot', [UserController::class, 'activatePot'])->name('users.activate-pot');
    Route::post('users/{user}/deactivate-pot', [UserController::class, 'deactivatePot'])->name('users.deactivate-pot');
    Route::post('users/{user}/reopen-predictions', [UserController::class, 'reopenPredictions'])->name('users.reopen-predictions');

    // Tournament
    Route::get('tournament', [TournamentController::class, 'show'])->name('tournament');
    Route::post('tournament/finalize', [TournamentController::class, 'finalize'])->name('tournament.finalize');
});
