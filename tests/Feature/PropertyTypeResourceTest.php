<?php

use App\Enums\PropertyGroup;
use App\Filament\Resources\PropertyTypes\Pages\ManagePropertyTypes;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\User;
use Database\Seeders\PropertyTypeSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('a new type is added at the end of the list', function () {
    $this->seed(PropertyTypeSeeder::class);

    Livewire::test(ManagePropertyTypes::class)
        ->callAction('create', data: [
            'code' => 'GAR',
            'group' => PropertyGroup::Built->value,
            'label_en' => 'Garage',
            'label_ar' => 'مرآب',
        ])
        ->assertHasNoFormErrors();

    expect(PropertyType::firstWhere('code', 'GAR'))
        ->group->toBe(PropertyGroup::Built)
        ->position->toBe(13);
});

test('type codes are short uppercase codes', function () {
    Livewire::test(ManagePropertyTypes::class)
        ->callAction('create', data: [
            'code' => 'garage',
            'group' => PropertyGroup::Built->value,
            'label_en' => 'Garage',
            'label_ar' => 'مرآب',
        ])
        ->assertHasFormErrors(['code' => 'regex']);
});

test('the code never changes and the group is locked once listings use the type', function () {
    $usedType = PropertyType::factory()->has(Property::factory())->create(['code' => 'VIL', 'group' => PropertyGroup::Built]);
    $unusedType = PropertyType::factory()->create(['group' => PropertyGroup::Built]);

    Livewire::test(ManagePropertyTypes::class)
        ->callAction(TestAction::make('edit')->table($usedType), data: [
            'code' => 'XXX',
            'group' => PropertyGroup::Land->value,
            'label_en' => 'Luxury villa',
        ])
        ->assertHasNoFormErrors()
        ->callAction(TestAction::make('edit')->table($unusedType), data: ['group' => PropertyGroup::Land->value])
        ->assertHasNoFormErrors();

    expect($usedType->fresh())
        ->code->toBe('VIL')
        ->group->toBe(PropertyGroup::Built)
        ->label_en->toBe('Luxury villa')
        ->and($unusedType->fresh()->group)->toBe(PropertyGroup::Land);
});

test('a type used by listings cannot be deleted', function () {
    $usedType = PropertyType::factory()->has(Property::factory())->create();
    $unusedType = PropertyType::factory()->create();

    Livewire::test(ManagePropertyTypes::class)
        ->assertActionHidden(TestAction::make('delete')->table($usedType))
        ->assertActionVisible(TestAction::make('delete')->table($unusedType));
});

test('types are reordered by dragging', function () {
    $first = PropertyType::factory()->create(['position' => 1]);
    $second = PropertyType::factory()->create(['position' => 2]);

    Livewire::test(ManagePropertyTypes::class)
        ->call('reorderTable', [(string) $second->getKey(), (string) $first->getKey()]);

    expect($second->fresh()->position)->toBe(1)
        ->and($first->fresh()->position)->toBe(2);
});
