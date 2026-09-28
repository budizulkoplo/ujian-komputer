<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\AppSetting;
use App\Models\ExamGroup;
use App\Models\Grade;
use App\Models\Question;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExaminationController extends Controller
{
    public function index()
    {
        $student = auth()->guard('student')->user()->loadMissing('classroom');
        $examGroups = ExamGroup::with('exam.lesson', 'exam_session', 'student.classroom')
            ->where('student_id', $student->id)->get();

        $data = $examGroups->map(function ($examGroup) {
            $grade = Grade::firstOrCreate([
                'exam_id' => $examGroup->exam_id,
                'exam_session_id' => $examGroup->exam_session_id,
                'student_id' => auth()->guard('student')->user()->id,
            ], [
                'duration' => $examGroup->exam->duration * 60000,
                'total_correct' => 0,
                'grade' => 0,
            ]);

            return ['exam_group' => $examGroup, 'grade' => $grade];
        })->values();

        $grades = $data->pluck('grade');
        $completedGrades = $grades->filter(fn ($grade) => filled($grade->end_time));
        $averageGrade = $completedGrades->isNotEmpty()
            ? round((float) $completedGrades->avg(fn ($grade) => (float) $grade->grade), 1)
            : 0;

        return Inertia::render('Student/Dashboard', [
            'exam_groups' => $data,
            'stats' => [
                'active_exams' => $grades->filter(fn ($grade) => blank($grade->end_time) && !$grade->is_locked)->count(),
                'total_exams' => $data->count(),
                'average_grade' => $averageGrade,
                'in_progress' => $grades->filter(fn ($grade) => filled($grade->start_time) && blank($grade->end_time) && !$grade->is_locked)->count(),
                'passed' => $completedGrades->filter(fn ($grade) => (float) $grade->grade >= 75)->count(),
                'failed' => $completedGrades->filter(fn ($grade) => (float) $grade->grade < 75)->count(),
            ],
            'student_classroom' => $student->classroom?->title,
        ]);
    }

    public function confirmation($id)
    {
        $examGroup = $this->examGroup($id);
        if (!$examGroup) return redirect()->route('student.dashboard');

        $grade = $this->grade($examGroup);
        return Inertia::render('Student/Exams/Confirmation', [
            'exam_group' => $examGroup,
            'grade' => $grade,
        ]);
    }

    public function startExam($id)
    {
        $examGroup = $this->examGroup($id);
        if (!$examGroup) return redirect()->route('student.dashboard');

        $grade = $this->grade($examGroup);
        if ($grade->is_locked || $grade->end_time) {
            return redirect()->route('student.dashboard')->with('error', 'Ujian ini tidak dapat dilanjutkan.');
        }
        $grade->start_time ??= Carbon::now();
        $grade->expires_at ??= Carbon::now()->addMilliseconds(max(0, (int) $grade->duration));
        $grade->save();

        $questions = Question::where('exam_id', $examGroup->exam->id)
            ->orderByRaw('COALESCE(sort_order, id) ASC')
            ->orderBy('id', 'ASC')
            ->get();

        foreach ($questions as $order => $question) {
            $answer = Answer::firstOrNew([
                'student_id' => auth()->guard('student')->user()->id,
                'exam_id' => $examGroup->exam->id,
                'exam_session_id' => $examGroup->exam_session->id,
                'question_id' => $question->id,
            ]);

            $answer->question_order = $order + 1;
            if (!$answer->exists) {
                $options = collect(range(1, 5))
                    ->filter(fn ($number) => filled($question->{"option_{$number}"}))
                    ->values()->all();
                if ($examGroup->exam->random_answer === 'Y' && $question->type !== 'ordering') shuffle($options);

                $answer->answer_order = implode(',', $options);
                $answer->answer = 0;
            }
            $answer->save();
        }

        return redirect()->route('student.examination.show', ['id' => $examGroup->id, 'page' => 1]);
    }

    public function show($id, $page)
    {
        $examGroup = $this->examGroup($id);
        if (!$examGroup) return redirect()->route('student.dashboard');

        $baseAnswers = Answer::with('question')->where('student_id', auth()->guard('student')->user()->id)
            ->where('exam_id', $examGroup->exam->id);
        $allQuestions = (clone $baseAnswers)->orderBy('question_order')->get();
        $answered = (clone $baseAnswers)->where(fn ($query) => $query->where('answer', '!=', 0)->orWhereNotNull('answer_value'))->count();
        $active = (clone $baseAnswers)->where('question_order', $page)->first();
        $answerOrder = $active && $active->answer_order ? explode(',', $active->answer_order) : [];
        $duration = $this->grade($examGroup);
        if ($duration->is_locked || $duration->end_time) {
            return redirect()->route('student.dashboard')->with('error', 'Ujian ini sudah dikunci atau selesai.');
        }
        $duration->start_time ??= Carbon::now();
        $duration->expires_at ??= Carbon::now()->addMilliseconds(max(0, (int) $duration->duration));
        $duration->duration = $this->remainingDuration($duration);
        $duration->save();

        return Inertia::render('Student/Exams/Show', [
            'id' => (int) $id,
            'page' => (int) $page,
            'exam_group' => $examGroup,
            'all_questions' => $allQuestions,
            'question_answered' => $answered,
            'question_active' => $active,
            'answer_order' => $answerOrder,
            'duration' => $duration,
        ]);
    }

    public function updateDuration(Request $request, $gradeId)
    {
        Grade::findOrFail($gradeId)->update(['duration' => $request->integer('duration')]);
        return response()->json(['success' => true]);
    }

    public function reportViolation(Request $request)
    {
        $studentId = auth()->guard('student')->user()->id;
        $examGroup = ExamGroup::where('id', $request->integer('exam_group_id'))
            ->where('student_id', $studentId)
            ->firstOrFail();

        $grade = Grade::where('exam_id', $examGroup->exam_id)
            ->where('exam_session_id', $examGroup->exam_session_id)
            ->where('student_id', $studentId)
            ->firstOrFail();

        $limit = max(1, (int) (AppSetting::first()?->cheat_limit ?? 3));
        if ($grade->end_time || $grade->is_locked) {
            return response()->json([
                'locked' => (bool) $grade->is_locked,
                'cheat_count' => (int) $grade->cheat_count,
                'limit' => $limit,
            ]);
        }

        $remaining = $this->remainingDuration($grade);
        $grade->increment('cheat_count');
        $grade->refresh();
        if ($grade->cheat_count >= $limit) {
            $grade->update([
                'is_locked' => true,
                'duration' => $remaining,
                'expires_at' => null,
            ]);
        }

        return response()->json([
            'locked' => (bool) $grade->is_locked,
            'cheat_count' => (int) $grade->cheat_count,
            'limit' => $limit,
        ]);
    }

    public function answerQuestion(Request $request)
    {
        $grade = Grade::where('exam_id', $request->exam_id)
            ->where('exam_session_id', $request->exam_session_id)
            ->where('student_id', auth()->guard('student')->user()->id)->firstOrFail();
        $question = Question::findOrFail($request->question_id);
        $submitted = $request->input('answer_value', $request->input('answer'));
        $answer = Answer::where('exam_id', $request->exam_id)
            ->where('exam_session_id', $request->exam_session_id)
            ->where('student_id', auth()->guard('student')->user()->id)
            ->where('question_id', $question->id)->firstOrFail();

        $answer->answer = is_numeric($submitted) ? (int) $submitted : 0;
        $answer->answer_value = is_array($submitted) ? json_encode(array_values($submitted)) : (string) $submitted;
        $isCorrect = $this->isCorrect($question, $submitted);
        $answer->is_correct = $isCorrect ? 'Y' : 'N';
        $answer->score = $question->type === 'essay' ? 0 : ($isCorrect ? $question->max_score : 0);
        $answer->save();

        return back();
    }

    public function endExam(Request $request)
    {
        $studentId = auth()->guard('student')->user()->id;
        $questions = Question::where('exam_id', $request->exam_id)->get();
        $answers = Answer::with('question')->where('exam_id', $request->exam_id)
            ->where('exam_session_id', $request->exam_session_id)
            ->where('student_id', $studentId)->get();
        $correct = $answers->where('is_correct', 'Y');
        $totalScore = (float) $questions->sum('max_score');
        $earnedScore = (float) $answers->sum(fn ($answer) => (float) $answer->score > 0
            ? (float) $answer->score
            : ($answer->is_correct === 'Y' ? (float) ($answer->question?->max_score ?? 0) : 0));
        $gradeValue = $totalScore > 0 ? round($earnedScore / $totalScore * 100, 2) : 0;

        Grade::where('exam_id', $request->exam_id)->where('exam_session_id', $request->exam_session_id)
            ->where('student_id', $studentId)->update([
                'end_time' => Carbon::now(),
                'total_correct' => $correct->count(),
                'grade' => $gradeValue,
            ]);

        return redirect()->route('student.examination.resultExam', $request->exam_group_id);
    }

    public function resultExam($examGroupId)
    {
        $examGroup = $this->examGroup($examGroupId);
        if (!$examGroup) return redirect()->route('student.dashboard');

        $answers = Answer::where('exam_id', $examGroup->exam_id)
            ->where('exam_session_id', $examGroup->exam_session_id)
            ->where('student_id', auth()->guard('student')->user()->id)
            ->get()
            ->keyBy('question_id');

        $answerDetails = Question::where('exam_id', $examGroup->exam_id)
            ->orderByRaw('COALESCE(sort_order, id) ASC')
            ->orderBy('id')
            ->get()
            ->values()
            ->map(function (Question $question, int $index) use ($answers) {
                $answer = $answers->get($question->id);
                $options = collect(range(1, 5))
                    ->filter(fn ($number) => filled($question->{"option_{$number}"}))
                    ->map(fn ($number) => [
                        'key' => (string) $number,
                        'letter' => chr(64 + $number),
                        'label' => $question->{"option_{$number}"},
                    ])->values()->all();

                return [
                    'number' => $index + 1,
                    'type' => $question->type,
                    'question' => $question->question,
                    'image' => $question->image,
                    'options' => $options,
                    'student_answer' => $this->resultAnswerValue($answer?->answer_value, $question->type),
                    'correct_answer' => $this->resultAnswerKey($question->answer_key, $question->type),
                    'is_correct' => $answer?->is_correct === 'Y',
                    'score' => (float) ($answer?->score ?? 0),
                    'max_score' => (float) $question->max_score,
                ];
            })->values();

        return Inertia::render('Student/Exams/Result', [
            'exam_group' => $examGroup,
            'grade' => $this->grade($examGroup),
            'answer_details' => $answerDetails,
        ]);
    }

    private function resultAnswerValue(mixed $value, string $type): mixed
    {
        if ($value === null || $value === '') return null;
        if (in_array($type, ['multiple_choice_complex', 'ordering'], true)) {
            $decoded = is_array($value) ? $value : json_decode((string) $value, true);
            return is_array($decoded) ? array_values(array_map('strval', $decoded)) : [];
        }

        return (string) $value;
    }

    private function resultAnswerKey(mixed $value, string $type): mixed
    {
        if ($value === null || $value === '') return null;
        if (in_array($type, ['multiple_choice_complex', 'ordering'], true)) {
            $decoded = is_array($value) ? $value : json_decode((string) $value, true);
            return is_array($decoded) ? array_values(array_map('strval', $decoded)) : [];
        }
        if (is_array($value)) return (string) ($value[0] ?? '');

        return (string) $value;
    }

    private function examGroup($id): ?ExamGroup
    {
        return ExamGroup::with('exam.lesson', 'exam_session', 'student.classroom')
            ->where('student_id', auth()->guard('student')->user()->id)->where('id', $id)->first();
    }

    private function grade(ExamGroup $examGroup): Grade
    {
        return Grade::firstOrCreate([
            'exam_id' => $examGroup->exam_id,
            'exam_session_id' => $examGroup->exam_session_id,
            'student_id' => auth()->guard('student')->user()->id,
        ], [
            'duration' => $examGroup->exam->duration * 60000,
            'total_correct' => 0,
            'grade' => 0,
        ]);
    }

    private function remainingDuration(Grade $grade): int
    {
        if (!$grade->expires_at) return max(0, (int) $grade->duration);

        return max(0, (int) Carbon::now()->diffInMilliseconds($grade->expires_at, false));
    }

    private function isCorrect(Question $question, mixed $submitted): bool
    {
        $key = $question->answer_key;
        if ($question->type === 'essay' || $key === null) return false;
        if (in_array($question->type, ['multiple_choice_complex', 'ordering'], true)) {
            $submitted = is_array($submitted) ? $submitted : json_decode((string) $submitted, true);
            $submitted = array_map('strval', is_array($submitted) ? $submitted : []);
            $key = array_map('strval', is_array($key) ? $key : []);
            if ($question->type === 'multiple_choice_complex') {
                sort($submitted);
                sort($key);
            }
            return $submitted === $key;
        }
        return (string) $submitted === (string) $key;
    }
}
