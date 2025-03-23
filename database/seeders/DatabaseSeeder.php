<?php

namespace Database\Seeders;

use App\Models\User;
use App\Domain\Inventory\Models\InventoryItem;
use App\Domain\Inventory\Models\InventoryTransaction;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Create a development user
        User::create([
            'name' => 'Dev User',
            'email' => 'dev@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Create users
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        // Create some initial inventory items
        $cassiterite = InventoryItem::create([
            'name' => 'Raw Cassiterite',
            'type' => 'cassiterite',
            'description' => 'Raw cassiterite ore for processing',
            'quantity' => 1000,
            'unit' => 'kg',
            'sn_content' => 75.0,
            'location' => 'Warehouse A',
            'status' => 'active'
        ]);

        $ingot = InventoryItem::create([
            'name' => 'Tin Ingot',
            'type' => 'ingot',
            'description' => 'Processed tin ingots',
            'quantity' => 500,
            'unit' => 'kg',
            'sn_content' => 99.9,
            'location' => 'Warehouse B',
            'status' => 'active'
        ]);

        $finishedTin = InventoryItem::create([
            'name' => 'Refined Tin',
            'type' => 'finished_tin',
            'description' => 'Final refined tin product',
            'quantity' => 200,
            'unit' => 'kg',
            'sn_content' => 99.99,
            'location' => 'Warehouse C',
            'status' => 'active'
        ]);

        $slag = InventoryItem::create([
            'name' => 'Processing Slag',
            'type' => 'slag',
            'description' => 'Slag from tin processing',
            'quantity' => 300,
            'unit' => 'kg',
            'sn_content' => 5.0,
            'location' => 'Warehouse D',
            'status' => 'active'
        ]);

        // Create some initial transactions
        InventoryTransaction::create([
            'inventory_item_id' => $cassiterite->id,
            'type' => 'production',
            'quantity' => 1000,
            'unit_price' => 50.00,
            'currency' => 'USD',
            'reference_type' => 'initial_stock',
            'notes' => 'Initial stock entry'
        ]);

        InventoryTransaction::create([
            'inventory_item_id' => $ingot->id,
            'type' => 'production',
            'quantity' => 500,
            'unit_price' => 100.00,
            'currency' => 'USD',
            'reference_type' => 'initial_stock',
            'notes' => 'Initial stock entry'
        ]);

        InventoryTransaction::create([
            'inventory_item_id' => $finishedTin->id,
            'type' => 'production',
            'quantity' => 200,
            'unit_price' => 150.00,
            'currency' => 'USD',
            'reference_type' => 'initial_stock',
            'notes' => 'Initial stock entry'
        ]);

        InventoryTransaction::create([
            'inventory_item_id' => $slag->id,
            'type' => 'production',
            'quantity' => 300,
            'unit_price' => 10.00,
            'currency' => 'USD',
            'reference_type' => 'initial_stock',
            'notes' => 'Initial stock entry'
        ]);

        // Seed batches
        $this->call([
            BatchSeeder::class,
        ]);
    }
}
