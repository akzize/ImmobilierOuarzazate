<?php

use App\Enums\LegalTitle;
use App\Enums\PropertyStatus;
use App\Enums\TransactionType;
use App\Filament\Exports\PropertyExporter;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('exported rows use readable labels and plain-text descriptions', function () {
    $property = Property::factory()->create([
        'transaction' => TransactionType::SeasonalRent,
        'status' => PropertyStatus::SoldOrRented,
        'legal_title' => LegalTitle::Melkia,
        'featured' => true,
        'price_negotiable' => false,
        'description_ar' => '<p>شقة <strong>جميلة</strong> &amp; مشمسة</p>',
    ]);

    $row = PropertyExporter::test(columnMap: [
        'transaction' => 'Transaction',
        'status' => 'Status',
        'legal_title' => 'Legal title',
        'featured' => 'Featured',
        'price_negotiable' => 'Price negotiable',
        'description_ar' => 'Description (Arabic)',
    ])->export($property);

    expect($row)->toBe(['Seasonal rent', 'Sold / Rented', 'Melkia', 'Yes', 'No', 'شقة جميلة & مشمسة']);
});
