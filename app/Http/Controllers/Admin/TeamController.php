<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Teams/Index', [
            'teams' => Team::with('group')->orderBy('name')->get(),
        ]);
    }

    public function edit(Team $team): Response
    {
        return Inertia::render('Admin/Teams/Edit', [
            'team'   => $team->load('group'),
            'groups' => Group::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'fifa_code' => ['required', 'string', 'size:3'],
            'flag_url'  => ['nullable', 'string', 'max:500'],
            'group_id'  => ['required', 'exists:groups,id'],
        ]);

        $team->update($data);

        return redirect()->route('admin.teams.index')->with('status', "Equipo '{$team->name}' actualizado.");
    }
}
