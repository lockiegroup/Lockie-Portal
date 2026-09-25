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

            <div style="overflow-x:auto;">
            <table class="ps-table" style="min-width:700px;">
                <thead>
                    <tr>
                        <th>Name</th>
                        @foreach($schedDays as $dk => $dl)
                            <th style="text-align:center;min-width:90px;">{{ $dl }}<br><span style="font-size:.68rem;color:#94a3b8;font-weight:500;">AM / PM</span></th>
                        @endforeach
                        <th style="text-align:center;">Total/wk</th>
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
                        <td style="font-weight:600;">{{ $op->name }}</td>
                        @foreach($schedDays as $dk => $dl)
                            <td style="text-align:center;font-size:.82rem;font-family:monospace;">
                                {{ $sched[$dk]['am'] ?? 4 }}h / {{ $sched[$dk]['pm'] ?? 4 }}h
                            </td>
                        @endforeach
                        <td style="text-align:center;font-weight:700;color:#1e293b;">{{ $weeklyTotal }}h</td>
                        <td>
                            @if($op->is_active)
                                <span class="ps-active-yes">Yes</span>
                            @else
                                <span class="ps-active-no">No</span>
                            @endif
                        </td>
                        <td style="white-space:nowrap;">
                            <button class="ps-btn ps-btn-edit ps-btn-sm"
                                onclick="psToggleEdit('op-{{ $op->id }}')">Edit</button>
                            <form method="POST" action="{{ route('admin.production.operators.destroy', $op) }}"
                                style="display:inline;"
                                onsubmit="return confirm('Delete {{ addslashes($op->name) }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="ps-btn ps-btn-danger ps-btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                    {{-- Inline edit row --}}
                    <tr id="op-{{ $op->id }}" class="ps-inline-form">
                        <td colspan="{{ count($schedDays) + 4 }}" style="padding:0;">
                            <form method="POST" action="{{ route('admin.production.operators.update', $op) }}"
                                style="padding:14px 16px;background:#f0f9ff;border-bottom:1px solid #e2e8f0;">
                                @csrf @method('PUT')
                                <div class="ps-form-row" style="flex-wrap:wrap;gap:10px 20px;">
                                    <div class="ps-form-group">
                                        <label>Name</label>
                                        <input name="name" class="ps-input" value="{{ old('name', $op->name) }}" required style="width:150px;">
                                    </div>

                                    {{-- Per-day schedule grid --}}
                                    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-start;">
                                        @foreach($schedDays as $dk => $dl)
                                            <div class="ps-form-group" style="min-width:72px;">
                                                <label style="text-align:center;display:block;">{{ $dl }}</label>
                                                <div style="display:flex;gap:3px;align-items:center;">
                                                    <input name="schedule_{{ $dk }}_am" type="number" class="ps-input"
                                                        step="0.5" min="0" max="12"
                                                        value="{{ old("schedule_{$dk}_am", $sched[$dk]['am'] ?? 4) }}"
                                                        style="width:44px;padding:5px 4px;text-align:center;"
                                                        title="{{ $dl }} AM hours">
                                                    <span style="color:#94a3b8;font-size:.7rem;">/</span>
                                                    <input name="schedule_{{ $dk }}_pm" type="number" class="ps-input"
                                                        step="0.5" min="0" max="12"
                                                        value="{{ old("schedule_{$dk}_pm", $sched[$dk]['pm'] ?? 4) }}"
                                                        style="width:44px;padding:5px 4px;text-align:center;"
                                                        title="{{ $dl }} PM hours">
                                                </div>
                                                <div style="font-size:.62rem;color:#94a3b8;text-align:center;margin-top:1px;">AM / PM</div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="ps-form-group">
                                        <label>Active</label>
                                        <select name="is_active" class="ps-input" style="width:75px;">
                                            <option value="1" {{ $op->is_active ? 'selected' : '' }}>Yes</option>
                                            <option value="0" {{ !$op->is_active ? 'selected' : '' }}>No</option>
                                        </select>
                                    </div>
                                    <div style="display:flex;gap:6px;align-items:flex-end;padding-bottom:2px;">
                                        <div class="ps-form-group">
                                            <label>Total/wk</label>
                                            <span id="total-{{ $op->id }}" style="font-size:.9rem;font-weight:700;color:#1e293b;padding:5px 8px;display:inline-block;">{{ $weeklyTotal }}h</span>
                                        </div>
                                        <button type="submit" class="ps-btn ps-btn-primary ps-btn-sm" style="align-self:flex-end;">Save</button>
                                        <button type="button" class="ps-btn ps-btn-sm" style="align-self:flex-end;background:#f1f5f9;color:#475569;"
                                            onclick="psToggleEdit('op-{{ $op->id }}')">Cancel</button>
                                    </div>
                                </div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($schedDays) + 4 }}" style="padding:16px;text-align:center;color:#94a3b8;">No operators yet.</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>{{-- /overflow-x:auto --}}

            <div class="ps-form">
                <h4>Add Operator</h4>
                <form method="POST" action="{{ route('admin.production.operators.store') }}">
                    @csrf
                    <div class="ps-form-row" style="flex-wrap:wrap;gap:10px 20px;">
                        <div class="ps-form-group">
                            <label>Name</label>
                            <input name="name" class="ps-input" placeholder="Full name" required style="width:150px;" value="{{ old('name') }}">
                        </div>

                        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-start;">
                            @foreach($schedDays as $dk => $dl)
                                <div class="ps-form-group" style="min-width:72px;">
                                    <label style="text-align:center;display:block;">{{ $dl }}</label>
                                    <div style="display:flex;gap:3px;align-items:center;">
                                        <input name="schedule_{{ $dk }}_am" type="number" class="ps-input"
                                            step="0.5" min="0" max="12"
                                            value="{{ old("schedule_{$dk}_am", 4) }}"
                                            style="width:44px;padding:5px 4px;text-align:center;"
                                            title="{{ $dl }} AM hours">
                                        <span style="color:#94a3b8;font-size:.7rem;">/</span>
                                        <input name="schedule_{{ $dk }}_pm" type="number" class="ps-input"
                                            step="0.5" min="0" max="12"
                                            value="{{ old("schedule_{$dk}_pm", 4) }}"
                                            style="width:44px;padding:5px 4px;text-align:center;"
                                            title="{{ $dl }} PM hours">
                                    </div>
                                    <div style="font-size:.62rem;color:#94a3b8;text-align:center;margin-top:1px;">AM / PM</div>
                                </div>
                            @endforeach
                        </div>

                        <div style="align-self:flex-end;padding-bottom:2px;">
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
                        <td><code style="font-size:0.72rem;background:#f1f5f9;padding:2px 5px;border-radius:3px;">{{ $machine->key }}</code></td>
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
                        <td style="white-space:nowrap;">
                            <button class="ps-btn ps-btn-edit ps-btn-sm"
                                onclick="psToggleEdit('mc-{{ $machine->id }}')">Edit</button>
                            <form method="POST" action="{{ route('admin.production.machines.destroy', $machine) }}"
                                style="display:inline;"
                                onsubmit="return confirm('Deactivate {{ addslashes($machine->name) }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="ps-btn ps-btn-danger ps-btn-sm">Deactivate</button>
                            </form>
                        </td>
                    </tr>
                    {{-- Inline edit row --}}
                    <tr id="mc-{{ $machine->id }}" class="ps-inline-form">
                        <td colspan="5" style="padding:0;">
                            <form method="POST" action="{{ route('admin.production.machines.update', $machine) }}"
                                style="padding:12px 16px;background:#f0f9ff;border-bottom:1px solid #e2e8f0;">
                                @csrf @method('PUT')
                                <input type="hidden" name="hue" value="{{ $mh }}">
                                <div class="ps-form-row">
                                    <div class="ps-form-group">
                                        <label>Key (slug)</label>
                                        <input name="key" class="ps-input" value="{{ old('key', $machine->key) }}" required style="width:120px;">
                                    </div>
                                    <div class="ps-form-group">
                                        <label>Name</label>
                                        <input name="name" class="ps-input" value="{{ old('name', $machine->name) }}" required style="width:140px;">
                                    </div>
                                    <div class="ps-form-group">
                                        <label>Division</label>
                                        <select name="division" class="ps-input" style="width:160px;">
                                            @foreach($divisions as $div)
                                                <option value="{{ $div->name }}" {{ $machine->division===$div->name?'selected':'' }}>{{ $div->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="ps-form-group">
                                        <label>Active</label>
                                        <select name="is_active" class="ps-input" style="width:80px;">
                                            <option value="1" {{ $machine->is_active ? 'selected' : '' }}>Yes</option>
                                            <option value="0" {{ !$machine->is_active ? 'selected' : '' }}>No</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="ps-btn ps-btn-primary ps-btn-sm" style="align-self:flex-end;">Save</button>
                                    <button type="button" class="ps-btn ps-btn-sm" style="align-self:flex-end;background:#f1f5f9;color:#475569;"
                                        onclick="psToggleEdit('mc-{{ $machine->id }}')">Cancel</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="padding:16px;text-align:center;color:#94a3b8;">No machines yet.</td></tr>
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
                            <input name="key" class="ps-input" placeholder="e.g. mypress" required style="width:110px;" value="{{ old('key') }}">
                        </div>
                        <div class="ps-form-group">
                            <label>Name</label>
                            <input name="name" class="ps-input" placeholder="Display name" required style="width:140px;" value="{{ old('name') }}">
                        </div>
                        <div class="ps-form-group">
                            <label>Division</label>
                            <select name="division" class="ps-input" style="width:160px;" id="add-machine-div">
                                @foreach($divisions as $div)
                                    <option value="{{ $div->name }}" data-hue="{{ $div->hue }}" {{ old('division')===$div->name?'selected':'' }}>{{ $div->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <input type="hidden" name="hue" id="add-machine-hue" value="22">
                        <button type="submit" class="ps-btn ps-btn-primary" style="align-self:flex-end;">Add</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    {{-- DIVISIONS --}}
    <div class="ps-card" style="margin-top:20px;">
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
                    <td style="font-weight:600;">{{ $division->name }}</td>
                    <td>
                        <span class="ps-badge" style="background:hsl({{ $division->hue }},65%,70%);"></span>
                        <span style="font-size:0.72rem;color:#64748b;margin-left:4px;">hue {{ $division->hue }}°</span>
                    </td>
                    <td style="white-space:nowrap;">
                        <button class="ps-btn ps-btn-edit ps-btn-sm"
                            onclick="psToggleEdit('div-{{ $division->id }}')">Edit</button>
                        <form method="POST" action="{{ route('admin.production.divisions.destroy', $division) }}"
                            style="display:inline;"
                            onsubmit="return confirm('Delete {{ addslashes($division->name) }}? This will fail if machines are assigned to it.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="ps-btn ps-btn-danger ps-btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
                <tr id="div-{{ $division->id }}" class="ps-inline-form">
                    <td colspan="3" style="padding:0;">
                        <form method="POST" action="{{ route('admin.production.divisions.update', $division) }}"
                            style="padding:12px 16px;background:#f0f9ff;border-bottom:1px solid #e2e8f0;">
                            @csrf @method('PUT')
                            <div class="ps-form-row" style="align-items:flex-end;">
                                <div class="ps-form-group">
                                    <label>Name</label>
                                    <input name="name" class="ps-input" value="{{ old('name', $division->name) }}" required style="width:180px;">
                                </div>
                                <div class="ps-form-group">
                                    <label>Hue (0–359)</label>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <input name="hue" type="range" min="0" max="359"
                                            value="{{ old('hue', $division->hue) }}"
                                            class="ps-input" style="width:120px;padding:4px 2px;"
                                            oninput="psDivHuePreview(this,'div-prev-{{ $division->id }}')">
                                        <span id="div-prev-{{ $division->id }}" class="ps-badge"
                                            style="background:hsl({{ $division->hue }},65%,70%);width:28px;height:28px;"></span>
                                        <span id="div-hue-val-{{ $division->id }}" style="font-size:0.75rem;color:#64748b;min-width:28px;">{{ $division->hue }}°</span>
                                    </div>
                                </div>
                                <button type="submit" class="ps-btn ps-btn-primary ps-btn-sm">Save</button>
                                <button type="button" class="ps-btn ps-btn-sm" style="background:#f1f5f9;color:#475569;"
                                    onclick="psToggleEdit('div-{{ $division->id }}')">Cancel</button>
                            </div>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" style="padding:16px;text-align:center;color:#94a3b8;">No divisions yet.</td></tr>
            @endforelse
            </tbody>
        </table>

        <div class="ps-form">
            <h4>Add Division</h4>
            <form method="POST" action="{{ route('admin.production.divisions.store') }}">
                @csrf
                <div class="ps-form-row" style="align-items:flex-end;">
                    <div class="ps-form-group">
                        <label>Name</label>
                        <input name="name" class="ps-input" placeholder="Division name" required style="width:180px;" value="{{ old('name') }}">
                    </div>
                    <div class="ps-form-group">
                        <label>Hue (0–359)</label>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <input name="hue" type="range" min="0" max="359"
                                value="{{ old('hue', 180) }}"
                                class="ps-input" style="width:120px;padding:4px 2px;"
                                oninput="psDivHuePreview(this,'add-div-prev')">
                            <span id="add-div-prev" class="ps-badge" style="background:hsl(180,65%,70%);width:28px;height:28px;"></span>
                            <span id="add-div-hue-val" style="font-size:0.75rem;color:#64748b;min-width:28px;">180°</span>
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
