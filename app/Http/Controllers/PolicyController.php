<?php

namespace App\Http\Controllers;

use App\Models\CompanyPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PolicyController extends Controller
{
    public function index()
    {
        $policies = CompanyPolicy::orderBy('sort_order')->orderBy('title')->get();
        $grouped  = $policies->groupBy(fn($p) => $p->category ?: 'General');

        return view('policies.index', compact('grouped'));
    }

    public function download(CompanyPolicy $policy): StreamedResponse
    {
        abort_unless(Storage::exists($policy->file_path), 404);

        return Storage::download($policy->file_path, $policy->file_name);
    }
}
