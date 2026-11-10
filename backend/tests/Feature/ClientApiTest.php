<?php

namespace Tests\Feature;

use App\Client;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClientApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_create_update_and_delete_a_client(): void
    {
        $this->signIn();

        $clientId = $this->postJson('/api/clients', [
            'name' => 'Taylor Reed',
            'phone_number' => '01234567',
            'last_follow_up_date' => '2026-11-10',
        ])->assertCreated()
            ->assertJsonPath('data.email', null)
            ->assertJsonPath('data.last_follow_up_date', '2026-11-10')
            ->json('data.id');

        $url = "/api/clients/{$clientId}";

        $this->patchJson($url, [
            'email' => 'taylor@example.com',
            'last_follow_up_date' => null,
        ])->assertOk()
            ->assertJsonPath('data.name', 'Taylor Reed')
            ->assertJsonPath('data.phone_number', '01234567')
            ->assertJsonPath('data.email', 'taylor@example.com')
            ->assertJsonPath('data.last_follow_up_date', null);

        $this->getJson($url)->assertOk()->assertJsonPath('data.email', 'taylor@example.com');
        $this->deleteJson($url)->assertNoContent();
        $this->getJson($url)->assertNotFound();
    }

    public function test_an_admin_can_search_clients_and_view_their_companies_and_follow_up_date(): void
    {
        $this->signIn();
        $client = Client::create([
            'name' => 'Taylor Reed',
            'phone_number' => '01234567',
            'email' => 'taylor@example.com',
            'last_follow_up_date' => '2026-11-10',
        ]);
        $otherClient = Client::create(['name' => 'Morgan Lee', 'phone_number' => '08987654']);
        DB::table('companies')->insert([
            ['name' => 'Taylor Studio', 'client_id' => $client->getKey()],
            ['name' => 'Morgan Studio', 'client_id' => $otherClient->getKey()],
        ]);

        $this->getJson('/api/clients')->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Morgan Lee');

        foreach (['reed', 'example.com', '234567'] as $search) {
            $this->getJson('/api/clients?search='.$search)->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $client->getKey())
                ->assertJsonPath('data.0.last_follow_up_date', '2026-11-10');
        }

        $this->getJson('/api/clients?search=%25')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/clients/'.$client->getKey())->assertOk()
            ->assertJsonPath('data.last_follow_up_date', '2026-11-10')
            ->assertJsonCount(1, 'data.companies')
            ->assertJsonPath('data.companies.0.name', 'Taylor Studio');
    }

    public function test_client_routes_require_authentication_and_validate_input(): void
    {
        $this->getJson('/api/clients')->assertUnauthorized();
        $this->postJson('/api/clients')->assertUnauthorized();

        $this->signIn();
        $this->postJson('/api/clients', [
            'email' => 'not-an-email',
            'last_follow_up_date' => '2026-02-30',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'phone_number', 'email', 'last_follow_up_date']);
    }

    public function test_a_client_with_companies_cannot_be_deleted(): void
    {
        $this->signIn();
        $client = Client::create(['name' => 'Taylor Reed', 'phone_number' => '01234567']);
        DB::table('companies')->insert(['name' => 'Taylor Studio', 'client_id' => $client->getKey()]);

        $this->deleteJson('/api/clients/'.$client->getKey())->assertConflict();
        $this->assertModelExists($client);
        $this->assertDatabaseHas('companies', ['client_id' => $client->getKey()]);
    }

    private function signIn(): void
    {
        $this->actingAs(Admin::create([
            'email' => 'admin@example.com',
            'password' => 'local test password',
        ]));
    }
}
