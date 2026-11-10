<?php

namespace App\Http\Controllers;

use App\Models\Edit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
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
        $path = $file->store('edit-attachments', 'local');

        if ($path === false) {
            throw ValidationException::withMessages([
                'file' => 'The file could not be saved. Try again.',
            ]);
        }

        try {
            $edit->attachments()->create([
                'path' => $path,
                'original_name' => Str::substr($file->getClientOriginalName(), 0, 255),
                'description' => $validated['description'] ?? null,
            ]);
        } catch (Throwable $error) {
            Storage::disk('local')->delete($path);

            throw $error;
        }

        return redirect()->route('edit.attachments.index', $edit);
    }
}
