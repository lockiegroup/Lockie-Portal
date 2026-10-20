<x-layout title="Production Planner Settings">

<style>
.ps-wrap { padding: 24px; max-width: 1200px; }
.ps-wrap h1 { font-size:1.25rem; font-weight:700; color:#1e293b; margin:0 0 20px; }

.ps-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
@media(max-width:900px){ .ps-grid { grid-template-columns:1fr; } }

.ps-card {
    background:white; border-radius:10px; box-shadow:0 1px 3px rgba(0,0,0,0.08);
    overflow:hidden;
}
.ps-card-head {
    padding:14px 18px; background:#f8fafc; border-bottom:1px solid #e2e8f0;
    font-size:0.9375rem; font-weight:700; color:#1e293b;
}

.ps-table { width:100%; border-collapse:collapse; font-size:0.8125rem; }
.ps-table th { padding:8px 12px; text-align:left; background:#f1f5f9; font-weight:700; color:#475569; border-bottom:1px solid #e2e8f0; }
.ps-table td { padding:8px 12px; border-bottom:1px solid #f8fafc; color:#1e293b; vertical-align:middle; }
.ps-table tr:last-child td { border-bottom:none; }
.ps-table tr:hover td { background:#f8fafc; }

.ps-badge {
    display:inline-block; width:18px; height:18px; border-radius:50%;
    vertical-align:middle;
}

.ps-form { padding:14px 18px; background:#f8fafc; border-top:1px solid #e2e8f0; }
.ps-form h4 { font-size:0.8125rem; font-weight:700; color:#475569; margin:0 0 10px; text-transform:uppercase; letter-spacing:.04em; }
.ps-form-row { display:flex; gap:8px; flex-wrap:wrap; align-items:flex-end; }
.ps-form-group { display:flex; flex-direction:column; gap:4px; }
.ps-form-group label { font-size:0.75rem; font-weight:600; color:#64748b; }
.ps-input {
    padding:6px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.8125rem;
    outline:none; background:white; transition:border-color .15s;
}
.ps-input:focus { border-color:#6366f1; }
.ps-btn {
    padding:7px 16px; border-radius:6px; border:none; cursor:pointer;
    font-size:0.8125rem; font-weight:600; transition:background .15s;
}
.ps-btn-primary { background:#1e293b; color:white; }
.ps-btn-primary:hover { background:#334155; }
.ps-btn-danger  { background:#fee2e2; color:#991b1b; }
.ps-btn-danger:hover  { background:#fecaca; }
.ps-btn-edit    { background:#e0f2fe; color:#0369a1; }
.ps-btn-edit:hover    { background:#bae6fd; }
.ps-btn-sm { padding:4px 10px; font-size:0.75rem; }

.ps-active-yes { color:#166534; font-weight:700; }
.ps-active-no  { color:#9ca3af; }

.ps-hue-preview { width:28px; height:28px; border-radius:50%; border:2px solid #e2e8f0; flex-shrink:0; }

.ps-flash { padding:10px 16px; border-radius:7px; margin-bottom:16px; font-size:0.8125rem; font-weight:600; }
.ps-flash-success { background:#dcfce7; color:#166534; border:1px solid #bbf7d0; }
.ps-flash-error   { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }

.ps-inline-form { display:none; }
</style>

<div class="ps-wrap">
    <h1>Production Planner Settings</h1>

    @if(session('success'))
        <div class="ps-flash ps-flash-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="ps-flash ps-flash-error">{{ $errors->first() }}</div>
    @endif

    <div class="ps-grid">

        {{-- OPERATORS --}}
        <div class="ps-card">
            <div class="ps-card-head">Operators</div>

            @php
                $schedDays = [
                    'mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri',
                ];
            @endphp

            <div class="overflow-x-auto">
            <table class="ps-table min-w-[700px]">
                <thead>
                    <tr>
                        <th>Name</th>
                        @foreach($schedDays as $dk => $dl)
                            <th class="text-center min-w-[90px]">{{ $dl }}<br><span class="text-[.68rem] text-slate-400 font-medium">AM / PM</span></th>
                        @endforeach
                        <th class="text-center">Total/wk</th>
                        <th>Active</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($operators as $op)
                    @php
                        $sched = $op->schedule ?? [];
                        $weeklyTotal = collect($schedDays)->sum(fn($dl, $dk) => ($sched[$dk]['am'] ?? 4) + ($sched[$dk]['pm'] ?? 4));
                    @endphp
                    <tr>
                        <td class="font-semibold">{{ $op->name }}</td>
                        @foreach($schedDays as $dk => $dl)
                            <td class="text-center text-[.82rem] font-mono">
                                {{ $sched[$dk]['am'] ?? 4 }}h / {{ $sched[$dk]['pm'] ?? 4 }}h
                            </td>
                        @endforeach
                        <td class="text-center font-bold text-slate-900">{{ $weeklyTotal }}h</td>
                        <td>
                            @if($op->is_active)
                                <span class="ps-active-yes">Yes</span>
                            @else
                                <span class="ps-active-no">No</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap">
                            <button class="ps-btn ps-btn-edit ps-btn-sm"
                                onclick="psToggleEdit('op-{{ $op->id }}')">Edit</button>
                            <form method="POST" action="{{ route('admin.production.operators.destroy', $op) }}"
                                class="inline"
                                onsubmit="return confirm('Delete {{ addslashes($op->name) }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="ps-btn ps-btn-danger ps-btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                    {{-- Inline edit row --}}
                    <tr id="op-{{ $op->id }}" class="ps-inline-form">
                        <td colspan="{{ count($schedDays) + 4 }}" class="p-0">
                            <form method="POST" action="{{ route('admin.production.operators.update', $op) }}"
                                class="px-4 py-3.5 bg-sky-50 border-b border-slate-200">
                                @csrf @method('PUT')
                                <div class="ps-form-row flex-wrap gap-x-5 gap-y-2.5">
                                    <div class="ps-form-group">
                                        <label>Name</label>
                                        <input name="name" class="ps-input w-[150px]" value="{{ old('name', $op->name) }}" required>
                                    </div>

                                    {{-- Per-day schedule grid --}}
                                    <div class="flex gap-2.5 flex-wrap items-start">
                                        @foreach($schedDays as $dk => $dl)
                                            <div class="ps-form-group min-w-[72px]">
                                                <label class="text-center block">{{ $dl }}</label>
                                                <div class="flex gap-1 items-center">
                                                    <input name="schedule_{{ $dk }}_am" type="number" class="ps-input w-[44px] px-1 py-[5px] text-center"
                                                        step="0.5" min="0" max="12"
                                                        value="{{ old("schedule_{$dk}_am", $sched[$dk]['am'] ?? 4) }}"
                                                        title="{{ $dl }} AM hours">
                                                    <span class="text-slate-400 text-[.7rem]">/</span>
                                                    <input name="schedule_{{ $dk }}_pm" type="number" class="ps-input w-[44px] px-1 py-[5px] text-center"
                                                        step="0.5" min="0" max="12"
                                                        value="{{ old("schedule_{$dk}_pm", $sched[$dk]['pm'] ?? 4) }}"
                                                        title="{{ $dl }} PM hours">
                                                </div>
                                                <div class="text-[.62rem] text-slate-400 text-center mt-px">AM / PM</div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="ps-form-group">
                                        <label>Active</label>
                                        <select name="is_active" class="ps-input w-[75px]">
                                            <option value="1" {{ $op->is_active ? 'selected' : '' }}>Yes</option>
                                            <option value="0" {{ !$op->is_active ? 'selected' : '' }}>No</option>
                                        </select>
                                    </div>
                                    <div class="flex gap-1.5 items-end pb-0.5">
                                        <div class="ps-form-group">
                                            <label>Total/wk</label>
                                            <span id="total-{{ $op->id }}" class="text-[.9rem] font-bold text-slate-900 px-2 py-[5px] inline-block">{{ $weeklyTotal }}h</span>
                                        </div>
                                        <button type="submit" class="ps-btn ps-btn-primary ps-btn-sm self-end">Save</button>
                                        <button type="button" class="ps-btn ps-btn-sm self-end bg-slate-100 text-slate-600"
                                            onclick="psToggleEdit('op-{{ $op->id }}')">Cancel</button>
                                    </div>
                                </div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($schedDays) + 4 }}" class="px-4 py-4 text-center text-slate-400">No operators yet.</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>{{-- /overflow-x:auto --}}

            <div class="ps-form">
                <h4>Add Operator</h4>
                <form method="POST" action="{{ route('admin.production.operators.store') }}">
                    @csrf
                    <div class="ps-form-row flex-wrap gap-x-5 gap-y-2.5">
                        <div class="ps-form-group">
                            <label>Name</label>
                            <input name="name" class="ps-input w-[150px]" placeholder="Full name" required value="{{ old('name') }}">
                        </div>

                        <div class="flex gap-2.5 flex-wrap items-start">
                            @foreach($schedDays as $dk => $dl)
                                <div class="ps-form-group min-w-[72px]">
                                    <label class="text-center block">{{ $dl }}</label>
                                    <div class="flex gap-1 items-center">
                                        <input name="schedule_{{ $dk }}_am" type="number" class="ps-input w-[44px] px-1 py-[5px] text-center"
                                            step="0.5" min="0" max="12"
                                            value="{{ old("schedule_{$dk}_am", 4) }}"
                                            title="{{ $dl }} AM hours">
                                        <span class="text-slate-400 text-[.7rem]">/</span>
                                        <input name="schedule_{{ $dk }}_pm" type="number" class="ps-input w-[44px] px-1 py-[5px] text-center"
                                            step="0.5" min="0" max="12"
                                            value="{{ old("schedule_{$dk}_pm", 4) }}"
                                            title="{{ $dl }} PM hours">
                                    </div>
                                    <div class="text-[.62rem] text-slate-400 text-center mt-px">AM / PM</div>
                                </div>
                            @endforeach
                        </div>

                        <div class="self-end pb-0.5">
                            <button type="submit" class="ps-btn ps-btn-primary">Add</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- MACHINES --}}
        @php $divHues = $divisions->pluck('hue','name')->toArray(); @endphp
        <div class="ps-card">
            <div class="ps-card-head">Machines</div>

            <table class="ps-table">
                <thead>
                    <tr>
                        <th>Key</th>
                        <th>Name</th>
                        <th>Division</th>
                        <th>Active</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($machines as $machine)
                    @php $mh = $divHues[$machine->division] ?? 220; @endphp
                    <tr>
                        <td><code class="text-[0.72rem] bg-slate-100 px-1.5 py-0.5 rounded">{{ $machine->key }}</code></td>
                        <td>{{ $machine->name }}</td>
                        <td>
                            <span class="ps-badge" style="background:hsl({{ $mh }},65%,70%);"></span>
                            {{ $machine->division }}
                        </td>
                        <td>
                            @if($machine->is_active)
                                <span class="ps-active-yes">Yes</span>
                            @else
                                <span class="ps-active-no">No</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap">
                            <button class="ps-btn ps-btn-edit ps-btn-sm"
                                onclick="psToggleEdit('mc-{{ $machine->id }}')">Edit</button>
                            <form method="POST" action="{{ route('admin.production.machines.destroy', $machine) }}"
                                class="inline"
                                onsubmit="return confirm('Deactivate {{ addslashes($machine->name) }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="ps-btn ps-btn-danger ps-btn-sm">Deactivate</button>
                            </form>
                        </td>
                    </tr>
                    {{-- Inline edit row --}}
                    <tr id="mc-{{ $machine->id }}" class="ps-inline-form">
                        <td colspan="5" class="p-0">
                            <form method="POST" action="{{ route('admin.production.machines.update', $machine) }}"
                                class="px-4 py-3 bg-sky-50 border-b border-slate-200">
                                @csrf @method('PUT')
                                <input type="hidden" name="hue" value="{{ $mh }}">
                                <div class="ps-form-row">
                                    <div class="ps-form-group">
                                        <label>Key (slug)</label>
                                        <input name="key" class="ps-input w-[120px]" value="{{ old('key', $machine->key) }}" required>
                                    </div>
                                    <div class="ps-form-group">
                                        <label>Name</label>
                                        <input name="name" class="ps-input w-[140px]" value="{{ old('name', $machine->name) }}" required>
                                    </div>
                                    <div class="ps-form-group">
                                        <label>Division</label>
                                        <select name="division" class="ps-input w-[160px]">
                                            @foreach($divisions as $div)
                                                <option value="{{ $div->name }}" {{ $machine->division===$div->name?'selected':'' }}>{{ $div->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="ps-form-group">
                                        <label>Active</label>
                                        <select name="is_active" class="ps-input w-[80px]">
                                            <option value="1" {{ $machine->is_active ? 'selected' : '' }}>Yes</option>
                                            <option value="0" {{ !$machine->is_active ? 'selected' : '' }}>No</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="ps-btn ps-btn-primary ps-btn-sm self-end">Save</button>
                                    <button type="button" class="ps-btn ps-btn-sm self-end bg-slate-100 text-slate-600"
                                        onclick="psToggleEdit('mc-{{ $machine->id }}')">Cancel</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-4 text-center text-slate-400">No machines yet.</td></tr>
                @endforelse
                </tbody>
            </table>

            <div class="ps-form">
                <h4>Add Machine</h4>
                <form method="POST" action="{{ route('admin.production.machines.store') }}" id="add-machine-form">
                    @csrf
                    <div class="ps-form-row">
                        <div class="ps-form-group">
                            <label>Key (slug)</label>
                            <input name="key" class="ps-input w-[110px]" placeholder="e.g. mypress" required value="{{ old('key') }}">
                        </div>
                        <div class="ps-form-group">
                            <label>Name</label>
                            <input name="name" class="ps-input w-[140px]" placeholder="Display name" required value="{{ old('name') }}">
                        </div>
                        <div class="ps-form-group">
                            <label>Division</label>
                            <select name="division" class="ps-input w-[160px]" id="add-machine-div">
                                @foreach($divisions as $div)
                                    <option value="{{ $div->name }}" data-hue="{{ $div->hue }}" {{ old('division')===$div->name?'selected':'' }}>{{ $div->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <input type="hidden" name="hue" id="add-machine-hue" value="22">
                        <button type="submit" class="ps-btn ps-btn-primary self-end">Add</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    {{-- DIVISIONS --}}
    <div class="ps-card mt-5">
        <div class="ps-card-head">Divisions &amp; Colours</div>
        <table class="ps-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Colour</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($divisions as $division)
                <tr>
                    <td class="font-semibold">{{ $division->name }}</td>
                    <td>
                        <span class="ps-badge" style="background:hsl({{ $division->hue }},65%,70%);"></span>
                        <span class="text-[0.72rem] text-slate-500 ml-1">hue {{ $division->hue }}°</span>
                    </td>
                    <td class="whitespace-nowrap">
                        <button class="ps-btn ps-btn-edit ps-btn-sm"
                            onclick="psToggleEdit('div-{{ $division->id }}')">Edit</button>
                        <form method="POST" action="{{ route('admin.production.divisions.destroy', $division) }}"
                            class="inline"
                            onsubmit="return confirm('Delete {{ addslashes($division->name) }}? This will fail if machines are assigned to it.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="ps-btn ps-btn-danger ps-btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
                <tr id="div-{{ $division->id }}" class="ps-inline-form">
                    <td colspan="3" class="p-0">
                        <form method="POST" action="{{ route('admin.production.divisions.update', $division) }}"
                            class="px-4 py-3 bg-sky-50 border-b border-slate-200">
                            @csrf @method('PUT')
                            <div class="ps-form-row items-end">
                                <div class="ps-form-group">
                                    <label>Name</label>
                                    <input name="name" class="ps-input w-[180px]" value="{{ old('name', $division->name) }}" required>
                                </div>
                                <div class="ps-form-group">
                                    <label>Hue (0–359)</label>
                                    <div class="flex items-center gap-2">
                                        <input name="hue" type="range" min="0" max="359"
                                            value="{{ old('hue', $division->hue) }}"
                                            class="ps-input w-[120px] px-0.5 py-1"
                                            oninput="psDivHuePreview(this,'div-prev-{{ $division->id }}')">
                                        <span id="div-prev-{{ $division->id }}" class="ps-badge w-7 h-7"
                                            style="background:hsl({{ $division->hue }},65%,70%);"></span>
                                        <span id="div-hue-val-{{ $division->id }}" class="text-[0.75rem] text-slate-500 min-w-[28px]">{{ $division->hue }}°</span>
                                    </div>
                                </div>
                                <button type="submit" class="ps-btn ps-btn-primary ps-btn-sm">Save</button>
                                <button type="button" class="ps-btn ps-btn-sm bg-slate-100 text-slate-600"
                                    onclick="psToggleEdit('div-{{ $division->id }}')">Cancel</button>
                            </div>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="px-4 py-4 text-center text-slate-400">No divisions yet.</td></tr>
            @endforelse
            </tbody>
        </table>

        <div class="ps-form">
            <h4>Add Division</h4>
            <form method="POST" action="{{ route('admin.production.divisions.store') }}">
                @csrf
                <div class="ps-form-row items-end">
                    <div class="ps-form-group">
                        <label>Name</label>
                        <input name="name" class="ps-input w-[180px]" placeholder="Division name" required value="{{ old('name') }}">
                    </div>
                    <div class="ps-form-group">
                        <label>Hue (0–359)</label>
                        <div class="flex items-center gap-2">
                            <input name="hue" type="range" min="0" max="359"
                                value="{{ old('hue', 180) }}"
                                class="ps-input w-[120px] px-0.5 py-1"
                                oninput="psDivHuePreview(this,'add-div-prev')">
                            <span id="add-div-prev" class="ps-badge w-7 h-7" style="background:hsl(180,65%,70%);"></span>
                            <span id="add-div-hue-val" class="text-[0.75rem] text-slate-500 min-w-[28px]">180°</span>
                        </div>
                    </div>
                    <button type="submit" class="ps-btn ps-btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function psToggleEdit(id) {
    var row = document.getElementById(id);
    if (!row) return;
    row.style.display = (row.style.display === 'table-row') ? 'none' : 'table-row';
}
// Auto-set hidden hue from division for the Add Machine form
var addDiv = document.getElementById('add-machine-div');
var addHue = document.getElementById('add-machine-hue');
if (addDiv && addHue) {
    addDiv.addEventListener('change', function() {
        var opt = this.options[this.selectedIndex];
        addHue.value = opt.dataset.hue || 220;
    });
    // Set initial value
    if (addDiv.options[addDiv.selectedIndex]) {
        addHue.value = addDiv.options[addDiv.selectedIndex].dataset.hue || 220;
    }
}
// Division hue slider preview
function psDivHuePreview(input, previewId) {
    var preview = document.getElementById(previewId);
    if (preview) preview.style.background = 'hsl(' + input.value + ',65%,70%)';
    // Update numeric label (sibling span after the preview)
    var valSpan = preview ? preview.nextElementSibling : null;
    if (valSpan) valSpan.textContent = input.value + '°';
}
function psUpdateTotal(form, totalId) {
    var inputs = form.querySelectorAll('input[type=number][name^=schedule_]');
    var sum = 0;
    inputs.forEach(function(inp) { sum += parseFloat(inp.value) || 0; });
    var el = document.getElementById(totalId);
    if (el) el.textContent = sum + 'h';
}
// Attach live total listeners to all operator schedule inputs
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[type=number][name^=schedule_]').forEach(function(inp) {
        inp.addEventListener('input', function() {
            var form = this.closest('form');
            if (!form) return;
            // Find the total span in this form (id starts with "total-")
            var totalSpan = form.querySelector('[id^=total-]');
            if (totalSpan) psUpdateTotal(form, totalSpan.id);
        });
    });
});
</script>

</x-layout>
