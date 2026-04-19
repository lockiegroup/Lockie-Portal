<x-layout title="H&S Settings — Lockie Portal">
    <nav class="bg-slate-900 shadow-lg">
        <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ route('dashboard') }}">
                <img src="{{ asset('images/logo.png') }}" alt="Lockie Group" class="h-12 w-auto">
            </a>
            <a href="{{ route('hs.actions.index') }}" class="text-slate-400 hover:text-white text-sm transition-colors">← Back to Actions</a>
        </div>
    </nav>

    <main class="max-w-xl mx-auto px-6 py-10">
        <h1 class="text-2xl font-bold text-slate-800 mb-2">H&S Settings</h1>
        <p class="text-slate-500 text-sm mb-6">Configure who receives action reminder emails and when.</p>

        @if(session('success'))
            <div class="mb-5 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg px-4 py-3">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8">
            <form action="{{ route('hs.settings.update') }}" method="POST" class="space-y-6">
                @csrf

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-3">Reminder email recipients</label>
                    <p class="text-xs text-slate-400 mb-3">These users will receive a daily email listing overdue and upcoming actions.</p>
                    <div class="space-y-2">
                        @foreach($users as $user)
                            <label class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer transition-colors">
                                <input type="checkbox" name="recipient_ids[]" value="{{ $user->id }}"
                                    class="rounded"
                                    {{ in_array($user->id, $recipientIds ?? []) ? 'checked' : '' }}>
                                <div>
                                    <p class="text-sm font-medium text-slate-800">{{ $user->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $user->email }}</p>
                                </div>
                                <span class="ml-auto inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $user->role === 'admin' ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ ucfirst($user->role) }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Send reminder how many days before due date?</label>
                    <div class="flex items-center gap-3">
                        <input type="number" name="reminder_days_before" value="{{ $reminderDaysBefore }}"
                            min="1" max="30" required
                            class="w-24 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                        <span class="text-sm text-slate-500">days before the due date</span>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">Overdue actions are always included regardless of this setting.</p>
                </div>

                <div class="border-t border-slate-100 pt-5">
                    <p class="text-xs text-slate-400 mb-4">
                        Emails are sent automatically each morning at 8:00am.<br>
                        To test email delivery, ensure <code class="bg-slate-100 px-1 rounded">MAIL_MAILER</code> is configured in your <code class="bg-slate-100 px-1 rounded">.env</code> file.
                    </p>
                    <button type="submit"
                        class="w-full bg-slate-900 hover:bg-slate-700 text-white font-semibold py-3 rounded-lg transition-colors">
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </main>
</x-layout>
