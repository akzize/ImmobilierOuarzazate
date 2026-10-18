<?php

namespace Database\Seeders;

use App\Enums\PropertyGroup;
use App\Models\PropertyType;
use Illuminate\Database\Seeder;

class PropertyTypeSeeder extends Seeder
{
    /**
     * Seed the property types from the specification (section 4).
     */
    public function run(): void
    {
        /** @var list<array{0: string, 1: PropertyGroup, 2: string, 3: string}> $types */
        $types = [
            ['APT', PropertyGroup::Built, 'شقة', 'Apartment'],
            ['VIL', PropertyGroup::Built, 'فيلا', 'Villa'],
            ['HSE', PropertyGroup::Built, 'منزل', 'House'],
            ['RIAD', PropertyGroup::Built, 'رياض', 'Riad'],
            ['KSB', PropertyGroup::Built, 'قصبة', 'Kasbah'],
            ['STU', PropertyGroup::Built, 'استوديو', 'Studio'],
            ['LND', PropertyGroup::Land, 'أرض', 'Land / plot'],
            ['FARM', PropertyGroup::Land, 'ضيعة / أرض فلاحية', 'Farm / agricultural land'],
            ['SHOP', PropertyGroup::Commercial, 'محل تجاري', 'Shop / commercial unit'],
            ['OFF', PropertyGroup::Commercial, 'مكتب', 'Office'],
            ['WAR', PropertyGroup::Commercial, 'مستودع', 'Warehouse / industrial'],
            ['HOT', PropertyGroup::Commercial, 'فندق / دار ضيافة', 'Hotel / guesthouse'],
        ];

        foreach ($types as $position => [$code, $group, $labelAr, $labelEn]) {
            PropertyType::updateOrCreate(
                ['code' => $code],
                ['group' => $group, 'label_ar' => $labelAr, 'label_en' => $labelEn, 'position' => $position + 1],
            );
        }
    }
}
