<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FixtureController;
use App\Http\Controllers\Admin\PlayerController;
use App\Http\Controllers\Admin\RoundController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\ScoreEntryController;
use App\Http\Controllers\Admin\TournamentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ActivationController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MatchesController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SpecialPredictionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('migrate', function () {
    Artisan::call('migrate --force');
    Artisan::call('db:seed --force');
    return 'Migrated';
});

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return Inertia::render('Welcome');
})->name('home');

Route::inertia('/how-to-play', 'HowTo')->name('how-to-play');
Route::inertia('/rules', 'Rules')->name('rules');

Route::get('/dashboard', [HomeController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/activation', [ActivationController::class, 'show'])->name('activation');

    // Lectura libre para todos los usuarios autenticados
    Route::get('/chat', [ChatController::class, 'index'])->name('chat');
    Route::get('/ranking', [RankingController::class, 'index'])->name('ranking');
    Route::get('/matches', [MatchesController::class, 'index'])->name('matches');

    // Escritura solo para usuarios activados
    Route::post('/chat/messages', [ChatController::class, 'store'])->middleware('activated')->name('chat.store');
});

Route::middleware('auth')->prefix('predictions')->name('predictions.')->group(function () {
    // Lectura libre
    Route::get('/', [PredictionController::class, 'index'])->name('index');
    Route::get('/special', [SpecialPredictionController::class, 'show'])->name('special');
    Route::get('/{round}/receipt', [PredictionController::class, 'receipt'])->name('receipt');
    Route::get('/{round}', [PredictionController::class, 'show'])->name('show');

    // Escritura solo para usuarios activados
    Route::middleware('activated')->group(function () {
        Route::post('/special', [SpecialPredictionController::class, 'save'])->name('special.save');
        Route::post('/{round}/save', [PredictionController::class, 'save'])->name('save');
    });
});

require __DIR__.'/auth.php';

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Rounds
    Route::get('rounds', [RoundController::class, 'index'])->name('rounds.index');
    Route::get('rounds/{round}/pending-submissions', [RoundController::class, 'pendingSubmissions'])->name('rounds.pending');
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
    Route::get('users/{user}/predictions', [UserController::class, 'predictions'])->name('users.predictions');

    // Tournament
    Route::get('tournament', [TournamentController::class, 'show'])->name('tournament');
    Route::post('tournament/finalize', [TournamentController::class, 'finalize'])->name('tournament.finalize');

    // Score Entry
    Route::get('score-entry', [ScoreEntryController::class, 'index'])->name('score-entry');
    Route::patch('score-entry/{fixture}', [ScoreEntryController::class, 'update'])->name('score-entry.update');
});
