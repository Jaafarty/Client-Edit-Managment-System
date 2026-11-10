<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Edit;
use finfo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AttachmentController
{
    public function index(Edit $edit): Response
    {
        return Inertia::render('attachments', [
            'edit' => $edit->only(['id', 'title']),
            'attachments' => $edit->attachments()->latest('id')->get(['id', 'original_name', 'description']),
            'uploadUrl' => route('edit.attachments.store', $edit),
        ]);
    }

    public function store(Request $request, Edit $edit): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:2048'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        /** @var UploadedFile $file */
        $file = $validated['file'];

        $this->saveUploads($edit, [$file], $validated['description'] ?? null, 'file');

        return redirect()->route('edit.attachments.index', $edit);
    }

    public function listForEdit(Edit $edit): JsonResponse
    {
        return response()->json(['data' => $edit->attachments()->latest('id')->get()]);
    }

    public function upload(Request $request, Edit $edit): JsonResponse
    {
        $validated = $request->validate([
            'files' => ['required', 'array', 'list', 'min:1', 'max:10'],
            'files.*' => ['required', 'file', 'max:2048'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        return response()->json([
            'data' => $this->saveUploads($edit, $validated['files'], $validated['description'] ?? null, 'files'),
        ], 201);
    }

    public function show(Edit $edit, Attachment $attachment): JsonResponse
    {
        return response()->json(['data' => $attachment]);
    }

    public function download(Edit $edit, Attachment $attachment): StreamedResponse
    {
        return $this->fileResponse($attachment);
    }

    public function preview(Edit $edit, Attachment $attachment): StreamedResponse
    {
        return $this->fileResponse($attachment, preview: true);
    }

    private function fileResponse(Attachment $attachment, bool $preview = false): StreamedResponse
    {
        $stream = Storage::disk('local')->readStream($attachment->path);
        abort_unless(is_resource($stream), 404);

        $contentType = 'application/octet-stream';
        if ($preview) {
            $contentType = (new finfo(FILEINFO_MIME_TYPE))->buffer(fread($stream, 8192) ?: '');
            if (! in_array($contentType, ['application/pdf', 'image/gif', 'image/jpeg', 'image/png', 'image/webp', 'text/plain'], true)) {
                fclose($stream);
                abort(415, 'This file type is available as a download.');
            }
            rewind($stream);
        }

        $name = str_replace(['/', '\\'], '_', $attachment->original_name);
        $name = trim(preg_replace('/[\x00-\x1f\x7f]/', '_', $name) ?? '');
        if (in_array($name, ['', '.', '..'], true)) {
            $name = 'attachment-'.$attachment->getKey();
        }

        return response()->streamDownload(static function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, $name, [
            'Content-Type' => $contentType,
            'Content-Security-Policy' => "sandbox; default-src 'none'",
            'X-Content-Type-Options' => 'nosniff',
        ], $preview ? 'inline' : 'attachment');
    }

    public function destroy(Edit $edit, Attachment $attachment): HttpResponse
    {
        $disk = Storage::disk('local');
        $stagedPath = null;

        try {
            DB::transaction(function () use ($edit, $attachment, $disk, &$stagedPath): void {
                $locked = $edit->attachments()->whereKey($attachment->getKey())->lockForUpdate()->firstOrFail();

                if ($disk->fileExists($locked->path)) {
                    $target = 'deleted-attachments/'.Str::uuid();
                    abort_unless($disk->move($locked->path, $target), 500, 'The attachment could not be removed from storage.');
                    $stagedPath = $target;
                }

                if (! $locked->delete()) {
                    throw new RuntimeException('The attachment record could not be deleted.');
                }
            });
        } catch (Throwable $error) {
            if ($stagedPath !== null && ! $disk->move($stagedPath, $attachment->path)) {
                report(new RuntimeException('Could not restore the attachment file after a failed deletion.'));
            }

            throw $error;
        }

        if ($stagedPath !== null && ! $disk->delete($stagedPath)) {
            report(new RuntimeException('Could not remove a deleted attachment file from private storage.'));
        }

        return response()->noContent();
    }

    /**
     * @param  list<UploadedFile>  $files
     * @return list<Attachment>
     */
    private function saveUploads(Edit $edit, array $files, ?string $description, string $field): array
    {
        $paths = [];

        try {
            return DB::transaction(function () use ($edit, $files, $description, $field, &$paths): array {
                $attachments = [];

                foreach ($files as $index => $file) {
                    $path = $file->store('edit-attachments', 'local');
                    if ($path === false) {
                        throw ValidationException::withMessages([
                            $field === 'files' ? "files.{$index}" : $field => 'The file could not be saved. Try again.',
                        ]);
                    }

                    $paths[] = $path;
                    $attachments[] = $edit->attachments()->create([
                        'path' => $path,
                        'original_name' => Str::substr($file->getClientOriginalName(), 0, 255),
                        'description' => $description,
                    ]);
                }

                return $attachments;
            });
        } catch (Throwable $error) {
            if ($paths !== [] && ! Storage::disk('local')->delete($paths)) {
                report(new RuntimeException('Could not clean up files after a failed attachment upload.'));
            }

            throw $error;
        }
    }
}
