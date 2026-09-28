import { useEffect, useMemo, useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { CKEditor } from '@ckeditor/ckeditor5-react';
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import { IconPencilPlus, IconTrash } from '@tabler/icons-react';
import Button from '@/Components/Dashboard/Button';
import Card from '@/Components/Dashboard/Card';
import Input from '@/Components/Dashboard/Input';
import toast from 'react-hot-toast';

const types = [
    { value: 'multiple_choice', label: 'Pilihan ganda', hint: 'Satu jawaban benar.' },
    { value: 'multiple_choice_complex', label: 'Pilihan ganda kompleks', hint: 'Bisa memilih lebih dari satu jawaban.' },
    { value: 'essay', label: 'Essay', hint: 'Jawaban dikoreksi dan dinilai manual oleh guru.' },
    { value: 'ordering', label: 'Urutan', hint: 'Siswa menyusun pilihan sesuai urutan yang benar.' },
    { value: 'true_false', label: 'Benar / Salah', hint: 'Satu jawaban berupa Benar atau Salah.' },
];

const emptyOptions = { option_1: '', option_2: '', option_3: '', option_4: '', option_5: '' };

export default function QuestionForm({ exam, question = null }) {
    const initialType = question?.type || 'multiple_choice';
    const initialKey = question?.answer_key ?? (question?.answer ? [String(question.answer)] : []);
    const initialKeyArray = Array.isArray(initialKey) ? initialKey.map(String) : (initialKey ? [String(initialKey)] : []);
    const [orderKey, setOrderKey] = useState(initialKeyArray);
    const [complexKey, setComplexKey] = useState(initialKeyArray);
    const { data, setData, post, processing, errors } = useForm({
        question: question?.question || '',
        image: null,
        video_url: question?.video_url || '',
        type: initialType,
        max_score: question?.max_score || 10,
        ...emptyOptions,
        ...(question ? {
            option_1: question.option_1 || '', option_2: question.option_2 || '', option_3: question.option_3 || '',
            option_4: question.option_4 || '', option_5: question.option_5 || '',
        } : {}),
        answer_key: initialType === 'ordering' ? orderKey : initialType === 'multiple_choice_complex' ? complexKey : (initialKeyArray[0] || ''),
        _method: question ? 'PUT' : undefined,
    });

    useEffect(() => {
        if (data.type === 'ordering') setData('answer_key', orderKey);
        if (data.type === 'multiple_choice_complex') setData('answer_key', complexKey);
    }, [orderKey, complexKey, data.type]);

    const optionNumbers = useMemo(() => [1, 2, 3, 4, 5].filter((number) => data[`option_${number}`]?.trim()), [data]);
    const selectedType = types.find((type) => type.value === data.type);
    const setEditor = (field, value) => setData(field, value);

    const toggleComplex = (number) => {
        const value = String(number);
        const next = complexKey.includes(value) ? complexKey.filter((item) => item !== value) : [...complexKey, value];
        setComplexKey(next);
        setData('answer_key', next);
    };

    const addOrder = (number) => {
        const value = String(number);
        if (!orderKey.includes(value)) {
            const next = [...orderKey, value];
            setOrderKey(next);
            setData('answer_key', next);
        }
    };

    const removeOrder = (number) => {
        const next = orderKey.filter((item) => item !== String(number));
        setOrderKey(next);
        setData('answer_key', next);
    };

    const saveQuestion = (event) => {
        event.preventDefault();
        post(question ? route('exams.questions.update', [exam.id, question.id]) : route('exams.questions.store', exam.id), {
            onSuccess: () => toast.success('Soal berhasil disimpan'),
        });
    };

    return <>
        <Head title={question ? 'Ubah Soal' : 'Tambah Soal'} />
        <Card title={question ? 'Ubah Soal' : 'Tambah Soal'} footer={<Button type="submit" label={processing ? 'Menyimpan...' : 'Simpan'} icon={<IconPencilPlus size={18} />} disabled={processing} className="border bg-teal-700 text-white hover:bg-teal-800" />} form={saveQuestion}>
            <div className="mb-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div className="grid gap-4 md:grid-cols-[1fr_180px]">
                    <div>
                        <label className="mb-2 block text-sm font-semibold text-slate-700">Jenis soal</label>
                        <select value={data.type} onChange={(event) => setData('type', event.target.value)} className="w-full rounded-lg border-slate-300 bg-white text-sm text-slate-700 focus:border-teal-500 focus:ring-teal-500">
                            {types.map((type) => <option key={type.value} value={type.value}>{type.label}</option>)}
                        </select>
                        <p className="mt-2 text-xs text-slate-500">{selectedType?.hint}</p>
                    </div>
                    <Input label="Skor maksimal" type="number" min="1" value={data.max_score} errors={errors.max_score} onChange={(event) => setData('max_score', event.target.value)} />
                </div>
            </div>

            <EditorField label="Pertanyaan" value={data.question} error={errors.question} onChange={(value) => setEditor('question', value)} />

            <div className="mt-5 grid gap-4 md:grid-cols-2">
                <div><label className="mb-2 block text-sm font-semibold text-slate-700">Gambar</label><input type="file" accept="image/*" onChange={(event) => setData('image', event.target.files?.[0] || null)} className="block w-full rounded-lg border border-slate-300 bg-white text-sm text-slate-600 file:mr-3 file:border-0 file:bg-slate-100 file:px-4 file:py-2.5 file:text-sm file:font-semibold" />{question?.image && <p className="mt-2 text-xs text-slate-500">Gambar tersimpan: {question.image.split('/').pop()}</p>}{errors.image && <p className="mt-1 text-xs text-rose-600">{errors.image}</p>}</div>
                <Input label="Video URL" type="url" placeholder="URL YouTube" value={data.video_url} errors={errors.video_url} onChange={(event) => setData('video_url', event.target.value)} />
            </div>

            {data.type !== 'essay' && data.type !== 'true_false' && <div className="mt-6 rounded-xl border border-slate-200">
                <div className="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">{data.type === 'ordering' ? 'Pilihan yang akan diurutkan' : 'Opsi jawaban (A-E)'}</div>
                <div className="grid gap-4 p-4 md:grid-cols-2">
                    {[1, 2, 3, 4, 5].map((number) => <EditorField key={number} label={`Pilihan ${String.fromCharCode(64 + number)}`} value={data[`option_${number}`]} onChange={(value) => setEditor(`option_${number}`, value)} />)}
                </div>
            </div>}

            {data.type === 'multiple_choice' && <AnswerPanel title="Jawaban benar">
                <select value={data.answer_key || ''} onChange={(event) => setData('answer_key', event.target.value)} className="w-full rounded-lg border-slate-300 bg-white text-sm text-slate-700 focus:border-teal-500 focus:ring-teal-500">
                    <option value="">Pilih jawaban benar</option>{optionNumbers.map((number) => <option value={number} key={number}>Pilihan {String.fromCharCode(64 + number)}</option>)}
                </select>
            </AnswerPanel>}

            {data.type === 'multiple_choice_complex' && <AnswerPanel title="Jawaban benar (boleh lebih dari satu)"><div className="grid gap-2 sm:grid-cols-2">{optionNumbers.map((number) => <label key={number} className="flex items-center gap-2 rounded-lg border border-slate-200 bg-white p-3 text-sm"><input type="checkbox" checked={complexKey.includes(String(number))} onChange={() => toggleComplex(number)} className="rounded border-slate-300 text-teal-600 focus:ring-teal-500" /> Pilihan {String.fromCharCode(64 + number)}</label>)}</div></AnswerPanel>}

            {data.type === 'ordering' && <AnswerPanel title="Urutan jawaban benar"><div className="flex flex-wrap gap-2">{optionNumbers.map((number) => orderKey.includes(String(number)) ? <button type="button" key={number} onClick={() => removeOrder(number)} className="inline-flex items-center gap-1 rounded-lg bg-teal-700 px-3 py-2 text-sm font-semibold text-white">{orderKey.indexOf(String(number)) + 1}. {String.fromCharCode(64 + number)} <IconTrash size={14} /></button> : <button type="button" key={number} onClick={() => addOrder(number)} className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 hover:border-teal-500">Tambah {String.fromCharCode(64 + number)}</button>)}</div><p className="mt-2 text-xs text-slate-500">Klik pilihan sesuai urutan yang benar. Klik lagi untuk menghapus.</p></AnswerPanel>}

            {data.type === 'true_false' && <AnswerPanel title="Jawaban benar"><div className="grid gap-3 sm:grid-cols-2">{[['true', 'Benar'], ['false', 'Salah']].map(([value, label]) => <label key={value} className={`flex cursor-pointer items-center gap-3 rounded-lg border p-3 text-sm ${data.answer_key === value ? 'border-teal-500 bg-teal-50' : 'border-slate-200 bg-white'}`}><input type="radio" name="answer_key" value={value} checked={data.answer_key === value} onChange={(event) => setData('answer_key', event.target.value)} className="text-teal-600 focus:ring-teal-500" />{label}</label>)}</div></AnswerPanel>}

            {data.type === 'essay' && <div className="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">Jawaban essay akan tersimpan untuk diperiksa dan diberi nilai secara manual oleh guru.</div>}
        </Card>
    </>;
}

function EditorField({ label, value, onChange, error }) {
    return <div className="min-w-0"><label className="mb-2 block text-sm font-semibold text-slate-700">{label}</label><div className="overflow-hidden rounded-lg border border-slate-300"><CKEditor editor={ClassicEditor} data={value || ''} onChange={(_, editor) => onChange(editor.getData())} /></div>{error && <p className="mt-1 text-xs text-rose-600">{error}</p>}</div>;
}

function AnswerPanel({ title, children }) {
    return <div className="mt-6 rounded-xl border border-slate-200"><div className="border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">{title}</div><div className="p-4">{children}</div></div>;
}
