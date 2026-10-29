<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            ['name' => 'Bahrain'],
            ['name' => 'Egypt'],
            ['name' => 'Iran'],
            ['name' => 'Iraq'],
            ['name' => 'Palastine'],
            ['name' => 'Jordan'],
            ['name' => 'Kuwait'],
            ['name' => 'Lebanon'],
            ['name' => 'Oman'],
            ['name' => 'Qatar'],
            ['name' => 'Saudi Arabia'],
            ['name' => 'Syrian Arab Republic'],
            ['name' => 'United Arab Emirates'],
            ['name' => 'Yemen'],
        ];

        DB::table('countries')->insert($countries);
    }
}
