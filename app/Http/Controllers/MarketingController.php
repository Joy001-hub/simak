<?php

namespace App\Http\Controllers;

use App\Http\Requests\MarketerRequest;
use App\Models\Marketer;

class MarketingController extends Controller
{
    public function index()
    {
        $teams = Marketer::all();
        return view('marketing.index', compact('teams'));
    }

    public function create()
    {
        return view('marketing.create');
    }

    public function store(MarketerRequest $request)
    {
        Marketer::create($request->validated());
        return redirect()->route('marketing.index')->with('success', 'Salesman ditambahkan');
    }

    public function destroy(Marketer $marketing)
    {
        $marketing->delete();
        return redirect()->route('marketing.index')->with('success', 'Salesman dihapus');
    }

    public function edit(Marketer $marketing)
    {
        return view('marketing.edit', ['marketer' => $marketing]);
    }

    public function update(MarketerRequest $request, Marketer $marketing)
    {
        $marketing->update($request->validated());
        return redirect()->route('marketing.index')->with('success', 'Salesman diperbarui');
    }
}
