<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_is_available_to_admins(): void
    {
        $admin = Admin::create([
            'email' => 'admin@example.com',
            'password' => 'local test password',
        ]);
        $response = $this->actingAs($admin)->get(route('home'));

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('home')
            ->where('name', config('app.name')));
    }

    public function test_starter_kit_pages_are_unavailable(): void
    {
        $this->get('/register')->assertNotFound();
        $this->get('/dashboard')->assertNotFound();
        $this->get('/settings/profile')->assertNotFound();
    }
}
