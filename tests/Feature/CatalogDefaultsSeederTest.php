<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\TeamMember;
use Database\Seeders\CatalogDefaultsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogDefaultsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_defaults_for_empty_catalog_tables_and_is_idempotent(): void
    {
        $this->seed(CatalogDefaultsSeeder::class);

        $this->assertDatabaseCount('products', 3);
        $this->assertDatabaseCount('team_members', 5);
        $this->assertDatabaseHas('products', ['name' => 'FIX-IT HERBAL CAPSULE']);
        $this->assertDatabaseHas('team_members', ['name' => 'Dr. Cecilia Oluwamodupe']);

        $this->seed(CatalogDefaultsSeeder::class);

        $this->assertDatabaseCount('products', 3);
        $this->assertDatabaseCount('team_members', 5);
    }

    public function test_it_preserves_existing_products_and_team_members(): void
    {
        Product::query()->create([
            'name' => 'FIX-IT HERBAL CAPSULE',
            'type' => 'Existing catalog entry',
            'price' => 999,
            'image' => '/existing-product.png',
        ]);
        TeamMember::query()->create([
            'name' => 'Dr. Cecilia Oluwamodupe',
            'role' => 'Existing team role',
            'is_active' => false,
        ]);

        $this->seed(CatalogDefaultsSeeder::class);

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', [
            'name' => 'FIX-IT HERBAL CAPSULE',
            'type' => 'Existing catalog entry',
            'price' => 999,
        ]);
        $this->assertDatabaseCount('team_members', 1);
        $this->assertDatabaseHas('team_members', [
            'name' => 'Dr. Cecilia Oluwamodupe',
            'role' => 'Existing team role',
            'is_active' => false,
        ]);
    }
}
