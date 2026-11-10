<?php

namespace App\Http\Controllers;

use App\Models\Edit;
use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class EditController
{
    public function page(): InertiaResponse
    {
        return Inertia::render('edits', [
            'edits' => $this->listEdits(),
            'projects' => Project::query()->orderBy('name')->orderBy('id')->get(['id', 'name']),
            'priorities' => Edit::PRIORITIES,
            'statuses' => Edit::STATUSES,
        ]);
    }

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->listEdits()]);
    }

    public function store(Request $request): JsonResponse
    {
        $edit = Edit::create($request->validate($this->rules()));

        return response()->json(['data' => $edit->load('project:id,name')], 201);
    }

    public function show(Edit $edit): JsonResponse
    {
        return response()->json([
            'data' => $edit->load(['project:id,name', 'attachments' => fn ($attachments) => $attachments->latest('id')]),
        ]);
    }

    public function update(Request $request, Edit $edit): JsonResponse
    {
        $edit->update($request->validate($this->rules(partial: true)));

        return response()->json(['data' => $edit->refresh()->load('project:id,name')]);
    }

    public function destroy(Edit $edit): Response|JsonResponse
    {
        try {
            $edit->deleteOrFail();
        } catch (QueryException $error) {
            if (($error->errorInfo[0] ?? null) !== '23000') {
                throw $error;
            }

            return response()->json(['message' => 'An edit with linked attachments cannot be deleted.'], 409);
        }

        return response()->noContent();
    }

    /**
     * @return Collection<int, Edit>
     */
    private function listEdits(): Collection
    {
        return Edit::query()->with('project:id,name')->latest('id')->get();
    }

    /**
     * @return array<string, list<string>>
     */
    private function rules(bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return [
            'project_id' => [...$required, 'integer', 'min:1', 'exists:projects,id'],
            'title' => [...$required, 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:16000'],
            'priority' => [...$required, 'in:'.implode(',', Edit::PRIORITIES)],
            'status' => [...$required, 'in:'.implode(',', Edit::STATUSES)],
            'price' => ['nullable', 'decimal:0,2', 'numeric', 'between:-99999999.99,99999999.99'],
            'is_paid' => ['sometimes', 'required', 'boolean'],
            'due_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:1000-01-01'],
        ];
    }
}
