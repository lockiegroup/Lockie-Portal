<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyPolicy;
use App\Models\PolicyCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PolicyController extends Controller
{
    public function index()
    {
        $policies   = CompanyPolicy::orderBy('sort_order')->orderBy('title')->get();
        $categories = PolicyCategory::orderBy('sort_order')->orderBy('name')->get();

        return view('admin.policies.index', compact('policies', 'categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'category'         => ['nullable', 'string', 'max:100'],
            'description'      => ['nullable', 'string', 'max:1000'],
            'last_reviewed_at' => ['nullable', 'date'],
            'file'             => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $file     = $request->file('file');
        $stored   = $file->store('policies');
        $fileName = $file->getClientOriginalName();

        CompanyPolicy::create([
            'title'            => $data['title'],
            'category'         => $data['category'] ?: null,
            'description'      => $data['description'] ?: null,
            'last_reviewed_at' => $data['last_reviewed_at'] ?: null,
            'file_name'        => $fileName,
            'file_path'        => $stored,
            'sort_order'       => CompanyPolicy::max('sort_order') + 1,
        ]);

        \App\Models\ActivityLog::record('policy.upload', "Uploaded policy: {$data['title']}");

        return back()->with('success', 'Policy uploaded successfully.');
    }

    public function update(Request $request, CompanyPolicy $policy)
    {
        $data = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'category'         => ['nullable', 'string', 'max:100'],
            'description'      => ['nullable', 'string', 'max:1000'],
            'last_reviewed_at' => ['nullable', 'date'],
            'file'             => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $updates = [
            'title'            => $data['title'],
            'category'         => $data['category'] ?: null,
            'description'      => $data['description'] ?: null,
            'last_reviewed_at' => $data['last_reviewed_at'] ?: null,
        ];

        if ($request->hasFile('file')) {
            Storage::delete($policy->file_path);
            $file                 = $request->file('file');
            $updates['file_path'] = $file->store('policies');
            $updates['file_name'] = $file->getClientOriginalName();
        }

        $policy->update($updates);

        \App\Models\ActivityLog::record('policy.update', "Updated policy: {$policy->title}");

        return back()->with('success', 'Policy updated.');
    }

    public function destroy(CompanyPolicy $policy)
    {
        \App\Models\ActivityLog::record('policy.delete', "Deleted policy: {$policy->title}");

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

    public function storeCategory(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:policy_categories,name']]);
        $cat  = PolicyCategory::create([
            'name'       => $data['name'],
            'sort_order' => PolicyCategory::max('sort_order') + 1,
        ]);

        return response()->json(['success' => true, 'id' => $cat->id, 'name' => $cat->name]);
    }

    public function destroyCategory(PolicyCategory $category): JsonResponse
    {
        $category->delete();
        return response()->json(['success' => true]);
    }
}
