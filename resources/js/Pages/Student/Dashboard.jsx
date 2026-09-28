import { Head, Link, usePage } from '@inertiajs/react';

export default function Dashboard({ exam_groups = [] }) {
    const { appSetting, auth } = usePage().props;
    const student = auth.student;
    const logoUrl = appSetting?.school_logo ? `/storage/${appSetting.school_logo}` : null;

    return (
        <>
            <Head title={`${appSetting?.app_name || 'SIBITI'} - Dashboard Siswa`} />
            <div className="min-h-screen bg-slate-50 px-4 py-6 sm:px-6">
                <div className="mx-auto max-w-5xl">
                    <header className="flex items-center justify-between rounded-2xl bg-white px-5 py-4 shadow-lg sm:px-7">
                        <div className="flex items-center gap-3">
                            {logoUrl ? <img src={logoUrl} alt="Logo sekolah" className="h-11 w-11 rounded-xl object-contain" /> : null}
                            <div>
                                <p className="font-bold text-slate-900">{appSetting?.app_name || 'SIBITI'}</p>
                                <p className="text-sm text-slate-500">{appSetting?.school_name || 'Portal ujian sekolah'}</p>
                            </div>
                        </div>
                        <div className="flex items-center gap-4">
                            <div className="text-right">
                            <p className="text-sm font-semibold text-slate-900">{student?.name}</p>
                            <p className="text-xs text-slate-500">NISN {student?.nisn}</p>
                            </div>
                            <Link href={route('student.logout')} method="post" as="button" className="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700">Keluar</Link>
                        </div>
                    </header>

                    <main className="mt-8">
                        <div className="mb-6 text-slate-900">
                            <p className="text-sm font-semibold uppercase tracking-[0.2em] text-teal-700">Ruang siswa</p>
                            <h1 className="mt-2 text-3xl font-bold">Daftar ujian</h1>
                            <p className="mt-2 text-sm text-slate-500">Pilih ujian yang tersedia untuk mulai mengerjakan.</p>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            {exam_groups.length ? exam_groups.map(({ exam_group: group, grade }) => (
                                <article key={group.id} className="rounded-2xl bg-white p-5 shadow-lg">
                                    <div className="flex items-start justify-between gap-4">
                                        <div>
                                            <p className="text-xs font-semibold uppercase tracking-wider text-teal-700">{group.exam?.lesson?.title || 'Ujian'}</p>
                                            <h2 className="mt-2 text-xl font-bold text-slate-900">{group.exam?.title}</h2>
                                            <p className="mt-2 text-sm text-slate-500">Sesi: {group.exam_session?.title}</p>
                                        </div>
                                        <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{group.exam?.duration} menit</span>
                                    </div>
                                    <div className="mt-5 flex items-center justify-between border-t border-slate-100 pt-4">
                                        <span className="text-sm text-slate-500">Nilai: {grade?.grade ?? 0}</span>
                                        <Link href={route('student.examination.confirmation', group.id)} className="rounded-xl bg-teal-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800">Mulai ujian</Link>
                                    </div>
                                </article>
                            )) : (
                                <div className="rounded-2xl bg-white p-8 text-center text-slate-500 md:col-span-2">Belum ada ujian yang ditugaskan.</div>
                            )}
                        </div>
                    </main>
                </div>
            </div>
        </>
    );
}
