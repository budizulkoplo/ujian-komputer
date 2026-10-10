@php
    $formatDate = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('d/m/Y H:i') : '-';
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Ujian - {{ $exam->title }}</title>
    <style>
        * { box-sizing: border-box; }
        body { color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 10px; margin: 0; }
        h1 { font-size: 18px; margin: 0; text-align: center; }
        .subtitle { color: #526174; margin: 5px 0 16px; text-align: center; }
        .info { border: 1px solid #b8c4d3; margin-bottom: 16px; padding: 9px 12px; }
        .info table { border: 0; margin: 0; }
        .info td { border: 0; padding: 2px 14px 2px 0; }
        .label { color: #607086; font-size: 8px; text-transform: uppercase; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #b8c4d3; padding: 6px 7px; vertical-align: top; }
        th { background: #0f766e; color: #fff; font-size: 8px; text-align: left; text-transform: uppercase; }
        .center { text-align: center; }
        .footer { color: #607086; margin-top: 12px; }
    </style>
</head>
<body>
    <h1>LAPORAN HASIL UJIAN</h1>
    <p class="subtitle">Rekap nilai peserta ujian</p>
    <div class="info">
        <table>
            <tr>
                <td><span class="label">Ujian</span><br>{{ $exam->title }}</td>
                <td><span class="label">Mata Pelajaran</span><br>{{ $exam->lesson?->title ?: '-' }}</td>
                <td><span class="label">Kelas</span><br>{{ $exam->classroom?->title ?: '-' }}</td>
                <td><span class="label">Semester</span><br>{{ $exam->semester ?: '-' }}</td>
            </tr>
        </table>
    </div>
    <table>
        <thead>
            <tr>
                <th class="center" style="width: 35px">No</th>
                <th>Nama Siswa</th>
                <th>NISN</th>
                <th>Sesi</th>
                <th class="center">Nilai</th>
                <th>Status Nilai</th>
                <th>Selesai Pada</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($grades as $index => $grade)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $grade->student?->name ?: '-' }}</td>
                    <td>{{ $grade->student?->nisn ?: '-' }}</td>
                    <td>{{ $grade->exam_session?->title ?: '-' }}</td>
                    <td class="center"><b>{{ $grade->grade ?? 0 }}</b></td>
                    <td>{{ $grade->isReleased() ? 'Dipublikasikan' : 'Belum dipublikasikan' }}</td>
                    <td>{{ $formatDate($grade->end_time) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="center">Belum ada hasil ujian.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="footer">Dicetak pada {{ now()->format('d/m/Y H:i') }}.</p>
</body>
</html>
