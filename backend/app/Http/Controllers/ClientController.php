<?php

namespace App\Http\Controllers;

use App\Client;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ClientController
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $search = $validated['search'] ?? '';
        $clients = Client::query()->orderBy('name')->orderBy('id');

        if ($search !== '') {
            $pattern = '%'.strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']).'%';

            $clients->where(fn ($query) => $query
                ->whereRaw("name LIKE ? ESCAPE '!'", [$pattern])
                ->orWhereRaw("email LIKE ? ESCAPE '!'", [$pattern])
                ->orWhereRaw("phone_number LIKE ? ESCAPE '!'", [$pattern]));
        }

        return response()->json(['data' => $clients->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $client = Client::create($request->validate($this->rules()));

        return response()->json(['data' => $client], 201);
    }

    public function show(Client $client): JsonResponse
    {
        return response()->json(['data' => $client->load('companies')]);
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $client->update($request->validate($this->rules(partial: true)));

        return response()->json(['data' => $client]);
    }

    public function destroy(Client $client): Response|JsonResponse
    {
        try {
            $client->deleteOrFail();
        } catch (QueryException $error) {
            if (($error->errorInfo[0] ?? null) !== '23000') {
                throw $error;
            }

            return response()->json(['message' => 'A client with linked companies cannot be deleted.'], 409);
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
            'phone_number' => [...$required, 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'last_follow_up_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:1000-01-01'],
        ];
    }
}
