import Button from '@/Components/Dashboard/Button';
import Pagination from '@/Components/Dashboard/Pagination';
import Search from '@/Components/Dashboard/Search';
import Table from '@/Components/Dashboard/Table';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, useForm } from '@inertiajs/react';
import { IconChevronsLeft, IconCirclePlus, IconDownload, IconPencilCog, IconTrash, IconUpload } from '@tabler/icons-react';
import toast from 'react-hot-toast';

const questionPreview = (value, limit = 110) => {
    const plainText = String(value || '').replace(/<[^>]*>/g, ' ').replace(/&nbsp;/gi, ' ').replace(/\s+/g, ' ').trim();
    return plainText.length > limit ? `${plainText.slice(0, limit)}...` : plainText;
};

export default function Show({ exam }) {
    const { data, setData, post, processing, errors } = useForm({ file: null });

    const importQuestions = (event) => {
        event.preventDefault();
        if (!data.file) {
            toast.error('Pilih file Excel terlebih dahulu.');
            return;
        }

        post(route('exams.questions.import', exam.id), {
            forceFormData: true,
            onSuccess: () => toast.success('Soal berhasil diimport.'),
        });
    };

    return (
        <>
            <Head title='Ujian' />
            <div className='mb-2'>
                <div className='flex justify-between items-center gap-2'>
                    <div className='flex flex-row gap-2 items-center'>
                        <Button
                            type={'link'}
                            icon={<IconChevronsLeft size={20} strokeWidth={1.5} />}
                            className={'border bg-white text-gray-700 dark:bg-gray-950 dark:border-gray-800 dark:text-gray-200'}
                            href={route('exams.index')}
                        />
                    </div>
                    {/* <div className='w-full md:w-4/12'>
                        <Search
                            url={route('exams.index')}
                            placeholder={'Cari data berdasarkan nama'}
                        />
                    </div> */}
                </div>
            </div>
            <div className="mb-5">
                <Table.Card title={'Data Ujian'}>
                    <Table>
                        <Table.Tbody>
                            <tr>
                                <Table.Td>Ujian</Table.Td>
                                <Table.Td>{exam.classroom.title}</Table.Td>
                            </tr>
                            <tr>
                                <Table.Td>Mata Pelajaran</Table.Td>
                                <Table.Td>{exam.lesson.title}</Table.Td>
                            </tr>
                            <tr>
                                <Table.Td>Ujian</Table.Td>
                                <Table.Td>{exam.title}</Table.Td>
                            </tr>
                            <tr>
                                <Table.Td>Durasi</Table.Td>
                                <Table.Td>{exam.duration}</Table.Td>
                            </tr>
                        </Table.Tbody>
                    </Table>
                </Table.Card>
            </div>

            <div className='mb-2'>
                <div className='flex justify-between items-center gap-2'>
                    <div className='flex flex-row gap-2 items-center'>
                        <Button
                            type={'link'}
                            icon={<IconCirclePlus size={20} strokeWidth={1.5} />}
                            className={'border bg-white text-gray-700 dark:bg-gray-950 dark:border-gray-800 dark:text-gray-200'}
                            label={'Tambah Soal'}
                            href={route('exams.questions.create', [exam.id])}
                            added={true}
                        />
                    </div>
                </div>
            </div>
            <div className='mb-5 rounded-xl border border-slate-200 bg-slate-50 p-4'>
                <div className='mb-3'>
                    <h3 className='text-sm font-semibold text-slate-800'>Import soal dari Excel</h3>
                    <p className='mt-1 text-xs text-slate-500'>Sheet 1 untuk pilihan ganda, pilihan ganda kompleks, urutan, dan benar/salah. Sheet 2 untuk essay.</p>
                </div>
                <div className='flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between'>
                    <form onSubmit={importQuestions} className='flex flex-col gap-2 sm:flex-row sm:items-end'>
                        <div>
                            <label className='mb-1 block text-xs font-semibold text-slate-600'>File Excel</label>
                            <input type='file' accept='.xlsx,.xls,.csv' onChange={(event) => setData('file', event.target.files?.[0] || null)} className='block w-full rounded-lg border border-slate-300 bg-white text-sm text-slate-600 file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:font-semibold' />
                            {errors.file && <p className='mt-1 text-xs text-rose-600'>{errors.file}</p>}
                        </div>
                        <button type='submit' disabled={processing} className='inline-flex items-center justify-center gap-2 rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-60'>
                            <IconUpload size={17} /> {processing ? 'Mengimport...' : 'Import Soal'}
                        </button>
                    </form>
                    <a href={route('exams.questions.template', exam.id)} className='inline-flex items-center justify-center gap-2 rounded-lg border border-teal-200 bg-white px-4 py-2.5 text-sm font-semibold text-teal-700 hover:bg-teal-50'>
                        <IconDownload size={17} /> Download Template Excel
                    </a>
                </div>
            </div>
            <Table.Card title={'Soal-soal Ujian'}>
                <Table>
                    <Table.Thead>
                        <tr>
                            <Table.Th className={'w-10'}>No</Table.Th>
                            <Table.Th>Soal</Table.Th>
                            <Table.Th></Table.Th>
                        </tr>
                    </Table.Thead>
                    <Table.Tbody>
                        {exam.questions.data.length ?
                            exam.questions.data.map((question, i) => (
                                <tr className='hover:bg-gray-100 dark:hover:bg-gray-900' key={question.id}>
                                    <Table.Td className='text-center'>
                                        {i + 1 + (exam.questions.current_page - 1) * exam.questions.per_page}
                                    </Table.Td>
                                    <Table.Td title={questionPreview(question.question, 500)}>{questionPreview(question.question)}</Table.Td>
                                    <Table.Td>
                                        <div className='flex gap-2'>
                                            <Button
                                                type={'edit'}
                                                icon={<IconPencilCog size={16} strokeWidth={1.5} />}
                                                className={'border bg-orange-100 border-orange-300 text-orange-500 hover:bg-orange-200 dark:bg-orange-950 dark:border-orange-800 dark:text-gray-300  dark:hover:bg-orange-900'}
                                                href={route('exams.questions.edit', [exam.id, question.id])}
                                            />
                                            <Button
                                                type={'delete'}
                                                icon={<IconTrash size={16} strokeWidth={1.5} />}
                                                className={'border bg-rose-100 border-rose-300 text-rose-500 hover:bg-rose-200 dark:bg-rose-950 dark:border-rose-800 dark:text-gray-300  dark:hover:bg-rose-900'}
                                                url={route('exams.questions.destroy', [exam.id, question.id])}
                                            />
                                        </div>
                                    </Table.Td>
                                </tr>
                            ))
                            :
                            <tr>
                                <Table.Td colSpan={3} className={'text-center'}>Tidak ada data</Table.Td>
                            </tr>
                        }
                    </Table.Tbody>
                </Table>
                {exam.questions.last_page > 1 && <Pagination links={exam.questions.links} />}
            </Table.Card>
        </>
    );
}

Show.layout = page => <DashboardLayout children={page} />
