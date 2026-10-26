<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'id' => 1,
                'name' => 'FIX-IT HERBAL CAPSULE',
                'type' => 'Botanical supplement · Capsules',
                'price' => 25000,
                'image' => '/fix-it.PNG',
                'alt' => 'Fix-It Herbal Capsule',
                'description' => 'Targeted botanical care made for everyday wellness support.',
                'featured' => true,
            ],
            [
                'id' => 2,
                'name' => 'GLUCOFIX HERBAL CAPSULE',
                'type' => 'Botanical supplement · Capsules',
                'price' => 25000,
                'image' => '/product%202.PNG',
                'alt' => 'Glucofix Herbal Capsule',
                'description' => 'Plant-based support developed with quality, consistency, and care in mind.',
                'featured' => false,
            ],
            [
                'id' => 3,
                'name' => 'PHYTOGOLD TEA',
                'type' => 'Botanical infusion · 60 g',
                'price' => 8500,
                'image' => '/product%203.PNG',
                'alt' => 'PhytoGold Tea',
                'description' => 'A scientifically formulated herbal infusion for overall vitality and wellness.',
                'featured' => false,
            ],
        ];

        foreach ($products as $product) {
            Product::query()->updateOrCreate(
                ['id' => $product['id']],
                $product,
            );
        }
    }
}