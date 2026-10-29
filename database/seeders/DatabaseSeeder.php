<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ModulesTableSeeder::class,
            PaymentGatewaysTableSeeder::class,
            RolesTableSeeder::class,
            SettingsTableSeeder::class,
            TicketCategoriesTableSeeder::class,
            EmailTemplateSeeder::class,
            LanguageTableSeeder::class,
            AddonsTableSeeder::class,
            AddonsTableSeeder2::class,
            AddonsTableSeeder3::class,
            PageSeeder::class,
            FolderSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
