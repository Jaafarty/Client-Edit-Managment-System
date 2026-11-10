<?php

namespace Tests\Feature;

use App\Client;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_create_list_rename_reassign_and_delete_a_project(): void
    {
        $this->signIn();
        $client = Client::create(['name' => 'Taylor Reed', 'phone_number' => '01234567']);
        $originalCompany = Company::create(['name' => 'Studio North', 'client_id' => $client->getKey()]);
        $newCompany = Company::create(['name' => 'Studio West', 'client_id' => $client->getKey()]);

        $projectId = $this->postJson('/api/projects', [
            'name' => 'Website refresh',
            'company_id' => (string) $originalCompany->getKey(),
        ])->assertCreated()
            ->assertJsonPath('data.company_id', $originalCompany->getKey())
            ->json('data.id');

        $this->getJson('/api/projects')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $projectId);

        $url = "/api/projects/{$projectId}";
        $this->patchJson($url, ['name' => 'Website launch'])->assertOk()
            ->assertJsonPath('data.name', 'Website launch')
            ->assertJsonPath('data.company_id', $originalCompany->getKey());

        $this->patchJson($url, ['company_id' => $newCompany->getKey()])->assertOk()
            ->assertJsonPath('data.name', 'Website launch')
            ->assertJsonPath('data.company_id', $newCompany->getKey());

        $this->getJson($url)->assertOk()
            ->assertJsonPath('data.company.name', 'Studio West')
            ->assertJsonPath('data.edits', []);
        $this->deleteJson($url)->assertNoContent();
        $this->getJson($url)->assertNotFound();
    }

    public function test_project_details_include_only_its_edits_and_protect_them_from_deletion(): void
    {
        $this->signIn();
        $company = $this->createCompany();
        $project = Project::create(['name' => 'Website', 'company_id' => $company->getKey()]);
        $otherProject = Project::create(['name' => 'Newsletter', 'company_id' => $company->getKey()]);

        DB::table('edits')->insert([
            ['title' => 'Update logo', 'project_id' => $project->getKey(), 'priority' => 'High', 'status' => 'Pending'],
            ['title' => 'Update footer', 'project_id' => $project->getKey(), 'priority' => 'Low', 'status' => 'Done'],
            ['title' => 'Change subject line', 'project_id' => $otherProject->getKey(), 'priority' => 'Medium', 'status' => 'Confirmed'],
        ]);

        $url = '/api/projects/'.$project->getKey();
        $this->getJson($url)->assertOk()
            ->assertJsonPath('data.company.id', $company->getKey())
            ->assertJsonCount(2, 'data.edits')
            ->assertJsonFragment(['title' => 'Update logo'])
            ->assertJsonFragment(['title' => 'Update footer']);

        $this->deleteJson($url)->assertConflict();
        $this->assertModelExists($project);
        $this->assertDatabaseCount('edits', 3);
    }

    public function test_project_routes_require_an_admin_and_a_valid_company_assignment(): void
    {
        $this->getJson('/api/projects')->assertUnauthorized();
        $this->postJson('/api/projects')->assertUnauthorized();

        $this->signIn();
        $this->postJson('/api/projects')->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'company_id']);
        $this->postJson('/api/projects', ['name' => 'Website', 'company_id' => 1])
            ->assertUnprocessable()->assertJsonValidationErrors('company_id');

        $company = $this->createCompany();
        $project = Project::create(['name' => 'Website', 'company_id' => $company->getKey()]);
        $this->patchJson('/api/projects/'.$project->getKey(), ['company_id' => null])
            ->assertUnprocessable()->assertJsonValidationErrors('company_id');
        $this->assertSame($company->getKey(), $project->refresh()->company_id);
    }

    private function createCompany(): Company
    {
        $client = Client::create(['name' => 'Taylor Reed', 'phone_number' => '01234567']);

        return Company::create(['name' => 'Studio North', 'client_id' => $client->getKey()]);
    }

    private function signIn(): void
    {
        $this->actingAs(Admin::create([
            'email' => 'admin@example.com',
            'password' => 'local test password',
        ]));
    }
}
