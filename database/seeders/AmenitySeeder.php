<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;

class AmenitySeeder extends Seeder
{
    /**
     * Seed the amenities from the specification (section 5.2).
     */
    public function run(): void
    {
        /** @var array<string, array{0: string, 1: string}> $amenities */
        $amenities = [
            'pool' => ['مسبح', 'Pool'],
            'garden' => ['حديقة', 'Garden'],
            'terrace' => ['سطح', 'Terrace'],
            'elevator' => ['مصعد', 'Elevator'],
            'air_conditioning' => ['مكيف هواء', 'Air conditioning'],
            'heating' => ['تدفئة', 'Heating'],
            'fireplace' => ['مدفأة', 'Fireplace'],
            'security' => ['حراسة', 'Security'],
            'concierge' => ['بواب', 'Concierge'],
            'internet' => ['إنترنت', 'Internet'],
            'solar_water_heater' => ['سخان شمسي', 'Solar water heater'],
            'balcony' => ['شرفة', 'Balcony'],
            'cellar' => ['قبو', 'Cellar'],
        ];

        foreach ($amenities as $code => [$labelAr, $labelEn]) {
            Amenity::updateOrCreate(['code' => $code], ['label_ar' => $labelAr, 'label_en' => $labelEn]);
        }
    }
}
