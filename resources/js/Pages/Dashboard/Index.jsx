import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import { IconArrowUpRight, IconBooks, IconBolt, IconHistory, IconReport, IconSettings, IconUserPlus, IconUsers } from '@tabler/icons-react';

const statStyles = {
    blue: { border: 'border-blue-500', icon: 'bg-blue-600', iconComponent: <IconUsers size={24} /> },
    green: { border: 'border-emerald-500', icon: 'bg-emerald-600', iconComponent: <IconUserPlus size={24} /> },
    cyan: { border: 'border-cyan-500', icon: 'bg-cyan-500', iconComponent: <IconReport size={24} /> },
    amber: { border: 'border-amber-500', icon: 'bg-amber-500', iconComponent: <IconBooks size={24} /> },
};

export default function Dashboard({ exams, students, teachers, lessons, recentExams, recentTeachers }) {
    const { props } = usePage();
    const auth = props.auth || {};
    const canManageMasterData = auth.teacher !== true;

    const stats = [
        { label: 'SISWA', value: students, color: 'blue' },
        { label: 'GURU', value: teachers, color: 'green' },
        { label: 'TOTAL UJIAN', value: exams, color: 'cyan' },
        { label: 'MATA PELAJARAN', value: lessons, color: 'amber' },
    ];

    const actions = [
        { label: 'Kelola Siswa', href: route('students.index'), color: 'blue', icon: <IconUsers size={18} />, show: canManageMasterData },
        { label: 'Kelola Guru', href: route('teachers.index'), color: 'green', icon: <IconUserPlus size={18} />, show: canManageMasterData },
        { label: 'Kelola Mata Pelajaran', href: route('lessons.index'), color: 'cyan', icon: <IconBooks size={18} />, show: true },
        { label: 'Kelola Ujian', href: route('exams.index'), color: 'amber', icon: <IconReport size={18} />, show: true },
        { label: 'Kelola Soal', href: route('exams.index'), color: 'slate', icon: <IconBolt size={18} />, show: true },
        { label: 'Pengaturan', href: route('settings.index'), color: 'slate', icon: <IconSettings size={18} />, show: canManageMasterData },
    ];

    return <>
        <Head title="Dashboard" />
        <div className="space-y-6">
            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                {stats.map((stat) => {
                    const style = statStyles[stat.color];
                    return <div key={stat.label} className={`flex items-center justify-between rounded-2xl border-2 ${style.border} bg-white p-5 shadow-sm dark:bg-gray-950`}>
                        <div><p className="text-sm font-medium tracking-wide text-slate-500">{stat.label}</p><p className="mt-1 text-3xl font-bold text-slate-900 dark:text-white">{stat.value}</p></div>
                        <div className={`flex h-14 w-14 items-center justify-center rounded-full text-white ${style.icon}`}>{style.iconComponent}</div>
                    </div>;
                })}
            </div>

            <div className="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(300px,1fr)]">
                <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-gray-950">
                    <PanelHeader icon={<IconHistory size={21} />} title="Ujian Terbaru" href={route('exams.index')} />
                    <div className="divide-y divide-slate-200 dark:divide-slate-800">
                        {recentExams.length ? recentExams.map((exam) => <div key={exam.id} className="flex items-center gap-4 px-5 py-4">
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-600 text-2xl text-white">+</div>
                            <div className="min-w-0 flex-1"><p className="truncate text-sm font-semibold uppercase tracking-wide text-slate-700 dark:text-slate-200">{exam.title}</p><p className="truncate text-sm text-slate-500">{exam.lesson?.title || '-'} · {formatRelativeDate(exam.created_at)}</p></div>
                            <span className="rounded-md bg-blue-600 px-2 py-1 text-xs font-semibold text-white">Baru</span>
                        </div>) : <Empty text="Belum ada ujian." />}
                    </div>
                </section>

                <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-gray-950">
                    <PanelHeader icon={<IconBolt size={21} />} title="Quick Actions" />
                    <div className="space-y-2 p-4">
                        {actions.filter((action) => action.show).map((action) => <Link key={action.label} href={action.href} className={`flex items-center gap-2 rounded-lg border px-3 py-2.5 text-sm font-medium transition hover:bg-slate-50 dark:hover:bg-slate-900 ${action.color === 'blue' ? 'border-blue-500 text-blue-600' : action.color === 'green' ? 'border-emerald-500 text-emerald-600' : action.color === 'cyan' ? 'border-cyan-500 text-cyan-600' : action.color === 'amber' ? 'border-amber-500 text-amber-600' : 'border-slate-500 text-slate-600'}`}>{action.icon}{action.label}<IconArrowUpRight size={15} className="ml-auto" /></Link>)}
                    </div>
                </section>
            </div>

            <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-gray-950">
                <PanelHeader icon={<IconUserPlus size={21} />} title="Guru Terbaru" href={canManageMasterData ? route('teachers.index') : undefined} />
                <div className="divide-y divide-slate-200 dark:divide-slate-800">
                    {recentTeachers.length ? recentTeachers.map((teacher) => <div key={teacher.id} className="flex items-center gap-4 px-5 py-4">
                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white"><IconUserPlus size={20} /></div>
                        <div className="flex-1"><p className="text-sm font-semibold uppercase tracking-wide text-slate-700 dark:text-slate-200">{teacher.user?.name || '-'}</p><p className="text-sm text-slate-500">Bergabung {formatRelativeDate(teacher.created_at)}</p></div>
                        <span className="rounded-md bg-emerald-600 px-2 py-1 text-xs font-semibold text-white">Baru</span>
                    </div>) : <Empty text="Belum ada data guru." />}
                </div>
            </section>
        </div>
    </>;
}

function PanelHeader({ icon, title, href }) {
    return <div className="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800"><h2 className="flex items-center gap-2 text-lg font-semibold text-slate-700 dark:text-slate-200">{icon}{title}</h2>{href && <Link href={href} className="rounded border border-blue-500 px-3 py-1.5 text-xs font-semibold text-blue-600 hover:bg-blue-50">Lihat Semua</Link>}</div>;
}

function Empty({ text }) {
    return <div className="px-5 py-8 text-center text-sm text-slate-500">{text}</div>;
}

function formatRelativeDate(value) {
    if (!value) return '-';
    const date = new Date(value);
    const days = Math.max(0, Math.floor((Date.now() - date.getTime()) / 86400000));
    if (days === 0) return 'Dibuat hari ini';
    if (days === 1) return 'Dibuat kemarin';
    if (days < 7) return `Dibuat ${days} hari yang lalu`;
    if (days < 30) return `Dibuat ${Math.floor(days / 7)} minggu yang lalu`;
    return `Dibuat ${Math.floor(days / 30)} bulan yang lalu`;
}

Dashboard.layout = (page) => <DashboardLayout>{page}</DashboardLayout>;
