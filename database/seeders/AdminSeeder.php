<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('admin.email');

        if (Admin::where('email', $email)->exists()) {
            return;
        }

        $password = config('admin.initial_password');
        if (! is_string($password) || strlen($password) < 12) {
            throw new RuntimeException('Set ADMIN_INITIAL_PASSWORD to a unique value of at least 12 characters before creating the initial admin.');
        }

        Admin::create([
            'name' => 'Phytofix Admin',
            'email' => $email,
            'password' => Hash::make($password),
        ]);
    }
}