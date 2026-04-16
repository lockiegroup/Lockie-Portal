import { cookies } from 'next/headers';
import { redirect } from 'next/navigation';
import { verifyToken } from '@/lib/token';
import LogoutButton from '@/components/LogoutButton';

const modules = [
  {
    title: 'Sales Figures',
    description: 'View and track your team\'s sales performance, targets, and reports.',
    icon: (
      <svg className="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2}>
        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
      </svg>
    ),
    color: 'bg-emerald-50 text-emerald-600',
    available: false,
  },
  {
    title: 'Health & Safety',
    description: 'Manage incidents, risk assessments, and compliance documentation.',
    icon: (
      <svg className="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2}>
        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
      </svg>
    ),
    color: 'bg-amber-50 text-amber-600',
    available: false,
  },
  {
    title: 'Tasks',
    description: 'View assigned tasks, deadlines, and track completion progress.',
    icon: (
      <svg className="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2}>
        <path d="M9 11l3 3L22 4" />
        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
      </svg>
    ),
    color: 'bg-sky-50 text-sky-600',
    available: false,
  },
];

export default async function DashboardPage() {
  const cookieStore = await cookies();
  const token = cookieStore.get('lockie_session')?.value;
  if (!token) redirect('/login');

  const payload = await verifyToken(token);
  if (!payload || !payload.verified) redirect('/login');

  const firstName = payload.name.split(' ')[0];

  return (
    <div className="min-h-screen bg-slate-100">
      {/* Top nav */}
      <nav className="bg-slate-900 shadow-lg">
        <div className="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <svg className="w-7 h-7 text-sky-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2}>
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
              <path d="M7 11V7a5 5 0 0 1 10 0v4" />
            </svg>
            <span className="text-white font-bold text-lg tracking-tight">Lockie Portal</span>
          </div>
          <div className="flex items-center gap-4">
            <span className="text-slate-400 text-sm hidden sm:block">{payload.email}</span>
            <LogoutButton />
          </div>
        </div>
      </nav>

      <main className="max-w-5xl mx-auto px-6 py-10">
        {/* Welcome */}
        <div className="mb-8">
          <h1 className="text-2xl font-bold text-slate-800">Welcome back, {firstName}</h1>
          <p className="text-slate-500 mt-1">What would you like to do today?</p>
        </div>

        {/* Module cards */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
          {modules.map((m) => (
            <div
              key={m.title}
              className="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex flex-col gap-4 opacity-70 cursor-not-allowed"
            >
              <div className={`w-12 h-12 rounded-xl flex items-center justify-center ${m.color}`}>
                {m.icon}
              </div>
              <div>
                <h2 className="font-semibold text-slate-800">{m.title}</h2>
                <p className="text-slate-500 text-sm mt-1">{m.description}</p>
              </div>
              <span className="text-xs font-medium text-slate-400 uppercase tracking-wide mt-auto">
                Coming soon
              </span>
            </div>
          ))}
        </div>

        {/* Role badge */}
        <div className="mt-8 text-center">
          <span className="inline-block bg-slate-200 text-slate-600 text-xs font-medium px-3 py-1 rounded-full uppercase tracking-wide">
            {payload.role}
          </span>
        </div>
      </main>
    </div>
  );
}
