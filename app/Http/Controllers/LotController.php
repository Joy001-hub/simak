<?php

namespace App\Http\Controllers;

use App\Http\Requests\LotRequest;
use App\Models\Lot;
use App\Models\Project;
use Illuminate\Http\Request;

class LotController extends Controller
{
    public function index()
    {
        $lots = Lot::with('project')->get();
        return view('kavling.index', ['lots' => $lots]);
    }

    public function create()
    {
        $projects = Project::all();
        return view('kavling.create', compact('projects'));
    }

    public function store(LotRequest $request)
    {
        if ($request->input('mode') === 'bulk') {
            $prefix = $request->input('bulk_prefix');
            $start = (int) $request->input('bulk_start');
            $end = (int) $request->input('bulk_end');
            $suffix = $request->input('bulk_suffix');
            $projectId = $request->input('project_id');

            $created = 0;
            $skipped = 0;

            $commonData = $request->validated();
            // Remove bulk keys from data to be inserted
            unset($commonData['bulk_prefix'], $commonData['bulk_start'], $commonData['bulk_end'], $commonData['bulk_suffix']);

            for ($i = $start; $i <= $end; $i++) {
                // Format: "A-1", "A-2 B"
                $blockNumber = trim($prefix . '-' . $i . ($suffix ? ' ' . $suffix : ''));

                // Check duplicate within the same project
                $exists = Lot::where('project_id', $projectId)
                    ->where('block_number', $blockNumber)
                    ->exists();

                if (!$exists) {
                    $lotData = array_merge($commonData, ['block_number' => $blockNumber]);
                    Lot::create($lotData);
                    $created++;
                } else {
                    $skipped++;
                }
            }

            // Increment dashboard updates counter by number of created lots
            session(['dashboard_updates' => session('dashboard_updates', 0) + $created]);

            $msg = "{$created} kavling berhasil ditambahkan.";
            if ($skipped > 0) {
                $msg .= " {$skipped} kavling dilewatkan karena duplikat.";
            }

            return redirect()->route('kavling.index')->with('success', $msg);
        }

        // Single Mode
        Lot::create($request->validated());
        session(['dashboard_updates' => session('dashboard_updates', 0) + 1]);
        return redirect()->route('kavling.index')->with('success', 'Kavling ditambahkan');
    }

    public function destroy(Lot $kavling, Request $request)
    {
        $kavling->delete();
        // Increment dashboard updates counter
        session(['dashboard_updates' => session('dashboard_updates', 0) + 1]);
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Kavling dihapus']);
        }
        return redirect()->route('kavling.index')->with('success', 'Kavling dihapus');
    }

    public function edit(Lot $kavling)
    {
        $projects = Project::all();
        return view('kavling.edit', ['lot' => $kavling, 'projects' => $projects]);
    }

    public function update(LotRequest $request, Lot $kavling)
    {
        $kavling->update($request->validated());
        // Increment dashboard updates counter
        session(['dashboard_updates' => session('dashboard_updates', 0) + 1]);
        return redirect()->route('kavling.index')->with('success', 'Kavling diperbarui');
    }

    public function pricing(Lot $lot)
    {
        $basePrice = (int) ($lot->base_price ?? 0);
        $dpPercent = 20;
        $dpNominal = $basePrice > 0 ? (int) round($basePrice * ($dpPercent / 100)) : 0;

        return response()->json([
            'id' => $lot->id,
            'project' => optional($lot->project)->name,
            'block_number' => $lot->block_number,
            'area' => $lot->area,
            'base_price' => $basePrice,
            'status' => $lot->status,
            'is_sold' => (bool) $lot->sale()->exists(),
            'payment_defaults' => [
                'dp_percent' => $dpPercent,
                'dp_nominal' => $dpNominal,
                'tenor_months' => 12,
                'due_day' => 10,
            ],
        ]);
    }
}
