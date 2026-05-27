<?php
// tests/Feature/HowToRulesTest.php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders HowTo page without authentication', function () {
    $this->withoutVite()
        ->get('/how-to-play')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('HowTo'));
});

it('renders Rules page without authentication', function () {
    $this->withoutVite()
        ->get('/rules')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Rules'));
});
