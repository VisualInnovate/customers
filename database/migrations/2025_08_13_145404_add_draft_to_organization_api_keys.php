<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('organization_api_keys', function (Blueprint $table) {
            $table->string('draft')->nullable()->after('token')->comment('Describe the purpose of the API key, e.g., "Shopify Integration"');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organization_api_keys', function (Blueprint $table) {
            //
        });
    }
};
