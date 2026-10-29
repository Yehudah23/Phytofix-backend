<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DatabaseMigrationMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.db_migration_maintenance') && ! $request->is('up')) {
            return response()->json([
                'message' => 'The service is temporarily unavailable for a database migration.',
            ], Response::HTTP_SERVICE_UNAVAILABLE)
                ->header('Retry-After', '120');
        }

        return $next($request);
    }
}
