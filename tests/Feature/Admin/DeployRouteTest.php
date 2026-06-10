<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

it('the destructive restartdata route no longer exists', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/admin/restartdata')->assertNotFound();
});

it('deploy route runs migrate --force and rebuilds caches, never migrate:fresh', function () {
    Artisan::spy();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/admin/deploy')->assertOk();

    Artisan::shouldHaveReceived('call')->with('migrate', ['--force' => true]);
    Artisan::shouldHaveReceived('call')->with('config:cache');
    Artisan::shouldHaveReceived('call')->with('route:cache');
    Artisan::shouldHaveReceived('call')->with('view:cache');
    Artisan::shouldNotHaveReceived('call', ['migrate:fresh', Mockery::any()]);
});

it('deploy route is forbidden for non-admin users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin/deploy')->assertForbidden();
});
