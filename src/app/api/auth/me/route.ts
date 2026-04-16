import { NextRequest, NextResponse } from 'next/server';
import { verifyToken } from '@/lib/token';

export async function GET(req: NextRequest) {
  const token = req.cookies.get('lockie_session')?.value;
  if (!token) return NextResponse.json({ user: null }, { status: 401 });

  const payload = await verifyToken(token);
  if (!payload || !payload.verified) return NextResponse.json({ user: null }, { status: 401 });

  return NextResponse.json({
    user: { email: payload.email, name: payload.name, role: payload.role },
  });
}
