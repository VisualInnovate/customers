<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TicketCategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Define the ticket categories data
        $categories = [
            ['name' => 'Signup/login issues'],
            ['name' => 'Campaigns issues'],
            ['name' => 'Whatsapp issue'],
            ['name' => 'Template Issues'],
            ['name' => 'Chatbot Issues'],
            ['name' => 'Other'],
        ];

        // Insert data into the ticket_categories table
        DB::table('ticket_categories')->insert($categories);
    }
}