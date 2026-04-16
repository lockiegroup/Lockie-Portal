import { NextRequest, NextResponse } from 'next/server';
import { getUserByEmail, checkPassword, generateOtp, storeOtp } from '@/lib/auth';
import { signToken } from '@/lib/token';
import { sendOtpEmail } from '@/lib/email';

export async function POST(req: NextRequest) {
  try {
    const { email, password } = await req.json();

    if (!email || !password) {
      return NextResponse.json({ error: 'Email and password are required.' }, { status: 400 });
    }

    const user = getUserByEmail(email.toLowerCase().trim());

    // Constant-time-like response to prevent user enumeration
    if (!user || !(await checkPassword(password, user.password_hash))) {
      return NextResponse.json({ error: 'Invalid email or password.' }, { status: 401 });
    }

    const otp = generateOtp();
    storeOtp(user.id, otp);
    await sendOtpEmail(user.email, user.name, otp);

    const preAuthToken = await signToken(
      { userId: user.id, email: user.email, name: user.name, role: user.role, verified: false },
      '10m'
    );

    const res = NextResponse.json({ ok: true });
    res.cookies.set('lockie_pre_auth', preAuthToken, {
      httpOnly: true,
      sameSite: 'lax',
      path: '/',
      maxAge: 60 * 10, // 10 minutes
    });
    return res;
  } catch (err) {
    console.error('[login]', err);
    return NextResponse.json({ error: 'Something went wrong.' }, { status: 500 });
  }
}
