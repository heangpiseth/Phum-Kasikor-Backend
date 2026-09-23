<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Fix field_id
        |--------------------------------------------------------------------------
        */

        Schema::table('crops', function (Blueprint $table) {
            $table->foreignId('field_id')
                ->nullable()
                ->change();
        });


        /*
        |--------------------------------------------------------------------------
        | Fix expected harvest date column
        |--------------------------------------------------------------------------
        */

        if (Schema::hasColumn('crops', 'expected_harvest_data')) {

            Schema::table('crops', function (Blueprint $table) {
                $table->renameColumn(
                    'expected_harvest_data',
                    'expected_harvest_date'
                );
            });

        } elseif (!Schema::hasColumn('crops', 'expected_harvest_date')) {

            Schema::table('crops', function (Blueprint $table) {
                $table->date('expected_harvest_date')
                    ->nullable();
            });
        }


        /*
        |--------------------------------------------------------------------------
        | Add crop image
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasColumn('crops', 'image')) {

            Schema::table('crops', function (Blueprint $table) {
                $table->string('image', 500)
                    ->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('crops', 'image')) {
            Schema::table('crops', function (Blueprint $table) {
                $table->dropColumn('image');
            });
        }

        if (Schema::hasColumn('crops', 'expected_harvest_date')) {
            Schema::table('crops', function (Blueprint $table) {
                $table->renameColumn(
                    'expected_harvest_date',
                    'expected_harvest_data'
                );
            });
        }
    }
};