import DashboardLayout from '@/Layouts/DashboardLayout';
import Card from '@/Components/Dashboard/Card';
import { Head, router, useForm } from '@inertiajs/react';
import { IconAlertTriangle, IconArrowLeft, IconCheck, IconDeviceFloppy, IconPencil, IconSend, IconX } from '@tabler/icons-react';

const typeLabels = {
    multiple_choice: 'Pilihan Ganda',
    multiple_choice_complex: 'Pilihan Ganda Kompleks',
    essay: 'Essay',
    ordering: 'Urutan',
    true_false: 'Benar / Salah',
};

export default function Index({ exams = [], attempts = [], answers = [], selectedExamId = null, selectedAttemptKey = null }) {
    const selectedAttempt = attempts.find((attempt) => attempt.key === selectedAttemptKey);
    const publishForm = useForm({ exam_id: '' });
    const { post: publishResults, processing: publishing } = publishForm;
    const allReleased = attempts.length > 0 && attempts.every((attempt) => attempt.released);

    const selectExam = (event) => {
        const examId = event.target.value;
        if (examId) router.get(route('corrections.index', { exam_id: examId }));
    };

    const openCorrection = (attempt) => router.get(route('corrections.index', { exam_id: selectedExamId, attempt: attempt.key }), { preserveScroll: true });
    const backToStudents = () => router.get(route('corrections.index', { exam_id: selectedExamId }), { preserveScroll: true });
    const publish = () => {
        if (!selectedExamId || allReleased || !window.confirm('Publikasikan nilai seluruh siswa yang sudah menyelesaikan ujian ini?')) return;
        publishForm.transform(() => ({ exam_id: selectedExamId }));
        publishResults(route('corrections.publish'), { preserveScroll: true });
    };

    return <>
        <Head title="Koreksi Ujian" />
        <Card title="Koreksi Ujian">
            <div className="grid gap-4 md:grid-cols-1">
                <label className="block text-sm font-medium text-slate-700">Pilih ujian
                    <select value={selectedExamId || ''} onChange={selectExam} className="mt-2 w-full rounded-lg border-slate-300 text-sm">
                        <option value="">Pilih ujian</option>{exams.map((exam) => <option key={exam.id} value={exam.id}>{exam.title} — {exam.lesson?.title}</option>)}
                    </select>
                </label>
            </div>

            {!selectedExamId ? <Notice>Silakan pilih ujian untuk melihat jawaban siswa.</Notice> : null}
            {selectedExamId && !attempts.length ? <Notice>Belum ada siswa yang menyelesaikan ujian ini.</Notice> : null}
            {selectedExamId && attempts.length > 0 ? <div className="mt-5 flex flex-col gap-3 rounded-xl border border-sky-200 bg-sky-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div><p className="font-semibold text-sky-900">Publikasi nilai ujian</p><p className="mt-1 text-sm text-sky-800">Nilai siswa belum tampil sebelum dipublikasikan. Pastikan seluruh essay sudah dikoreksi.</p></div>
                <button type="button" onClick={publish} disabled={publishing || allReleased} className="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-sky-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-800 disabled:cursor-not-allowed disabled:opacity-60"><IconSend size={17} /> {allReleased ? 'Nilai sudah dipublikasikan' : publishing ? 'Mempublikasikan...' : 'Publish Nilai'}</button>
            </div> : null}
            {selectedAttempt ? <div className="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div><p className="text-lg font-bold text-slate-900">{selectedAttempt.student?.name}</p><p className="text-sm text-slate-500">NISN {selectedAttempt.student?.nisn || '-'} · Sesi {selectedAttempt.exam_session?.title || '-'}</p></div>
                <div className="flex items-center gap-2"><span className={`rounded-full px-3 py-1 text-xs font-semibold ${selectedAttempt.released ? 'bg-emerald-100 text-emerald-700' : selectedAttempt.correction_status === 'completed' ? 'bg-sky-100 text-sky-700' : 'bg-amber-100 text-amber-700'}`}>{selectedAttempt.released ? 'Sudah dipublikasikan' : selectedAttempt.correction_status === 'completed' ? 'Sudah dikoreksi' : 'Belum dikoreksi'}</span><button type="button" onClick={backToStudents} className="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100"><IconArrowLeft size={15} /> Daftar siswa</button></div>
            </div> : null}
        </Card>

        {selectedExamId && !selectedAttempt && attempts.length > 0 ? <StudentList attempts={attempts} onCorrection={openCorrection} /> : null}
        {selectedAttempt && <div className="mt-5 space-y-5">{answers.map((answer) => <CorrectionCard key={answer.id} answer={answer} />)}</div>}
    </>;
}

