<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('tags')) {
            Schema::create('tags', function (Blueprint $table) {
                $table->id();
                $table->integer('organization_id');
                $table->string('tag');
                $table->timestamps();
            });

            return;
        }

        Schema::table('tags', function (Blueprint $table) {
            if (!Schema::hasColumn('tags', 'organization_id')) {
                $table->integer('organization_id')->nullable();
            }

            if (!Schema::hasColumn('tags', 'tag')) {
                $table->string('tag')->nullable();
            }
        });

        if (Schema::hasColumn('tags', 'name')) {
            DB::table('tags')
                ->whereNull('tag')
                ->update(['tag' => DB::raw('name')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('tags')) {
            return;
        }

        if (Schema::hasColumn('tags', 'name')) {
            Schema::table('tags', function (Blueprint $table) {
                $columns = [];

                if (Schema::hasColumn('tags', 'organization_id')) {
                    $columns[] = 'organization_id';
                }

                if (Schema::hasColumn('tags', 'tag')) {
                    $columns[] = 'tag';
                }

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });

            return;
        }

        Schema::dropIfExists('tags');
    }
};
