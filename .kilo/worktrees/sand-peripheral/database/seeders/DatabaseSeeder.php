<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Farm;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Test accounts ----
        $farmer = User::create([
            'name' => 'Dara Farmer',
            'email' => 'farmer@test.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::FARMER,
        ]);

        $customer = User::create([
            'name' => 'Sokha Customer',
            'email' => 'customer@test.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::CUSTOMER,
        ]);

        // ---- Categories ----
        $vegetables = Category::create(['name' => 'Vegetables']);
        Category::create(['name' => 'Leafy Greens', 'parent_id' => $vegetables->id]);
        $fruits = Category::create(['name' => 'Fruits']);

        // ---- A farm for the test farmer ----
        $farm = Farm::create([
            'user_id' => $farmer->id,
            'farm_name' => 'Green Valley Farm',
            'description' => 'A small family farm growing seasonal produce.',
            'location' => 'Kampong Speu',
            'farm_size' => 5.5,
            'farming_method' => 'organic',
        ]);

        // ---- A few products for that farm ----
        Product::create([
            'farm_id' => $farm->id,
            'category_id' => $vegetables->id,
            'name' => 'Fresh Tomatoes',
            'description' => 'Vine-ripened, grown without pesticides.',
            'price' => 2.50,
            'unit' => 'kg',
            'quantity_available' => 100,
            'is_active' => true,
        ]);

        Product::create([
            'farm_id' => $farm->id,
            'category_id' => $fruits->id,
            'name' => 'Sweet Mangoes',
            'description' => 'Locally grown, ready to eat.',
            'price' => 3.00,
            'unit' => 'kg',
            'quantity_available' => 50,
            'is_active' => true,
        ]);
    }
}