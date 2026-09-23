<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();

            $table->foreignId('farm_id')
                ->constrained('farms')
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained('inventory_categories')
                ->nullOnDelete();

            $table->string('name');

            $table->decimal('quantity', 12, 2)
                ->nullable();

            $table->string('unit', 30)
                ->nullable();

            $table->decimal('minimum_stock', 12, 2)
                ->nullable();

            $table->enum('status', [
                'in_stock',
                'low_stock',
                'out_of_stock',
            ])->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};