<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farmer_verifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('id_number', 100);
            $table->string('full_name', 255);

            // Keep ID documents private.
            $table->string('front_image_path', 500);
            $table->string('back_image_path', 500);

            $table->string('status', 30)->default('pending');

            $table->text('rejection_reason')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // One verification record per farmer.
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmer_verifications');
    }
};