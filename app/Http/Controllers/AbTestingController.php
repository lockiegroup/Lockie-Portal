<?php

namespace App\Http\Controllers;

use App\Models\AbTest;
use App\Models\MarketingDivision;
use App\Models\MarketingRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AbTestingController extends Controller
{
    public function index(): View
    {
        $divisions = MarketingDivision::orderBy('sort_order')
            ->with(['tests.user', 'rules'])
            ->get();

        return view('ab-testing.index', [
            'divisions' => $divisions,
            'testTypes' => AbTest::TEST_TYPES,
            'canEdit'   => auth()->user()->hasPermission('ab_test') || auth()->user()->isMaster(),
        ]);
    }

    // ── A/B Tests ──────────────────────────────────────────────────────────────

    public function storeTest(Request $request): JsonResponse
    {
        abort_unless($this->canEdit(), 403);

        $data = $request->validate([
            'marketing_division_id' => ['required', 'exists:marketing_divisions,id'],
            'campaign_name'         => ['required', 'string', 'max:200'],
            'sent_at'               => ['required', 'date'],
            'test_type'             => ['nullable', 'in:' . implode(',', array_keys(AbTest::TEST_TYPES))],
            'variant_a'             => ['nullable', 'string', 'max:500'],
            'variant_a_result'      => ['nullable', 'numeric', 'min:0', 'max:100'],
            'variant_a_ctr'         => ['nullable', 'numeric', 'min:0', 'max:100'],
            'variant_b'             => ['nullable', 'string', 'max:500'],
            'variant_b_result'      => ['nullable', 'numeric', 'min:0', 'max:100'],
            'variant_b_ctr'         => ['nullable', 'numeric', 'min:0', 'max:100'],
            'winner'                => ['nullable', 'in:a,b,inconclusive'],
            'notes'                 => ['nullable', 'string', 'max:2000'],
            'revenue'               => ['nullable', 'numeric', 'min:0'],
        ]);

        $test = AbTest::create(array_merge($data, ['user_id' => auth()->id()]));
        $test->load('user');

        return response()->json(['success' => true, 'test' => $this->formatTest($test)]);
    }

    public function updateTest(Request $request, AbTest $test): JsonResponse
    {
        abort_unless($this->canEdit(), 403);

        $data = $request->validate([
            'campaign_name'    => ['required', 'string', 'max:200'],
            'sent_at'          => ['required', 'date'],
            'test_type'        => ['nullable', 'in:' . implode(',', array_keys(AbTest::TEST_TYPES))],
            'variant_a'        => ['nullable', 'string', 'max:500'],
            'variant_a_result' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'variant_a_ctr'    => ['nullable', 'numeric', 'min:0', 'max:100'],
            'variant_b'        => ['nullable', 'string', 'max:500'],
            'variant_b_result' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'variant_b_ctr'    => ['nullable', 'numeric', 'min:0', 'max:100'],
            'winner'           => ['nullable', 'in:a,b,inconclusive'],
            'notes'            => ['nullable', 'string', 'max:2000'],
            'revenue'          => ['nullable', 'numeric', 'min:0'],
        ]);

        $test->update($data);
        $test->load('user');

        return response()->json(['success' => true, 'test' => $this->formatTest($test)]);
    }

    public function destroyTest(AbTest $test): JsonResponse
    {
        abort_unless($this->canEdit(), 403);
        $test->delete();
        return response()->json(['success' => true]);
    }

    // ── Rules ──────────────────────────────────────────────────────────────────

    public function storeRule(Request $request): JsonResponse
    {
        abort_unless($this->canEdit(), 403);

        $data = $request->validate([
            'marketing_division_id' => ['required', 'exists:marketing_divisions,id'],
            'body'                  => ['required', 'string', 'max:1000'],
        ]);

        $maxOrder = MarketingRule::where('marketing_division_id', $data['marketing_division_id'])->max('sort_order') ?? -1;

        $rule = MarketingRule::create([
            'marketing_division_id' => $data['marketing_division_id'],
            'body'                  => $data['body'],
            'sort_order'            => $maxOrder + 1,
            'user_id'               => auth()->id(),
        ]);

        return response()->json(['success' => true, 'rule' => ['id' => $rule->id, 'body' => $rule->body, 'sort_order' => $rule->sort_order]]);
    }

    public function updateRule(Request $request, MarketingRule $rule): JsonResponse
    {
        abort_unless($this->canEdit(), 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        $rule->update(['body' => $data['body']]);

        return response()->json(['success' => true, 'body' => $rule->body]);
    }

    public function destroyRule(MarketingRule $rule): JsonResponse
    {
        abort_unless($this->canEdit(), 403);
        $rule->delete();
        return response()->json(['success' => true]);
    }

    public function reorderRules(Request $request): JsonResponse
    {
        abort_unless($this->canEdit(), 403);

        $request->validate(['order' => ['required', 'array'], 'order.*' => ['integer']]);

        foreach ($request->input('order') as $position => $id) {
            MarketingRule::where('id', $id)->update(['sort_order' => $position]);
        }

        return response()->json(['success' => true]);
    }

    // ── Divisions ──────────────────────────────────────────────────────────────

    public function storeDivision(Request $request): JsonResponse
    {
        abort_unless($this->canEdit(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:marketing_divisions,name'],
        ]);

        $maxOrder = MarketingDivision::max('sort_order') ?? -1;
        $division = MarketingDivision::create(['name' => $data['name'], 'sort_order' => $maxOrder + 1]);

        return response()->json(['success' => true, 'division' => ['id' => $division->id, 'name' => $division->name]]);
    }

    public function destroyDivision(MarketingDivision $division): JsonResponse
    {
        abort_unless($this->canEdit(), 403);
        abort_if($division->tests()->exists() || $division->rules()->exists(), 422, 'Remove all tests and rules before deleting a division.');
        $division->delete();
        return response()->json(['success' => true]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function canEdit(): bool
    {
        $user = auth()->user();
        return $user->isMaster() || $user->hasPermission('ab_test');
    }

    private function formatTest(AbTest $test): array
    {
        return [
            'id'                    => $test->id,
            'campaign_name'         => $test->campaign_name,
            'sent_at'               => $test->sent_at->format('d M Y'),
            'sent_at_input'         => $test->sent_at->format('Y-m-d'),
            'test_type'             => $test->test_type_label,
            'variant_a'             => $test->variant_a,
            'variant_a_result'      => $test->variant_a_result,
            'variant_a_ctr'         => $test->variant_a_ctr,
            'variant_b'             => $test->variant_b,
            'variant_b_result'      => $test->variant_b_result,
            'variant_b_ctr'         => $test->variant_b_ctr,
            'winner'                => $test->winner,
            'notes'                 => $test->notes,
            'revenue'               => $test->revenue,
            'logged_by'             => $test->user?->name,
        ];
    }
}
