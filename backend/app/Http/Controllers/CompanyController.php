<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CompanyController
{
    public function index(): JsonResponse
    {
        $companies = Company::query()->orderBy('name')->orderBy('id')->get();

        return response()->json(['data' => $companies]);
    }

    public function store(Request $request): JsonResponse
    {
        $company = Company::create($request->validate($this->rules()));

        return response()->json(['data' => $company], 201);
    }

    public function show(Company $company): JsonResponse
    {
        return response()->json(['data' => $company->load('client:id,name', 'projects')]);
    }

    public function update(Request $request, Company $company): JsonResponse
    {
        $company->update($request->validate($this->rules(partial: true)));

        return response()->json(['data' => $company]);
    }

    public function destroy(Company $company): Response|JsonResponse
    {
        try {
            $company->deleteOrFail();
        } catch (QueryException $error) {
            if (($error->errorInfo[0] ?? null) !== '23000') {
                throw $error;
            }

            return response()->json(['message' => 'A company with linked projects cannot be deleted.'], 409);
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
            'client_id' => [...$required, 'integer', 'min:1', 'exists:clients,id'],
        ];
    }
}
