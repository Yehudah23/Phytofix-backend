<?php

namespace Tests\Feature;

use Tests\TestCase;

class CorsConfigurationTest extends TestCase
{
    public function test_vercel_frontend_can_make_cross_origin_api_requests(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'https://phytofix.vercel.app',
            'Access-Control-Request-Method' => 'GET',
            'Access-Control-Request-Headers' => 'authorization,content-type',
        ])->options('/api/products');

        $response->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'https://phytofix.vercel.app');
    }

    public function test_unconfigured_origins_are_not_granted_cross_origin_api_access(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'https://untrusted.example',
            'Access-Control-Request-Method' => 'GET',
        ])->options('/api/products');

        $this->assertFalse($response->headers->has('Access-Control-Allow-Origin'));
    }
}
