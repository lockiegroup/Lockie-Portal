import { NextResponse } from 'next/server';

export async function POST() {
  const res = NextResponse.json({ ok: true });
  res.cookies.delete('lockie_session');
  res.cookies.delete('lockie_pre_auth');
  return res;
}
