import nodemailer from 'nodemailer';

function getTransporter() {
  const { SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS } = process.env;

  if (!SMTP_HOST) return null;

  return nodemailer.createTransport({
    host: SMTP_HOST,
    port: Number(SMTP_PORT) || 587,
    secure: Number(SMTP_PORT) === 465,
    auth: SMTP_USER ? { user: SMTP_USER, pass: SMTP_PASS } : undefined,
  });
}

export async function sendOtpEmail(toEmail: string, toName: string, otp: string): Promise<void> {
  const transporter = getTransporter();
  const from = process.env.FROM_EMAIL || 'noreply@lockie.com';

  if (!transporter) {
    console.log('\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    console.log(`  [DEV] 2FA code for ${toEmail}: ${otp}`);
    console.log('  (Configure SMTP in .env.local to send real emails)');
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n');
    return;
  }

  await transporter.sendMail({
    from: `"Lockie Portal" <${from}>`,
    to: toEmail,
    subject: `Your Lockie Portal login code: ${otp}`,
    text: `Hi ${toName},\n\nYour login verification code is: ${otp}\n\nThis code expires in 10 minutes.\n\nIf you did not request this, please ignore this email.\n\nLockie Portal`,
    html: `
      <div style="font-family:sans-serif;max-width:480px;margin:0 auto;padding:24px">
        <h2 style="color:#1e293b;margin-bottom:8px">Lockie Portal</h2>
        <p style="color:#475569">Hi ${toName},</p>
        <p style="color:#475569">Your login verification code is:</p>
        <div style="background:#f1f5f9;border-radius:8px;padding:24px;text-align:center;margin:24px 0">
          <span style="font-size:36px;font-weight:700;letter-spacing:8px;color:#1e293b">${otp}</span>
        </div>
        <p style="color:#64748b;font-size:14px">This code expires in <strong>10 minutes</strong>.</p>
        <p style="color:#64748b;font-size:14px">If you did not request this, you can safely ignore this email.</p>
      </div>
    `,
  });
}
