<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlayerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Players/Index', [
            'players' => Player::with('team.group')->orderBy('name')->get(),
            'teams'   => Team::with('group')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'team_id' => ['required', 'exists:teams,id'],
            'name'    => ['required', 'string', 'max:100'],
        ]);

        Player::create($data);

        return back()->with('status', 'Jugador creado.');
    }

    public function update(Request $request, Player $player): RedirectResponse
    {
        $data = $request->validate([
            'team_id' => ['required', 'exists:teams,id'],
            'name'    => ['required', 'string', 'max:100'],
        ]);

        $player->update($data);

        return back()->with('status', 'Jugador actualizado.');
    }

    public function destroy(Player $player): RedirectResponse
    {
        $player->delete();

        return back()->with('status', 'Jugador eliminado.');
    }
}
