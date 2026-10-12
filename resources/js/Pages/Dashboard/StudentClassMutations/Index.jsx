import { useMemo, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { IconArrowRight, IconDatabaseOff, IconUsers } from '@tabler/icons-react';
import Button from '@/Components/Dashboard/Button';
import Card from '@/Components/Dashboard/Card';
import InputSelect from '@/Components/Dashboard/InputSelect';
import DashboardLayout from '@/Layouts/DashboardLayout';

export default function Index({ classrooms = [], students = [], selectedClassroomId = null }) {
    const selectedSource = classrooms.find((classroom) => String(classroom.id) === String(selectedClassroomId)) || null;
    const [source, setSource] = useState(selectedSource);
    const [target, setTarget] = useState(null);
    const [search, setSearch] = useState('');
    const { data, setData, post, processing, errors } = useForm({
        from_classroom_id: selectedClassroomId || '',
        to_classroom_id: '',
        student_ids: [],
    });

    const availableTargets = classrooms.filter((classroom) => classroom.id !== source?.id);
    const filteredStudents = useMemo(() => {
        const keyword = search.trim().toLowerCase();
        if (!keyword) return students;

        return students.filter((student) => String(student.name || '').toLowerCase().includes(keyword)
            || String(student.nisn || '').toLowerCase().includes(keyword));
    }, [students, search]);
    const allVisibleSelected = filteredStudents.length > 0
        && filteredStudents.every((student) => data.student_ids.includes(student.id));

    const selectSource = (value) => {
        setSource(value);
        setTarget(null);
        setSearch('');
        setData({ from_classroom_id: value?.id || '', to_classroom_id: '', student_ids: [] });
        if (value?.id) {
            router.get(route('student_class_mutations.index', { classroom_id: value.id }), {}, { preserveScroll: true });
        }
    };

    const selectTarget = (value) => {
        setTarget(value);
        setData('to_classroom_id', value?.id || '');
    };

    const toggleStudent = (studentId) => {
        setData('student_ids', data.student_ids.includes(studentId)
            ? data.student_ids.filter((id) => id !== studentId)
            : [...data.student_ids, studentId]);
    };

    const toggleVisibleStudents = () => {
        if (allVisibleSelected) {
            setData('student_ids', data.student_ids.filter((id) => !filteredStudents.some((student) => student.id === id)));
            return;
        }

        setData('student_ids', [...new Set([...data.student_ids, ...filteredStudents.map((student) => student.id)])]);
    };

    const submit = (event) => {
        event.preventDefault();
        post(route('student_class_mutations.store'), {
            preserveScroll: true,
            onSuccess: () => {
                setTarget(null);
                setSearch('');
                setData('student_ids', []);
            },
        });
    };

    return <>
        <Head title="Mutasi Kelas Siswa" />
        <Card title="Mutasi Kelas Siswa" icon={<IconUsers size={20} strokeWidth={1.5} />}>
            <p className="mb-5 text-sm leading-6 text-slate-600">Pindahkan siswa secara massal, misalnya dari kelas VII ke kelas VIII. Pilih kelas asal, kelas tujuan, lalu centang siswa yang akan dipindahkan.</p>

            <div className="grid gap-4 lg:grid-cols-[1fr_auto_1fr] lg:items-end">
                <InputSelect label="Kelas asal" data={classrooms} selected={source} setSelected={selectSource} placeholder="Pilih kelas asal" errors={errors.from_classroom_id} searchable={true} displayKey="title" />
                <div className="hidden justify-center pb-1 text-slate-400 lg:flex"><IconArrowRight size={24} /></div>
                <InputSelect label="Kelas tujuan" data={availableTargets} selected={target} setSelected={selectTarget} placeholder="Pilih kelas tujuan" errors={errors.to_classroom_id} searchable={true} displayKey="title" />
            </div>

            {source ? <form onSubmit={submit} className="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div className="mb-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                    <div><h2 className="font-bold text-slate-800">Daftar siswa kelas {source.title}</h2><p className="text-sm text-slate-500">{students.length} siswa tersedia · {data.student_ids.length} siswa dipilih</p></div>
                    <input type="search" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Cari nama atau NISN..." className="w-full rounded-lg border-slate-300 bg-white text-sm sm:w-64" />
                </div>
                <div className="mb-3 flex items-center justify-between rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">
                    <label className="flex cursor-pointer items-center gap-2 font-semibold text-slate-700"><input type="checkbox" checked={allVisibleSelected} onChange={toggleVisibleStudents} disabled={!filteredStudents.length} className="rounded border-slate-300 text-teal-600 focus:ring-teal-500" /> Pilih semua yang tampil</label>
                    {errors.student_ids && <span className="text-xs text-rose-600">{errors.student_ids}</span>}
                </div>
                <div className="max-h-[45vh] overflow-y-auto rounded-lg border border-slate-200 bg-white">
                    {filteredStudents.length ? <div className="divide-y divide-slate-100">{filteredStudents.map((student) => <label key={student.id} className="flex cursor-pointer items-center gap-3 px-4 py-3 hover:bg-slate-50"><input type="checkbox" checked={data.student_ids.includes(student.id)} onChange={() => toggleStudent(student.id)} className="rounded border-slate-300 text-teal-600 focus:ring-teal-500" /><span className="min-w-0 flex-1"><b className="block text-sm text-slate-800">{student.name}</b><span className="text-xs text-slate-500">NISN: {student.nisn || '-'}</span></span></label>)}</div> : <div className="p-8 text-center text-sm text-slate-500"><IconDatabaseOff className="mx-auto mb-2" size={24} strokeWidth={1.5} />{students.length ? 'Siswa tidak ditemukan.' : 'Belum ada siswa di kelas ini.'}</div>}
                </div>
                <div className="mt-4 flex flex-col justify-between gap-3 sm:flex-row sm:items-center"><p className="text-xs text-slate-500">Siswa yang dipindahkan akan otomatis disinkronkan ke peserta sesi ujian aktif.</p><Button type="submit" disabled={processing || !data.to_classroom_id || !data.student_ids.length} label={processing ? 'Memindahkan...' : 'Pindahkan Siswa'} className="border bg-teal-700 text-white hover:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-50" /></div>
            </form> : <div className="mt-6 rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">Pilih kelas asal untuk menampilkan daftar siswa.</div>}
        </Card>
    </>;
}

Index.layout = page => <DashboardLayout children={page} />;
