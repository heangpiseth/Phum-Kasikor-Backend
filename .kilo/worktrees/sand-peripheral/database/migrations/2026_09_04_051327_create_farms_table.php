<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('farm_name');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->decimal('farm_size', 8, 2)->nullable();
            $table->string('farming_method')->nullable();
            $table->timestamps();
            
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('farms');
    }
};
