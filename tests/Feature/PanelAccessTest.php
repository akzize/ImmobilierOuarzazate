<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admins can open the admin panel', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin')
        ->assertOk();
});

test('non admin users are forbidden', function () {
    $this->actingAs(User::factory()->create())
        ->get('/admin')
        ->assertForbidden();
});

test('guests are redirected to the login page', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

test('public registration is closed', function () {
    $this->get('/admin/register')->assertNotFound();
});

test('guests can request a password reset', function () {
    $this->get('/admin/password-reset/request')->assertOk();
});

test('is_admin cannot be mass assigned', function () {
    $user = User::factory()->create();

    $user->fill(['is_admin' => true])->save();

    expect($user->fresh()->is_admin)->toBeFalse();
});
