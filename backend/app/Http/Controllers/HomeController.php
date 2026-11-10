<?php

namespace App\Http\Controllers;

use App\Models\Edit;
use Inertia\Inertia;
use Inertia\Response;

class HomeController
{
    public function index(): Response
    {
        return Inertia::render('home', [
            'name' => config('app.name'),
            'edits' => Edit::query()->latest('id')->limit(10)->get(['id', 'title']),
        ]);
    }
}
