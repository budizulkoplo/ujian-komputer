<?php

namespace App\Http\Controllers;

use App\Models\ExamGroup;
use App\Models\ExamSession;
use App\Models\Student;
use App\Models\Exam;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExamGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(ExamSession $exam_session)
    {
        $this->ensureAccess($exam_session);
        //get exams
        $exam = $exam_session->exam;

        //get students already enrolled
        $students_enrolled = ExamGroup::where('exam_id', $exam->id)->where('exam_session_id', $exam_session->id)->pluck('student_id')->all();

        //get students
        $students = Student::query()->with('classroom')->where('classroom_id', $exam->classroom_id)->whereNotIn('id', $students_enrolled)->get();

        //render with inertia
        return Inertia::render('Dashboard/ExamGroups/Create', [
            'exam' => $exam,
            'exam_session' => $exam_session,
            'students' => $students,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, ExamSession $exam_session)
    {
        $this->ensureAccess($exam_session);
        //validate request
        $request->validate([
            'student_id' => 'required',
        ]);

        //create exam_group
        $exam = $exam_session->exam;
        foreach ($request->student_id as $student_id) {

            //select student
            $student = Student::where('classroom_id', $exam->classroom_id)->findOrFail($student_id);

            //create exam_group
            ExamGroup::create([
                'exam_id' => $exam->id,
                'exam_session_id' => $exam_session->id,
                'student_id' => $student->id,
            ]);
        }

        //redirect
        return to_route('exam_sessions.show', $exam_session->id);
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ExamSession $exam_session, ExamGroup $exam_group)
    {
        $this->ensureAccess($exam_session);
        abort_unless($exam_group->exam_session_id === $exam_session->id, 404);
        //delete exam_group
        $exam_group->delete();

        //redirect
        return back();
    }

    private function ensureAccess(ExamSession $examSession): void
    {
        abort_unless(Exam::accessibleBy(auth()->user())->whereKey($examSession->exam_id)->exists(), 403);
    }
}
