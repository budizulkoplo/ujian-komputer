import { Head, Link } from '@inertiajs/react';
import { IconArrowLeft, IconCheck, IconClock, IconTrophy, IconX } from '@tabler/icons-react';
import StudentLayout from '@/Layouts/StudentLayout';

const typeLabels = {
    multiple_choice: 'Pilihan ganda',
    multiple_choice_complex: 'Pilihan ganda kompleks',
    essay: 'Essay',
    ordering: 'Urutan',
    true_false: 'Benar atau salah',
};

export default function Result({ exam_group: group, grade, answer_details: answerDetails = [] }) {
    const earnedPoints = answerDetails.reduce((total, detail) => total + Number(detail.score || 0), 0);
    const maximumPoints = answerDetails.reduce((total, detail) => total + Number(detail.max_score || 0), 0);

    return (
        <>
            <Head title="Detail Hasil Ujian" />
            <div className="mx-auto max-w-5xl py-6">
                <section className="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
                    <div className="flex flex-col items-center justify-between gap-5 sm:flex-row">
                        <div>
                            <p className="text-sm font-semibold uppercase tracking-widest text-teal-700">Ujian selesai</p>
                            <h1 className="mt-2 text-2xl font-bold text-slate-900">{group.exam.title}</h1>
                            <p className="mt-2 text-sm text-slate-500">{group.exam.lesson?.title || 'Ujian'} · {answerDetails.length} soal</p>
                        </div>
                        <div className="flex items-center gap-4">
                            <div className="flex h-28 w-28 flex-col items-center justify-center rounded-full border-8 border-teal-100 text-teal-700">
                                <span className="text-3xl font-bold">{grade.grade}</span>
                                <span className="text-[10px] uppercase tracking-wider">Nilai</span>
                            </div>
                        </div>
                    </div>

                    <div className="mt-7 grid gap-3 sm:grid-cols-3">
                        <Summary label="Jawaban benar" value={grade.total_correct ?? 0} />
                        <Summary label="Poin diperoleh" value={`${formatNumber(earnedPoints)} / ${formatNumber(maximumPoints)}`} />
                        <Summary label="Status" value={Number(grade.grade || 0) >= 75 ? 'Lulus' : 'Tidak lulus'} accent={Number(grade.grade || 0) >= 75 ? 'text-emerald-600' : 'text-rose-600'} />
                    </div>
                </section>

                <div className="mt-6 flex items-center justify-between gap-3">
                    <div>
                        <h2 className="text-xl font-bold text-slate-900">Rincian jawaban</h2>
                        <p className="mt-1 text-sm text-slate-500">Periksa jawaban, kunci jawaban, dan poin setiap soal.</p>
                    </div>
                    <span className="hidden rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 sm:inline-flex">{answerDetails.length} soal</span>
                </div>

                <div className="mt-4 space-y-4">
                    {answerDetails.map((detail) => <AnswerDetail key={detail.number} detail={detail} />)}
                </div>

                <Link href={route('student.dashboard')} className="mt-7 inline-flex items-center gap-2 rounded-xl bg-teal-700 px-5 py-3 text-sm font-semibold text-white hover:bg-teal-800">
                    <IconArrowLeft size={17} /> Kembali ke dashboard
                </Link>
            </div>
        </>
    );
}

function Summary({ label, value, accent = 'text-slate-900' }) {
    return <div className="rounded-xl bg-slate-50 px-4 py-3 text-center ring-1 ring-slate-200"><p className={`text-lg font-bold ${accent}`}>{value}</p><p className="mt-1 text-xs text-slate-500">{label}</p></div>;
}

