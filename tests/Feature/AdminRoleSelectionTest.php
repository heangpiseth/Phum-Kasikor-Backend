<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminRoleSelectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->string('role')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function test_regular_user_cannot_assign_the_admin_role(): void
    {
        $user = User::create([
            'name' => 'Marketplace user',
            'email' => 'marketplace@example.test',
            'password' => 'secret-password',
            'role' => UserRole::CUSTOMER,
        ]);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/profile/choose-role', ['role' => UserRole::ADMIN->value])
            ->assertUnprocessable();

        $this->assertSame(UserRole::CUSTOMER, $user->fresh()->role);
    }

    public function test_customer_and_farmer_roles_remain_selectable(): void
    {
        $user = User::create([
            'name' => 'Marketplace user',
            'email' => 'marketplace@example.test',
            'password' => 'secret-password',
            'role' => UserRole::CUSTOMER,
        ]);

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/profile/choose-role', ['role' => UserRole::FARMER->value])
            ->assertOk();

        $this->assertSame(UserRole::FARMER, $user->fresh()->role);
    }
}
