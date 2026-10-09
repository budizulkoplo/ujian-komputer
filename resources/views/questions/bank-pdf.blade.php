<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bank Soal - {{ $exam->lesson?->title ?: $exam->title }}</title>
    <style>
        @page { margin: 28px 34px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 11px; line-height: 1.5; }
        h1 { margin: 0 0 4px; color: #0f766e; font-size: 20px; }
        h2 { margin: 22px 0 8px; color: #0f766e; font-size: 14px; }
        .subtitle { color: #64748b; margin-bottom: 18px; }
        .meta { width: 100%; border-collapse: collapse; margin: 12px 0 20px; }
        .meta td { border: 1px solid #cbd5e1; padding: 6px 8px; }
        .meta .label { width: 22%; background: #f1f5f9; font-weight: bold; }
        .question { page-break-inside: avoid; border-top: 1px solid #cbd5e1; padding: 12px 0 10px; }
        .question:first-of-type { border-top: 0; }
        .number { display: inline-block; width: 25px; font-weight: bold; color: #0f766e; vertical-align: top; }
        .content { display: inline-block; width: 88%; vertical-align: top; }
        .content p { margin: 0 0 5px; }
        .content img { max-width: 480px; max-height: 180px; }
        .type { color: #64748b; font-size: 9px; text-transform: uppercase; }
        .options { margin: 7px 0 0 0; padding-left: 18px; }
        .options li { padding: 1px 0; }
        .answer, .explanation { margin-top: 8px; padding: 7px 9px; border-left: 3px solid #0f766e; background: #f0fdfa; }
        .explanation { border-left-color: #f59e0b; background: #fffbeb; }
        .answer strong, .explanation strong { color: #334155; }
        .empty { padding: 25px; text-align: center; color: #64748b; border: 1px dashed #cbd5e1; }
    </style>
</head>
<body>
    <h1>Bank Soal</h1>
    <div class="subtitle">Kumpulan soal beserta kunci jawaban dan pembahasan</div>

    <table class="meta">
        <tr><td class="label">Mata Pelajaran</td><td>{{ $exam->lesson?->title ?: '-' }}</td></tr>
        <tr><td class="label">Kelas</td><td>{{ $exam->classroom?->title ?: '-' }}</td></tr>
        <tr><td class="label">Semester</td><td>{{ $exam->semester ? 'Semester ' . $exam->semester : 'Belum diatur' }}</td></tr>
        <tr><td class="label">Ujian</td><td>{{ $exam->title }}</td></tr>
        <tr><td class="label">Jumlah Soal</td><td>{{ $exam->questions->count() }}</td></tr>
    </table>

    @forelse ($exam->questions as $index => $question)
        @php
            $typeLabels = [
                'multiple_choice' => 'Pilihan ganda',
                'multiple_choice_complex' => 'Pilihan ganda kompleks',
                'essay' => 'Essay',
                'ordering' => 'Urutan',
                'true_false' => 'Benar / Salah',
            ];
            $optionLetters = ['A', 'B', 'C', 'D', 'E'];
            $options = collect(range(1, 5))->mapWithKeys(fn ($number) => [$number => $question->{'option_' . $number}])->filter(fn ($value) => filled($value));
            $keys = is_array($question->answer_key) ? $question->answer_key : (filled($question->answer_key) ? [$question->answer_key] : []);
            $answerText = match ($question->type) {
                'essay' => 'Jawaban essay dinilai secara manual oleh guru.',
                'true_false' => ($keys[0] ?? '') === 'true' ? 'Benar' : (($keys[0] ?? '') === 'false' ? 'Salah' : '-'),
                default => $keys ? collect($keys)->map(fn ($key) => ($optionLetters[((int) $key) - 1] ?? $key) . (isset($options[(int) $key]) ? '. ' . strip_tags($options[(int) $key]) : ''))->implode(', ') : '-',
            };
        @endphp
        <div class="question">
            <span class="number">{{ $index + 1 }}.</span>
            <div class="content">
                <div class="type">{{ $typeLabels[$question->type] ?? $question->type }}</div>
                {!! $question->question !!}
                @if ($question->pdf_image)
                    <p><img src="{{ $question->pdf_image }}" alt="Ilustrasi soal"></p>
                @endif
                @if ($options->isNotEmpty())
                    <ol class="options" type="A">
                        @foreach ($options as $option)
                            <li>{!! $option !!}</li>
                        @endforeach
                    </ol>
                @endif
                <div class="answer"><strong>Jawaban:</strong> {!! nl2br(e($answerText)) !!}</div>
                @if (filled($question->explanation))
                    <div class="explanation"><strong>Pembahasan:</strong><br>{!! $question->explanation !!}</div>
                @else
                    <div class="explanation"><strong>Pembahasan:</strong> Belum diisi.</div>
                @endif
            </div>
        </div>
    @empty
        <div class="empty">Belum ada soal pada ujian ini.</div>
    @endforelse
</body>
</html>
