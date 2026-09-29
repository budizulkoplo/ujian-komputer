<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Teacher;
use App\Models\User;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;

class TeacherController extends Controller
{
    public function index()
    {
        $this->authorizeAdmin();
        $teachers = Teacher::with(['user', 'lessons', 'assignments.lesson', 'assignments.classroom'])
            ->when(request('search'), function ($query) {
                $search = request('search');
                $query->where('nip', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            })
            ->when(request('lesson_id'), fn ($query) => $query->whereHas('lessons', fn ($lesson) => $lesson->whereKey(request('lesson_id'))))
            ->latest()
            ->paginate(8)
            ->withQueryString();

        return Inertia::render('Dashboard/Teachers/Index', [
            'teachers' => $teachers,
            'lessons' => Lesson::orderBy('title')->get(['id', 'title']),
            'classrooms' => Classroom::orderBy('title')->get(['id', 'title']),
            'filters' => ['search' => request('search'), 'lesson_id' => request('lesson_id')],
        ]);
    }

    public function downloadTemplate()
    {
        $this->authorizeAdmin();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Guru');
        $sheet->fromArray([
            ['Nama Guru', 'Email', 'NIP', 'Password', 'Mata Pelajaran', 'Kelas'],
            ['CONTOH - HAPUS', 'guru@example.com', '19800101', 'password-minimal-6', 'Nama mapel sesuai aplikasi', 'Nama kelas sesuai aplikasi'],
        ]);
        $sheet->getStyle('B:B')->getNumberFormat()->setFormatCode('@');
        $sheet->getStyle('C:C')->getNumberFormat()->setFormatCode('@');

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'template-data-guru.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function export()
    {
        $this->authorizeAdmin();
        $teachers = Teacher::with(['user', 'assignments.lesson', 'assignments.classroom'])->orderBy('id')->get();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Guru');
        $sheet->fromArray([['Nama Guru', 'Email', 'NIP', 'Mata Pelajaran', 'Kelas']]);

        $row = 2;
        foreach ($teachers as $teacher) {
            $assignments = $teacher->assignments;
            if ($assignments->isEmpty()) {
                $sheet->fromArray([[$teacher->user?->name, $teacher->user?->email, $teacher->nip, '', '']], null, "A{$row}");
                $row++;
                continue;
            }
            foreach ($assignments as $assignment) {
                $sheet->fromArray([[
                    $teacher->user?->name, $teacher->user?->email, $teacher->nip,
                    $assignment->lesson?->title, $assignment->classroom?->title,
                ]], null, "A{$row}");
                $row++;
            }
        }
        foreach (['B', 'C'] as $column) $sheet->getStyle("{$column}:{$column}")->getNumberFormat()->setFormatCode('@');

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'data-guru.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request)
    {
        $this->authorizeAdmin();
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240']]);

        try {
            $rows = IOFactory::load($request->file('file')->getRealPath())->getActiveSheet()->toArray(null, true, true, true);
        } catch (\Throwable $exception) {
            throw \Illuminate\Validation\ValidationException::withMessages(['file' => 'File Excel tidak dapat dibaca. Gunakan template data guru.']);
        }

        $lessons = Lesson::all()->keyBy(fn ($lesson) => mb_strtolower(trim($lesson->title)));
        $classrooms = Classroom::all()->keyBy(fn ($classroom) => mb_strtolower(trim($classroom->title)));
        $grouped = [];
        $errors = [];

        foreach (array_slice($rows, 1, null, true) as $rowNumber => $row) {
            $name = trim((string) ($row['A'] ?? ''));
            $email = mb_strtolower(trim((string) ($row['B'] ?? '')));
            $nip = trim((string) ($row['C'] ?? ''));
            $password = trim((string) ($row['D'] ?? ''));
            $lessonName = mb_strtolower(trim((string) ($row['E'] ?? '')));
            $classroomName = mb_strtolower(trim((string) ($row['F'] ?? '')));
            if ($name === '' && $email === '' && $lessonName === '') continue;
            if (str_starts_with(mb_strtolower($name), 'contoh')) continue;

            $rowErrors = [];
            if ($name === '' || mb_strlen($name) > 255) $rowErrors[] = 'Nama guru wajib diisi.';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $rowErrors[] = 'Email tidak valid.';
            if ($password === '' || mb_strlen($password) < 6) $rowErrors[] = 'Password wajib diisi minimal 6 karakter.';
            $lesson = $lessons->get($lessonName);
            $classroom = $classrooms->get($classroomName);
            if (!$lesson) $rowErrors[] = 'Mata pelajaran tidak ditemukan.';
            if (!$classroom) $rowErrors[] = 'Nama kelas tidak ditemukan.';
            if ($rowErrors) {
                $errors[] = 'Baris ' . $rowNumber . ': ' . implode(' ', $rowErrors);
                continue;
            }

            $grouped[$email] ??= ['name' => $name, 'email' => $email, 'nip' => $nip !== '' ? $nip : null, 'password' => $password, 'assignments' => []];
            $grouped[$email]['assignments'][$lesson->id] ??= [];
            $grouped[$email]['assignments'][$lesson->id][] = $classroom->id;
        }

        $teachers = array_values($grouped);
        $emails = array_column($teachers, 'email');
        $nips = array_values(array_filter(array_column($teachers, 'nip')));
        if ($emails) {
            $existing = User::whereIn('email', $emails)->pluck('email')->all();
            if ($existing) $errors[] = 'Email sudah terdaftar: ' . implode(', ', $existing) . '.';
        }
        if ($nips) {
            $existing = Teacher::whereIn('nip', $nips)->pluck('nip')->all();
            if ($existing) $errors[] = 'NIP sudah terdaftar: ' . implode(', ', $existing) . '.';
        }
        if ($errors) throw \Illuminate\Validation\ValidationException::withMessages(['file' => implode(' ', $errors)]);
        if (!$teachers) throw \Illuminate\Validation\ValidationException::withMessages(['file' => 'Tidak ada data guru untuk diimpor.']);

        DB::transaction(function () use ($teachers) {
            foreach ($teachers as $data) {
                $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password'])]);
                $user->assignRole(Role::firstOrCreate(['name' => 'guru', 'guard_name' => 'web']));
                $teacher = Teacher::create(['user_id' => $user->id, 'nip' => $data['nip']]);
                $assignments = collect($data['assignments'])->map(fn ($classroomIds, $lessonId) => ['lesson_id' => (int) $lessonId, 'classroom_ids' => array_values(array_unique($classroomIds))])->values()->all();
                $this->syncAssignments($teacher, $assignments);
            }
        });

        return back()->with('success', count($teachers) . ' data guru berhasil diimpor.');
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'nip' => ['nullable', 'string', 'max:50', 'unique:teachers,nip'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*.lesson_id' => ['required', 'integer', 'exists:lessons,id'],
            'assignments.*.classroom_ids' => ['required', 'array', 'min:1'],
            'assignments.*.classroom_ids.*' => ['integer', 'exists:classrooms,id'],
        ]);

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);
            $user->assignRole(Role::firstOrCreate(['name' => 'guru', 'guard_name' => 'web']));

            $teacher = Teacher::create([
                'user_id' => $user->id,
                'nip' => $data['nip'] ?? null,
            ]);
            $this->syncAssignments($teacher, $data['assignments']);
        });

