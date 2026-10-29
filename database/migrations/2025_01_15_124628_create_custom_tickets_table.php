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
        Schema::create('custom_tickets', function (Blueprint $table) {
            $table->id();
            $table->integer('organization_id');
            $table->string('reference')->nullable();
            $table->string('status')->nullable();
            $table->json('contact')->nullable();
            $table->string('subject')->nullable();
            $table->string('type')->nullable();
            $table->string('service')->nullable();
            $table->string('priority')->nullable();
            $table->string('agent')->nullable();
            $table->json('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_tickets');
    }
};
