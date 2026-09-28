<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Grade;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index()
    {
        $exams = $this->accessibleExams()->with('lesson', 'classroom')->get();

        return Inertia::render('Dashboard/Reports/Index', [
            'exams' => $exams,
            'grades' => $this->gradesForExams($exams),
        ]);
    }

    /**
     * filter
     *
     * @param  mixed $request
     * @return void
     */
    public function filter(Request $request)
    {
        $this->validate($request, [
            'exam_id' => 'required',
        ]);

        $exams = $this->accessibleExams()->with('lesson', 'classroom')->get();

        //get exam
        $exam = $this->accessibleExams()->with('lesson', 'classroom')->find($request->exam_id);

        if ($exam) {
            $grades = $this->gradesForExams(collect([$exam]));

        } else {
            $grades = [];
        }

        return Inertia::render('Dashboard/Reports/Index', [
            'exams' => $exams,
            'grades' => $grades,
        ]);
    }

    private function accessibleExams()
    {
        $user = auth()->user();

        return Exam::accessibleBy($user);
    }

    private function gradesForExams($exams)
    {
        return Grade::with('student.classroom', 'exam.classroom', 'exam.lesson', 'exam_session')
            ->whereIn('exam_id', $exams->pluck('id'))
            ->latest()
            ->get();
    }
}