function StudentList({ attempts, onCorrection }) {
    return <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div className="border-b border-slate-200 px-5 py-4"><h2 className="text-lg font-bold text-slate-900">Daftar Siswa</h2><p className="mt-1 text-sm text-slate-500">Pilih siswa untuk membuka seluruh soal dan jawaban yang sudah dikerjakan.</p></div>
        <div className="overflow-x-auto"><table className="min-w-full text-sm"><thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500"><tr><th className="px-5 py-3">No</th><th className="px-5 py-3">Siswa</th><th className="px-5 py-3">NISN</th><th className="px-5 py-3">Sesi</th><th className="px-5 py-3">Progress Koreksi</th><th className="px-5 py-3">Status</th><th className="px-5 py-3 text-right">Aksi</th></tr></thead><tbody className="divide-y divide-slate-100">{attempts.map((attempt, index) => <tr key={attempt.key} className="hover:bg-slate-50"><td className="px-5 py-4 text-slate-500">{index + 1}</td><td className="px-5 py-4 font-semibold text-slate-900">{attempt.student?.name || '-'}</td><td className="px-5 py-4 text-slate-600">{attempt.student?.nisn || '-'}</td><td className="px-5 py-4 text-slate-600">{attempt.exam_session?.title || '-'}</td><td className="px-5 py-4 text-slate-600">{attempt.manual_questions ? `${attempt.reviewed_questions}/${attempt.manual_questions} essay` : 'Otomatis'}</td><td className="px-5 py-4"><span className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ${attempt.released ? 'bg-emerald-100 text-emerald-700' : attempt.correction_status === 'completed' ? 'bg-sky-100 text-sky-700' : 'bg-amber-100 text-amber-700'}`}>{attempt.released ? 'Sudah dipublikasikan' : attempt.correction_status === 'completed' ? 'Sudah dikoreksi' : 'Belum dikoreksi'}</span></td><td className="px-5 py-4 text-right"><button type="button" onClick={() => onCorrection(attempt)} className="inline-flex items-center gap-1.5 rounded-lg bg-teal-700 px-3 py-2 text-xs font-semibold text-white hover:bg-teal-800"><IconPencil size={15} /> Koreksi</button></td></tr>)}</tbody></table></div>
    </div>;
}

function CorrectionCard({ answer }) {
    const isEssay = answer.type === 'essay';
    const { data, setData, put, processing } = useForm({ score: answer.score ?? 0, comment: answer.comment || '' });
    const save = (event) => {
        event.preventDefault();
        put(route('corrections.update', answer.id), { preserveScroll: true });
    };

    return <article className={`overflow-hidden rounded-xl border-2 ${isEssay ? 'border-amber-400' : answer.is_correct ? 'border-emerald-300' : 'border-slate-200'} bg-white shadow-sm`}>
        <div className={`flex items-center justify-between px-4 py-2.5 ${isEssay ? 'bg-amber-400' : answer.is_correct ? 'bg-emerald-100' : 'bg-slate-100'}`}>
            <span className="font-bold text-slate-900">Soal #{answer.number}</span><span className="rounded-full bg-cyan-500 px-2 py-1 text-[10px] font-bold uppercase text-white">{typeLabels[answer.type] || 'Soal'}</span>
        </div>
        <div className="space-y-5 p-4 sm:p-5">
            <section><p className="mb-2 font-bold text-slate-800">Pertanyaan:</p><div className="question-content rounded-lg bg-slate-50 p-4 text-sm text-slate-700" dangerouslySetInnerHTML={{ __html: answer.question || '-' }} /></section>
            {!isEssay ? <section><p className="mb-2 font-bold text-slate-800">Pilihan jawaban:</p><div className="space-y-2">{answer.options?.map((option) => <div key={option.key} className={`flex gap-2 rounded-lg border p-3 text-sm ${contains(answer.student_answer, option.key) ? 'border-blue-300 bg-blue-50' : 'border-slate-200'}`}><b>{option.letter}.</b><span className="question-content" dangerouslySetInnerHTML={{ __html: option.label }} />{contains(answer.correct_answer, option.key) ? <IconCheck className="ml-auto shrink-0 text-emerald-600" size={18} /> : null}</div>)}</div></section> : null}
            <section><p className="mb-2 font-bold text-slate-800">Jawaban siswa:</p><div className="question-content min-h-16 rounded-lg bg-slate-50 p-4 text-sm text-slate-700">{formatAnswer(answer.student_answer, answer)}</div></section>
            {!isEssay ? <section className="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800"><b>Kunci jawaban:</b> {formatAnswer(answer.correct_answer, answer)}</section> : <div className="flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800"><IconAlertTriangle size={18} /> Soal ini memerlukan koreksi manual oleh guru.</div>}
            {isEssay ? <form onSubmit={save} className="grid gap-4 md:grid-cols-[1fr_1fr_auto] md:items-end">
                <label className="block text-sm text-slate-700">Nilai (maks: {answer.max_score} poin)<input type="number" min="0" max={answer.max_score} step="0.01" value={data.score} onChange={(event) => setData('score', event.target.value)} className="mt-2 w-full rounded-lg border-slate-300" /></label>
                <label className="block text-sm text-slate-700">Komentar (opsional)<textarea value={data.comment} onChange={(event) => setData('comment', event.target.value)} placeholder="Berikan komentar untuk siswa..." rows="2" className="mt-2 w-full rounded-lg border-slate-300" /></label>
                <button type="submit" disabled={processing} className="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800 disabled:opacity-60"><IconDeviceFloppy size={17} /> Simpan Nilai</button>
            </form> : <div className="flex items-center gap-2 text-sm font-semibold text-slate-500">{answer.is_correct ? <IconCheck className="text-emerald-600" size={18} /> : <IconX className="text-rose-600" size={18} />} Nilai otomatis: {answer.score} / {answer.max_score}</div>}
        </div>
    </article>;
}

function Notice({ children }) { return <div className="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">{children}</div>; }
function contains(value, key) { return (Array.isArray(value) ? value : [value]).some((item) => String(item) === String(key)); }
function formatAnswer(value, answer) {
    if (value === null || value === '' || (Array.isArray(value) && !value.length)) return <span className="italic text-slate-400">Tidak dijawab</span>;
    if (answer.type === 'true_false') return String(value) === 'true' ? 'Benar' : 'Salah';
    const keys = Array.isArray(value) ? value : [value];
    return keys.map((key) => answer.options?.find((option) => String(option.key) === String(key))?.letter || key).join(', ');
}

Index.layout = page => <DashboardLayout children={page} />;
