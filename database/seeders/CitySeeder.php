<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    /**
     * Seed Ouarzazate and its surrounding towns.
     */
    public function run(): void
    {
        $cities = [
            'Ouarzazate' => 'ورزازات',
            'Tarmigt' => 'ترميكت',
            'Skoura' => 'سكورة',
            'Ait Benhaddou' => 'آيت بن حدو',
            'Amerzgane' => 'أمرزكان',
            'Taznakht' => 'تازناخت',
            'Agdz' => 'أكدز',
            'Kalaat MGouna' => 'قلعة مكونة',
            'Zagora' => 'زاكورة',
            'Tinghir' => 'تنغير',
        ];

        foreach ($cities as $nameEn => $nameAr) {
            City::updateOrCreate(['name_en' => $nameEn], ['name_ar' => $nameAr]);
        }
    }
}
