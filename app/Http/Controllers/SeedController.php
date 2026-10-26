<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SeedController extends Controller
{
    public function seed(Request $request)
    {
        $key = $request->header('X-Seed-Key');

        if (! $key || ! hash_equals((string) config('services.seed.key', ''), (string) $key)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);

        return response()->json(['message' => 'Database seeded.']);
    }
}