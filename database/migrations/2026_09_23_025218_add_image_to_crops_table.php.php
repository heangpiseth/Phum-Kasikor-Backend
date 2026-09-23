<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // The image column already exists in the crops table.
    }

    public function down(): void
    {
        // Intentionally left empty.
    }
};