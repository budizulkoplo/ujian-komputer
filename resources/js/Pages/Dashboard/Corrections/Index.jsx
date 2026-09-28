import DashboardLayout from '@/Layouts/DashboardLayout';
import Card from '@/Components/Dashboard/Card';
import InputSelect from '@/Components/Dashboard/InputSelect';
import Button from '@/Components/Dashboard/Button';
import Table from '@/Components/Dashboard/Table';
import { Head, router, useForm } from '@inertiajs/react';
import { IconCheck } from '@tabler/icons-react';

export default function Index({ exams = [], answers = [], selectedExamId = null }) {
    const selectedExam = exams.find((exam) => exam.id === selectedExamId) || null;

    const filter = (event) => {
        event.preventDefault();
        if (selectedExam) router.get(route('corrections.index', { exam_id: selectedExam.id }));
    };

    return <>
        <Head title="Koreksi Ujian" />
        <Card title="Koreksi Ujian">
            <form onSubmit={filter} className="mb-6 flex flex-col gap-4 md:flex-row md:items-end">
                <div className="w-full md:max-w-xl"><InputSelect label="Pilih ujian" data={exams} selected={selectedExam} setSelected={(value) => router.get(route('corrections.index', { exam_id: value.id }))} placeholder="Pilih ujian" errors={null} displayKey="title" searchable /></div>
                <Button type="submit" label="Tampilkan jawaban essay" icon={<IconCheck size={18} />} className="border bg-teal-700 text-white hover:bg-teal-800" />
            </form>

            {selectedExamId && !answers.length && <div className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">Belum ada jawaban essay yang perlu dikoreksi pada ujian ini.</div>}
            {!selectedExamId && <div className="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">Pilih ujian untuk melihat jawaban essay siswa.</div>}

            {answers.length > 0 && <Table.Card title="Jawaban Essay">
                <Table>
                    <Table.Thead><tr><Table.Th>Siswa</Table.Th><Table.Th>Soal</Table.Th><Table.Th>Jawaban siswa</Table.Th><Table.Th>Skor maksimal</Table.Th><Table.Th>Nilai</Table.Th><Table.Th>Aksi</Table.Th></tr></Table.Thead>
                    <Table.Tbody>{answers.map((answer) => <CorrectionRow key={answer.id} answer={answer} />)}</Table.Tbody>
                </Table>
            </Table.Card>}
        </Card>
    </>;
}

function CorrectionRow({ answer }) {
    const { data, setData, put, processing } = useForm({ score: answer.score ?? 0 });
    const save = (event) => { event.preventDefault(); put(route('corrections.update', answer.id), { preserveScroll: true }); };
    return <tr>
        <Table.Td><div className="font-semibold">{answer.student?.name}</div><div className="text-xs text-slate-500">{answer.student?.classroom?.title}</div></Table.Td>
        <Table.Td><div className="max-w-xs text-sm" dangerouslySetInnerHTML={{ __html: answer.question?.question || '-' }} /></Table.Td>
        <Table.Td><div className="max-w-xs whitespace-pre-wrap text-sm text-slate-600">{answer.answer_value || '-'}</div></Table.Td>
        <Table.Td>{answer.question?.max_score ?? 0}</Table.Td>
        <Table.Td><input type="number" min="0" max={answer.question?.max_score ?? 0} step="0.01" value={data.score} onChange={(event) => setData('score', event.target.value)} className="w-24 rounded-lg border-slate-300 text-sm text-slate-700" /></Table.Td>
        <Table.Td><button onClick={save} disabled={processing} className="rounded-lg bg-teal-700 px-3 py-2 text-xs font-semibold text-white hover:bg-teal-800">Simpan</button></Table.Td>
    </tr>;
}

Index.layout = page => <DashboardLayout children={page} />;
