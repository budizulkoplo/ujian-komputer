@php
    $session = $report->exam_session;
    $exam = $session?->exam;
    $formatDate = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->locale('id')->translatedFormat('d F Y') : '-';
    $formatTime = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('H:i') : '-';
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Berita Acara - {{ $exam?->title }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f1f5f9; color: #0f172a; font-family: Arial, sans-serif; font-size: 12px; }
        .toolbar { display: flex; gap: 8px; justify-content: center; padding: 18px; }
        .toolbar a, .toolbar button { border: 0; border-radius: 5px; color: #fff; cursor: pointer; font-size: 12px; padding: 9px 14px; text-decoration: none; }
        .print { background: #0f766e; } .pdf { background: #dc2626; }
        .sheet { background: #fff; margin: 0 auto 24px; max-width: 794px; min-height: 1123px; padding: 42px 48px; }
        h1 { font-size: 20px; letter-spacing: .4px; margin: 0; text-align: center; text-transform: uppercase; }
        h2 { border-bottom: 1px solid #cbd5e1; font-size: 13px; margin: 25px 0 10px; padding-bottom: 6px; text-transform: uppercase; }
        .subtitle { color: #475569; margin: 8px 0 0; text-align: center; }
        .info { border: 1px solid #cbd5e1; border-radius: 4px; display: grid; grid-template-columns: 1fr 1fr; margin-top: 22px; }
        .info div { border-bottom: 1px solid #e2e8f0; padding: 9px 11px; } .info div:nth-last-child(-n+2) { border-bottom: 0; }
        .label { color: #64748b; display: block; font-size: 10px; margin-bottom: 3px; text-transform: uppercase; }
        table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid #cbd5e1; padding: 7px 8px; text-align: left; vertical-align: top; } th { background: #e2e8f0; font-size: 10px; text-transform: uppercase; } .center { text-align: center; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin: 12px 0; } .stat { border: 1px solid #cbd5e1; padding: 9px; text-align: center; } .stat strong { display: block; font-size: 17px; margin-bottom: 3px; }
        .notes { border: 1px solid #cbd5e1; min-height: 48px; padding: 9px; white-space: pre-line; }
        .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 55px; text-align: center; } .signature-space { height: 65px; }
        @media print { body { background: #fff; } .toolbar { display: none; } .sheet { margin: 0; max-width: none; min-height: auto; padding: 0; } }
    </style>
</head>
<body>
    @if (!$isPdf)
        <div class="toolbar">
            <button class="print" onclick="window.print()">Cetak Berita Acara</button>
            <a class="pdf" href="{{ route('exam_reports.pdf', $report->id) }}">Export PDF</a>
        </div>
    @endif
    <main class="sheet">
        <h1>Berita Acara Pelaksanaan Ujian</h1>
        <p class="subtitle">Dokumen pelaksanaan tes dan daftar kehadiran peserta</p>

        <div class="info">
            <div><span class="label">Judul Ujian</span>{{ $exam?->title ?: '-' }}</div>
            <div><span class="label">Mata Pelajaran</span>{{ $exam?->lesson?->title ?: '-' }}</div>
            <div><span class="label">Kelas</span>{{ $exam?->classroom?->title ?: '-' }}</div>
            <div><span class="label">Tanggal</span>{{ $formatDate($report->exam_date) }}</div>
            <div><span class="label">Waktu</span>{{ $formatTime($report->start_time) }} - {{ $formatTime($report->end_time) }}</div>
            <div><span class="label">Ruangan</span>{{ $report->room ?: '-' }}</div>
            <div><span class="label">Guru Pengawas</span>{{ $report->teacher?->user?->name ?: '-' }}</div>
            <div><span class="label">Status</span>{{ $report->status }}</div>
        </div>

        <h2>Rekap Kehadiran</h2>
        <div class="stats">
            <div class="stat"><strong>{{ $report->participant_count }}</strong>Jumlah Peserta</div>
            <div class="stat"><strong>{{ $report->present_count }}</strong>Hadir</div>
            <div class="stat"><strong>{{ $report->absent_count }}</strong>Tidak Hadir</div>
        </div>
        <table>
            <thead><tr><th class="center" style="width: 36px">No</th><th>Nama Siswa</th><th>NISN</th><th>Kelas</th><th class="center">Kehadiran</th></tr></thead>
            <tbody>
                @forelse ($participants as $index => $participant)
                    <tr><td class="center">{{ $index + 1 }}</td><td>{{ $participant['name'] ?: '-' }}</td><td>{{ $participant['nisn'] ?: '-' }}</td><td>{{ $participant['classroom'] ?: '-' }}</td><td class="center">{{ $participant['present'] ? 'Hadir' : 'Tidak hadir' }}</td></tr>
                @empty
                    <tr><td colspan="5" class="center">Belum ada data peserta.</td></tr>
                @endforelse
            </tbody>
        </table>

        <h2>Catatan Pelaksanaan</h2>
        <p><b>Kejadian Penting</b></p><div class="notes">{{ $report->important_events ?: '-' }}</div>
        <p><b>Kendala Teknis</b></p><div class="notes">{{ $report->technical_issues ?: '-' }}</div>
        <p><b>Tindak Lanjut</b></p><div class="notes">{{ $report->follow_up ?: '-' }}</div>

        <div class="signatures"><div>Guru Pengawas<div class="signature-space"></div><b>{{ $report->teacher?->user?->name ?: '................................' }}</b></div><div>Mengetahui,<div class="signature-space"></div><b>................................</b></div></div>
    </main>
</body>
</html>
