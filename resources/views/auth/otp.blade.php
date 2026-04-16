<x-layout title="Lockie Portal — Verify" bodyClass="min-h-screen bg-gradient-to-br from-slate-900 to-slate-700 flex items-center justify-center px-4 antialiased">
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
                <p class="text-slate-400 text-sm">Two-factor verification</p>
            </div>

            <form action="{{ route('otp.verify') }}" method="POST" class="px-8 py-8 space-y-6">
                @csrf

                <div class="text-center">
                    <div class="flex justify-center mb-4">
                        <div class="bg-sky-50 rounded-full p-3">
                            <svg class="w-8 h-8 text-sky-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                        </div>
                    </div>
                    <h1 class="text-slate-800 text-xl font-semibold">Check your email</h1>
                    <p class="text-slate-500 text-sm mt-2">We've sent a 6-digit verification code to your email. It expires in 10 minutes.</p>
                </div>

                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3 text-center">
                        {{ $errors->first() }}
                    </div>
                @endif

                {{-- OTP digit inputs --}}
                <div class="flex justify-center gap-2" id="otp-inputs">
                    @for($i = 0; $i < 6; $i++)
                        <input type="text" inputmode="numeric" maxlength="1"
                            class="otp-digit w-11 h-14 text-center text-xl font-bold border border-slate-300 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition"
                            {{ $i === 0 ? 'autofocus' : '' }}>
                    @endfor
                    <input type="hidden" name="code" id="otp-hidden">
                </div>

                <button type="submit"
                    class="w-full bg-slate-900 hover:bg-slate-700 text-white font-semibold py-3 rounded-lg transition-colors duration-150">
                    Verify & Sign In
                </button>

                <p class="text-center text-sm text-slate-500">
                    Didn't receive it?
                    <a href="{{ route('login') }}" class="text-sky-600 hover:text-sky-800 font-medium underline">Go back and try again</a>
                </p>
            </form>
        </div>
        <p class="text-center text-slate-400 text-xs mt-6">Lockie Group &copy; {{ date('Y') }} &mdash; Internal use only</p>
    </div>

    <script>
        const digits = document.querySelectorAll('.otp-digit');
        const hidden = document.getElementById('otp-hidden');

        digits.forEach((input, i) => {
            input.addEventListener('input', () => {
                input.value = input.value.replace(/\D/g, '').slice(-1);
                if (input.value && i < 5) digits[i + 1].focus();
                hidden.value = [...digits].map(d => d.value).join('');
            });
            input.addEventListener('keydown', e => {
                if (e.key === 'Backspace' && !input.value && i > 0) digits[i - 1].focus();
            });
            input.addEventListener('paste', e => {
                e.preventDefault();
                const pasted = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
                pasted.split('').forEach((c, j) => { if (digits[j]) digits[j].value = c; });
                hidden.value = pasted;
                if (digits[pasted.length - 1]) digits[Math.min(pasted.length, 5)].focus();
            });
        });
    </script>
</x-layout>
