import { useEffect, useMemo, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { IconArrowLeft, IconArrowRight, IconCheck, IconClock, IconFlag, IconSend } from '@tabler/icons-react';
import StudentLayout from '@/Layouts/StudentLayout';

export default function Show({ id, page, exam_group: group, all_questions: allQuestions, question_active: activeAnswer, question_answered: answered, answer_order: answerOrder, duration }) {
    const question = activeAnswer?.question;
    const [remaining, setRemaining] = useState(duration?.duration || group.exam.duration * 60000);
    const initialAnswer = parseAnswer(activeAnswer?.answer_value, question?.type);
    const [value, setValue] = useState(initialAnswer);
    const [processing, setProcessing] = useState(false);

    useEffect(() => {
        const timer = window.setInterval(() => setRemaining((current) => Math.max(0, current - 1000)), 1000);
        return () => window.clearInterval(timer);
    }, []);

    useEffect(() => {
        if (remaining <= 0) finishExam();
    }, [remaining]);

    const displayOptions = useMemo(() => (answerOrder || []).map(Number).filter((number) => question?.[`option_${number}`]), [answerOrder, question]);
    const saveAndGo = (nextPage) => {
        setProcessing(true);
        router.post(route('student.examination.answerQuestion'), {
            exam_id: group.exam.id,
            exam_session_id: group.exam_session.id,
            question_id: question.id,
            duration: remaining,
            answer_value: value,
        }, {
            preserveScroll: true,
            onFinish: () => setProcessing(false),
            onSuccess: () => router.visit(route('student.examination.show', { id, page: nextPage })),
        });
    };
    const finishExam = () => router.post(route('student.examination.endExam'), { exam_id: group.exam.id, exam_session_id: group.exam_session.id, exam_group_id: id });

    if (!question) return null;
    return <>
        <Head title={`Ujian - ${group.exam.title}`} />
        <div className="mx-auto max-w-6xl py-4">
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                <div><p className="text-xs font-semibold uppercase tracking-wider text-teal-700">{group.exam.lesson?.title}</p><h1 className="mt-1 font-bold text-slate-900">{group.exam.title}</h1></div>
                <div className={`inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-bold ${remaining < 60000 ? 'bg-rose-100 text-rose-700' : 'bg-teal-50 text-teal-700'}`}><IconClock size={17} /> {formatTime(remaining)}</div>
            </div>
            <div className="grid gap-4 lg:grid-cols-[1fr_280px]">
                <main className="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-7">
                    <div className="mb-6 flex items-center justify-between"><span className="text-sm font-semibold text-slate-500">Pertanyaan {page} dari {allQuestions.length}</span><span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Skor {question.max_score || 10}</span></div>
                    <div className="question-content prose prose-slate max-w-none" dangerouslySetInnerHTML={{ __html: question.question }} />
                    {question.image && <img src={`/storage/${question.image}`} alt="Ilustrasi soal" className="question-content mt-5 h-auto max-h-80 max-w-full rounded-xl object-contain" />}
                    {question.video_url && <a href={question.video_url} target="_blank" rel="noreferrer" className="mt-4 inline-flex text-sm font-semibold text-teal-700 hover:underline">Buka video pendukung</a>}
                    <div className="mt-7">{question.type === 'essay' ? <textarea value={value || ''} onChange={(event) => setValue(event.target.value)} rows="7" placeholder="Tulis jawaban Anda..." className="w-full rounded-xl border-slate-300 focus:border-teal-500 focus:ring-teal-500" /> : question.type === 'true_false' ? <ChoiceList options={[['true', 'Benar'], ['false', 'Salah']]} value={value} onChange={setValue} type="radio" /> : question.type === 'multiple_choice_complex' ? <ChoiceList options={displayOptions.map((number) => [String(number), question[`option_${number}`]])} value={Array.isArray(value) ? value : []} onChange={setValue} type="checkbox" /> : question.type === 'ordering' ? <Ordering options={displayOptions.map((number) => [String(number), question[`option_${number}`]])} value={Array.isArray(value) ? value : []} onChange={setValue} /> : <ChoiceList options={displayOptions.map((number) => [String(number), question[`option_${number}`]])} value={value} onChange={setValue} type="radio" />}</div>
                    <div className="mt-8 flex flex-wrap justify-between gap-3 border-t border-slate-100 pt-5"><button type="button" disabled={page <= 1 || processing} onClick={() => saveAndGo(page - 1)} className="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 disabled:opacity-40"><IconArrowLeft size={17} /> Sebelumnya</button>{page < allQuestions.length ? <button type="button" disabled={processing} onClick={() => saveAndGo(page + 1)} className="inline-flex items-center gap-2 rounded-xl bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-800 disabled:opacity-40">Simpan & berikutnya <IconArrowRight size={17} /></button> : <button type="button" onClick={finishExam} className="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-700"><IconSend size={17} /> Selesai ujian</button>}</div>
                </main>
                <aside className="h-fit rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><div className="flex items-center justify-between"><h2 className="font-bold text-slate-900">Navigasi soal</h2><span className="text-xs text-slate-500">{answered}/{allQuestions.length} terjawab</span></div><div className="mt-4 grid grid-cols-5 gap-2">{allQuestions.map((answer, index) => <Link key={answer.id} href={route('student.examination.show', { id, page: index + 1 })} className={`flex h-9 items-center justify-center rounded-lg text-xs font-semibold ${answer.question_order === page ? 'bg-teal-700 text-white' : isAnswered(answer) ? 'bg-teal-50 text-teal-700' : 'bg-slate-100 text-slate-600'}`}>{index + 1}</Link>)}</div><button type="button" onClick={finishExam} className="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-rose-200 px-4 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50"><IconFlag size={17} /> Akhiri ujian</button></aside>
            </div>
        </div>
    </>;
}

function ChoiceList({ options, value, onChange, type }) {
    return <div className="space-y-3">{options.map(([key, label], index) => { const checked = type === 'checkbox' ? value.includes(key) : value === key; return <label key={key} className={`flex cursor-pointer items-start gap-3 rounded-xl border p-4 ${checked ? 'border-teal-500 bg-teal-50' : 'border-slate-200 hover:border-teal-300'}`}><input type={type} value={key} checked={checked} onChange={() => type === 'checkbox' ? onChange(checked ? value.filter((item) => item !== key) : [...value, key]) : onChange(key)} className="mt-1 text-teal-600 focus:ring-teal-500" /><span className="question-content prose prose-sm max-w-none" dangerouslySetInnerHTML={{ __html: label }} /></label>; })}</div>;
}

function Ordering({ options, value, onChange }) { return <div className="space-y-3"><p className="text-sm text-slate-500">Klik pilihan sesuai urutan yang benar.</p>{options.map(([key, label]) => { const position = value.indexOf(key); return <button type="button" key={key} onClick={() => onChange(position >= 0 ? value.filter((item) => item !== key) : [...value, key])} className={`flex w-full items-center gap-3 rounded-xl border p-4 text-left ${position >= 0 ? 'border-teal-500 bg-teal-50' : 'border-slate-200'}`}><span className="flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 text-sm font-bold">{position >= 0 ? position + 1 : ''}</span><span className="question-content prose prose-sm max-w-none" dangerouslySetInnerHTML={{ __html: label }} /></button>; })}</div>; }
function parseAnswer(answer, type) { if (!answer) return type === 'multiple_choice_complex' || type === 'ordering' ? [] : ''; if (type === 'multiple_choice_complex' || type === 'ordering') { try { return JSON.parse(answer); } catch { return []; } } return answer; }
function isAnswered(answer) { return answer.answer !== 0 || answer.answer_value !== null; }
function formatTime(milliseconds) { const seconds = Math.max(0, Math.floor(milliseconds / 1000)); return `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`; }
Show.layout = (page) => <StudentLayout>{page}</StudentLayout>;
