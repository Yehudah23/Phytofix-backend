<?php

namespace Tests\Feature;

use Tests\TestCase;

class DatabaseMigrationMaintenanceTest extends TestCase
{
    public function test_requests_receive_service_unavailable_during_the_database_migration(): void
    {
        config(['app.db_migration_maintenance' => true]);

        $this->getJson('/api/products')
            ->assertServiceUnavailable()
            ->assertJson([
                'message' => 'The service is temporarily unavailable for a database migration.',
            ])
            ->assertHeader('Retry-After', '120');

        $this->get('/up')->assertOk();
    }
}
