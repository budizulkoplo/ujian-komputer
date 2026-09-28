import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link, usePage } from '@inertiajs/react';

export default function Guest({ children }) {
    const { appSetting } = usePage().props;
    const logoUrl = appSetting?.school_logo ? `/storage/${appSetting.school_logo}` : null;

    return <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-cyan-50 via-white to-amber-50 px-4 py-8 text-slate-900 sm:px-6">
        <div className="w-full max-w-md">
            <div className="mb-6 text-center">
                <Link href="/" className="inline-flex flex-col items-center gap-3">
                    {logoUrl ? <img src={logoUrl} alt="Logo sekolah" className="h-28 w-28 rounded-3xl bg-white object-contain p-3 shadow-md ring-1 ring-slate-200" /> : <ApplicationLogo className="h-28 w-28 fill-current text-teal-700" />}
                    <span className="text-3xl font-bold tracking-tight text-slate-900">{appSetting?.app_name || 'SIBITI'}</span>
                </Link>
            </div>
            <div className="rounded-3xl bg-white p-6 shadow-xl shadow-slate-200/70 ring-1 ring-slate-200 sm:p-9">
                <div className="mb-6 border-b border-slate-200 pb-4 text-center"><p className="text-sm font-semibold text-slate-700">{appSetting?.school_name || 'Portal ujian sekolah'}</p></div>
                {children}
            </div>
            <p className="mt-5 text-center text-xs text-slate-400">Akses aman untuk kegiatan belajar dan ujian.</p>
        </div>
    </div>;
}
