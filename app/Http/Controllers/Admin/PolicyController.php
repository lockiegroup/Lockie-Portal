<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PolicyController extends Controller
{
    public function index()
    {
        $policies = CompanyPolicy::orderBy('sort_order')->orderBy('title')->get();
        $categories = $policies->pluck('category')->filter()->unique()->sort()->values();

        return view('admin.policies.index', compact('policies', 'categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'category'    => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'file'        => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $file     = $request->file('file');
        $stored   = $file->store('policies');
        $fileName = $file->getClientOriginalName();

        CompanyPolicy::create([
            'title'       => $data['title'],
            'category'    => $data['category'] ?: null,
            'description' => $data['description'] ?: null,
            'file_name'   => $fileName,
            'file_path'   => $stored,
            'sort_order'  => CompanyPolicy::max('sort_order') + 1,
        ]);

        return back()->with('success', 'Policy uploaded successfully.');
    }

    public function update(Request $request, CompanyPolicy $policy)
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'category'    => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'file'        => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $updates = [
            'title'       => $data['title'],
            'category'    => $data['category'] ?: null,
            'description' => $data['description'] ?: null,
        ];

        if ($request->hasFile('file')) {
            Storage::delete($policy->file_path);
            $file             = $request->file('file');
            $updates['file_path'] = $file->store('policies');
            $updates['file_name'] = $file->getClientOriginalName();
        }

        $policy->update($updates);

        return back()->with('success', 'Policy updated.');
    }

    public function destroy(CompanyPolicy $policy)
    {
        Storage::delete($policy->file_path);
        $policy->delete();

        return back()->with('success', 'Policy deleted.');
    }

    public function reorder(Request $request)
    {
        $request->validate(['order' => ['required', 'array'], 'order.*' => ['integer']]);

        foreach ($request->input('order') as $position => $id) {
            CompanyPolicy::where('id', $id)->update(['sort_order' => $position]);
        }

        return response()->json(['success' => true]);
    }
}
