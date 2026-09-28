import { Head, Link } from '@inertiajs/react';
import { IconArrowLeft, IconCheck, IconTrophy } from '@tabler/icons-react';
import StudentLayout from '@/Layouts/StudentLayout';

export default function Result({ exam_group: group, grade }) {
    return <><Head title="Hasil Ujian" /><div className="mx-auto max-w-2xl py-8"><div className="rounded-2xl bg-white p-8 text-center shadow-sm ring-1 ring-slate-200"><div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-teal-50 text-teal-700"><IconTrophy size={32} /></div><p className="mt-5 text-sm font-semibold uppercase tracking-widest text-teal-700">Ujian selesai</p><h1 className="mt-2 text-2xl font-bold text-slate-900">{group.exam.title}</h1><div className="mx-auto mt-7 flex h-32 w-32 items-center justify-center rounded-full border-8 border-teal-100 text-4xl font-bold text-teal-700">{grade.grade}</div><p className="mt-4 text-sm text-slate-500">Jawaban benar: {grade.total_correct}</p><Link href={route('student.dashboard')} className="mt-8 inline-flex items-center gap-2 rounded-xl bg-teal-700 px-5 py-3 text-sm font-semibold text-white hover:bg-teal-800"><IconArrowLeft size={17} /> Kembali ke dashboard</Link></div></div></>;
}

Result.layout = (page) => <StudentLayout>{page}</StudentLayout>;
