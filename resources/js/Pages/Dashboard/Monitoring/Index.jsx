import DashboardLayout from '@/Layouts/DashboardLayout';
import Card from '@/Components/Dashboard/Card';
import { Head, router, useForm } from '@inertiajs/react';
import { IconActivity, IconInfoCircle, IconList, IconRefresh, IconUsers } from '@tabler/icons-react';

const statusLabels = {
    in_progress: 'Sedang ujian',
    finished: 'Selesai',
    not_started: 'Belum mulai',
    locked: 'Dikunci',
};

export default function Index({ exam_sessions = [], selected_session: session, classes = [], participants = [], filters = {} }) {
    const { data, setData } = useForm({
        exam_session_id: filters.exam_session_id || '',
        classroom_id: filters.classroom_id || '',
        status: filters.status || 'all',
    });

    const submit = (event) => {
        event.preventDefault();
        router.get(route('monitoring.index'), data, { preserveState: true, preserveScroll: true });
    };

    const reset = () => {
        const next = { exam_session_id: data.exam_session_id, classroom_id: '', status: 'all' };
        setData(next);
        router.get(route('monitoring.index'), next, { preserveState: true, preserveScroll: true });
    };

    const count = (status) => participants.filter((participant) => participant.status === status).length;
    const totalCheats = participants.reduce((total, participant) => total + (participant.cheat_count || 0), 0);

    return <>
        <Head title="Monitoring Peserta" />
        <div className="space-y-6">
            <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-center"><div className="flex items-center gap-3"><div className="flex h-11 w-11 items-center justify-center rounded-xl bg-teal-50 text-teal-700"><IconUsers size={25} /></div><div><h1 className="text-2xl font-bold text-slate-900">Monitoring Peserta</h1><p className="text-sm text-slate-500">Pantau status peserta ujian secara terpusat.</p></div></div><button type="button" onClick={() => router.reload({ only: ['participants', 'selected_session', 'classes'] })} className="inline-flex items-center justify-center gap-2 rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800"><IconRefresh size={17} /> Refresh</button></div>

            <Card title={<span className="flex items-center gap-2"><IconActivity size={19} />Filter Ujian</span>}>
                <form onSubmit={submit} className="grid gap-4 md:grid-cols-[1.4fr_1fr_1fr_auto] md:items-end">
                    <label className="text-sm font-semibold text-slate-700">Pilih ujian<select value={data.exam_session_id} onChange={(event) => setData('exam_session_id', event.target.value)} className="mt-2 w-full rounded-lg border-slate-300 bg-white font-normal text-slate-700 focus:border-teal-500 focus:ring-teal-500"><option value="">Pilih ujian</option>{exam_sessions.map((item) => <option key={item.id} value={item.id}>{item.exam?.title} — {item.title}</option>)}</select></label>
                    <label className="text-sm font-semibold text-slate-700">Kelas<select value={data.classroom_id} onChange={(event) => setData('classroom_id', event.target.value)} className="mt-2 w-full rounded-lg border-slate-300 bg-white font-normal text-slate-700 focus:border-teal-500 focus:ring-teal-500"><option value="">Semua kelas</option>{classes.map((item) => <option key={item.id} value={item.id}>{item.title}</option>)}</select></label>
                    <label className="text-sm font-semibold text-slate-700">Status<select value={data.status} onChange={(event) => setData('status', event.target.value)} className="mt-2 w-full rounded-lg border-slate-300 bg-white font-normal text-slate-700 focus:border-teal-500 focus:ring-teal-500"><option value="all">Semua status</option><option value="in_progress">Sedang ujian</option><option value="locked">Dikunci</option><option value="finished">Selesai</option><option value="not_started">Belum mulai</option></select></label>
                    <div className="flex gap-2"><button type="submit" className="rounded-lg bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-800">Terapkan</button><button type="button" onClick={reset} className="rounded-lg bg-slate-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-600">Reset</button></div>
                </form>
            </Card>

            {session && <Card title={<span className="flex items-center gap-2"><IconInfoCircle size={19} />Informasi Ujian</span>}><div className="grid gap-4 text-sm md:grid-cols-4"><Info label="Judul ujian" value={session.exam?.title} strong /><Info label="Mata pelajaran" value={session.exam?.lesson?.title} /><Info label="Waktu ujian" value={`${formatDate(session.start_time)} — ${formatDate(session.end_time)}`} /><Info label="Durasi" value={`${session.exam?.duration || 0} menit`} /><Info label="Kelas target" value={session.exam?.classroom?.title} /></div></Card>}

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-6"><Stat label="Total Peserta" value={participants.length} color="blue" /><Stat label="Sedang Ujian" value={count('in_progress')} color="green" /><Stat label="Dikunci" value={count('locked')} color="red" /><Stat label="Selesai" value={count('finished')} color="cyan" /><Stat label="Belum Mulai" value={count('not_started')} color="slate" /><Stat label="Total Kecurangan" value={totalCheats} color="amber" /></div>

            <Card title={<span className="flex items-center gap-2"><IconList size={19} />Daftar Peserta Ujian</span>}><p className="mb-4 text-sm text-slate-500">Total siswa pada tampilan: {participants.length}{session?.exam?.classroom?.title ? ` | Kelas target: ${session.exam.classroom.title}` : ''}</p><div className="overflow-x-auto"><table className="w-full text-left text-sm"><thead className="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500"><tr><th className="px-4 py-3">Peserta</th><th className="px-4 py-3">Kelas</th><th className="px-4 py-3">Status</th><th className="px-4 py-3">Mulai</th><th className="px-4 py-3">Selesai</th><th className="px-4 py-3">Nilai</th><th className="px-4 py-3">Kecurangan</th></tr></thead><tbody className="divide-y divide-slate-100">{participants.length ? participants.map((participant) => <tr key={participant.id} className="hover:bg-slate-50"><td className="px-4 py-3"><div className="font-semibold text-slate-800">{participant.student?.name}</div><div className="text-xs text-slate-500">NISN {participant.student?.nisn}</div></td><td className="px-4 py-3 text-slate-600">{participant.classroom || '-'}</td><td className="px-4 py-3"><span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${statusClass(participant.status)}`}>{statusLabels[participant.status]}</span></td><td className="px-4 py-3 text-slate-600">{formatDate(participant.start_time)}</td><td className="px-4 py-3 text-slate-600">{formatDate(participant.end_time)}</td><td className="px-4 py-3 font-semibold text-slate-800">{participant.grade ?? '-'}</td><td className="px-4 py-3 text-slate-600">{participant.cheat_count || 0}</td></tr>) : <tr><td colSpan="7" className="px-4 py-16 text-center text-slate-500">Belum ada peserta untuk filter ini.</td></tr>}</tbody></table></div></Card>
        </div>
    </>;
}

function Info({ label, value, strong = false }) { return <div><p className="text-xs text-slate-500">{label}</p><p className={`mt-1 text-slate-700 ${strong ? 'font-bold' : ''}`}>{value || '-'}</p></div>; }
function Stat({ label, value, color }) { const colors = { blue: 'border-blue-500 text-blue-600', green: 'border-emerald-500 text-emerald-600', red: 'border-rose-500 text-rose-600', cyan: 'border-cyan-500 text-cyan-600', slate: 'border-slate-500 text-slate-600', amber: 'border-amber-500 text-amber-600' }; return <div className={`rounded-xl border bg-white p-4 text-center shadow-sm ${colors[color]}`}><p className="text-2xl font-bold">{value}</p><p className="mt-1 text-xs text-slate-500">{label}</p></div>; }
function statusClass(status) { return { in_progress: 'bg-emerald-50 text-emerald-700', finished: 'bg-cyan-50 text-cyan-700', locked: 'bg-rose-50 text-rose-700', not_started: 'bg-slate-100 text-slate-600' }[status] || 'bg-slate-100 text-slate-600'; }
function formatDate(value) { if (!value) return '-'; return new Date(value).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' }); }

Index.layout = page => <DashboardLayout children={page} />;
