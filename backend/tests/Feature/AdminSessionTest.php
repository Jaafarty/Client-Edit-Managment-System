<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get(route('home'))->assertRedirect(route('login'));

        $login = $this->get(route('login'))->assertOk()->assertInertia(
            fn (Assert $page) => $page->component('login'),
        );
        $this->assertTrue($login->headers->hasCacheControlDirective('no-store'));
        $this->assertTrue($login->inertiaPage()['encryptHistory']);
    }

    public function test_an_admin_can_be_created_sign_in_and_sign_out(): void
    {
        $password = 'local test password';

        $this->artisan('admin:create')
            ->expectsQuestion('Admin email', 'Admin@example.com')
            ->expectsQuestion('Password', $password)
            ->expectsQuestion('Confirm password', $password)
            ->expectsOutput('Admin account created.')
            ->assertSuccessful();

        $admin = Admin::where('email', 'admin@example.com')->firstOrFail();
        $storedPassword = $admin->getAuthPassword();
        $this->assertNotSame($password, $storedPassword);
        $this->assertTrue(Hash::check($password, $storedPassword));
        $this->assertArrayNotHasKey('password', $admin->toArray());

        $this->post(route('login.store'), [
            'email' => 'Admin@example.com',
            'password' => $password,
        ])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($admin);

        $home = $this->get(route('home'))->assertOk();
        $this->assertTrue($home->headers->hasCacheControlDirective('no-store'));
        $this->assertTrue($home->inertiaPage()['encryptHistory']);
        $this->assertTrue($home->inertiaPage()['clearHistory']);
        $this->get(route('login'))->assertRedirect(route('home'));

        $sessionId = session()->getId();
        $csrfToken = session()->token();
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertNotSame($sessionId, session()->getId());
        $this->assertNotSame($csrfToken, session()->token());

        $login = $this->get(route('login'))->assertOk();
        $this->assertTrue($login->inertiaPage()['clearHistory']);
        $this->get(route('home'))->assertRedirect(route('login'));
        $this->getJson('/api/edits')->assertUnauthorized();
    }

    public function test_failed_logins_are_rejected_and_rate_limited(): void
    {
        Admin::create([
            'email' => 'admin@example.com',
            'password' => 'local test password',
        ]);

        $credentials = ['email' => 'admin@example.com', 'password' => 'wrong password'];

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from(route('login'))->post(route('login.store'), $credentials)
                ->assertRedirect(route('login'))
                ->assertSessionHasErrors(['email' => 'The email or password is incorrect.'])
                ->assertSessionMissing('_old_input.password');
        }

        $this->post(route('login.store'), $credentials)->assertTooManyRequests();
        $this->assertGuest();
    }
}
