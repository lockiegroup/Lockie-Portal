import { NextRequest, NextResponse } from 'next/server';
import { verifyToken } from '@/lib/token';

const PUBLIC_PATHS = ['/login', '/api/auth/login', '/api/auth/verify-otp'];

export async function middleware(req: NextRequest) {
  const { pathname } = req.nextUrl;

  if (PUBLIC_PATHS.some((p) => pathname.startsWith(p))) {
    return NextResponse.next();
  }

  // /verify requires a pre-auth token (unverified session)
  if (pathname.startsWith('/verify')) {
    const preAuth = req.cookies.get('lockie_pre_auth')?.value;
    if (!preAuth) return NextResponse.redirect(new URL('/login', req.url));
    const payload = await verifyToken(preAuth);
    if (!payload || payload.verified) return NextResponse.redirect(new URL('/login', req.url));
    return NextResponse.next();
  }

  // All other paths require a verified session
  const session = req.cookies.get('lockie_session')?.value;
  if (!session) return NextResponse.redirect(new URL('/login', req.url));
  const payload = await verifyToken(session);
  if (!payload || !payload.verified) return NextResponse.redirect(new URL('/login', req.url));

  return NextResponse.next();
}

export const config = {
  matcher: ['/((?!_next/static|_next/image|favicon.ico).*)'],
};
