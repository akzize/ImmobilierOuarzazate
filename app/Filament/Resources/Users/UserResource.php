<?php

namespace App\Filament\Resources\Users;

use App\Enums\NavigationGroup;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rules\Password;
use UnitEnum;

/**
 * Admin accounts. Public registration is closed, so new admins are created here.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Settings;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->rule(Password::default())
                    ->maxLength(255)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText(fn (string $operation): ?string => $operation === 'edit' ? __('Leave blank to keep the current password.') : null),
                Toggle::make('is_admin')
                    ->label(__('Can use the admin panel'))
                    ->default(true)
                    ->disabled(fn (?User $record): bool => $record?->is(auth()->user()) ?? false)
                    ->helperText(fn (?User $record): ?string => $record?->is(auth()->user()) ? __('You cannot remove your own access.') : null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                IconColumn::make('is_admin')
                    ->label(__('Admin access'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('Added'))
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_admin')
                    ->label(__('Admin access')),
            ])
            ->recordActions([
                EditAction::make()
                    ->using(fn (User $record, array $data): User => self::saveUser($record, $data)),
                DeleteAction::make()
                    ->hidden(fn (User $record): bool => $record->is(auth()->user())),
            ]);
    }

    /**
     * `is_admin` is deliberately not mass assignable, so it is set explicitly here.
     * It is missing from $data when the toggle is disabled (own account), which leaves it unchanged.
     *
     * @param  array<string, mixed>  $data
     */
    public static function saveUser(User $user, array $data): User
    {
        $user->fill(Arr::except($data, 'is_admin'));

        if (array_key_exists('is_admin', $data)) {
            $user->is_admin = (bool) $data['is_admin'];
        }

        $user->save();

        return $user;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
