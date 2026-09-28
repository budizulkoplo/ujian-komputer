<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $exams = $this->accessibleExams()->when(request()->search, function ($exams) {
            $exams = $exams->where('title', 'like', '%' . request()->search . '%');
        })->with('lesson', 'classroom', 'questions')->latest()->paginate(5);

        //append query string to pagination links
        $exams->appends(['q' => request()->search]);

        $lessons = $this->accessibleLessons()->get();

        $classrooms = $this->accessibleClassrooms()->get();

        //render with inertia
        return Inertia::render('Dashboard/Exams/Index', [
            'exams' => $exams,
            'lessons' => $lessons,
            'classrooms' => $classrooms,
        ]);
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
            'title' => 'required',
            'lesson_id' => 'required|integer',
            'classroom_id' => 'required|integer',
            'duration' => 'required|integer',
            'description' => 'required',
            'random_question' => 'required',
            'random_answer' => 'required',
            'show_answer' => 'required',
        ]);

        abort_unless($this->canTeachPair((int) $request->lesson_id, (int) $request->classroom_id), 403);

        Exam::create([
            'title' => $request->title,
            'lesson_id' => $request->lesson_id,
            'classroom_id' => $request->classroom_id,
            'duration' => $request->duration,
            'description' => $request->description,
            'random_question' => $request->random_question,
            'random_answer' => $request->random_answer,
            'show_answer' => $request->show_answer,
        ]);

        //redirect
        return back();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $exam = Exam::with('lesson', 'classroom')->findOrFail($id);
        $this->ensureExamAccess($exam);

        // Tampilkan 25 soal per halaman agar halaman tetap ringan saat jumlah soal banyak.
        $exam->setRelation('questions', $exam->questions()->paginate(25));

        //render with inertia
        return Inertia::render('Dashboard/Exams/Show', [
            'exam' => $exam,
        ]);
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
    public function update(Request $request, Exam $exam)
    {
        $this->ensureExamAccess($exam);
        //validate request
        $request->validate([
            'title' => 'required',
            'lesson_id' => 'required|integer',
            'classroom_id' => 'required|integer',
            'duration' => 'required|integer',
            'description' => 'required',
            'random_question' => 'required',
            'random_answer' => 'required',
            'show_answer' => 'required',
        ]);

        abort_unless($this->canTeachPair((int) $request->lesson_id, (int) $request->classroom_id), 403);

        $exam->update([
            'title' => $request->title,
            'lesson_id' => $request->lesson_id,
            'classroom_id' => $request->classroom_id,
            'duration' => $request->duration,
            'description' => $request->description,
            'random_question' => $request->random_question,
            'random_answer' => $request->random_answer,
            'show_answer' => $request->show_answer,
        ]);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $exam = Exam::findOrFail($id);
        $this->ensureExamAccess($exam);

        //delete exam
        $exam->forceDelete();

        //redirect
        return back();
    }

    private function accessibleLessons()
    {
        $user = auth()->user();

        return $user?->isTeacher()
            ? Lesson::whereHas('teachers.assignments', fn ($query) => $query->where('teacher_id', $user->teacher->id))
            : Lesson::query();
    }

    private function accessibleExams()
    {
        $user = auth()->user();

        return Exam::accessibleBy($user);
    }

    private function accessibleClassrooms()
    {
        $user = auth()->user();
        if (!$user?->isTeacher()) return Classroom::query();

        return Classroom::whereExists(function ($query) use ($user) {
            $query->selectRaw('1')->from('teacher_lesson_classroom as tlc')
                ->whereColumn('tlc.classroom_id', 'classrooms.id')
                ->where('tlc.teacher_id', $user->teacher->id);
        });
    }

    private function canTeachPair(int $lessonId, int $classroomId): bool
    {
        $user = auth()->user();
        return !$user?->isTeacher() || $user->canTeach($lessonId, $classroomId);
    }

    private function ensureExamAccess(Exam $exam): void
    {
        abort_unless($this->accessibleExams()->whereKey($exam->id)->exists(), 403);
    }
}
