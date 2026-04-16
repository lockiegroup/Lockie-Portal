import bcrypt from 'bcryptjs';
import { v4 as uuidv4 } from 'uuid';
import { getDb, type DbUser, type DbOtp } from './db';
export { signToken, verifyToken, type SessionPayload } from './token';

export async function hashPassword(password: string): Promise<string> {
  return bcrypt.hash(password, 12);
}

export async function checkPassword(password: string, hash: string): Promise<boolean> {
  return bcrypt.compare(password, hash);
}

export function getUserByEmail(email: string): DbUser | null {
  const db = getDb();
  return db.prepare('SELECT * FROM users WHERE email = ? AND is_active = 1').get(email) as DbUser | null;
}

export function createUser(email: string, name: string, passwordHash: string, role = 'staff'): DbUser {
  const db = getDb();
  const id = uuidv4();
  db.prepare(
    'INSERT INTO users (id, email, name, password_hash, role) VALUES (?, ?, ?, ?, ?)'
  ).run(id, email.toLowerCase(), name, passwordHash, role);
  return db.prepare('SELECT * FROM users WHERE id = ?').get(id) as DbUser;
}

export function generateOtp(): string {
  return Math.floor(100000 + Math.random() * 900000).toString();
}

export function storeOtp(userId: string, code: string): void {
  const db = getDb();
  // Invalidate any previous unused OTPs for this user
  db.prepare('UPDATE otp_codes SET used = 1 WHERE user_id = ? AND used = 0').run(userId);
  const expiresAt = new Date(Date.now() + 10 * 60 * 1000).toISOString();
  db.prepare(
    'INSERT INTO otp_codes (id, user_id, code, expires_at) VALUES (?, ?, ?, ?)'
  ).run(uuidv4(), userId, code, expiresAt);
}

export function consumeOtp(userId: string, code: string): boolean {
  const db = getDb();
  const otp = db.prepare(`
    SELECT * FROM otp_codes
    WHERE user_id = ? AND code = ? AND used = 0 AND expires_at > datetime('now')
  `).get(userId, code) as DbOtp | null;

  if (!otp) return false;

  db.prepare('UPDATE otp_codes SET used = 1 WHERE id = ?').run(otp.id);
  return true;
}
