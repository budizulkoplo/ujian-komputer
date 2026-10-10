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
        $exams = $this->accessibleExams()->with('lesson')->latest()->get();
        $selectedExamId = $request->integer('exam_id') ?: null;
        $attempts = collect();
        $selectedAttempt = null;
        $answers = collect();

        if ($selectedExamId && $exams->contains('id', $selectedExamId)) {
            $attempts = Grade::with(['student.classroom', 'exam_session'])
                ->where('exam_id', $selectedExamId)
                ->whereNotNull('end_time')
                ->latest('end_time')
                ->get();

            $answerRows = Answer::with('question')
                ->where('exam_id', $selectedExamId)
                ->whereIn('exam_session_id', $attempts->pluck('exam_session_id'))
                ->whereIn('student_id', $attempts->pluck('student_id'))
                ->get()
                ->groupBy(fn (Answer $answer) => $answer->student_id . '-' . $answer->exam_session_id);

            $attempts = $attempts->map(function (Grade $grade) use ($answerRows) {
                $key = $grade->student_id . '-' . $grade->exam_session_id;
                $studentAnswers = $answerRows->get($key, collect());
                $manualAnswers = $studentAnswers->filter(fn (Answer $answer) => $answer->question?->type === 'essay');
                $reviewedAnswers = $manualAnswers->filter(fn (Answer $answer) => (bool) $answer->is_reviewed);

                return [
                    'student_id' => $grade->student_id,
                    'exam_session_id' => $grade->exam_session_id,
                    'key' => $key,
                    'student' => $grade->student,
                    'exam_session' => $grade->exam_session,
                    'grade' => $grade->grade,
                    'released' => $grade->isReleased(),
                    'total_questions' => $studentAnswers->count(),
                    'manual_questions' => $manualAnswers->count(),
                    'reviewed_questions' => $reviewedAnswers->count(),
                    'correction_status' => $manualAnswers->isEmpty() || $manualAnswers->count() === $reviewedAnswers->count()
                        ? 'completed'
                        : 'pending',
                ];
            })->values();

            $selectedKey = (string) $request->input('attempt');
            $selectedAttempt = $selectedKey !== '' ? $attempts->firstWhere('key', $selectedKey) : null;

            if ($selectedAttempt) {
                $answers = Answer::with('question')
                    ->where('exam_id', $selectedExamId)
                    ->where('exam_session_id', $selectedAttempt['exam_session_id'])
                    ->where('student_id', $selectedAttempt['student_id'])
                    ->orderBy('question_order')
                    ->get()
                    ->map(fn (Answer $answer, $index) => $this->answerDetail($answer, $index + 1));
            }
        }

        return Inertia::render('Dashboard/Corrections/Index', [
            'exams' => $exams,
            'attempts' => $attempts,
            'answers' => $answers,
            'selectedExamId' => $selectedExamId,
            'selectedAttemptKey' => $selectedAttempt['key'] ?? null,
        ]);
    }

    public function update(Request $request, Answer $answer)
    {
        $answer->load('question');
        $this->ensureExamAccess($answer->exam_id);
        abort_unless($answer->question?->type === 'essay', 422, 'Hanya jawaban essay yang perlu dikoreksi manual.');

        $data = $request->validate([
            'score' => ['required', 'numeric', 'min:0', 'max:' . ($answer->question->max_score ?? 0)],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $answer->update([
            'score' => $data['score'],
            'is_correct' => (float) $data['score'] > 0 ? 'Y' : 'N',
            'is_reviewed' => true,
            'teacher_comment' => $data['comment'] ?? null,
        ]);

        $this->recalculateGrade($answer);

        return back()->with('success', 'Nilai essay berhasil disimpan.');
    }

    public function publish(Request $request)
    {
        $data = $request->validate([
            'exam_id' => ['required', 'integer'],
        ]);
        $this->ensureExamAccess((int) $data['exam_id']);

        $grades = Grade::where('exam_id', $data['exam_id'])
            ->whereNotNull('end_time')
            ->get();

        if ($grades->isEmpty()) {
            return back()->with('error', 'Belum ada siswa yang menyelesaikan ujian ini.');
        }

        $unfinished = $grades->filter(fn (Grade $grade) => !$grade->isReadyForRelease());
        if ($unfinished->isNotEmpty()) {
            return back()->with('error', 'Nilai belum dapat dipublikasikan. Selesaikan koreksi essay terlebih dahulu.');
        }

        Grade::whereIn('id', $grades->pluck('id'))->update(['results_released' => true]);

        return back()->with('success', $grades->count() . ' nilai siswa berhasil dipublikasikan.');
    }

    private function answerDetail(Answer $answer, int $number): array
    {
        $question = $answer->question;
        $options = collect(range(1, 5))
            ->filter(fn ($option) => filled($question?->{"option_{$option}"}))
            ->map(fn ($option) => [
                'key' => (string) $option,
                'letter' => chr(64 + $option),
                'label' => $question->{"option_{$option}"},
            ])->values()->all();

        return [
            'id' => $answer->id,
            'number' => $number,
            'type' => $question?->type,
            'question' => $question?->question,
            'image' => $question?->image,
            'options' => $options,
            'student_answer' => $this->answerValue($answer->answer_value, $question?->type, $answer->answer),
            'correct_answer' => $this->answerKey($question?->answer_key, $question?->type),
            'is_correct' => $answer->is_correct === 'Y',
            'is_reviewed' => (bool) $answer->is_reviewed,
            'comment' => $answer->teacher_comment,
            'score' => (float) $answer->score,
            'max_score' => (float) ($question?->max_score ?? 0),
        ];
    }

    private function answerValue(mixed $value, ?string $type, mixed $legacy = null): mixed
    {
        if (($value === null || $value === '') && $legacy !== null && (int) $legacy !== 0) $value = (string) $legacy;
        if ($value === null || $value === '') return null;
        if (in_array($type, ['multiple_choice_complex', 'ordering'], true)) {
            $decoded = is_array($value) ? $value : json_decode((string) $value, true);
            return is_array($decoded) ? array_values(array_map('strval', $decoded)) : [];
        }
        return (string) $value;
    }

    private function answerKey(mixed $value, ?string $type): mixed
    {
        if ($value === null || $value === '') return null;
        if (in_array($type, ['multiple_choice_complex', 'ordering'], true)) {
            $decoded = is_array($value) ? $value : json_decode((string) $value, true);
            return is_array($decoded) ? array_values(array_map('strval', $decoded)) : [];
        }
        return is_array($value) ? (string) ($value[0] ?? '') : (string) $value;
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
        return Exam::accessibleBy(auth()->user());
    }

    private function ensureExamAccess(int $examId): void
    {
        abort_unless($this->accessibleExams()->whereKey($examId)->exists(), 403);
    }
}
