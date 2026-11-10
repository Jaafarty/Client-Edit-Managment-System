<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Edit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AttachmentUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_upload_multiple_attachments_for_an_edit(): void
    {
        $disk = Storage::fake('local');
        $edit = $this->createEdit();
        $this->actingAs($this->createAdmin());

        $this->get(route('home'))->assertInertia(fn (Assert $page) => $page
            ->where('edits.0.id', $edit->id));

        $index = route('edit.attachments.index', $edit);
        $upload = route('edit.attachments.store', $edit);

        $this->post($upload, [
            'file' => UploadedFile::fake()->create('brief.pdf', 10),
            'description' => 'Client brief',
        ])->assertRedirect($index);

        $this->post($upload, [
            'file' => UploadedFile::fake()->create('notes.txt', 1),
        ])->assertRedirect($index);

        $attachments = $edit->attachments()->oldest('id')->get();
        $this->assertCount(2, $attachments);
        $this->assertSame('Client brief', $attachments[0]->description);
        $this->assertNull($attachments[1]->description);
        $disk->assertExists($attachments[0]->path);
        $disk->assertExists($attachments[1]->path);

        $this->get($index)->assertInertia(fn (Assert $page) => $page
            ->component('attachments')
            ->where('edit.id', $edit->id)
            ->where('uploadUrl', $upload)
            ->has('attachments', 2)
            ->where('attachments.0.original_name', 'notes.txt')
            ->missing('attachments.0.path'));
    }

    public function test_upload_requires_an_admin_and_a_file_within_the_limit(): void
    {
        $disk = Storage::fake('local');
        $edit = $this->createEdit();
        $upload = route('edit.attachments.store', $edit);
        $index = route('edit.attachments.index', $edit);

        $this->get($index)->assertRedirect(route('login'));
        $this->post($upload)->assertRedirect(route('login'));

        $this->actingAs($this->createAdmin());

        $this->from($index)->post($upload)->assertSessionHasErrors('file');
        $this->from($index)->post($upload, [
            'file' => UploadedFile::fake()->create('large.zip', 2049),
        ])->assertSessionHasErrors('file');

        $this->assertDatabaseCount('attachments', 0);
        $disk->assertDirectoryEmpty('/');
    }

    private function createAdmin(): Admin
    {
        return Admin::create([
            'email' => 'admin@example.com',
            'password' => 'local test password',
        ]);
    }

    private function createEdit(): Edit
    {
        $client = DB::table('clients')->insertGetId(['name' => 'Client', 'phone_number' => '01234567']);
        $company = DB::table('companies')->insertGetId(['name' => 'Company', 'client_id' => $client]);
        $project = DB::table('projects')->insertGetId(['name' => 'Project', 'company_id' => $company]);

        $edit = DB::table('edits')->insertGetId([
            'project_id' => $project,
            'title' => 'Edit',
            'priority' => 'High',
            'status' => 'Pending',
        ]);

        return Edit::findOrFail($edit);
    }
}
