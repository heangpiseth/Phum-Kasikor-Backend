<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('harvest_logs', function (Blueprint $table) {
            $table->foreignId('farm_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('field_id')
                ->nullable()
                ->constrained('fields')
                ->nullOnDelete();

            $table->foreignId('crop_id')
                ->constrained('crops')
                ->cascadeOnDelete();

            $table->date('harvest_date');

            $table->decimal('quantity', 12, 2);

            $table->string('quality', 100)
                ->nullable();

            $table->string('condition', 100)
                ->nullable();

            $table->text('notes')
                ->nullable();

            $table->index([
                'crop_id',
                'harvest_date',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('harvest_logs', function (Blueprint $table) {
            $table->dropForeign(['farm_id']);
            $table->dropForeign(['field_id']);
            $table->dropForeign(['crop_id']);

            $table->dropIndex([
                'harvest_logs_crop_id_harvest_date_index',
            ]);

            $table->dropColumn([
                'farm_id',
                'field_id',
                'crop_id',
                'harvest_date',
                'quantity',
                'quality',
                'condition',
                'notes',
            ]);
        });
    }
};