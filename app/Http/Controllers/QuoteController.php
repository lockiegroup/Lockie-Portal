<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\QuoteLine;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    public function index()
    {
        $quotes = Quote::with('createdBy')
            ->orderByDesc('created_at')
            ->paginate(30);

        return view('quotes.index', compact('quotes'));
    }

    public function create()
    {
        $nextNumber = Quote::nextNumber();
        return view('quotes.create', compact('nextNumber'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'reference'     => 'nullable|string|max:255',
            'notes'         => 'nullable|string|max:2000',
            'lines'         => 'required|array|min:1',
            'lines.*.description' => 'required|string|max:500',
            'lines.*.quantity'    => 'required|numeric|min:0.01',
            'lines.*.unit_price'  => 'required|numeric|min:0',
        ]);

        $lines = collect($request->lines)->map(function ($line, $i) {
            return [
                'description' => $line['description'],
                'quantity'    => (float) $line['quantity'],
                'unit_price'  => (float) $line['unit_price'],
                'line_total'  => round((float) $line['quantity'] * (float) $line['unit_price'], 2),
                'sort_order'  => $i,
            ];
        });

        $total = $lines->sum('line_total');

        $quote = Quote::create([
            'quote_number'  => Quote::nextNumber(),
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
            'reference'     => $request->reference,
            'notes'         => $request->notes,
            'total_value'   => $total,
            'created_by'    => auth()->id(),
        ]);

        $quote->lines()->createMany($lines->all());

        return redirect()->route('quotes.show', $quote)->with('success', 'Quote created.');
    }

    public function show(Quote $quote)
    {
        $quote->load('lines', 'createdBy');
        return view('quotes.show', compact('quote'));
    }

    public function print(Quote $quote)
    {
        $quote->load('lines', 'createdBy');
        return view('quotes.print', compact('quote'));
    }

    public function destroy(Quote $quote)
    {
        $quote->delete();
        return redirect()->route('quotes.index')->with('success', 'Quote deleted.');
    }
}