function AnswerDetail({ detail }) {
    const isEssay = detail.type === 'essay';
    const isCorrect = detail.is_correct;
    const statusClass = isEssay ? 'border-amber-200 bg-amber-50 text-amber-700' : isCorrect ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700';
    const statusText = isEssay ? (Number(detail.score || 0) > 0 ? 'Sudah dinilai' : 'Koreksi manual') : isCorrect ? 'Benar' : 'Salah';

    return (
        <article className="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50 px-5 py-3">
                <div className="flex items-center gap-2"><span className="flex h-8 w-8 items-center justify-center rounded-full bg-teal-700 text-sm font-bold text-white">{detail.number}</span><span className="text-xs font-semibold uppercase tracking-wider text-slate-500">{typeLabels[detail.type] || 'Soal'}</span></div>
                <div className="flex items-center gap-2"><span className={`inline-flex items-center gap-1 rounded-full border px-3 py-1 text-xs font-semibold ${statusClass}`}>{isEssay ? <IconClock size={14} /> : isCorrect ? <IconCheck size={14} /> : <IconX size={14} />} {statusText}</span><span className="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">{formatNumber(detail.score)} / {formatNumber(detail.max_score)} poin</span></div>
            </div>

            <div className="p-5 sm:p-6">
                <div className="question-content prose prose-slate max-w-none" dangerouslySetInnerHTML={{ __html: detail.question }} />
                {detail.image ? <img src={imageUrl(detail.image)} alt={`Gambar soal nomor ${detail.number}`} className="question-content mt-4 h-auto max-h-80 max-w-full rounded-xl object-contain" /> : null}

                <div className="mt-6 grid gap-4 md:grid-cols-2">
                    <AnswerBox title="Jawaban Anda" tone="slate"><AnswerValue detail={detail} value={detail.student_answer} empty="Tidak dijawab" /></AnswerBox>
                    <AnswerBox title={isEssay ? 'Keterangan' : 'Jawaban benar'} tone={isEssay ? 'amber' : isCorrect ? 'emerald' : 'teal'}><AnswerValue detail={detail} value={detail.correct_answer} empty={isEssay ? 'Dinilai secara manual oleh guru.' : 'Kunci jawaban belum tersedia.'} correct /></AnswerBox>
                </div>
            </div>
        </article>
    );
}

function AnswerBox({ title, tone, children }) {
    const tones = { slate: 'border-slate-200 bg-slate-50', amber: 'border-amber-200 bg-amber-50', emerald: 'border-emerald-200 bg-emerald-50', teal: 'border-teal-200 bg-teal-50' };
    return <div className={`rounded-xl border p-4 ${tones[tone] || tones.slate}`}><p className="mb-2 text-xs font-bold uppercase tracking-wider text-slate-500">{title}</p>{children}</div>;
}

function AnswerValue({ detail, value, empty, correct = false }) {
    if (value === null || value === '' || (Array.isArray(value) && value.length === 0)) return <p className="text-sm italic text-slate-500">{empty}</p>;
    if (detail.type === 'essay') return <p className="whitespace-pre-wrap text-sm leading-6 text-slate-700">{value}</p>;

    const keys = Array.isArray(value) ? value : [value];
    if (detail.type === 'true_false') return <p className="text-sm font-semibold text-slate-800">{String(value) === 'true' ? 'Benar' : 'Salah'}</p>;

    return <div className="space-y-2">{keys.map((key, index) => {
        const option = detail.options?.find((item) => String(item.key) === String(key));
        return <div key={`${key}-${index}`} className="flex items-start gap-2 text-sm text-slate-700"><span className="mt-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-white text-xs font-bold text-slate-600 ring-1 ring-slate-300">{option?.letter || key}</span><span className="question-content prose prose-sm max-w-none" dangerouslySetInnerHTML={{ __html: option?.label || `Pilihan ${key}` }} /></div>;
    })}</div>;
}

function imageUrl(path) {
    if (/^https?:\/\//i.test(path) || path.startsWith('/')) return path;
    return `/storage/${path}`;
}

function formatNumber(value) {
    const number = Number(value || 0);
    return Number.isInteger(number) ? String(number) : number.toFixed(2).replace(/0+$/, '').replace(/\.$/, '');
}

Result.layout = (page) => <StudentLayout>{page}</StudentLayout>;
