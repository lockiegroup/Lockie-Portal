<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\UnleashedProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function searchCustomers(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));
        if (strlen($q) < 2) return response()->json([]);

        $customers = DB::table('sales_lines')
            ->select('customer_code', DB::raw('MIN(customer) as customer_name'))
            ->where('warehouse', 'JW Products')
            ->where(function ($query) use ($q) {
                $query->where('customer', 'like', "%{$q}%")
                      ->orWhere('customer_code', 'like', "%{$q}%");
            })
            ->whereNotNull('customer')
            ->groupBy('customer_code')
            ->orderByRaw('MIN(customer)')
            ->limit(10)
            ->get();

        return response()->json($customers);
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $q           = trim($request->input('q', ''));
        $customerCode = trim($request->input('customer_code', ''));
        if (strlen($q) < 1) return response()->json([]);

        $products = UnleashedProduct::where(function ($query) use ($q) {
                $query->where('product_code', 'like', "%{$q}%")
                      ->orWhere('product_name', 'like', "%{$q}%");
            })
            ->orderBy('product_code')
            ->limit(12)
            ->get(['product_code', 'product_name']);

        // Attach last unit price from sales history
        $codes = $products->pluck('product_code')->all();

        // If we have a customer, prefer their most recent price; fall back to overall average
        $priceQuery = DB::table('sales_lines')
            ->select('product_code', DB::raw('SUM(sub_total) / NULLIF(SUM(quantity), 0) as avg_price'))
            ->whereIn('product_code', $codes)
            ->where('warehouse', 'JW Products')
            ->where('quantity', '>', 0)
            ->where('sub_total', '>', 0)
            ->groupBy('product_code');

        if ($customerCode) {
            $customerPrices = (clone $priceQuery)
                ->where('customer_code', $customerCode)
                ->pluck('avg_price', 'product_code');
        }

        $allPrices = $priceQuery->pluck('avg_price', 'product_code');

        $results = $products->map(function ($p) use ($allPrices, $customerPrices) {
            $price = isset($customerPrices) ? ($customerPrices[$p->product_code] ?? $allPrices[$p->product_code] ?? null)
                                            : ($allPrices[$p->product_code] ?? null);
            return [
                'product_code' => $p->product_code,
                'product_name' => $p->product_name,
                'suggested_price' => $price ? round((float) $price, 2) : null,
            ];
        });

        return response()->json($results);
    }
}
