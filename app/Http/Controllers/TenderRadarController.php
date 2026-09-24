<?php

namespace App\Http\Controllers;

use App\Models\HistoricalContract;
use App\Models\Tender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenderRadarController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->input('tab', 'new');

        $statsNew      = Tender::where('status', 'new')->whereIn('ai_relevance', ['high', 'relevant'])->count();
        $statsHigh     = Tender::where('status', 'new')->where('ai_relevance', 'high')->count();
        $statsClosing  = Tender::whereNotIn('status', ['ignored', 'lost'])
            ->whereIn('ai_relevance', ['high', 'relevant'])
            ->whereBetween('deadline_at', [now(), now()->addDays(14)])->count();
        $statsExpiring = HistoricalContract::where('status', 'monitoring')
            ->whereNotNull('contract_end')
            ->whereBetween('contract_end', [now(), now()->addDays(90)])->count();

        $query = match ($tab) {
            'high'     => Tender::where('status', 'new')->where('ai_relevance', 'high')
                            ->orderByDesc('ai_score'),
            'closing'  => Tender::whereNotIn('status', ['ignored', 'lost'])
                            ->whereIn('ai_relevance', ['high', 'relevant'])
                            ->whereBetween('deadline_at', [now(), now()->addDays(14)])
                            ->orderBy('deadline_at'),
            'reviewed' => Tender::whereIn('status', ['reviewed', 'shortlisted', 'applied', 'won', 'lost'])
                            ->orderByDesc('updated_at'),
            'ignored'  => Tender::where('status', 'ignored')->orderByDesc('updated_at'),
            'possible' => Tender::where('status', 'new')->where('ai_relevance', 'possible')
                            ->orderByDesc('ai_score'),
            default    => Tender::where('status', 'new')->whereIn('ai_relevance', ['high', 'relevant'])
                            ->orderByDesc('ai_score'),
        };

        $tenders = $query->paginate(25)->withQueryString();

        $expiringContracts = HistoricalContract::where('status', 'monitoring')
            ->whereNotNull('contract_end')
            ->whereBetween('contract_end', [now(), now()->addDays(180)])
            ->orderBy('contract_end')
            ->get();

        return view('tender-radar.index', compact(
            'tenders', 'tab',
            'statsNew', 'statsHigh', 'statsClosing', 'statsExpiring',
            'expiringContracts'
        ));
    }

    public function show(Tender $tender): View
    {
        return view('tender-radar.show', compact('tender'));
    }

    public function updateStatus(Request $request, Tender $tender): RedirectResponse
    {
        $request->validate(['status' => 'required|in:new,reviewed,shortlisted,applied,won,lost,ignored']);

        $tender->update([
            'status' => $request->status,
            'notes'  => $request->input('notes', $tender->notes),
        ]);

        return back()->with('success', 'Tender status updated.');
    }
}
