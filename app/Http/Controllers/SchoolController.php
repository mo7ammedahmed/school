<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    public function index()
    {
        return redirect()->route('dashboard');
    }

    public function create()
    {
        return redirect()->route('dashboard');
    }

    public function store(Request $request)
    {
        return redirect()->route('dashboard');
    }

    public function show(string $id)
    {
        return redirect()->route('dashboard');
    }

    public function edit(string $id)
    {
        return redirect()->route('dashboard');
    }

    public function update(Request $request, string $id)
    {
        return redirect()->route('dashboard');
    }

    public function destroy(string $id)
    {
        return redirect()->route('dashboard');
    }
}
