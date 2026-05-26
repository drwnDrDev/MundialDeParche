<?php

use App\Models\Fixture;
use App\Models\Group;
use App\Models\Prediction;
use App\Models\Round;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['is_active' => true, 'total_points' => 50]);
});

it('renders the Home page', function () {
    $this->withoutVite()
        ->actingAs($this->user)
        ->get('/dashboard')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Home'));
});

it('guests are redirected to login', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

it('returns user props with name, position, totalPoints, avatarColor', function () {
    $response = $this->withoutVite()->actingAs($this->user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page
        ->has('user.name')
        ->has('user.position')
        ->has('user.totalPoints')
        ->has('user.totalActive')
        ->has('user.avatarColor')
        ->has('user.isActivated')
    );
});

it('calculates position correctly', function () {
    User::factory()->create(['is_active' => true, 'total_points' => 200]);
    User::factory()->create(['is_active' => true, 'total_points' => 100]);
    // $this->user has 50 pts → position 3

    $response = $this->withoutVite()->actingAs($this->user)->get('/dashboard');

    $props = $response->original->getData()['page']['props'];
    expect($props['user']['position'])->toBe(3);
    expect($props['user']['totalActive'])->toBe(3); // 2 created + $this->user
});

it('returns null featured when no matches exist', function () {
    $response = $this->withoutVite()->actingAs($this->user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page->where('featured', null));
});

it('returns a live match as featured when one is in_progress', function () {
    $round = Round::factory()->create();
    $group = Group::factory()->create();
    $home  = Team::factory()->create(['group_id' => $group->id, 'fifa_code' => 'COL']);
    $away  = Team::factory()->create(['group_id' => $group->id, 'fifa_code' => 'BRA']);

    $fixture = Fixture::factory()->create([
        'round_id'      => $round->id,
        'group_id'      => $group->id,
        'home_team_id'  => $home->id,
        'away_team_id'  => $away->id,
        'status'        => 'in_progress',
        'home_score'    => 1,
        'away_score'    => 0,
    ]);

    $response = $this->withoutVite()->actingAs($this->user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page
        ->where('featured.status', 'live')
        ->where('featured.codeA', 'COL')
        ->where('featured.codeB', 'BRA')
        ->where('featured.scoreA', 1)
        ->where('featured.scoreB', 0)
    );
});

it('returns upcoming match as featured when no live match', function () {
    $round = Round::factory()->create();
    $group = Group::factory()->create();
    $home  = Team::factory()->create(['group_id' => $group->id, 'fifa_code' => 'ARG']);
    $away  = Team::factory()->create(['group_id' => $group->id, 'fifa_code' => 'ALE']);

    Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'status'       => 'scheduled',
        'match_date'   => now()->addHours(2),
    ]);

    $response = $this->withoutVite()->actingAs($this->user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page
        ->where('featured.status', 'upcoming')
        ->where('featured.codeA', 'ARG')
        ->where('featured.codeB', 'ALE')
    );
});

it('includes my prediction pick and pts on featured match', function () {
    $round   = Round::factory()->create(['points_exact' => 3, 'points_result' => 1]);
    $group   = Group::factory()->create();
    $home    = Team::factory()->create(['group_id' => $group->id]);
    $away    = Team::factory()->create(['group_id' => $group->id]);
    $fixture = Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'status'       => 'scheduled',
        'match_date'   => now()->addHours(3),
    ]);

    Prediction::factory()->create([
        'user_id'        => $this->user->id,
        'match_id'       => $fixture->id,
        'predicted_home' => 2,
        'predicted_away' => 1,
        'pts_exact'      => 0,
        'pts_result'     => 0,
    ]);

    $response = $this->withoutVite()->actingAs($this->user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page
        ->where('featured.myPick', '2-1')
        ->where('featured.myPts', 0)
    );
});

it('returns stats with position and acertados', function () {
    $round   = Round::factory()->create();
    $group   = Group::factory()->create();
    $home    = Team::factory()->create(['group_id' => $group->id]);
    $away    = Team::factory()->create(['group_id' => $group->id]);
    $fixture = Fixture::factory()->create([
        'round_id'     => $round->id,
        'group_id'     => $group->id,
        'home_team_id' => $home->id,
        'away_team_id' => $away->id,
        'status'       => 'finished',
    ]);

    Prediction::factory()->create([
        'user_id'    => $this->user->id,
        'match_id'   => $fixture->id,
        'pts_result' => 1,
    ]);

    $response = $this->withoutVite()->actingAs($this->user)->get('/dashboard');

    $props = $response->original->getData()['page']['props'];
    expect($props['stats']['acertados'])->toBe(1);
    expect($props['stats']['position'])->toBeInt();
});

it('returns phase data from the open round', function () {
    $round = Round::factory()->create(['name' => 'Grupos', 'is_open' => true]);

    $response = $this->withoutVite()->actingAs($this->user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page
        ->has('phase.name')
        ->has('phase.missing')
    );

    $props = $response->original->getData()['page']['props'];
    expect($props['phase']['name'])->toBe('Grupos');
});

it('returns null phase when no round is open', function () {
    $response = $this->withoutVite()->actingAs($this->user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page->where('phase', null));
});

it('returns nextBets for upcoming predictions', function () {
    $round = Round::factory()->create(['points_exact' => 3]);
    $group = Group::factory()->create();

    $fixtures = Fixture::factory(3)->create([
        'round_id'  => $round->id,
        'group_id'  => $group->id,
        'status'    => 'scheduled',
        'match_date' => now()->addDays(1),
    ]);

    foreach ($fixtures as $fixture) {
        Prediction::factory()->create([
            'user_id'  => $this->user->id,
            'match_id' => $fixture->id,
        ]);
    }

    $response = $this->withoutVite()->actingAs($this->user)->get('/dashboard');

    $props = $response->original->getData()['page']['props'];
    expect($props['nextBets'])->toHaveCount(3);
    expect($props['nextBets'][0])->toHaveKeys(['teamA', 'teamB', 'pick', 'pts', 'time', 'hot']);
});
