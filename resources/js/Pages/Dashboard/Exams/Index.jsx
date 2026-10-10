import Checkbox from '@/Components/Dashboard/Checkbox';
import Button from '@/Components/Dashboard/Button';
import Card from '@/Components/Dashboard/Card';
import Table from '@/Components/Dashboard/Table';
import Pagination from '@/Components/Dashboard/Pagination';
import Widget from '@/Components/Dashboard/Widget';
import Modal from '@/Components/Dashboard/Modal';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { IconArrowsSort, IconBox, IconChartBar, IconChevronDown, IconChevronUp, IconCirclePlus, IconCopy, IconDatabaseOff, IconEye, IconPackage, IconPencilCheck, IconPencilCog, IconSearch, IconTrash, IconUserShield, IconUsers, IconWallet } from '@tabler/icons-react';
import Input from '@/Components/Dashboard/Input';
import InputSelect from '@/Components/Dashboard/InputSelect';
import { useState } from 'react';
import Textarea from '@/Components/Dashboard/TextArea';

export default function Index({ lessons, classrooms, exams, filters = {} }) {
    const { errors } = usePage().props;
    const [search, setSearch] = useState(filters.search || '');
    const [sort, setSort] = useState(filters.sort || 'created_at');
    const [direction, setDirection] = useState(filters.direction || 'desc');
    const [perPage, setPerPage] = useState(String(filters.per_page || 10));

    const tableRequest = (changes = {}) => {
        const params = {
            search: (changes.search ?? search) || undefined,
            sort: changes.sort ?? sort,
            direction: changes.direction ?? direction,
            per_page: changes.per_page ?? perPage,
            page: changes.page,
        };
        if (!params.page) delete params.page;
        router.get(route('exams.index'), params, { preserveState: true, preserveScroll: true });
    };

    const submitSearch = (event) => {
        event.preventDefault();
        tableRequest({ page: 1 });
    };

    const changeSort = (column) => {
        const nextDirection = sort === column && direction === 'asc' ? 'desc' : 'asc';
        setSort(column);
        setDirection(nextDirection);
        tableRequest({ sort: column, direction: nextDirection, page: 1 });
    };

    const changePerPage = (event) => {
        const value = event.target.value;
        setPerPage(value);
        tableRequest({ per_page: value, page: 1 });
    };

    const is_selected = [
        { id: 'Y', name: 'Ya' },
        { id: 'N', name: 'Tidak' },
    ]

    // define form helper inertia
    const { data, setData, transform, post } = useForm({
        id: '',
        classroom_id: '',
        semester: '',
        lesson_id: '',
        title: '',
        duration: '',
        description: '',
        random_question: '',
        random_answer: '',
        show_answer: '',
        isUpdate: false,
        isOpen: false,
    });
    const copyForm = useForm({ classroom_id: '' });

    // define set selected value

    // State for select inputs
    const [selectedLesson, setSelectedLesson] = useState(null)
    const [selectedClassroom, setSelectedClassroom] = useState(null)
    const [selectedRandomQuestion, setSelectedRandomQuestion] = useState(null)
    const [selectedRandomAnswer, setSelectedRandomAnswer] = useState(null)
    const [selectedShowAnswer, setSelectedShowAnswer] = useState(null)
    const [selectedCopyClassroom, setSelectedCopyClassroom] = useState(null)
    const [copySource, setCopySource] = useState(null)

    // Set gender
    const setSelectedLessonHandler = (value) => {
        setSelectedLesson(value)
        setData('lesson_id', value.id)
    }

    // Set classroom
    const setSelectedClassroomHandler = (value) => {
        setSelectedClassroom(value)
        setData('classroom_id', value.id)
    }

    // Set random question
    const setSelectedRandomQuestionHandler = (value) => {
        setSelectedRandomQuestion(value)
        setData('random_question', value.id)
    }

    // Set random answer
    const setSelectedRandomAnswerHandler = (value) => {
        setSelectedRandomAnswer(value)
        setData('random_answer', value.id)
    }

    // Set show answer
    const setSelectedShowAnswerHandler = (value) => {
        setSelectedShowAnswer(value)
        setData('show_answer', value.id)
    }

    const openCopyModal = (exam) => {
        setCopySource(exam)
        copyForm.reset()
        copyForm.clearErrors()
        setSelectedCopyClassroom(null)
    }

    const setSelectedCopyClassroomHandler = (value) => {
        setSelectedCopyClassroom(value)
        copyForm.setData('classroom_id', value.id)
    }

    const closeCopyModal = () => {
        setCopySource(null)
        copyForm.reset()
        copyForm.clearErrors()
        setSelectedCopyClassroom(null)
    }

    const submitCopy = (e) => {
        e.preventDefault()
        if (!copySource) return

        copyForm.post(route('exams.copy', copySource.id), {
            onSuccess: closeCopyModal,
        })
    }


    // transform data before submit
    transform((data) => ({
        title: data.title,
        lesson_id: data.lesson_id,
        classroom_id: data.classroom_id,
        semester: data.semester,
        duration: data.duration,
        description: data.description,
        random_question: data.random_question,
        random_answer: data.random_answer,
        show_answer: data.show_answer,
        ...(data.isUpdate === true ? { _method: 'put' } : {}),
    }))

    // define function create new classrooms
    const saveExam = async (e) => {
        e.preventDefault();
        post(route('exams.store'), {
            onSuccess: () => {
                setData({
                    classroom_id: '',
                    semester: '',
                    lesson_id: '',
                    title: '',
                    duration: '',
                    description: '',
                    random_question: '',
                    random_answer: '',
                    show_answer: '',
                    isUpdate: false,
                    isOpen: false,
                })
                setSelectedLesson(null);
                setSelectedClassroom(null);
                setSelectedRandomQuestion(null);
                setSelectedRandomAnswer(null);
                setSelectedShowAnswer(null);
            }
        });
    }

    // define function update role by id
    const updateExam = async (e) => {
        e.preventDefault();
        post(route('exams.update', data.id), {
            onSuccess: () => {
                setData({
                    id: '',
                    classroom_id: '',
                    semester: '',
                    lesson_id: '',
                    title: '',
                    duration: '',
                    description: '',
                    random_question: '',
                    random_answer: '',
                    show_answer: '',
                    isUpdate: false,
                    isOpen: false,
                });
                setSelectedLesson(null);
                setSelectedClassroom(null);
                setSelectedRandomQuestion(null);
                setSelectedRandomAnswer(null);
                setSelectedShowAnswer(null);
            }
        })
    }
    return (
        <>
            <Head title='Pelajar' />
            <div className='mb-2'>
                <div className='flex justify-between items-center gap-2'>
                    <div className='flex flex-row gap-2 items-center'>
                        <Button
                            type={'button'}
                            icon={<IconCirclePlus size={20} strokeWidth={1.5} />}
                            className={'border bg-white text-gray-700 dark:bg-gray-950 dark:border-gray-800 dark:text-gray-200'}
                            label={'Tambah Data Ujian'}
                            onClick={() => setData('isOpen', true)}
                            added={true}
                        />
                    </div>
                </div>
            </div>
            <div className="mb-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <form onSubmit={submitSearch} className="flex w-full lg:max-w-xl">
                        <div className="relative min-w-0 flex-1">
                            <IconSearch size={18} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                            <input type="search" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Cari judul, mata pelajaran, atau kelas..." className="w-full rounded-l-lg border-slate-300 py-2.5 pl-10 pr-4 text-sm focus:border-teal-500 focus:ring-teal-500" />
                        </div>
                        <button type="submit" className="inline-flex items-center gap-1.5 rounded-r-lg bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-800"><IconSearch size={16} /> Cari</button>
                    </form>
                    <div className="flex items-center gap-2 text-sm text-slate-600">
                        <span>Tampilkan</span>
                        <select value={perPage} onChange={changePerPage} className="rounded-lg border-slate-300 py-2 text-sm focus:border-teal-500 focus:ring-teal-500">
                            {[10, 25, 50].map((value) => <option key={value} value={value}>{value}</option>)}
                        </select>
                        <span>data</span>
                    </div>
                </div>
                <div className="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                    <span>{exams.total ? `Menampilkan ${exams.from}–${exams.to} dari ${exams.total} ujian` : 'Tidak ada data ujian'}</span>
                    <span>Gunakan judul kolom untuk mengurutkan data.</span>
                </div>
            </div>
            <Modal
                show={data.isOpen}
                onClose={() =>
                    setData({
                        isOpen: false,
                        id: '',
                        classroom_id: '',
                        semester: '',
                        lesson_id: '',
                        title: '',
                        duration: '',
                        description: '',
                        random_question: '',
                        random_answer: '',
                        show_answer: '',
                        isUpdate: false,
                    })
                }
                title={`${data.isUpdate === true ? 'Ubah Data Ujain' : 'Tambah Data Baru'}`}
                icon={<IconUserShield size={20} strokeWidth={1.5} />}
            >
                <form onSubmit={data.isUpdate === true ? updateExam : saveExam}>
                    <div className="mb-4">
                        <Input
                            label={'Nama Ujian'}
                            type="text"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            className="w-full px-3 py-2 border rounded"
                            required
                            errors={errors.title}
                        />
                    </div>
                    <div className="mb-4">
                        <Input
                            label={'Durasi'}
                            type="number"
                            value={data.duration}
                            onChange={(e) => setData('duration', e.target.value)}
                            className="w-full px-3 py-2 border rounded"
                            required
                            errors={errors.duration}
                        />
                    </div>
                    <div className="mb-4">
                        <Textarea
                            label={'Deskripsi'}
                            type="text"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            className="w-full px-3 py-2 border rounded"
                            required
                            errors={errors.description}
                        />
                    </div>
                    <div className="mb-4">
                        <InputSelect
                            label="Mata Pelajaran"
                            data={lessons}
                            selected={selectedLesson}
                            setSelected={setSelectedLessonHandler}
                            placeholder="Pilih Mata Pelajaran"
                            errors={errors.lesson_id}
                            multiple={false}
                            searchable
                            displayKey='title'
                        />
                    </div>
                    <div className="mb-4">
                        <label className="mb-2 block text-sm font-semibold text-slate-700">Semester</label>
                        <select value={data.semester} onChange={(e) => setData('semester', e.target.value)} className="w-full rounded-lg border-slate-300 bg-white text-sm text-slate-700 focus:border-teal-500 focus:ring-teal-500" required>
                            <option value="">Pilih semester</option>
                            <option value="1">Semester 1</option>
                            <option value="2">Semester 2</option>
                        </select>
                        {errors.semester && <p className="mt-1 text-xs text-rose-600">{errors.semester}</p>}
                    </div>
                    <div className="mb-4">
                        <InputSelect
                            label="Ruang Kelas"
                            data={classrooms}
                            selected={selectedClassroom}
                            setSelected={setSelectedClassroomHandler}
                            placeholder="Pilih Ruang Kelas"
                            errors={errors.classroom_id}
                            multiple={false}
                            searchable={true}
                            displayKey='title'
                        />
                    </div>
                    <div className="mb-4">
                        <InputSelect
                            label="Acak Soal"
                            data={is_selected}
                            selected={selectedRandomQuestion}
                            setSelected={setSelectedRandomQuestionHandler}
                            placeholder="Soal Akan Diacak?"
                            errors={errors.random_question}
                            multiple={false}
                            searchable={false}
                            displayKey='name'
                        />
                    </div>
                    <div className="mb-4">
                        <InputSelect
                            label="Acak Jawaban"
                            data={is_selected}
                            selected={selectedRandomAnswer}
                            setSelected={setSelectedRandomAnswerHandler}
                            placeholder="Jawaban Akan Diacak?"
                            errors={errors.random_answer}
                            multiple={false}
                            searchable={false}
                            displayKey='name'
                        />
                    </div>
                    <div className="mb-4">
                        <InputSelect
                            label="Tampilkan Jawaban"
                            data={is_selected}
                            selected={selectedShowAnswer}
                            setSelected={setSelectedShowAnswerHandler}
                            placeholder="Tampilkan Jawaban?"
                            errors={errors.show_answer}
                            multiple={false}
                            searchable={false}
                            displayKey='name'
                        />
                    </div>
                    <Button
                        type={'submit'}
                        icon={<IconPencilCheck size={20} strokeWidth={1.5} />}
                        className={'border bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-950 dark:border-gray-800 dark:text-gray-200'}
                        added={true}
                        label={data.isUpdate ? 'Update' : 'Save'}
                    />
                </form>
            </Modal>
            <Modal
                show={copySource !== null}
                onClose={closeCopyModal}
                title="Salin Ujian"
            >
                {copySource && (
                    <form onSubmit={submitCopy}>
                        <p className="mb-4 text-sm text-gray-600 dark:text-gray-300">
                            Salin ujian “{copySource.title}” dari kelas {copySource.classroom.title} beserta pengaturan dan seluruh soalnya.
                        </p>
                        <div className="mb-4">
                            <InputSelect
                                label="Kelas tujuan"
                                data={copySource.copy_classrooms}
                                selected={selectedCopyClassroom}
                                setSelected={setSelectedCopyClassroomHandler}
                                placeholder="Pilih kelas tujuan"
                                errors={copyForm.errors.classroom_id}
                                searchable
                                displayKey="title"
                            />
                        </div>
                        <Button
                            type="submit"
                            icon={<IconCopy size={18} strokeWidth={1.5} />}
                            className="border bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-950 dark:border-gray-800 dark:text-gray-200"
                            label={copyForm.processing ? 'Menyalin...' : 'Salin Ujian'}
                            disabled={!copyForm.data.classroom_id || copyForm.processing}
                        />
                    </form>
                )}
            </Modal>
            <Table.Card title={'Data Ujian'}>
                <Table>
                    <Table.Thead>
                        <tr>
                            <Table.Th className={'w-10'}>No</Table.Th>
                            <SortHeader label="Kelas" column="classroom" sort={sort} direction={direction} onSort={changeSort} />
                            <SortHeader label="Mata Pelajaran" column="lesson" sort={sort} direction={direction} onSort={changeSort} />
                            <SortHeader label="Semester" column="semester" sort={sort} direction={direction} onSort={changeSort} />
                            <SortHeader label="Ujian" column="title" sort={sort} direction={direction} onSort={changeSort} />
                            <SortHeader label="Durasi" column="duration" sort={sort} direction={direction} onSort={changeSort} />
                            <SortHeader label="Jumlah Soal" column="question_count" sort={sort} direction={direction} onSort={changeSort} />
                            <Table.Th></Table.Th>
                        </tr>
                    </Table.Thead>
                    <Table.Tbody>
                        {exams.data.length ?
                            exams.data.map((exam, i) => (
                                <tr className='hover:bg-gray-100 dark:hover:bg-gray-900' key={exam.id}>
                                    <Table.Td className='text-center'>
                                        {++i + (exams.current_page - 1) * exams.per_page}
                                    </Table.Td>
                                    <Table.Td>
                                        {exam.classroom.title}
                                    </Table.Td>
                                    <Table.Td>
                                        {exam.lesson.title}
                                    </Table.Td>
                                    <Table.Td>
                                        {exam.semester ? `Semester ${exam.semester}` : '-'}
                                    </Table.Td>
                                    <Table.Td>
                                        {exam.title}
                                    </Table.Td>
                                    <Table.Td>
                                        {exam.duration}
                                    </Table.Td>
                                    <Table.Td>
                                        {exam.questions_count}
                                    </Table.Td>
                                    <Table.Td>
                                        <div className='flex gap-2 md:justify-center'>
                                            <Button
                                                type={'link'}
                                                // icon={<IconEye size={16} strokeWidth={1.5} />}
                                                className={'border bg-green-100 border-green-300 text-green-500 hover:bg-green-200 dark:bg-green-950 dark:border-green-800 dark:text-gray-300  dark:hover:bg-green-900'}
                                                href={route('exams.show', exam.id)}
                                                label={'Detail'}
                                            />
                                            {exam.copy_classrooms.length > 0 && (
                                                <Button
                                                    type={'modal'}
                                                    icon={<IconCopy size={16} strokeWidth={1.5} />}
                                                    className={'border bg-sky-100 border-sky-300 text-sky-600 hover:bg-sky-200 dark:bg-sky-950 dark:border-sky-800 dark:text-gray-300 dark:hover:bg-sky-900'}
                                                    onClick={() => openCopyModal(exam)}
                                                    title="Salin ujian ke kelas lain pada tingkatan yang sama"
                                                    aria-label="Salin ujian"
                                                />
                                            )}
                                            <Button
                                                type={'modal'}
                                                icon={<IconPencilCog size={16} strokeWidth={1.5} />}
                                                className={'border bg-orange-100 border-orange-300 text-orange-500 hover:bg-orange-200 dark:bg-orange-950 dark:border-orange-800 dark:text-gray-300  dark:hover:bg-orange-900'}
                                                onClick={() => {
                                                    const selectedStudentLesson = lessons.find(g => g.id === exam.lesson_id);
                                                    const selectedStudentClassroom = classrooms.find(c => c.id === exam.classroom_id);
                                                    const selectedRandomQuestion = is_selected.find(r => r.id === exam.random_question);
                                                    const selectedRandomAnswer = is_selected.find(r => r.id === exam.random_answer);
                                                    const selectedShowAnswer = is_selected.find(r => r.id === exam.show_answer);

                                                    setData({
                                                        id: exam.id,
                                                        classroom_id: exam.classroom_id,
                                                        semester: exam.semester || '',
                                                        lesson_id: exam.lesson_id,
                                                        title: exam.title,
                                                        duration: exam.duration,
                                                        description: exam.description,
                                                        random_question: exam.random_question,
                                                        random_answer: exam.random_answer,
                                                        show_answer: exam.show_answer,
                                                        isUpdate: true,
                                                        isOpen: true,
                                                    });
                                                    setSelectedLesson(selectedStudentLesson);
                                                    setSelectedClassroom(selectedStudentClassroom);
                                                    setSelectedRandomQuestion(selectedRandomQuestion);
                                                    setSelectedRandomAnswer(selectedRandomAnswer);
                                                    setSelectedShowAnswer(selectedShowAnswer);
                                                }}
                                            />
                                            <Button
                                                type={'delete'}
                                                icon={<IconTrash size={16} strokeWidth={1.5} />}
                                                className={'border bg-rose-100 border-rose-300 text-rose-500 hover:bg-rose-200 dark:bg-rose-950 dark:border-rose-800 dark:text-gray-300  dark:hover:bg-rose-900'}
                                                url={route('exams.destroy', exam.id)}
                                            />
                                        </div>
                                    </Table.Td>
                                </tr>
                            )) :
                            <Table.Empty colSpan={8} message={
                                <>
                                    <div className='flex justify-center items-center text-center mb-2'>
                                        <IconDatabaseOff size={24} strokeWidth={1.5} className='text-gray-500 dark:text-white' />
                                    </div>
                                    <span className='text-gray-500'>Data ujian</span> <span className='text-rose-500 underline underline-offset-2'>tidak ditemukan.</span>
                                </>
                            } />
                        }
                    </Table.Tbody>
                </Table>
            </Table.Card>
            {exams.links && <Pagination links={exams.links} />}
        </>
    );
}

Index.layout = page => <DashboardLayout children={page} />

function SortHeader({ label, column, sort, direction, onSort }) {
    const active = sort === column;
    return <Table.Th>
        <button type="button" onClick={() => onSort(column)} className="inline-flex items-center gap-1.5 font-semibold text-slate-700 hover:text-teal-700">
            {label}
            {active ? direction === 'asc' ? <IconChevronUp size={15} /> : <IconChevronDown size={15} /> : <IconArrowsSort size={14} className="text-slate-400" />}
        </button>
    </Table.Th>;
}
