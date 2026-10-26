<?php

namespace Database\Seeders;

use App\Models\TeamMember;
use Illuminate\Database\Seeder;

class TeamMemberSeeder extends Seeder
{
    public function run(): void
    {
        $members = [
            ['name' => 'Dr. Cecilia Oluwamodupe', 'role' => 'Founder/CEO', 'bio' => null, 'image' => '/team/dr-cecilia.webp', 'sort_order' => 1],
            ['name' => 'Mercy Adelabu', 'role' => 'Product Manager', 'bio' => null, 'image' => '/team/mercy-adelabu.webp', 'sort_order' => 2],
            ['name' => 'Chris Balogun', 'role' => 'Technical Supervisor', 'bio' => null, 'image' => '/team/chris-balogun.webp', 'sort_order' => 3],
            ['name' => 'Clement Raji', 'role' => 'Visual Communication Specialist', 'bio' => null, 'image' => '/team/clement-raji.webp', 'sort_order' => 4],
            ['name' => 'Judah King', 'role' => 'Media Team Head', 'bio' => null, 'image' => '/team/judah-king.webp', 'sort_order' => 5],
        ];

        foreach ($members as $index => $member) {
            TeamMember::query()->updateOrCreate(
                ['name' => $member['name']],
                array_merge($member, ['is_active' => true, 'sort_order' => $index + 1]),
            );
        }
    }
}