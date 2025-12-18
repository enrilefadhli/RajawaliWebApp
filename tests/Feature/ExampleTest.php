<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/admin');
    }

    public function test_login_route_redirects_to_filament_login(): void
    {
        $response = $this->get('/login');

        $response->assertRedirect('/admin/login');
    }
}
