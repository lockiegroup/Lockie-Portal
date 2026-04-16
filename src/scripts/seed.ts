import { getDb } from '../lib/db';
import { hashPassword, createUser } from '../lib/auth';

async function seed() {
  getDb(); // initialise schema

  const passwordHash = await hashPassword('Admin1234!');
  try {
    createUser('admin@lockiegroup.com', 'Admin User', passwordHash, 'admin');
    console.log('✓ Created admin user: admin@lockiegroup.com / Admin1234!');
  } catch {
    console.log('Admin user already exists.');
  }

  console.log('Seed complete.');
  process.exit(0);
}

seed();
