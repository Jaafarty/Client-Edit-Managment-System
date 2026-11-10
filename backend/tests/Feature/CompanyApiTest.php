<?php

namespace Tests\Feature;

use App\Client;
use App\Models\Admin;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompanyApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_create_list_rename_reassign_and_delete_a_company(): void
    {
        $this->signIn();
        $originalClient = Client::create(['name' => 'Taylor Reed', 'phone_number' => '01234567']);
        $newClient = Client::create(['name' => 'Morgan Lee', 'phone_number' => '08987654']);

        $companyId = $this->postJson('/api/companies', [
            'name' => 'Taylor Studio',
            'client_id' => (string) $originalClient->getKey(),
        ])->assertCreated()
            ->assertJsonPath('data.client_id', $originalClient->getKey())
            ->json('data.id');

        $this->getJson('/api/companies')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $companyId);

        $url = "/api/companies/{$companyId}";
        $this->patchJson($url, ['name' => 'Studio North'])->assertOk()
            ->assertJsonPath('data.name', 'Studio North')
            ->assertJsonPath('data.client_id', $originalClient->getKey());

        $this->patchJson($url, ['client_id' => $newClient->getKey()])->assertOk()
            ->assertJsonPath('data.name', 'Studio North')
            ->assertJsonPath('data.client_id', $newClient->getKey());

        $this->getJson($url)->assertOk()
            ->assertJsonPath('data.client.name', 'Morgan Lee')
            ->assertJsonPath('data.projects', []);
        $this->deleteJson($url)->assertNoContent();
        $this->getJson($url)->assertNotFound();
    }

    public function test_company_details_include_only_its_projects_and_protect_them_from_deletion(): void
    {
        $this->signIn();
        $client = Client::create(['name' => 'Taylor Reed', 'phone_number' => '01234567']);
        $company = Company::create(['name' => 'Taylor Studio', 'client_id' => $client->getKey()]);
        $otherCompany = Company::create(['name' => 'Studio West', 'client_id' => $client->getKey()]);

        DB::table('projects')->insert([
            ['name' => 'Website', 'company_id' => $company->getKey()],
            ['name' => 'Campaign', 'company_id' => $company->getKey()],
            ['name' => 'Newsletter', 'company_id' => $otherCompany->getKey()],
        ]);

        $url = '/api/companies/'.$company->getKey();
        $this->getJson($url)->assertOk()
            ->assertJsonPath('data.client.id', $client->getKey())
            ->assertJsonCount(2, 'data.projects')
            ->assertJsonFragment(['name' => 'Website'])
            ->assertJsonFragment(['name' => 'Campaign']);

        $this->deleteJson($url)->assertConflict();
        $this->assertModelExists($company);
        $this->assertDatabaseCount('projects', 3);
    }

    public function test_company_routes_require_an_admin_and_a_valid_client_assignment(): void
    {
        $this->getJson('/api/companies')->assertUnauthorized();
        $this->postJson('/api/companies')->assertUnauthorized();

        $this->signIn();
        $this->postJson('/api/companies')->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'client_id']);
        $this->postJson('/api/companies', ['name' => 'Studio North', 'client_id' => 1])
            ->assertUnprocessable()->assertJsonValidationErrors('client_id');

        $client = Client::create(['name' => 'Taylor Reed', 'phone_number' => '01234567']);
        $company = Company::create(['name' => 'Taylor Studio', 'client_id' => $client->getKey()]);
        $this->patchJson('/api/companies/'.$company->getKey(), ['client_id' => null])
            ->assertUnprocessable()->assertJsonValidationErrors('client_id');
        $this->assertSame($client->getKey(), $company->refresh()->client_id);
    }

    private function signIn(): void
    {
        $this->actingAs(Admin::create([
            'email' => 'admin@example.com',
            'password' => 'local test password',
        ]));
    }
}
