<?php

namespace App\Http\Controllers;

use App\Models\HistoricalContract;
use App\Models\Tender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoricalContractController extends Controller
{
    public function index(): View
    {
        $contracts = HistoricalContract::with('relatedTender')
            ->orderByRaw("CASE status WHEN 'monitoring' THEN 0 WHEN 'replacement_found' THEN 1 ELSE 2 END")
            ->orderBy('contract_end')
            ->paginate(30);

        return view('tender-radar.historical.index', compact('contracts'));
    }

    public function create(): View
    {
        $tenders = Tender::whereIn('ai_relevance', ['high', 'relevant'])
            ->orderByDesc('published_at')
            ->limit(50)
            ->get();

        return view('tender-radar.historical.form', compact('tenders'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'buyer'              => 'required|string|max:255',
            'product_category'   => 'required|string|max:255',
            'description'        => 'nullable|string',
            'incumbent_supplier' => 'nullable|string|max:255',
            'value_estimate'     => 'nullable|integer|min:0',
            'contract_start'     => 'nullable|date',
            'contract_end'       => 'nullable|date',
            'extension_options'  => 'nullable|string',
            'status'             => 'required|in:monitoring,replacement_found,closed',
            'notes'              => 'nullable|string',
            'related_tender_id'  => 'nullable|exists:tenders,id',
        ]);

        HistoricalContract::create($data);

        return redirect()->route('tender-radar.historical.index')
            ->with('success', 'Historical contract saved.');
    }

    public function edit(HistoricalContract $contract): View
    {
        $tenders = Tender::whereIn('ai_relevance', ['high', 'relevant'])
            ->orderByDesc('published_at')
            ->limit(50)
            ->get();

        return view('tender-radar.historical.form', compact('contract', 'tenders'));
    }

    public function update(Request $request, HistoricalContract $contract): RedirectResponse
    {
        $data = $request->validate([
            'buyer'              => 'required|string|max:255',
            'product_category'   => 'required|string|max:255',
            'description'        => 'nullable|string',
            'incumbent_supplier' => 'nullable|string|max:255',
            'value_estimate'     => 'nullable|integer|min:0',
            'contract_start'     => 'nullable|date',
            'contract_end'       => 'nullable|date',
            'extension_options'  => 'nullable|string',
            'status'             => 'required|in:monitoring,replacement_found,closed',
            'notes'              => 'nullable|string',
            'related_tender_id'  => 'nullable|exists:tenders,id',
        ]);

        $contract->update($data);

        return redirect()->route('tender-radar.historical.index')
            ->with('success', 'Contract updated.');
    }

    public function destroy(HistoricalContract $contract): RedirectResponse
    {
        $contract->delete();
        return redirect()->route('tender-radar.historical.index')
            ->with('success', 'Contract removed.');
    }
}
