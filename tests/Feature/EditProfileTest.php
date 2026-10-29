<?php

use App\Filament\Pages\Auth\EditProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake(User::AVATAR_DISK);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('the profile page opens', function () {
    $this->get('/profile')->assertOk();
});

test('an uploaded profile photo becomes the panel avatar', function () {
    Livewire::test(EditProfile::class)
        ->fillForm(['avatar_path' => UploadedFile::fake()->image('me.jpg', 512, 512)])
        ->call('save')
        ->assertHasNoFormErrors();

    $user = $this->user->fresh();

    Storage::disk(User::AVATAR_DISK)->assertExists($user->avatar_path);
    expect($user->avatar_path)->toStartWith(User::AVATAR_DIRECTORY.'/')
        ->and(filament()->getUserAvatarUrl($user))->toBe(url(Storage::disk(User::AVATAR_DISK)->url($user->avatar_path)));
});

test('users without a photo get the initials avatar', function () {
    expect($this->user->getFilamentAvatarUrl())->toBeNull()
        ->and(filament()->getUserAvatarUrl($this->user))->toContain('ui-avatars.com');
});

test('avatar files are deleted when replaced, removed and with their user', function () {
    $oldPath = UploadedFile::fake()->image('old.jpg')->store(User::AVATAR_DIRECTORY, User::AVATAR_DISK);
    $newPath = UploadedFile::fake()->image('new.jpg')->store(User::AVATAR_DIRECTORY, User::AVATAR_DISK);

    $this->user->update(['avatar_path' => $oldPath]);
    $this->user->update(['avatar_path' => $newPath]);

    Storage::disk(User::AVATAR_DISK)->assertMissing($oldPath);
    Storage::disk(User::AVATAR_DISK)->assertExists($newPath);

    $this->user->update(['avatar_path' => null]);

    Storage::disk(User::AVATAR_DISK)->assertMissing($newPath);

    $lastPath = UploadedFile::fake()->image('last.jpg')->store(User::AVATAR_DIRECTORY, User::AVATAR_DISK);
    $this->user->update(['avatar_path' => $lastPath]);
    $this->user->delete();

    Storage::disk(User::AVATAR_DISK)->assertMissing($lastPath);
});
