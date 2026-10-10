import Button from '@/Components/Dashboard/Button';
import Card from '@/Components/Dashboard/Card';
import InputSelect from '@/Components/Dashboard/InputSelect';
import Pagination from '@/Components/Dashboard/Pagination';
import Search from '@/Components/Dashboard/Search';
import Table from '@/Components/Dashboard/Table'
import DashboardLayout from '@/Layouts/DashboardLayout'
import { Head, router, useForm, usePage } from '@inertiajs/react'
import { IconDatabaseOff, IconDownload } from '@tabler/icons-react';
import React, { useState } from 'react'
export default function Index({ exams = [], grades = [], selectedExamId = null }) {

    // destruct permissions from props
    const { permissions } = usePage().props;

    const { data, setData, transform, post } = useForm({
        exam_id: '',
    });

    const [selectedExam, setSelectedExam] = useState(() => exams.find((exam) => String(exam.id) === String(selectedExamId)) || null)

    // Set gender
    const setSelectedExamHandler = (value) => {
        setSelectedExam(value)
        setData('exam_id', value.id)
    }

    const filterData = (e) => {
        e.preventDefault();
        router.get(route('reports.filter', data))
    }

    return (
        <>
            <Head title='Laporan Ujian' />
            <Card
                title={'Laporan Ujian'}
            >
                <div className='mb-5'>
                    <form onSubmit={filterData}>
                        <div className='mb-4 flex flex-col md:flex-row justify-between items-end gap-4'>
                            <div className='w-full md:w-1/2'>
                                <InputSelect
                                    label="Filter Berdasarkan Ujian"
                                    data={exams}
                                    placeholder="Cari ujian"
                                    selected={selectedExam}
                                    setSelected={setSelectedExamHandler}
                                    errors={null}
                                    multiple={false}
                                    searchable={true}
                                    displayKey='title'
                                />
                            </div>
                            <div className='w-full md:w-1/2'>
                                <Button
                                    type={'submit'}
                                    label={'Filter'}
                                    className={'border bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-950 dark:border-gray-800 dark:text-gray-200'}
                                />
                            </div>
                        </div>
                    </form>
                </div>
                {selectedExamId ? <div className='mb-5 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-teal-200 bg-teal-50 p-4'>
                    <div className='text-sm text-teal-900'>Laporan terpilih: <span className='font-bold'>{exams.find((exam) => String(exam.id) === String(selectedExamId))?.title || '-'}</span></div>
                    <div className='flex flex-wrap gap-2'>
                        <a href={route('reports.excel', { exam_id: selectedExamId })} className='inline-flex items-center gap-2 rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800'><IconDownload size={16} /> Download Excel</a>
                        <a href={route('reports.pdf', { exam_id: selectedExamId })} className='inline-flex items-center gap-2 rounded-lg bg-rose-700 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-800'><IconDownload size={16} /> Download PDF</a>
                    </div>
                </div> : null}
                <Table.Card title={'Hasil Ujian'}>
                    <Table>
                        <Table.Thead>
                            <tr>
                                <Table.Th className='w-10'>No</Table.Th>
                                <Table.Th>Ujian</Table.Th>
                                <Table.Th>Sesi</Table.Th>
                                <Table.Th>Nama Siswa</Table.Th>
                                <Table.Th>Kelas</Table.Th>
                                <Table.Th>Pelajaran</Table.Th>
                                <Table.Th>Nilai</Table.Th>
                            </tr>
                        </Table.Thead>
                        <Table.Tbody>
                            {grades.length ?
                                grades.map((grade, i) => (
                                    <tr className='hover:bg-gray-100 dark:hover:bg-gray-900' key={i}>
                                        <Table.Td className='text-center'>
                                            {i + 1}
                                        </Table.Td>
                                        <Table.Td>
                                            {grade.exam?.title || '-'}
                                        </Table.Td>
                                        <Table.Td>
                                            {grade.exam_session?.title || '-'}
                                        </Table.Td>
                                        <Table.Td>
                                            {grade.student?.name || '-'}
                                        </Table.Td>
                                        <Table.Td>
                                            {grade.student?.classroom?.title || grade.exam?.classroom?.title || '-'}
                                        </Table.Td>
                                        <Table.Td>
                                            {grade.exam?.lesson?.title || '-'}
                                        </Table.Td>
                                        <Table.Td>
                                            {grade.grade ?? 0}
                                        </Table.Td>
                                    </tr>
                                )) :
                                <Table.Empty colSpan={7} message={
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
            </Card>
        </>
    )
}
Index.layout = page => <DashboardLayout children={page} />
