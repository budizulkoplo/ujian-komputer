import { Head, Link } from '@inertiajs/react';
import { IconArrowLeft, IconClock, IconFileDescription, IconPlayerPlay } from '@tabler/icons-react';
import StudentLayout from '@/Layouts/StudentLayout';

export default function Confirmation({ exam_group: group, grade }) {
    const exam = group.exam;
    return <>
        <Head title="Konfirmasi Ujian" />
        <div className="mx-auto max-w-3xl py-6">
            <Link href={route('student.dashboard')} className="mb-5 inline-flex items-center gap-2 text-sm font-semibold text-teal-700"><IconArrowLeft size={17} /> Kembali ke daftar ujian</Link>
            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div className="bg-gradient-to-r from-teal-700 to-cyan-600 px-6 py-8 text-white sm:px-8">
                    <p className="text-sm font-semibold uppercase tracking-widest text-teal-100">Konfirmasi ujian</p>
                    <h1 className="mt-2 text-2xl font-bold">{exam.title}</h1>
                    <p className="mt-1 text-sm text-teal-100">{exam.lesson?.title} · Kelas {exam.classroom?.title}</p>
                </div>
                <div className="grid gap-4 p-6 sm:grid-cols-2 sm:p-8">
                    <Info icon={<IconClock size={20} />} label="Durasi" value={`${exam.duration} menit`} />
                    <Info icon={<IconFileDescription size={20} />} label="Sesi" value={group.exam_session?.title} />
                </div>
                <div className="border-t border-slate-100 bg-slate-50 p-6 sm:p-8">
                    <p className="text-sm leading-6 text-slate-600">Pastikan koneksi internet stabil. Waktu ujian mulai dihitung saat tombol mulai ditekan. Jawaban yang sudah disimpan dapat diperiksa kembali selama waktu masih tersedia.</p>
                    {grade?.end_time && <p className="mt-3 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-700">Ujian ini sudah selesai dikerjakan.</p>}
                    {grade?.is_locked && <p className="mt-3 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700">Ujian ini dikunci karena terdeteksi perpindahan tab atau aplikasi. Silakan hubungi guru/pengawas.</p>}
                    <div className="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <Link href={route('student.dashboard')} className="rounded-xl border border-slate-300 px-5 py-3 text-center text-sm font-semibold text-slate-700 hover:bg-white">Batal</Link>
                        {!grade?.end_time && !grade?.is_locked && <Link href={route('student.examination.startExam', group.id)} className="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-700 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-teal-800"><IconPlayerPlay size={17} /> Mulai ujian</Link>}
                    </div>
                </div>
            </div>
        </div>
    </>;
}

function Info({ icon, label, value }) {
    return <div className="flex items-center gap-3 rounded-xl border border-slate-200 p-4"><span className="rounded-lg bg-teal-50 p-2 text-teal-700">{icon}</span><div><p className="text-xs text-slate-500">{label}</p><p className="mt-1 text-sm font-semibold text-slate-800">{value || '-'}</p></div></div>;
}

Confirmation.layout = (page) => <StudentLayout>{page}</StudentLayout>;
