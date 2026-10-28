<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->string('call_id');
            $table->foreignIdFor(\App\Models\Organization::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(\App\Models\Contact::class)->constrained()->cascadeOnDelete();
            $table->string('from');
            $table->string('to');
            $table->string('status')->default('initiated');
            $table->enum('direction', ['inbound', 'outbound'])->default('inbound');
            $table->json('metadata')->nullable();
            $table->string('session_id')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->integer('duration')->nullable();
            $table->timestamps();

            $table->index('call_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
