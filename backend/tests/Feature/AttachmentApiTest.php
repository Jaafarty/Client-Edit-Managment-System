<?php

namespace Tests\Feature;

use App\Client;
use App\Models\Admin;
use App\Models\Attachment;
use App\Models\Company;
use App\Models\Edit;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AttachmentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_upload_list_view_download_and_delete_attachments(): void
    {
        $disk = Storage::fake('local');
        $this->signIn();
        $edit = $this->createEdit();
        $details = '/api/edits/'.$edit->getKey();
        $url = $details.'/attachments';

        $this->post($url, [
            'files' => [
                UploadedFile::fake()->createWithContent('brief.txt', 'Client brief'),
                UploadedFile::fake()->createWithContent('notes.txt', 'Project notes'),
            ],
            'description' => 'Project files',
        ], ['Accept' => 'application/json'])->assertCreated()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.original_name', 'brief.txt')
            ->assertJsonPath('data.1.original_name', 'notes.txt')
            ->assertJsonPath('data.0.description', 'Project files')
            ->assertJsonMissingPath('data.0.path');

        $attachment = $edit->attachments()->oldest('id')->firstOrFail();
        $disk->assertExists($attachment->path);
        $this->getJson($url)->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonMissingPath('data.0.path');
        $this->getJson($details)->assertOk()->assertJsonCount(2, 'data.attachments')
            ->assertJsonMissingPath('data.attachments.0.path');

        $attachmentUrl = $url.'/'.$attachment->getKey();
        $this->getJson($attachmentUrl)->assertOk()
            ->assertJsonPath('data.edit_id', $edit->getKey())
            ->assertJsonMissingPath('data.path');
        $preview = $this->get($attachmentUrl.'/preview')->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=utf-8')
            ->assertHeader('Content-Disposition', 'inline; filename=brief.txt');
        $this->assertSame('Client brief', $preview->streamedContent());
        $download = $this->get($attachmentUrl.'/download')->assertOk()->assertDownload('brief.txt')
            ->assertHeader('Content-Type', 'application/octet-stream')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame('Client brief', $download->streamedContent());

        $this->deleteJson($attachmentUrl)->assertNoContent();
        $this->assertModelMissing($attachment);
        $disk->assertMissing($attachment->path);
        $disk->assertDirectoryEmpty('deleted-attachments');
        $this->getJson($attachmentUrl.'/download')->assertNotFound();
        $this->getJson($details)->assertOk()->assertJsonCount(1, 'data.attachments')
            ->assertJsonPath('data.attachments.0.original_name', 'notes.txt');
    }

    public function test_attachment_routes_require_authentication_and_scope_the_attachment_to_its_edit(): void
    {
        Storage::fake('local');
        $edit = $this->createEdit();
        $url = '/api/edits/'.$edit->getKey().'/attachments';
        $this->getJson($url)->assertUnauthorized();
        $this->postJson($url)->assertUnauthorized();

        $this->signIn();
        $attachmentId = $this->post($url, [
            'files' => [UploadedFile::fake()->create('single.pdf', 1)],
        ], ['Accept' => 'application/json'])->assertCreated()
            ->assertJsonCount(1, 'data')
            ->json('data.0.id');

        $otherEdit = $this->createEdit();
        $wrongUrl = "/api/edits/{$otherEdit->id}/attachments/{$attachmentId}";
        $this->getJson($wrongUrl)->assertNotFound();
        $this->getJson($wrongUrl.'/preview')->assertNotFound();
        $this->getJson($wrongUrl.'/download')->assertNotFound();
        $this->deleteJson($wrongUrl)->assertNotFound();
        $this->assertDatabaseHas('attachments', ['id' => $attachmentId, 'edit_id' => $edit->getKey()]);
    }

    public function test_active_file_types_can_be_downloaded_but_not_previewed(): void
    {
        Storage::fake('local');
        $this->signIn();
        $edit = $this->createEdit();
        $url = '/api/edits/'.$edit->getKey().'/attachments';
        $attachmentId = $this->post($url, [
            'files' => [UploadedFile::fake()->createWithContent('document.html', '<html><script>alert(1)</script></html>')],
        ], ['Accept' => 'application/json'])->assertCreated()->json('data.0.id');

        $this->getJson($url.'/'.$attachmentId.'/preview')->assertStatus(415);
        $this->get($url.'/'.$attachmentId.'/download')->assertDownload('document.html')
            ->assertHeader('Content-Type', 'application/octet-stream');
    }

    public function test_an_invalid_file_rejects_the_entire_upload(): void
    {
        $disk = Storage::fake('local');
        $this->signIn();
        $edit = $this->createEdit();
        $url = '/api/edits/'.$edit->getKey().'/attachments';

        $this->postJson($url)->assertUnprocessable()->assertJsonValidationErrors('files');
        $this->post($url, [
            'files' => [
                UploadedFile::fake()->create('brief.txt', 1),
                UploadedFile::fake()->create('large.zip', 2049),
            ],
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('files.1');
        $this->assertDatabaseCount('attachments', 0);
        $disk->assertDirectoryEmpty('/');
    }

    public function test_a_failed_batch_write_removes_files_and_rolls_back_records(): void
    {
        $disk = Storage::fake('local');
        $this->signIn();
        $edit = $this->createEdit();
        Event::listen('eloquent.creating: '.Attachment::class, function (Attachment $attachment): void {
            if ($attachment->original_name === 'reject.txt') {
                throw ValidationException::withMessages(['files.1' => 'The attachment could not be saved.']);
            }
        });

        $this->post('/api/edits/'.$edit->getKey().'/attachments', [
            'files' => [
                UploadedFile::fake()->create('brief.txt', 1),
                UploadedFile::fake()->create('reject.txt', 1),
            ],
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('files.1');
        $this->assertDatabaseCount('attachments', 0);
        $disk->assertDirectoryEmpty('/');
    }

    public function test_a_failed_record_deletion_restores_the_attachment_file(): void
    {
        $disk = Storage::fake('local');
        $this->signIn();
        $edit = $this->createEdit();
        $attachment = $edit->attachments()->create([
            'path' => 'edit-attachments/brief.txt',
            'original_name' => 'brief.txt',
        ]);
        $disk->put($attachment->path, 'Client brief');
        Event::listen('eloquent.deleting: '.Attachment::class, function (): void {
            throw ValidationException::withMessages(['attachment' => 'The attachment could not be deleted.']);
        });

        $url = '/api/edits/'.$edit->getKey().'/attachments/'.$attachment->getKey();
        $this->deleteJson($url)->assertUnprocessable();
        $this->assertModelExists($attachment);
        $disk->assertExists($attachment->path);
        $disk->assertDirectoryEmpty('deleted-attachments');
    }

    private function createEdit(): Edit
    {
        $client = Client::create(['name' => 'Taylor Reed', 'phone_number' => '01234567']);
        $company = Company::create(['name' => 'Studio North', 'client_id' => $client->getKey()]);
        $project = Project::create(['name' => 'Website', 'company_id' => $company->getKey()]);

        return Edit::create([
            'project_id' => $project->getKey(),
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
