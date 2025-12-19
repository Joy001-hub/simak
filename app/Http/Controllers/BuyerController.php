<?php

namespace App\Http\Controllers;

use App\Http\Requests\BuyerRequest;
use App\Models\Buyer;

class BuyerController extends Controller
{
    public function index()
    {
        $buyers = Buyer::all();
        return view('buyers.index', compact('buyers'));
    }

    public function create(\Illuminate\Http\Request $request)
    {
        $fromSale = $request->query('from_sale');
        return view('buyers.create', compact('fromSale'));
    }

    public function store(BuyerRequest $request)
    {
        $buyer = Buyer::create($request->validated());
        // Increment dashboard updates counter
        session(['dashboard_updates' => session('dashboard_updates', 0) + 1]);

        if ($request->has('from_sale')) {
            return redirect()->route('penjualan.create', [
                'buyer_id' => $buyer->id,
                'from_sale' => $request->input('from_sale')
            ])->with('success', 'Pembeli berhasil dibuat. Silakan lanjut buat transaksi baru.');
        }
        return redirect()->route('buyers.index')->with('success', 'Buyer ditambahkan');
    }

    public function destroy(Buyer $buyer)
    {
        $buyer->delete();
        // Increment dashboard updates counter
        session(['dashboard_updates' => session('dashboard_updates', 0) + 1]);
        return redirect()->route('buyers.index')->with('success', 'Buyer dihapus');
    }

    public function edit(Buyer $buyer)
    {
        return view('buyers.edit', ['buyer' => $buyer]);
    }

    public function update(BuyerRequest $request, Buyer $buyer)
    {
        $buyer->update($request->validated());
        // Increment dashboard updates counter
        session(['dashboard_updates' => session('dashboard_updates', 0) + 1]);
        return redirect()->route('buyers.index')->with('success', 'Buyer diperbarui');
    }
}