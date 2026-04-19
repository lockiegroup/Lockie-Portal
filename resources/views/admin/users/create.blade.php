<x-layout title="Add Staff Member — Lockie Portal">
    <nav class="bg-slate-900 shadow-lg">
        <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ route('dashboard') }}">
                <img src="{{ asset('images/logo.png') }}" alt="Lockie Group" class="h-8 w-auto">
            </a>
            <a href="{{ route('admin.users.index') }}" class="text-slate-400 hover:text-white text-sm transition-colors">← Back to Users</a>
        </div>
    </nav>

    <main class="max-w-xl mx-auto px-6 py-10">
        <h1 class="text-2xl font-bold text-slate-800 mb-6">Add Staff Member</h1>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8">
            <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-5">
                @csrf

                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Full name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Email address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Role</label>
                    <select name="role" class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                        <option value="staff" {{ old('role') === 'staff' ? 'selected' : '' }}>Staff</option>
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Temporary password</label>
                    <input type="password" name="password" required
                        placeholder="Min 8 chars, uppercase, number"
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                    <p class="text-xs text-slate-400 mt-1">They can change this after logging in.</p>
                </div>

                <button type="submit"
                    class="w-full bg-slate-900 hover:bg-slate-700 text-white font-semibold py-3 rounded-lg transition-colors">
                    Add Staff Member
                </button>
            </form>
        </div>
    </main>
</x-layout>
