<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Question;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuestionController extends Controller
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
    public function create(Exam $exam)
    {
        $this->ensureExamAccess($exam);

        //render with inertia
        return Inertia::render('Dashboard/Questions/Create', [
            'exam' => $exam,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Exam $exam)
    {
        $this->ensureExamAccess($exam);
        $data = $this->validatedData($request);
        $data['exam_id'] = $exam->id;
        Question::create($data);

        return to_route('exams.show', $exam);
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
    public function edit(Exam $exam, Question $question)
    {
        $this->ensureExamAccess($exam);

        //render with inertia
        return Inertia::render('Dashboard/Questions/Edit', [
            'exam' => $exam,
            'question' => $question,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Exam $exam, Question $question)
    {
        $this->ensureExamAccess($exam);
        $data = $this->validatedData($request);
        if (!array_key_exists('image', $data)) unset($data['image']);
        $question->update($data);

        return to_route('exams.show', $exam);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Exam $exam, Question $question)
    {
        $this->ensureExamAccess($exam);
        $question->forceDelete();

        return back();
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'question' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
            'video_url' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(['multiple_choice', 'multiple_choice_complex', 'essay', 'ordering', 'true_false'])],
            'max_score' => ['required', 'integer', 'min:1'],
            'option_1' => ['nullable', 'string'],
            'option_2' => ['nullable', 'string'],
            'option_3' => ['nullable', 'string'],
            'option_4' => ['nullable', 'string'],
            'option_5' => ['nullable', 'string'],
            'answer_key' => ['nullable'],
        ]);

        $type = $data['type'];
        $options = collect(range(1, 5))
            ->filter(fn ($number) => trim(strip_tags($data["option_{$number}"] ?? '')) !== '');

        if (in_array($type, ['multiple_choice', 'multiple_choice_complex', 'ordering'], true) && $options->count() < 2) {
            $this->validationError('option_1', 'Minimal dua pilihan jawaban harus diisi.');
        }

        $answerKey = $data['answer_key'] ?? null;
        if ($type === 'multiple_choice') {
            $answerKey = (string) ($answerKey ?: $request->input('answer'));
            if (!in_array((int) $answerKey, $options->keys()->all(), true)) {
                $this->validationError('answer_key', 'Pilih satu jawaban benar dari pilihan yang terisi.');
            }
        } elseif ($type === 'multiple_choice_complex') {
            $answerKey = collect(is_array($answerKey) ? $answerKey : [$answerKey])
                ->filter(fn ($value) => in_array((string) $value, ['1', '2', '3', '4', '5'], true))
                ->map(fn ($value) => (string) $value)
                ->unique()->values()->all();
            if (!$answerKey || collect($answerKey)->contains(fn ($value) => !in_array((int) $value, $options->keys()->all(), true))) {
                $this->validationError('answer_key', 'Pilih minimal satu jawaban benar dari pilihan yang terisi.');
            }
        } elseif ($type === 'ordering') {
            $answerKey = collect(is_array($answerKey) ? $answerKey : [])
                ->map(fn ($value) => (string) $value)
                ->filter(fn ($value) => ctype_digit($value) && (int) $value >= 1 && (int) $value <= 5)
                ->values()->all();
            $filledOptionNumbers = $options->keys()->map(fn ($index) => (string) $index)->values()->all();
            $answerNumbers = array_values(array_unique($answerKey));
            sort($answerNumbers);
            sort($filledOptionNumbers);
            if (count($answerKey) !== $options->count() || $answerNumbers !== $filledOptionNumbers) {
                $this->validationError('answer_key', 'Urutan jawaban benar harus mencakup semua pilihan yang diisi satu kali.');
            }
        } elseif ($type === 'true_false') {
            $answerKey = in_array((string) $answerKey, ['true', 'false'], true) ? (string) $answerKey : null;
            if (!$answerKey) {
                $this->validationError('answer_key', 'Pilih jawaban Benar atau Salah.');
            }
        } else {
            $answerKey = null;
        }

        $result = [
            'question' => $data['question'],
            'video_url' => $data['video_url'] ?? null,
            'type' => $type,
            'max_score' => $data['max_score'],
            'option_1' => $data['option_1'] ?? null,
            'option_2' => $data['option_2'] ?? null,
            'option_3' => $data['option_3'] ?? null,
            'option_4' => $data['option_4'] ?? null,
            'option_5' => $data['option_5'] ?? null,
            'answer' => is_numeric($answerKey) ? (int) $answerKey : 0,
            'answer_key' => $answerKey,
        ];

        if ($request->hasFile('image')) {
            $result['image'] = $request->file('image')->store('questions', 'public');
        }

        return $result;
    }

    private function validationError(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }

    private function ensureExamAccess(Exam $exam): void
    {
        $user = auth()->user();
        abort_unless(Exam::accessibleBy($user)->whereKey($exam->id)->exists(), 403);
    }
}