        return to_route('teachers.index');
    }

    public function update(Request $request, Teacher $teacher)
    {
        $this->authorizeAdmin();
        $teacher->load('user');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($teacher->user_id)],
            'nip' => ['nullable', 'string', 'max:50', Rule::unique('teachers', 'nip')->ignore($teacher->id)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*.lesson_id' => ['required', 'integer', 'exists:lessons,id'],
            'assignments.*.classroom_ids' => ['required', 'array', 'min:1'],
            'assignments.*.classroom_ids.*' => ['integer', 'exists:classrooms,id'],
        ]);

        DB::transaction(function () use ($data, $teacher) {
            $teacher->user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                ...(!empty($data['password']) ? ['password' => Hash::make($data['password'])] : []),
            ]);
            $teacher->update(['nip' => $data['nip'] ?? null]);
            $this->syncAssignments($teacher, $data['assignments']);
        });

        return to_route('teachers.index');
    }

    public function destroy(Teacher $teacher)
    {
        $this->authorizeAdmin();
        DB::transaction(function () use ($teacher) {
            $user = $teacher->user;
            $teacher->lessons()->detach();
            $teacher->delete();
            $user?->delete();
        });

        return back();
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }

    private function syncAssignments(Teacher $teacher, array $assignments): void
    {
        $lessonIds = collect($assignments)->pluck('lesson_id')->unique()->values()->all();
        $teacher->lessons()->sync($lessonIds);
        $teacher->assignments()->delete();

        $rows = collect($assignments)->flatMap(function ($assignment) use ($teacher) {
            return collect($assignment['classroom_ids'])->map(fn ($classroomId) => [
                'teacher_id' => $teacher->id,
                'lesson_id' => $assignment['lesson_id'],
                'classroom_id' => $classroomId,
            ]);
        })->unique(fn ($row) => $row['lesson_id'] . '-' . $row['classroom_id'])->values()->all();

        if ($rows) $teacher->assignments()->createMany($rows);
    }
}
