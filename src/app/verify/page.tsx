'use client';

import { useRef, useState, KeyboardEvent, ClipboardEvent } from 'react';
import { useRouter } from 'next/navigation';

export default function VerifyPage() {
  const router = useRouter();
  const [digits, setDigits] = useState<string[]>(Array(6).fill(''));
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [resent, setResent] = useState(false);
  const inputs = useRef<(HTMLInputElement | null)[]>([]);

  function updateDigit(index: number, value: string) {
    const next = [...digits];
    next[index] = value.slice(-1);
    setDigits(next);
    if (value && index < 5) inputs.current[index + 1]?.focus();
  }

  function handleKey(index: number, e: KeyboardEvent<HTMLInputElement>) {
    if (e.key === 'Backspace' && !digits[index] && index > 0) {
      inputs.current[index - 1]?.focus();
    }
  }

  function handlePaste(e: ClipboardEvent<HTMLInputElement>) {
    e.preventDefault();
    const pasted = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
    if (!pasted) return;
    const next = [...digits];
    pasted.split('').forEach((c, i) => { next[i] = c; });
    setDigits(next);
    inputs.current[Math.min(pasted.length, 5)]?.focus();
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    const otp = digits.join('');
    if (otp.length < 6) { setError('Please enter all 6 digits.'); return; }

    setError('');
    setLoading(true);

    try {
      const res = await fetch('/api/auth/verify-otp', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ otp }),
      });
      const data = await res.json();
      if (!res.ok) { setError(data.error || 'Verification failed.'); return; }
      router.push('/dashboard');
    } catch {
      setError('Network error. Please try again.');
    } finally {
      setLoading(false);
    }
  }

  async function handleResend() {
    router.push('/login');
  }

  return (
    <main className="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 to-slate-700 px-4">
      <div className="w-full max-w-md">
        <div className="bg-white rounded-2xl shadow-2xl overflow-hidden">
          <div className="bg-slate-900 px-8 py-6 text-center">
            <div className="flex items-center justify-center gap-3 mb-1">
              <svg className="w-8 h-8 text-sky-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2}>
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
              </svg>
              <span className="text-white text-2xl font-bold tracking-tight">Lockie Portal</span>
            </div>
            <p className="text-slate-400 text-sm">Two-factor verification</p>
          </div>

          <form onSubmit={handleSubmit} className="px-8 py-8 space-y-6">
            <div>
              <div className="flex justify-center mb-4">
                <div className="bg-sky-50 rounded-full p-3">
                  <svg className="w-8 h-8 text-sky-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2}>
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                    <polyline points="22,6 12,13 2,6" />
                  </svg>
                </div>
              </div>
              <h1 className="text-slate-800 text-xl font-semibold text-center">Check your email</h1>
              <p className="text-slate-500 text-sm mt-2 text-center">
                We&apos;ve sent a 6-digit verification code to your email address. It expires in 10 minutes.
              </p>
            </div>

            {error && (
              <div className="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3 text-center">
                {error}
              </div>
            )}

            {/* OTP inputs */}
            <div className="flex justify-center gap-2">
              {digits.map((d, i) => (
                <input
                  key={i}
                  ref={(el) => { inputs.current[i] = el; }}
                  type="text"
                  inputMode="numeric"
                  maxLength={1}
                  value={d}
                  onChange={(e) => updateDigit(i, e.target.value.replace(/\D/g, ''))}
                  onKeyDown={(e) => handleKey(i, e)}
                  onPaste={i === 0 ? handlePaste : undefined}
                  className="w-11 h-14 text-center text-xl font-bold border border-slate-300 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition"
                />
              ))}
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full bg-slate-900 hover:bg-slate-700 disabled:bg-slate-400 text-white font-semibold py-3 rounded-lg transition-colors duration-150"
            >
              {loading ? 'Verifying…' : 'Verify & Sign In'}
            </button>

            <p className="text-center text-sm text-slate-500">
              Didn&apos;t receive it?{' '}
              <button
                type="button"
                onClick={handleResend}
                className="text-sky-600 hover:text-sky-800 font-medium underline"
              >
                Go back and try again
              </button>
            </p>
          </form>
        </div>

        <p className="text-center text-slate-400 text-xs mt-6">
          Lockie Group &copy; {new Date().getFullYear()} &mdash; Internal use only
        </p>
      </div>
    </main>
  );
}
