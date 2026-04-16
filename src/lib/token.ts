import { SignJWT, jwtVerify } from 'jose';

export interface SessionPayload {
  userId: string;
  email: string;
  name: string;
  role: string;
  verified: boolean;
}

function getSecret() {
  return new TextEncoder().encode(
    process.env.JWT_SECRET || 'dev-secret-lockie-portal-change-in-production'
  );
}

export async function signToken(payload: SessionPayload, expiresIn: string): Promise<string> {
  return new SignJWT({ ...payload })
    .setProtectedHeader({ alg: 'HS256' })
    .setIssuedAt()
    .setExpirationTime(expiresIn)
    .sign(getSecret());
}

export async function verifyToken(token: string): Promise<SessionPayload | null> {
  try {
    const { payload } = await jwtVerify(token, getSecret());
    return payload as unknown as SessionPayload;
  } catch {
    return null;
  }
}
