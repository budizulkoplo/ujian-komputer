import { useMemo, useRef, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import Button from '@/Components/Dashboard/Button';
import Card from '@/Components/Dashboard/Card';
import Input from '@/Components/Dashboard/Input';
import Modal from '@/Components/Dashboard/Modal';
import Pagination from '@/Components/Dashboard/Pagination';
import Search from '@/Components/Dashboard/Search';
import Table from '@/Components/Dashboard/Table';
import { IconCirclePlus, IconFileSpreadsheet, IconPencil, IconTrash, IconUpload, IconUser } from '@tabler/icons-react';

const emptyData = { id: '', name: '', email: '', nip: '', password: '', password_confirmation: '', assignments: [], isUpdate: false, isOpen: false };

export default function Index({ teachers, lessons, classrooms, filters = {} }) {
    const { data, setData, post, transform, errors, processing } = useForm(emptyData);
    const importInput = useRef(null);
    const { data: importData, setData: setImportData, post: postImport, processing: importing, errors: importErrors, reset: resetImport } = useForm({ file: null });
    const [lessonSearch, setLessonSearch] = useState('');
    const filteredLessons = useMemo(() => lessons.filter((lesson) => lesson.title.toLowerCase().includes(lessonSearch.toLowerCase())), [lessons, lessonSearch]);

    transform((form) => ({
        ...form,
        _method: form.isUpdate ? 'PUT' : 'POST',
    }));

    const resetForm = () => {
        setData({ ...emptyData, assignments: [] });
        setLessonSearch('');
    };
    const toggleClassroom = (lessonId, classroomId) => {
        const current = data.assignments.find((assignment) => assignment.lesson_id === lessonId);
        const classroomIds = current?.classroom_ids || [];
        const nextClassroomIds = classroomIds.includes(classroomId) ? classroomIds.filter((id) => id !== classroomId) : [...classroomIds, classroomId];
        const next = data.assignments.filter((assignment) => assignment.lesson_id !== lessonId);
        if (nextClassroomIds.length) next.push({ lesson_id: lessonId, classroom_ids: nextClassroomIds });
        setData('assignments', next);
    };

    const submit = (event) => {
        event.preventDefault();
        post(data.isUpdate ? route('teachers.update', data.id) : route('teachers.store'), { onSuccess: resetForm });
    };

    const importTeachers = (event) => {
        event.preventDefault();
        postImport(route('teachers.import'), {
            forceFormData: true,
            onSuccess: () => {
                resetImport();
                if (importInput.current) importInput.current.value = '';
            },
        });
    };

    const edit = (teacher) => setData({
        ...data,
        id: teacher.id,
        name: teacher.user?.name || '',
        email: teacher.user?.email || '',
        nip: teacher.nip || '',
        password: '',
        password_confirmation: '',
        assignments: Object.values((teacher.assignments || []).reduce((result, assignment) => {
            result[assignment.lesson_id] ||= { lesson_id: assignment.lesson_id, classroom_ids: [] };
            result[assignment.lesson_id].classroom_ids.push(assignment.classroom_id);
            return result;
        }, {})),
        isUpdate: true,
        isOpen: true,
    });

    return <>
        <Head title="Data Guru" />
        <div className="mb-3 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
            <div className="flex flex-wrap gap-2">
                <Button type="button" label="Tambah Guru" icon={<IconCirclePlus size={19} />} className="w-fit border bg-white text-gray-700 hover:bg-gray-100" onClick={() => setData('isOpen', true)} />
                <a href={route('teachers.template')} className="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"><IconFileSpreadsheet size={18} /> Template</a>
                <a href={route('teachers.export')} className="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100"><IconFileSpreadsheet size={18} /> Export Excel</a>
            </div>
            <div className="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                <Search url={route('teachers.index')} placeholder="Cari nama, email, atau NIP" />
                <select value={filters.lesson_id || ''} onChange={(event) => router.get(route('teachers.index'), { search: filters.search || undefined, lesson_id: event.target.value || undefined }, { preserveState: true })} className="rounded-lg border-slate-300 bg-white text-sm text-slate-700 focus:border-teal-500 focus:ring-teal-500"><option value="">Semua mata pelajaran</option>{lessons.map((lesson) => <option key={lesson.id} value={lesson.id}>{lesson.title}</option>)}</select>
            </div>
        </div>

        <div className="mb-3 flex flex-col gap-2 rounded-lg border border-slate-200 bg-white p-3 sm:flex-row sm:items-center">
            <form onSubmit={importTeachers} className="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center">
                <input ref={importInput} type="file" accept=".xlsx,.xls,.csv" required onChange={(event) => setImportData('file', event.target.files?.[0] || null)} className="max-w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
                <Button type="submit" disabled={importing || !importData.file} className="bg-teal-700 text-white hover:bg-teal-800 disabled:opacity-50" icon={<IconUpload size={18} />} label={importing ? 'Mengimpor...' : 'Import Excel'} />
            </form>
            <p className="text-xs text-slate-500">Satu guru dapat memiliki beberapa baris mapel/kelas dengan email yang sama.</p>
        </div>
        {importErrors.file && <p className="mb-3 text-sm text-rose-600">{importErrors.file}</p>}

        <Card title="Data Guru">
            <Table>
                <Table.Thead><tr><Table.Th>Nama</Table.Th><Table.Th>Email</Table.Th><Table.Th>NIP</Table.Th><Table.Th>Mapel dan kelas</Table.Th><Table.Th>Aksi</Table.Th></tr></Table.Thead>
                <Table.Tbody>{teachers.data?.length ? teachers.data.map((teacher) => <tr key={teacher.id} className="hover:bg-slate-50"><Table.Td><div className="font-semibold">{teacher.user?.name}</div><div className="text-xs text-slate-500">Akun guru</div></Table.Td><Table.Td>{teacher.user?.email}</Table.Td><Table.Td>{teacher.nip || '-'}</Table.Td><Table.Td><div className="flex max-w-md flex-wrap gap-1">{teacher.lessons?.map((lesson) => <span key={lesson.id} className="rounded-full bg-teal-50 px-2 py-1 text-xs font-semibold text-teal-700">{lesson.title}: {(teacher.assignments || []).filter((assignment) => assignment.lesson_id === lesson.id).map((assignment) => assignment.classroom?.title).filter((title, index, values) => title && values.indexOf(title) === index).join(', ') || 'semua kelas'}</span>)}</div></Table.Td><Table.Td><div className="flex gap-2"><Button type="button" icon={<IconPencil size={17} />} className="border bg-white text-gray-700 hover:bg-gray-100" onClick={() => edit(teacher)} /><Button type="delete" icon={<IconTrash size={17} />} className="border border-rose-200 bg-rose-50 text-rose-600 hover:bg-rose-100" url={route('teachers.destroy', teacher.id)} /></div></Table.Td></tr>) : <Table.Empty colSpan={5} message="Belum ada data guru." />}</Table.Tbody>
            </Table>
            {teachers.links && <Pagination links={teachers.links} />}
        </Card>

        <Modal show={data.isOpen} maxWidth="2xl" onClose={resetForm} title={data.isUpdate ? 'Ubah Data Guru' : 'Tambah Data Guru'}>
            <form onSubmit={submit} className="space-y-4">
                <div className="grid gap-4 md:grid-cols-2">
                    <Input label="Nama guru" type="text" value={data.name} onChange={(event) => setData('name', event.target.value)} errors={errors.name} required />
                    <Input label="NIP (opsional)" type="text" value={data.nip} onChange={(event) => setData('nip', event.target.value)} errors={errors.nip} />
                    <Input label="Email login" type="email" value={data.email} onChange={(event) => setData('email', event.target.value)} errors={errors.email} required />
                    <Input label={data.isUpdate ? 'Password baru (opsional)' : 'Password login'} type="password" value={data.password} onChange={(event) => setData('password', event.target.value)} errors={errors.password} required={!data.isUpdate} />
                    <Input label="Konfirmasi password" type="password" value={data.password_confirmation} onChange={(event) => setData('password_confirmation', event.target.value)} errors={errors.password_confirmation} required={!data.isUpdate} />
                </div>
                <div><div className="mb-3 flex flex-col justify-between gap-2 sm:flex-row sm:items-center"><p className="text-sm font-semibold text-slate-700">Mata pelajaran dan kelas yang diampu</p><input type="search" value={lessonSearch} onChange={(event) => setLessonSearch(event.target.value)} placeholder="Cari mata pelajaran..." className="w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 sm:w-64 focus:border-teal-500 focus:ring-teal-500" /></div><div className="space-y-3">{filteredLessons.length ? filteredLessons.map((lesson) => { const selected = data.assignments.find((assignment) => assignment.lesson_id === lesson.id)?.classroom_ids || []; return <div key={lesson.id} className="rounded-xl border border-slate-200 bg-slate-50 p-3"><p className="mb-2 text-sm font-semibold text-slate-700">{lesson.title}</p><div className="flex flex-wrap gap-2">{classrooms.map((classroom) => <label key={classroom.id} className="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700"><input type="checkbox" checked={selected.includes(classroom.id)} onChange={() => toggleClassroom(lesson.id, classroom.id)} className="rounded border-slate-300 text-teal-600 focus:ring-teal-500" />{classroom.title}</label>)}</div></div>; }) : <div className="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-500">Mata pelajaran tidak ditemukan.</div>}</div>{errors.assignments && <p className="mt-1 text-xs text-rose-600">Pilih minimal satu mapel dan satu kelas.</p>}</div>
                <div className="flex justify-end gap-2"><button type="button" onClick={resetForm} className="rounded-lg border bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100">Batal</button><Button type="submit" label={processing ? 'Menyimpan...' : 'Simpan'} icon={<IconUser size={18} />} className="bg-teal-700 text-white hover:bg-teal-800" disabled={processing} /></div>
            </form>
        </Modal>
    </>;
}

Index.layout = page => <DashboardLayout children={page} />;
