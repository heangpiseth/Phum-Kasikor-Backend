<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->nullable()->change();
            $table->string('display_name')->nullable()->after('name');
            $table->text('bio')->nullable()->after('display_name');
            $table->string('gender', 20)->nullable()->after('bio');
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->decimal('latitude', 10, 7)->nullable()->after('location');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['display_name', 'bio', 'gender', 'date_of_birth', 'latitude', 'longitude']);
            $table->string('role', 20)->nullable(false)->change();
        });
    }
};