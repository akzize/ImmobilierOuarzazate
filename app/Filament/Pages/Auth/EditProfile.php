<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;

/**
 * Filament's profile page with a profile photo on top.
 */
class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('avatar_path')
                    ->label(__('Photo'))
                    ->avatar()
                    ->disk(User::AVATAR_DISK)
                    ->directory(User::AVATAR_DIRECTORY)
                    ->visibility('public')
                    ->maxSize(5120)
                    ->automaticallyCropImagesToAspectRatio()
                    ->automaticallyResizeImagesMode('cover')
                    ->automaticallyResizeImagesToWidth('512')
                    ->automaticallyResizeImagesToHeight('512'),
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getCurrentPasswordFormComponent(),
            ]);
    }
}
