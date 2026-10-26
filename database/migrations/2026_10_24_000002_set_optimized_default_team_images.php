<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const IMAGES = [
        'Dr. Cecilia Oluwamodupe' => '/team/dr-cecilia.webp',
        'Mercy Adelabu' => '/team/mercy-adelabu.webp',
        'Chris Balogun' => '/team/chris-balogun.webp',
        'Clement Raji' => '/team/clement-raji.webp',
        'Judah King' => '/team/judah-king.webp',
    ];

    public function up(): void
    {
        foreach (self::IMAGES as $name => $image) {
            DB::table('team_members')
                ->where('name', $name)
                ->whereNull('image')
                ->update(['image' => $image]);
        }
    }

    public function down(): void
    {
        foreach (self::IMAGES as $name => $image) {
            DB::table('team_members')
                ->where('name', $name)
                ->where('image', $image)
                ->update(['image' => null]);
        }
    }
};
