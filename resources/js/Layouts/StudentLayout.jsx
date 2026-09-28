import { Head, Link, usePage } from '@inertiajs/react';

export default function StudentLayout({ children }) {
    const { appSetting, auth } = usePage().props;
    const student = auth?.student;
    return <div className="min-h-screen bg-slate-50 px-4 py-5 sm:px-6">
        <div className="mx-auto max-w-6xl">
            <header className="flex items-center justify-between rounded-2xl bg-white px-5 py-4 shadow-sm ring-1 ring-slate-200 sm:px-7">
                <Link href={route('student.dashboard')} className="flex items-center gap-3"><div className="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-700 text-lg font-bold text-white">{(appSetting?.app_name || 'S').charAt(0)}</div><div><p className="font-bold text-slate-900">{appSetting?.app_name || 'SIBITI'}</p><p className="text-xs text-slate-500">{appSetting?.school_name || 'Portal ujian sekolah'}</p></div></Link>
                <div className="flex items-center gap-4"><div className="text-right"><p className="text-sm font-semibold text-slate-900">{student?.name}</p><p className="text-xs text-slate-500">NISN {student?.nisn}</p></div><Link href={route('student.logout')} method="post" as="button" className="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700">Keluar</Link></div>
            </header>
            <main>{children}</main>
        </div>
    </div>;
}
