<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\TeamMember;
use Illuminate\Database\Seeder;

class CatalogDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        if (! Product::query()->exists()) {
            $this->call(ProductSeeder::class);
        }

        if (! TeamMember::query()->exists()) {
            $this->call(TeamMemberSeeder::class);
        }
    }
}
