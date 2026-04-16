import { NextRequest, NextResponse } from 'next/server';
import { consumeOtp } from '@/lib/auth';
import { verifyToken, signToken } from '@/lib/token';

export async function POST(req: NextRequest) {
  try {
    const { otp } = await req.json();

    if (!otp || otp.length !== 6) {
      return NextResponse.json({ error: 'Please enter a valid 6-digit code.' }, { status: 400 });
    }

    const preAuthToken = req.cookies.get('lockie_pre_auth')?.value;
    if (!preAuthToken) {
      return NextResponse.json({ error: 'Session expired. Please log in again.' }, { status: 401 });
    }

    const payload = await verifyToken(preAuthToken);
    if (!payload || payload.verified) {
      return NextResponse.json({ error: 'Session expired. Please log in again.' }, { status: 401 });
    }

    const valid = consumeOtp(payload.userId, otp);
    if (!valid) {
      return NextResponse.json({ error: 'Invalid or expired code. Please try again.' }, { status: 401 });
    }

    const sessionToken = await signToken(
      { userId: payload.userId, email: payload.email, name: payload.name, role: payload.role, verified: true },
      '8h'
    );

    const res = NextResponse.json({ ok: true });
    res.cookies.delete('lockie_pre_auth');
    res.cookies.set('lockie_session', sessionToken, {
      httpOnly: true,
      sameSite: 'lax',
      path: '/',
      maxAge: 60 * 60 * 8, // 8 hours
    });
    return res;
  } catch (err) {
    console.error('[verify-otp]', err);
    return NextResponse.json({ error: 'Something went wrong.' }, { status: 500 });
  }
}
