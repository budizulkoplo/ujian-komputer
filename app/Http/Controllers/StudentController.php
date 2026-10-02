<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use App\Services\ExamParticipantSyncService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //get students
        $students = Student::query()->when(request()->search, function ($students) {
            $students = $students->where('name', 'like', '%' . request()->search . '%')->orWhere('nisn', 'like', '%' . request()->search . '%');
        })->with('classroom')->latest()->paginate(5);

        $classrooms = Classroom::all();

        //append query string to pagination links
        $students->withQueryString();

        //render with inertia
        return Inertia::render('Dashboard/Students/Index', [
            'students' => $students,
            'classrooms' => $classrooms
        ]);
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Siswa');
        $sheet->fromArray([
            ['NISN', 'Nama', 'Jenis Kelamin (L/P)', 'Kelas', 'Password (opsional)'],
            ['CONTOH - HAPUS', 'Nama Siswa', 'L', 'Nama kelas sesuai aplikasi', ''],
        ]);
        $sheet->getStyle('A:A')->getNumberFormat()->setFormatCode('@');
        $sheet->setCellValue('G1', 'Password kosong akan menggunakan NISN. Isi NISN sebagai teks agar angka nol di depan tidak hilang.');

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'template-data-siswa.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        try {
            $sheet = IOFactory::load($request->file('file')->getRealPath())->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages(['file' => 'File Excel tidak dapat dibaca. Gunakan template data siswa.']);
        }

        $classrooms = Classroom::all()->keyBy(fn ($classroom) => mb_strtolower(trim($classroom->title)));
        $students = [];
        $errors = [];
        $seenNisn = [];

        foreach (array_slice($rows, 1, null, true) as $rowNumber => $row) {
            $nisnCell = $sheet->getCell("A{$rowNumber}");
            $nisn = trim((string) ($nisnCell->getFormattedValue() ?: ($row['A'] ?? '')));
            // Jika Excel menyimpan NISN sebagai angka biasa, nol di depan sudah hilang.
            // NISN standar terdiri dari 10 digit, sehingga angka tersebut dipulihkan di sini.
            if ($nisn !== '' && $nisnCell->getDataType() === DataType::TYPE_NUMERIC && preg_match('/^\d{1,9}$/', $nisn)) {
                $nisn = str_pad($nisn, 10, '0', STR_PAD_LEFT);
            }
            $name = trim((string) ($row['B'] ?? ''));
            $genderInput = mb_strtolower(trim((string) ($row['C'] ?? '')));
            $classroomName = mb_strtolower(trim((string) ($row['D'] ?? '')));
            $password = trim((string) ($row['E'] ?? ''));

            if ($nisn === '' && $name === '' && $classroomName === '') continue;
            if (str_starts_with(mb_strtolower($nisn), 'contoh')) continue;

            $rowErrors = [];
            if (!preg_match('/^\d{1,19}$/', $nisn)) $rowErrors[] = 'NISN wajib berupa angka (maksimal 19 digit).';
            if ($name === '' || mb_strlen($name) > 255) $rowErrors[] = 'Nama wajib diisi dan maksimal 255 karakter.';

            $gender = match ($genderInput) {
                'l', 'laki-laki', 'laki laki' => 'L',
                'p', 'perempuan' => 'P',
                default => null,
            };
            if (!$gender) $rowErrors[] = 'Jenis kelamin harus L atau P.';

            $classroom = $classrooms->get($classroomName);
            if (!$classroom) $rowErrors[] = 'Nama kelas tidak ditemukan.';

            if (isset($seenNisn[$nisn])) {
                $rowErrors[] = 'NISN duplikat di dalam file.';
            } elseif ($nisn !== '') {
                $seenNisn[$nisn] = true;
            }

            if ($rowErrors) {
                $errors[] = 'Baris ' . $rowNumber . ': ' . implode(' ', $rowErrors);
                continue;
            }

            $students[] = [
                'nisn' => $nisn,
                'name' => $name,
                'gender' => $gender,
                'classroom_id' => $classroom->id,
                'password' => $password !== '' ? $password : $nisn,
            ];
        }

        if ($students) {
            $existingNisn = Student::withTrashed()->whereIn('nisn', array_column($students, 'nisn'))->pluck('nisn')->map(fn ($nisn) => (string) $nisn)->all();
            if ($existingNisn) {
                $errors[] = 'NISN sudah terdaftar: ' . implode(', ', $existingNisn) . '.';
            }
        }

        if ($errors) {
            throw ValidationException::withMessages(['file' => implode(' ', $errors)]);
        }
        if (!$students) {
            throw ValidationException::withMessages(['file' => 'Tidak ada data siswa untuk diimpor.']);
        }

        DB::transaction(function () use ($students) {
            foreach ($students as $studentData) {
                $student = Student::create($studentData);
                app(ExamParticipantSyncService::class)->syncStudent($student);
            }
        });

        return back()->with('success', count($students) . ' data siswa berhasil diimpor.');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //validate request
        $request->validate([
            'name' => 'required|string|max:255',
            'nisn' => 'required|unique:students',
            'gender' => 'required|string',
            'password' => 'required',
            'classroom_id' => 'required'
        ]);

        //create student
        $student = Student::create([
            'name' => $request->name,
            'nisn' => $request->nisn,
            'gender' => $request->gender,
            'password' => $request->password,
            'classroom_id' => $request->classroom_id
        ]);
        app(ExamParticipantSyncService::class)->syncStudent($student);

        return back();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Student $student)
    {
        //validate request
        $request->validate([
            'name' => 'required|string|max:255',
            'nisn' => 'required|unique:students,nisn,' . $student->id,
            'gender' => 'required|string',
            'classroom_id' => 'required',
            'password' => ''
        ]);

        //check passwordy
        if ($request->password == "") {

            //update student without password
            $student->update([
                'name' => $request->name,
                'nisn' => $request->nisn,
                'gender' => $request->gender,
                'classroom_id' => $request->classroom_id
            ]);
        } else {

            //update student with password
            $student->update([
                'name' => $request->name,
                'nisn' => $request->nisn,
                'gender' => $request->gender,
                'password' => $request->password,
                'classroom_id' => $request->classroom_id
            ]);
        }

        app(ExamParticipantSyncService::class)->syncStudent($student->fresh());

        //redirect
        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //get student
        $student = Student::findOrFail($id);

        //delete student
        $student->forceDelete();

        //redirect
        return back();
    }
}
