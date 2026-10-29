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
        Schema::create('categories', function (Blueprint $table) {
           $table->bigIncrements('id');

            $table->string('name')->unique();
            $table->mediumText('description')->nullable();
            $table->binary('image')->nullable();

            $table->unsignedBigInteger('parent_category_id')->nullable();
            $table->foreign('parent_category_id')
                ->references('id')->on('categories')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
