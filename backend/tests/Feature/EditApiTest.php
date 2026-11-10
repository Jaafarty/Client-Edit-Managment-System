<?php

namespace Tests\Feature;

use App\Client;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Edit;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EditApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_create_list_view_update_and_delete_an_edit(): void
    {
        $this->signIn();
        $project = $this->createProject();

        $editId = $this->postJson('/api/edits', [
            'project_id' => $project->getKey(),
            'title' => 'Update logo',
            'priority' => 'High',
            'status' => 'In Progress',
            'price' => '125.50',
            'due_date' => '2026-11-20',
        ])->assertCreated()
            ->assertJsonPath('data.is_paid', false)
            ->assertJsonPath('data.price', '125.50')
            ->assertJsonPath('data.due_date', '2026-11-20')
            ->assertJsonPath('data.project.name', 'Website')
            ->json('data.id');

        $url = "/api/edits/{$editId}";
        $this->getJson('/api/edits')->assertOk()->assertJsonPath('data.0.id', $editId);
        $this->getJson($url)->assertOk()->assertJsonPath('data.title', 'Update logo');
        $this->get('/edits')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('edits')
            ->where('edits.0.id', $editId)
            ->where('projects.0.id', $project->getKey()));

        $this->patchJson($url, ['title' => 'Update footer', 'price' => null])->assertOk()
            ->assertJsonPath('data.title', 'Update footer')
            ->assertJsonPath('data.price', null);
        $this->deleteJson($url)->assertNoContent();
        $this->getJson($url)->assertNotFound();
    }

    public function test_an_admin_can_mark_an_edit_paid_and_unpaid_without_changing_its_details(): void
    {
        $this->signIn();
        $edit = $this->createEdit();
        $url = '/api/edits/'.$edit->getKey();

        foreach ([true, false] as $isPaid) {
            $this->patchJson($url, ['is_paid' => $isPaid])->assertOk()
                ->assertJsonPath('data.is_paid', $isPaid)
                ->assertJsonPath('data.title', 'Update logo')
                ->assertJsonPath('data.status', 'Pending');
            $this->getJson($url)->assertOk()->assertJsonPath('data.is_paid', $isPaid);
        }
    }

    public function test_edit_routes_require_an_admin_and_validate_input(): void
    {
        $this->get('/edits')->assertRedirect(route('login'));
        $this->getJson('/api/edits')->assertUnauthorized();

        $this->signIn();
        $this->postJson('/api/edits', [
            'project_id' => 1,
            'priority' => 'Urgent',
            'status' => 'Other',
            'price' => '100000000.00',
            'due_date' => '2026-02-30',
            'is_paid' => null,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['project_id', 'title', 'priority', 'status', 'price', 'due_date', 'is_paid']);
    }

    public function test_an_edit_with_attachments_cannot_be_deleted(): void
    {
        $this->signIn();
        $edit = $this->createEdit();
        $attachment = $edit->attachments()->create(['path' => 'edit-attachments/brief.pdf', 'original_name' => 'brief.pdf']);

        $this->deleteJson('/api/edits/'.$edit->getKey())->assertConflict();
        $this->assertModelExists($edit);
        $this->assertModelExists($attachment);
    }

    private function createProject(): Project
    {
        $client = Client::create(['name' => 'Taylor Reed', 'phone_number' => '01234567']);
        $company = Company::create(['name' => 'Studio North', 'client_id' => $client->getKey()]);

        return Project::create(['name' => 'Website', 'company_id' => $company->getKey()]);
    }

    private function createEdit(): Edit
    {
        return Edit::create([
            'project_id' => $this->createProject()->getKey(),
            'title' => 'Update logo',
            'priority' => 'High',
            'status' => 'Pending',
        ]);
    }

    private function signIn(): void
    {
        $this->actingAs(Admin::create([
            'email' => 'admin@example.com',
            'password' => 'local test password',
        ]));
    }
}
