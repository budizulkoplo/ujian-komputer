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
