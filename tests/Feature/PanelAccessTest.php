<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('users can open the admin panel', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertOk();
});

test('guests are redirected to the login page', function () {
    $this->get('/')->assertRedirect('/login');
});

test('public registration is closed', function () {
    $this->get('/register')->assertNotFound();
});

test('guests can request a password reset', function () {
    $this->get('/password-reset/request')->assertOk();
});
