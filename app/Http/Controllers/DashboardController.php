<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Exam;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $accessibleExams = Exam::accessibleBy($user);

        // Total Exam
        $exams = (clone $accessibleExams)->count();

        // Total Students
        $students = Student::query()->count();

        // Total Guru
        $teachers = Teacher::query()->count();

        // Total Mata Pelajaran
        $lessonsQuery = Lesson::query();
        if ($user?->isTeacher()) {
            $lessonsQuery->whereHas('teachers', fn ($query) => $query->whereKey($user->teacher?->id));
        }

        $recentExams = (clone $accessibleExams)
            ->with('classroom', 'lesson')
            ->latest()
            ->take(5)
            ->get();
        $recentTeachers = Teacher::with('user')->latest()->take(5)->get();

        return Inertia::render('Dashboard/Index', [
            'exams' => $exams,
            'students' => $students,
            'teachers' => $teachers,
            'lessons' => $lessonsQuery->count(),
            'recentExams' => $recentExams,
            'recentTeachers' => $recentTeachers,
        ]);
    }
}
