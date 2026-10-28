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
        Schema::table('api_token_logs', function (Blueprint $table) {
            $table->text('request_headers')->nullable()->after('ip_address');
            $table->text('request_body')->nullable()->after('request_headers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_token_logs', function (Blueprint $table) {
            //
        });
    }
};
