<x-layout title="Edit H&S Action — Lockie Portal">

    <main class="max-w-xl mx-auto px-6 py-10">
        <h1 class="text-2xl font-bold text-slate-800 mb-6">Edit Action</h1>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8">
            <form action="{{ route('hs.actions.update', $action) }}" method="POST" class="space-y-5">
                @csrf @method('PUT')

                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $action->title) }}" required
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Description</label>
                    <textarea name="description" rows="3"
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition resize-none">{{ old('description', $action->description) }}</textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Location</label>
                        <input type="text" name="location" value="{{ old('location', $action->location) }}"
                            placeholder="e.g. Warehouse A"
                            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Priority <span class="text-red-500">*</span></label>
                        <select name="priority" required
                            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                            @foreach(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $val => $label)
                                <option value="{{ $val }}" {{ old('priority', $action->priority) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Assigned To</label>
                        <select name="assigned_to"
                            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                            <option value="">— Unassigned —</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('assigned_to', $action->assigned_to) == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Due Date <span class="text-red-500">*</span></label>
                        <input type="date" name="due_date" value="{{ old('due_date', $action->due_date->format('Y-m-d')) }}" required
                            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Status</label>
                    <select name="status" required
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                        @foreach(['open' => 'Open', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'overdue' => 'Overdue'] as $val => $label)
                            <option value="{{ $val }}" {{ old('status', $action->status) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="border border-slate-200 rounded-lg p-4 space-y-3">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="is_recurring" id="is_recurring" value="1"
                            class="rounded" {{ old('is_recurring', $action->is_recurring) ? 'checked' : '' }}
                            onchange="document.getElementById('recurrence-row').classList.toggle('hidden', !this.checked)">
                        <label for="is_recurring" class="text-sm font-medium text-slate-700">Recurring action</label>
                    </div>
                    <div id="recurrence-row" class="{{ old('is_recurring', $action->is_recurring) ? '' : 'hidden' }}">
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Repeats</label>
                        <select name="recurrence_type"
                            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                            @foreach(['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'annually' => 'Annually'] as $val => $label)
                                <option value="{{ $val }}" {{ old('recurrence_type', $action->recurrence_type) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Notes</label>
                    <textarea name="notes" rows="2"
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition resize-none">{{ old('notes', $action->notes) }}</textarea>
                </div>

                <button type="submit"
                    class="w-full bg-slate-900 hover:bg-slate-700 text-white font-semibold py-3 rounded-lg transition-colors">
                    Save Changes
                </button>
            </form>
        </div>
    </main>
</x-layout>
