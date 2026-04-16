import type { Metadata } from 'next';
import './globals.css';

export const metadata: Metadata = {
  title: 'Lockie Portal — Staff Login',
  description: 'Lockie Group internal staff portal',
  robots: { index: false, follow: false },
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body className="bg-slate-100 min-h-screen antialiased">{children}</body>
    </html>
  );
}
