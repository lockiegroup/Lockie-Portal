<x-layout title="Lockie Portal — Sign In" bodyClass="min-h-screen bg-gradient-to-br from-slate-900 to-slate-700 flex items-center justify-center px-4 antialiased">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">

            <div class="bg-slate-900 px-8 py-6 text-center">
                <div class="flex items-center justify-center gap-3 mb-1">
                    <svg class="w-8 h-8 text-sky-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <span class="text-white text-2xl font-bold tracking-tight">Lockie Portal</span>
                </div>
                <p class="text-slate-400 text-sm">Staff access only</p>
            </div>

            <form action="{{ route('login') }}" method="POST" class="px-8 py-8 space-y-5">
                @csrf

                <div>
                    <h1 class="text-slate-800 text-xl font-semibold">Sign in to your account</h1>
                    <p class="text-slate-500 text-sm mt-1">Enter your work email and password. A verification code will be sent to you.</p>
                </div>

                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5" for="email">Email address</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                            placeholder="you@lockiegroup.com"
                            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5" for="password">Password</label>
                        <input id="password" type="password" name="password" required
                            placeholder="••••••••"
                            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-slate-900 hover:bg-slate-700 text-white font-semibold py-3 rounded-lg transition-colors duration-150">
                    Sign In
                </button>
            </form>
        </div>
        <p class="text-center text-slate-400 text-xs mt-6">Lockie Group &copy; {{ date('Y') }} &mdash; Internal use only</p>
    </div>
</x-layout>
