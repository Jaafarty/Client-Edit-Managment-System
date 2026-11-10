<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProjectController
{
    public function index(): JsonResponse
    {
        $projects = Project::query()->orderBy('name')->orderBy('id')->get();

        return response()->json(['data' => $projects]);
    }

    public function store(Request $request): JsonResponse
    {
        $project = Project::create($request->validate($this->rules()));

        return response()->json(['data' => $project], 201);
    }

    public function show(Project $project): JsonResponse
    {
        return response()->json(['data' => $project->load('company:id,name', 'edits')]);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $project->update($request->validate($this->rules(partial: true)));

        return response()->json(['data' => $project]);
    }

    public function destroy(Project $project): Response|JsonResponse
    {
        try {
            $project->deleteOrFail();
        } catch (QueryException $error) {
            if (($error->errorInfo[0] ?? null) !== '23000') {
                throw $error;
            }

            return response()->json(['message' => 'A project with linked edits cannot be deleted.'], 409);
        }

        return response()->noContent();
    }

    /**
     * @return array<string, list<string>>
     */
    private function rules(bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return [
            'name' => [...$required, 'string', 'max:255'],
            'company_id' => [...$required, 'integer', 'min:1', 'exists:companies,id'],
        ];
    }
}
