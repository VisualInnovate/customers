<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('addons')->where('name', 'Embedded Signup')->exists();

        if ($exists) {
            return;
        }

        DB::table('addons')->insert([
            'uuid' => (string) Illuminate\Support\Str::uuid(),
            'category' => 'chat',
            'name' => 'Embedded Signup',
            'logo' => 'whatsapp.png',
            'description' => 'An Embedded Signup add-on allows app users to register using their WhatsApp account.',
            'metadata' => json_encode([
                'input_fields' => [
                    [
                        'element' => 'input',
                        'type' => 'text',
                        'name' => 'whatsapp_callback_token',
                        'label' => 'Verify token',
                        'class' => 'col-span-2',
                    ],
                    [
                        'element' => 'input',
                        'type' => 'text',
                        'name' => 'whatsapp_app_id',
                        'label' => 'App ID',
                        'class' => 'col-span-2',
                    ],
                    [
                        'element' => 'input',
                        'type' => 'text',
                        'name' => 'whatsapp_client_id',
                        'label' => 'Client ID',
                        'class' => 'col-span-1',
                    ],
                    [
                        'element' => 'input',
                        'type' => 'text',
                        'name' => 'whatsapp_config_id',
                        'label' => 'Config ID',
                        'class' => 'col-span-1',
                    ],
                    [
                        'element' => 'input',
                        'type' => 'password',
                        'name' => 'whatsapp_access_token',
                        'label' => 'Access Token',
                        'class' => 'col-span-2',
                    ],
                    [
                        'element' => 'input',
                        'type' => 'password',
                        'name' => 'whatsapp_client_secret',
                        'label' => 'Client Secret',
                        'class' => 'col-span-2',
                    ],
                    [
                        'element' => 'toggle',
                        'type' => 'checkbox',
                        'name' => 'is_embedded_signup_active',
                        'label' => 'Enable/disable embedded signup',
                        'class' => 'col-span-2',
                    ],
                ],
            ]),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Keep this add-on record so configuration is not lost during rollback.
    }
};
