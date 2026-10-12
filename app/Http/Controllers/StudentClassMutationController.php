<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Student;
use App\Services\ExamParticipantSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class StudentClassMutationController extends Controller
{
    public function index(Request $request)
    {
        $classrooms = Classroom::query()->orderBy('title')->get(['id', 'title']);
        $selectedClassroomId = $request->integer('classroom_id') ?: null;
        $students = $selectedClassroomId
            ? Student::query()
                ->where('classroom_id', $selectedClassroomId)
                ->orderBy('name')
                ->get(['id', 'name', 'nisn', 'gender', 'classroom_id'])
            : collect();

        return Inertia::render('Dashboard/StudentClassMutations/Index', [
            'classrooms' => $classrooms,
            'students' => $students,
            'selectedClassroomId' => $selectedClassroomId,
        ]);
    }

    public function store(Request $request, ExamParticipantSyncService $participantSync)
    {
        $data = $request->validate([
            'from_classroom_id' => ['required', 'integer', Rule::exists('classrooms', 'id')->whereNull('deleted_at')],
            'to_classroom_id' => ['required', 'integer', Rule::exists('classrooms', 'id')->whereNull('deleted_at')],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', Rule::exists('students', 'id')->whereNull('deleted_at')],
        ]);

        $fromClassroomId = (int) $data['from_classroom_id'];
        $toClassroomId = (int) $data['to_classroom_id'];
        if ($fromClassroomId === $toClassroomId) {
            throw ValidationException::withMessages([
                'to_classroom_id' => 'Kelas tujuan harus berbeda dengan kelas asal.',
            ]);
        }

        $students = Student::query()
            ->where('classroom_id', $fromClassroomId)
            ->whereIn('id', $data['student_ids'])
            ->get();

        if ($students->count() !== count(array_unique($data['student_ids']))) {
            throw ValidationException::withMessages([
                'student_ids' => 'Sebagian siswa sudah tidak berada di kelas asal. Muat ulang data lalu coba lagi.',
            ]);
        }

        DB::transaction(function () use ($students, $toClassroomId, $participantSync) {
            foreach ($students as $student) {
                $student->update(['classroom_id' => $toClassroomId]);
                $participantSync->syncStudent($student->fresh());
            }
        });

        return to_route('student_class_mutations.index', ['classroom_id' => $fromClassroomId])
            ->with('success', $students->count() . ' siswa berhasil dipindahkan ke kelas tujuan.');
    }
}
