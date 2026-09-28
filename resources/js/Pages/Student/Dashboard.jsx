import { Head, Link, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import {
    IconArrowRight,
    IconCheck,
    IconClipboardList,
    IconClock,
    IconHistory,
    IconBulb,
    IconLogout,
    IconPlayerPlay,
    IconSchool,
    IconTrophy,
} from '@tabler/icons-react';

function StatCard({ value, label, icon: Icon, color, action, onClick }) {
    const content = (
        <>
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="text-3xl font-bold tracking-tight text-white">{value}</p>
                    <p className="mt-1 text-sm text-white/80">{label}</p>
                </div>
                <span className="rounded-full bg-white/20 p-3 text-white">
                    <Icon size={27} stroke={1.8} />
                </span>
            </div>
            {action ? (
                <div className="mt-5 flex items-center gap-2 border-t border-white/25 pt-3 text-xs font-semibold text-white">
                    {action} <IconArrowRight size={15} />
                </div>
            ) : null}
        </>
    );

    return <button type="button" onClick={onClick} className={`w-full rounded-xl p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md ${color}`}>{content}</button>;
}

export default function Dashboard({ exam_groups = [], stats = {}, student_classroom = null }) {
    const [filter, setFilter] = useState('all');
    const { appSetting, auth } = usePage().props;
    const student = auth.student;
    const classroom = student_classroom || student?.classroom?.title || exam_groups[0]?.exam_group?.student?.classroom?.title || 'Kelas belum diatur';
    const logoUrl = appSetting?.school_logo ? `/storage/${appSetting.school_logo}` : null;
    const filteredExamGroups = useMemo(() => exam_groups.filter(({ grade }) => {
        if (filter === 'active') return !grade?.end_time && !grade?.is_locked;
        if (filter === 'in_progress') return grade?.start_time && !grade?.end_time && !grade?.is_locked;
        if (filter === 'completed') return Boolean(grade?.end_time);
        if (filter === 'passed') return grade?.end_time && Number(grade?.grade || 0) >= 75;
        if (filter === 'failed') return grade?.end_time && Number(grade?.grade || 0) < 75;
        return true;
    }), [exam_groups, filter]);
    const selectFilter = (nextFilter) => {
        setFilter(nextFilter);
        window.setTimeout(() => document.getElementById('daftar-ujian')?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 0);
    };

    return (
        <>
            <Head title={`${appSetting?.app_name || 'SIBITI'} - Dashboard Siswa`} />
            <div className="min-h-screen bg-slate-50 px-4 py-6 sm:px-6">
                <div className="mx-auto max-w-7xl">
                    <header className="flex items-center justify-between rounded-2xl bg-white px-5 py-4 shadow-sm ring-1 ring-slate-200 sm:px-7">
                        <div className="flex items-center gap-3">
                            {logoUrl ? <img src={logoUrl} alt="Logo sekolah" className="h-11 w-11 rounded-xl object-contain" /> : null}
                            <div>
                                <p className="font-bold text-slate-900">{appSetting?.app_name || 'SIBITI'}</p>
                                <p className="text-sm text-slate-500">{appSetting?.school_name || 'Portal ujian sekolah'}</p>
                            </div>
                        </div>
                        <div className="flex items-center gap-3">
                            <div className="hidden text-right sm:block">
                                <p className="text-sm font-semibold text-slate-900">{student?.name}</p>
                                <p className="text-xs text-slate-500">NISN {student?.nisn}</p>
                            </div>
                            <Link href={route('student.logout')} method="post" as="button" className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700">
                                <IconLogout size={16} /> Keluar
                            </Link>
                        </div>
                    </header>

                    <main className="mt-6">
                        <section className="rounded-2xl bg-gradient-to-r from-indigo-500 to-purple-700 px-5 py-6 text-white shadow-sm sm:px-7">
                            <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                                <div>
                                    <h1 className="text-xl font-bold sm:text-2xl">Halo, {student?.name || 'siswa'}! &#x1F44B;</h1>
                                    <p className="mt-1 text-sm text-white/85">Selamat datang di dashboard ujian online</p>
                                </div>
                                <div className="inline-flex w-fit items-center gap-2 rounded-full bg-white/20 px-4 py-2 text-sm font-semibold">
                                    <IconSchool size={17} /> {classroom}
                                </div>
                            </div>
                        </section>

                        <section className="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            <StatCard value={stats.active_exams ?? 0} label="Ujian Aktif" icon={IconClipboardList} color="bg-blue-600" action="Lihat daftar" onClick={() => selectFilter('active')} />
                            <StatCard value={stats.total_exams ?? 0} label="Total Ujian" icon={IconHistory} color="bg-green-600" action="Lihat semua" onClick={() => selectFilter('all')} />
                            <StatCard value={stats.average_grade ?? 0} label="Rata-rata Nilai" icon={IconTrophy} color="bg-cyan-600" action="Lihat hasil" onClick={() => selectFilter('completed')} />
                            <StatCard value={stats.in_progress ?? 0} label="Sedang Ujian" icon={IconPlayerPlay} color="bg-amber-500" action="Lanjutkan" onClick={() => selectFilter('in_progress')} />
                        </section>

                        <section className="mt-4 grid gap-4 sm:grid-cols-2">
                            <button type="button" onClick={() => selectFilter('passed')} className="rounded-2xl bg-white px-6 py-5 text-center shadow-sm ring-1 ring-slate-200 transition hover:shadow-md">
                                <p className="text-3xl font-bold text-emerald-600">{stats.passed ?? 0}</p>
                                <p className="mt-1 text-sm text-slate-500">Lulus</p>
                            </button>
                            <button type="button" onClick={() => selectFilter('failed')} className="rounded-2xl bg-white px-6 py-5 text-center shadow-sm ring-1 ring-slate-200 transition hover:shadow-md">
                                <p className="text-3xl font-bold text-rose-600">{stats.failed ?? 0}</p>
                                <p className="mt-1 text-sm text-slate-500">Tidak Lulus</p>
                            </button>
                        </section>

                        <section className="mt-6 flex items-start gap-3 rounded-2xl border border-sky-200 bg-sky-50 px-5 py-4 text-slate-700">
                            <IconBulb className="mt-0.5 shrink-0 text-amber-500" size={25} />
                            <div>
                                <p className="font-semibold">Tips Belajar</p>
                                <p className="mt-1 text-sm text-slate-600">Persiapkan diri dengan baik sebelum ujian. Baca soal dengan teliti dan kelola waktu dengan bijak.</p>
                            </div>
                        </section>

                        <section id="daftar-ujian" className="mt-8">
                            <div className="mb-5 flex items-end justify-between gap-4">
                                <div>
                                    <p className="text-sm font-semibold uppercase tracking-[0.2em] text-teal-700">Ruang siswa</p>
                                    <h2 className="mt-2 text-2xl font-bold text-slate-900">Daftar ujian</h2>
                                    <p className="mt-1 text-sm text-slate-500">Pilih ujian yang tersedia untuk mulai mengerjakan.</p>
                                </div>
                                <div className="flex items-center gap-3">
                                    <span className="hidden items-center gap-1.5 text-sm text-slate-500 sm:flex"><IconClock size={17} /> {filteredExamGroups.length} ujian</span>
                                    {filter !== 'all' ? <button type="button" onClick={() => selectFilter('all')} className="text-sm font-semibold text-teal-700 hover:text-teal-900">Tampilkan semua</button> : null}
                                </div>
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                {filteredExamGroups.length ? filteredExamGroups.map(({ exam_group: group, grade }) => (
                                    <article key={group.id} className="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:shadow-md">
                                        <div className="flex items-start justify-between gap-4">
                                            <div>
                                                <p className="text-xs font-semibold uppercase tracking-wider text-teal-700">{group.exam?.lesson?.title || 'Ujian'}</p>
                                                <h3 className="mt-2 text-xl font-bold text-slate-900">{group.exam?.title}</h3>
                                                <p className="mt-2 text-sm text-slate-500">Sesi: {group.exam_session?.title}</p>
                                            </div>
                                            <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{group.exam?.duration} menit</span>
                                        </div>
                                        <div className="mt-5 flex items-center justify-between border-t border-slate-100 pt-4">
                                            <span className="inline-flex items-center gap-1.5 text-sm text-slate-500">{grade?.is_locked ? <span className="text-rose-600">Dikunci</span> : grade?.end_time ? <IconCheck className="text-emerald-600" size={17} /> : <IconClock className="text-amber-600" size={17} />} {!grade?.is_locked && `Nilai: ${grade?.grade ?? 0}`}</span>
                                            {grade?.is_locked ? (
                                                <span className="rounded-xl bg-rose-100 px-4 py-2 text-sm font-semibold text-rose-700">Hubungi guru</span>
                                            ) : grade?.end_time ? (
                                                <Link href={route('student.examination.resultExam', group.id)} className="rounded-xl bg-slate-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">Lihat hasil</Link>
                                            ) : grade?.start_time ? (
                                                <Link href={route('student.examination.show', { id: group.id, page: 1 })} className="rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-700">Lanjutkan</Link>
                                            ) : (
                                                <Link href={route('student.examination.confirmation', group.id)} className="rounded-xl bg-teal-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800">Mulai ujian</Link>
                                            )}
                                        </div>
                                    </article>
                                )) : (
                                    <div className="rounded-2xl bg-white p-8 text-center text-slate-500 ring-1 ring-slate-200 md:col-span-2">Tidak ada ujian pada kategori ini.</div>
                                )}
                            </div>
                        </section>
                    </main>
                </div>
            </div>
        </>
    );
}
