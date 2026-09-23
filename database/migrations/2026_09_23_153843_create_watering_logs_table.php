<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watering_logs', function (Blueprint $table) {
            $table->id();

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

            $table->date('watering_date');

            $table->decimal('water_amount', 12, 2)
                ->nullable();

            $table->text('notes')
                ->nullable();

            $table->timestamps();

            $table->index([
                'crop_id',
                'watering_date',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watering_logs');
    }
};