<?php

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

test('an admin creates another admin account', function () {
    Livewire::test(ManageUsers::class)
        ->callAction('create', data: [
            'name' => 'Salma Bennani',
            'email' => 'salma@example.com',
            'password' => 'Secret-Passw0rd!',
            'is_admin' => true,
        ])
        ->assertHasNoFormErrors();

    $user = User::firstWhere('email', 'salma@example.com');

    expect($user)
        ->name->toBe('Salma Bennani')
        ->is_admin->toBeTrue()
        ->and(Hash::check('Secret-Passw0rd!', $user->password))->toBeTrue();
});

test('a new account needs a name, an email and a password', function () {
    Livewire::test(ManageUsers::class)
        ->callAction('create', data: [])
        ->assertHasFormErrors(['name' => 'required', 'email' => 'required', 'password' => 'required']);

    expect(User::count())->toBe(1);
});

test('an email already in use is rejected', function () {
    Livewire::test(ManageUsers::class)
        ->callAction('create', data: [
            'name' => 'Duplicate',
            'email' => $this->admin->email,
            'password' => 'Secret-Passw0rd!',
        ])
        ->assertHasFormErrors(['email' => 'unique']);

    expect(User::count())->toBe(1);
});

test('editing an account without a password keeps the current one', function () {
    $user = User::factory()->admin()->create(['password' => 'Old-Passw0rd!']);

    Livewire::test(ManageUsers::class)
        ->callAction(TestAction::make('edit')->table($user), data: ['name' => 'Renamed', 'password' => ''])
        ->assertHasNoFormErrors();

    $user->refresh();

    expect($user->name)->toBe('Renamed')
        ->and(Hash::check('Old-Passw0rd!', $user->password))->toBeTrue();
});

test('an admin revokes the panel access of another account', function () {
    $user = User::factory()->admin()->create();

    Livewire::test(ManageUsers::class)
        ->callAction(TestAction::make('edit')->table($user), data: ['is_admin' => false])
        ->assertHasNoFormErrors();

    expect($user->fresh()->is_admin)->toBeFalse();
});

test('admins cannot remove their own panel access', function () {
    Livewire::test(ManageUsers::class)
        ->callAction(TestAction::make('edit')->table($this->admin), data: ['is_admin' => false])
        ->assertHasNoFormErrors();

    expect($this->admin->fresh()->is_admin)->toBeTrue();
});

test('admins cannot delete their own account', function () {
    $otherAdmin = User::factory()->admin()->create();

    Livewire::test(ManageUsers::class)
        ->assertActionHidden(TestAction::make('delete')->table($this->admin))
        ->assertActionVisible(TestAction::make('delete')->table($otherAdmin));
});
