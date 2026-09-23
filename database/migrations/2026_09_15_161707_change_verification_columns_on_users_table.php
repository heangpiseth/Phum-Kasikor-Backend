<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE users
            ALTER COLUMN email_verified_at
            TYPE TIMESTAMP(0) WITHOUT TIME ZONE
            USING NULLIF(email_verified_at, '')::timestamp(0)
        ");

        DB::statement("
            ALTER TABLE users
            ALTER COLUMN phone_verified_at
            TYPE TIMESTAMP(0) WITHOUT TIME ZONE
            USING NULLIF(phone_verified_at, '')::timestamp(0)
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE users
            ALTER COLUMN email_verified_at
            TYPE VARCHAR(255)
            USING email_verified_at::text
        ");

        DB::statement("
            ALTER TABLE users
            ALTER COLUMN phone_verified_at
            TYPE VARCHAR(255)
            USING phone_verified_at::text
        ");
    }
};