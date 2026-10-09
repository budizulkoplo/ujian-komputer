<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Question;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Barryvdh\DomPDF\Facade\Pdf;

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
        $data['sort_order'] = ((int) Question::where('exam_id', $exam->id)->max('sort_order')) + 1;
        Question::create($data);

        return to_route('exams.show', $exam);
    }

    public function downloadTemplate(Exam $exam)
    {
        $this->ensureExamAccess($exam);

        $spreadsheet = new Spreadsheet();
        $choiceSheet = $spreadsheet->getActiveSheet();
        $choiceSheet->setTitle('Pilihan Ganda');
        $this->configureTemplateSheet($choiceSheet, [
            'No', 'Jenis Soal', 'Pertanyaan', 'Pembahasan', 'Opsi A', 'Opsi B', 'Opsi C',
            'Opsi D', 'Opsi E', 'Jawaban Benar', 'Skor Maksimal', 'Video URL (opsional)',
        ]);
        $choiceSheet->fromArray([
            ['CONTOH - HAPUS', 'Pilihan Ganda', 'Contoh: ibukota Indonesia adalah ...', 'Pembahasan contoh soal.', 'Jakarta', 'Bandung', 'Surabaya', '', '', 'A', 10, ''],
            ['CONTOH - HAPUS', 'Pilihan Ganda Kompleks', 'Contoh: pilih semua jawaban yang benar ...', 'Pembahasan contoh soal.', 'A', 'B', 'C', '', '', 'A,C', 10, ''],
            ['CONTOH - HAPUS', 'Urutan', 'Contoh: urutkan langkah berikut ...', 'Pembahasan contoh soal.', 'Langkah 1', 'Langkah 2', 'Langkah 3', '', '', 'A,B,C', 10, ''],
            ['CONTOH - HAPUS', 'Benar/Salah', 'Contoh: matahari terbit dari timur.', 'Pembahasan contoh soal.', '', '', '', '', '', 'Benar', 10, ''],
        ], null, 'A2');

        $essaySheet = $spreadsheet->createSheet();
        $essaySheet->setTitle('Essay');
        $this->configureTemplateSheet($essaySheet, ['No', 'Pertanyaan', 'Pembahasan', 'Skor Maksimal', 'Video URL (opsional)']);
        $essaySheet->fromArray([
            ['CONTOH - HAPUS', 'Contoh: jelaskan pengertian ...', 'Poin-poin jawaban yang diharapkan.', 10, ''],
        ], null, 'A2');

        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'template-soal.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function uploadEditorImage(Request $request)
    {
        $request->validate([
            'upload' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:4096'],
        ]);

        $path = $request->file('upload')->store('questions/editor', 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($path),
        ]);
    }

    public function import(Request $request, Exam $exam)
    {
        $this->ensureExamAccess($exam);
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages(['file' => 'File Excel tidak dapat dibaca. Pastikan menggunakan template yang disediakan.']);
        }

        $rowsToCreate = [];
        $errors = [];
        $sheetDefinitions = [
            ['index' => 0, 'type' => 'choice'],
            ['index' => 1, 'type' => 'essay'],
        ];

        foreach ($sheetDefinitions as $definition) {
            if ($spreadsheet->getSheetCount() <= $definition['index']) continue;

            $rows = $spreadsheet->getSheet($definition['index'])->toArray(null, true, true, true);
            foreach (array_slice($rows, 1, null, true) as $rowNumber => $row) {
                $questionText = $definition['type'] === 'essay' ? ($row['B'] ?? '') : ($row['C'] ?? '');
                $number = trim((string) ($row['A'] ?? ''));
                if (trim((string) $questionText) === '' && $number === '') continue;
                if (str_starts_with(mb_strtolower($number), 'contoh')) continue;

                try {
                    $data = $definition['type'] === 'essay'
                        ? [
                            'question' => trim((string) ($row['B'] ?? '')),
                            'explanation' => trim((string) ($row['C'] ?? '')),
                            'type' => 'essay',
                            'max_score' => $row['D'] ?? '',
                            'video_url' => trim((string) ($row['E'] ?? '')),
                            'answer_key' => null,
                        ]
                        : [
                            'question' => trim((string) ($row['C'] ?? '')),
                            'explanation' => trim((string) ($row['D'] ?? '')),
                            'type' => $this->importedQuestionType($row['B'] ?? ''),
                            'max_score' => $row['K'] ?? '',
                            'option_1' => trim((string) ($row['E'] ?? '')),
                            'option_2' => trim((string) ($row['F'] ?? '')),
                            'option_3' => trim((string) ($row['G'] ?? '')),
                            'option_4' => trim((string) ($row['H'] ?? '')),
                            'option_5' => trim((string) ($row['I'] ?? '')),
                            'answer_key' => $this->importedAnswerKey($row['J'] ?? '', $this->importedQuestionType($row['B'] ?? '')),
                            'video_url' => trim((string) ($row['L'] ?? '')),
                        ];

                    $rowsToCreate[] = array_merge($this->normalizeQuestionData($data, 'Baris ' . $rowNumber), [
                        'exam_id' => $exam->id,
                    ]);
                } catch (ValidationException $exception) {
                    $errors[] = implode(' ', Arr::flatten($exception->errors()));
                } catch (\Throwable $exception) {
                    $errors[] = 'Baris ' . $rowNumber . ': format data tidak sesuai.';
                }
            }
        }

        if ($errors) {
            throw ValidationException::withMessages(['file' => implode(' ', $errors)]);
        }

        DB::transaction(function () use ($rowsToCreate, $exam) {
            $nextOrder = ((int) Question::where('exam_id', $exam->id)->max('sort_order')) + 1;
            foreach ($rowsToCreate as $data) {
                $data['sort_order'] = $nextOrder++;
                Question::create($data);
            }
        });

        return to_route('exams.show', $exam)->with('success', count($rowsToCreate) . ' soal berhasil diimport.');
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
        abort_unless($question->exam_id === $exam->id, 404);

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
        abort_unless($question->exam_id === $exam->id, 404);
        $data = $this->validatedData($request);
        if (
            array_key_exists('image', $data)
            && $question->image
            && $question->image !== $data['image']
            && !Question::where('image', $question->image)->where('id', '!=', $question->id)->exists()
        ) {
            Storage::disk('public')->delete($question->image);
        }
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
        abort_unless($question->exam_id === $exam->id, 404);
        $question->forceDelete();

        return back();
    }

    public function pdf(Exam $exam)
    {
        $this->ensureExamAccess($exam);
        $exam->load(['lesson', 'classroom', 'questions']);
        $exam->questions->each(function (Question $question) {
            $path = ltrim(str_replace('/storage/', '', (string) $question->image), '/');
            $question->setAttribute('pdf_image', $path && Storage::disk('public')->exists($path)
                ? 'data:' . (mime_content_type(Storage::disk('public')->path($path)) ?: 'image/jpeg') . ';base64,' . base64_encode(Storage::disk('public')->get($path))
                : null);
        });

        $filename = 'bank-soal-' . str()->slug($exam->lesson?->title ?: $exam->title) . '-semester-' . ($exam->semester ?: 'belum-diatur') . '.pdf';

        return Pdf::loadView('questions.bank-pdf', ['exam' => $exam])
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'question' => ['required', 'string'],
            'explanation' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
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

        $result = $this->normalizeQuestionData($data, '');
        if ($request->hasFile('image')) {
            $result['image'] = $request->file('image')->store('questions', 'public');
        } elseif ($request->boolean('remove_image')) {
            $result['image'] = null;
        }

        return $result;
    }

    private function normalizeQuestionData(array $data, string $context = ''): array
    {
        $prefix = $context !== '' ? $context . ': ' : '';
        $errorField = fn (string $fallback): string => $context !== '' ? 'file' : $fallback;
        if (trim((string) ($data['question'] ?? '')) === '') {
            $this->validationError($errorField('question'), $prefix . 'pertanyaan wajib diisi.');
        }
        if (!in_array($data['type'] ?? null, ['multiple_choice', 'multiple_choice_complex', 'essay', 'ordering', 'true_false'], true)) {
            $this->validationError($errorField('type'), $prefix . 'jenis soal tidak dikenal.');
        }
        if (!is_numeric($data['max_score'] ?? null) || (int) $data['max_score'] < 1) {
            $this->validationError($errorField('max_score'), $prefix . 'skor maksimal harus berupa angka minimal 1.');
        }

        $type = $data['type'];
        $options = collect(range(1, 5))
            ->filter(fn ($number) => trim(strip_tags($data["option_{$number}"] ?? '')) !== '');

        if (in_array($type, ['multiple_choice', 'multiple_choice_complex', 'ordering'], true) && $options->count() < 2) {
            $this->validationError($errorField('option_1'), $prefix . 'minimal dua pilihan jawaban harus diisi.');
        }

        $answerKey = $data['answer_key'] ?? null;
        if ($type === 'multiple_choice') {
            $answerKey = (string) ($answerKey ?: ($data['answer'] ?? ''));
            if (!in_array((int) $answerKey, $options->values()->all(), true)) {
                $this->validationError($errorField('answer_key'), $prefix . 'jawaban benar tidak sesuai dengan pilihan yang terisi.');
            }
        } elseif ($type === 'multiple_choice_complex') {
            $answerKey = collect(is_array($answerKey) ? $answerKey : [$answerKey])
                ->filter(fn ($value) => in_array((string) $value, ['1', '2', '3', '4', '5'], true))
                ->map(fn ($value) => (string) $value)
                ->unique()->values()->all();
            if (!$answerKey || collect($answerKey)->contains(fn ($value) => !in_array((int) $value, $options->values()->all(), true))) {
                $this->validationError($errorField('answer_key'), $prefix . 'jawaban benar tidak sesuai dengan pilihan yang terisi.');
            }
        } elseif ($type === 'ordering') {
            $answerKey = collect(is_array($answerKey) ? $answerKey : [])
                ->map(fn ($value) => (string) $value)
                ->filter(fn ($value) => ctype_digit($value) && (int) $value >= 1 && (int) $value <= 5)
                ->values()->all();
            $filledOptionNumbers = $options->values()->map(fn ($index) => (string) $index)->values()->all();
            $answerNumbers = array_values(array_unique($answerKey));
            sort($answerNumbers);
            sort($filledOptionNumbers);
            if (count($answerKey) !== $options->count() || $answerNumbers !== $filledOptionNumbers) {
                $this->validationError($errorField('answer_key'), $prefix . 'urutan jawaban benar harus mencakup semua pilihan yang diisi satu kali.');
            }
        } elseif ($type === 'true_false') {
            $answerKey = in_array((string) $answerKey, ['true', 'false'], true) ? (string) $answerKey : null;
            if (!$answerKey) {
                $this->validationError($errorField('answer_key'), $prefix . 'jawaban harus Benar atau Salah.');
            }
        } else {
            $answerKey = null;
        }

        $result = [
            'question' => $data['question'],
            'explanation' => $data['explanation'] ?? null,
            'video_url' => $data['video_url'] ?? null,
            'type' => $type,
            'max_score' => (int) $data['max_score'],
            'option_1' => $data['option_1'] ?? null,
            'option_2' => $data['option_2'] ?? null,
            'option_3' => $data['option_3'] ?? null,
            'option_4' => $data['option_4'] ?? null,
            'option_5' => $data['option_5'] ?? null,
            'answer' => is_numeric($answerKey) ? (int) $answerKey : 0,
            'answer_key' => $answerKey,
        ];

        return $result;
    }

    private function importedQuestionType(mixed $value): string
    {
        return match (mb_strtolower(trim((string) $value))) {
            'pilihan ganda kompleks', 'multiple_choice_complex', 'multiple choice complex' => 'multiple_choice_complex',
            'essay', 'esai' => 'essay',
            'urutan', 'ordering' => 'ordering',
            'benar/salah', 'benar salah', 'true_false', 'true false' => 'true_false',
            default => 'multiple_choice',
        };
    }

    private function importedAnswerKey(mixed $value, string $type): mixed
    {
        $value = trim((string) $value);
        if ($type === 'true_false') {
            return match (mb_strtolower($value)) {
                'benar', 'true', 'b', '1' => 'true',
                'salah', 'false', 's', '0' => 'false',
                default => $value,
            };
        }
        if ($type === 'multiple_choice') return (string) $this->optionLetterToNumber($value);

        return collect(preg_split('/[,;|]+/', $value, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($item) => (string) $this->optionLetterToNumber(trim($item)))
            ->values()->all();
    }

    private function optionLetterToNumber(string $value): int
    {
        if (ctype_digit($value)) return (int) $value;
        $letter = mb_strtoupper(trim($value));
        return match ($letter) {
            'A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5, default => 0,
        };
    }

    private function configureTemplateSheet($sheet, array $headers): void
    {
        $sheet->fromArray([$headers], null, 'A1');
        $lastColumn = $sheet->getHighestColumn();
        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F766E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:' . $lastColumn . '1');
        foreach (range('A', $lastColumn) as $column) $sheet->getColumnDimension($column)->setWidth($column === 'C' ? 42 : 20);
        $sheet->getRowDimension(1)->setRowHeight(28);
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
