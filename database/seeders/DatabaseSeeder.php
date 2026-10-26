<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\NewsPost;
use App\Models\Product;
use App\Models\ProductRoutine;
use App\Models\RoutineStep;
use App\Models\TeamMember;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            ProductSeeder::class,
            ProductRoutineSeeder::class,
            TeamMemberSeeder::class,
            NewsPostSeeder::class,
            AdminSeeder::class,
        ]);
    }
}