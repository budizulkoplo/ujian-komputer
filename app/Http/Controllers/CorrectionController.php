<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Exam;
use App\Models\Grade;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CorrectionController extends Controller
{
    public function index(Request $request)
    {
        $exams = $this->accessibleExams()->with('lesson')->get();
        $selectedExamId = $request->integer('exam_id') ?: null;
        $answers = collect();

        if ($selectedExamId && $exams->contains('id', $selectedExamId)) {
            $answers = Answer::with(['student.classroom', 'question', 'exam_session'])
                ->where('exam_id', $selectedExamId)
                ->whereHas('question', fn ($query) => $query->where('type', 'essay'))
                ->orderBy('student_id')
                ->orderBy('question_id')
                ->get();
        }

        return Inertia::render('Dashboard/Corrections/Index', [
            'exams' => $exams,
            'answers' => $answers,
            'selectedExamId' => $selectedExamId,
        ]);
    }

    public function update(Request $request, Answer $answer)
    {
        $answer->load('question');
        $this->ensureExamAccess($answer->exam_id);

        $data = $request->validate([
            'score' => ['required', 'numeric', 'min:0', 'max:' . ($answer->question->max_score ?? 0)],
        ]);

        $answer->update([
            'score' => $data['score'],
            'is_correct' => (float) $data['score'] > 0 ? 'Y' : 'N',
        ]);

        $this->recalculateGrade($answer);

        return back()->with('success', 'Nilai essay berhasil disimpan.');
    }

    private function recalculateGrade(Answer $answer): void
    {
        $answers = Answer::with('question')
            ->where('exam_id', $answer->exam_id)
            ->where('exam_session_id', $answer->exam_session_id)
            ->where('student_id', $answer->student_id)
            ->get();
        $totalScore = (float) $answers->sum(fn ($item) => (float) ($item->question?->max_score ?? 0));
        $earnedScore = (float) $answers->sum(fn ($item) => (float) $item->score > 0
            ? (float) $item->score
            : ($item->is_correct === 'Y' ? (float) ($item->question?->max_score ?? 0) : 0));

        Grade::where('exam_id', $answer->exam_id)
            ->where('exam_session_id', $answer->exam_session_id)
            ->where('student_id', $answer->student_id)
            ->update([
                'total_correct' => $answers->where('is_correct', 'Y')->count(),
                'grade' => $totalScore > 0 ? round($earnedScore / $totalScore * 100, 2) : 0,
            ]);
    }

    private function accessibleExams()
    {
        $user = auth()->user();

        return Exam::accessibleBy($user);
    }

    private function ensureExamAccess(int $examId): void
    {
        abort_unless($this->accessibleExams()->whereKey($examId)->exists(), 403);
    }
}
