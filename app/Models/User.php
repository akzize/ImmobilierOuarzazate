<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'email', 'password', 'avatar_path'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const string AVATAR_DISK = 'public';

    public const string AVATAR_DIRECTORY = 'avatars';

    /**
     * Remove avatar files that are no longer referenced.
     */
    protected static function booted(): void
    {
        static::saved(function (User $user): void {
            if ($user->wasChanged('avatar_path') && filled($user->getOriginal('avatar_path'))) {
                Storage::disk(self::AVATAR_DISK)->delete($user->getOriginal('avatar_path'));
            }
        });

        static::deleted(function (User $user): void {
            if (filled($user->avatar_path)) {
                Storage::disk(self::AVATAR_DISK)->delete($user->avatar_path);
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Every account is an admin. Public registration is closed, so accounts only come from the Users resource.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * Falls back to Filament's initials avatar when no photo is uploaded.
     */
    public function getFilamentAvatarUrl(): ?string
    {
        return filled($this->avatar_path) ? Storage::disk(self::AVATAR_DISK)->url($this->avatar_path) : null;
    }
}
