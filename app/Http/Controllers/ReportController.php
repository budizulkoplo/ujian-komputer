<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Grade;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    public function index()
    {
        $exams = $this->accessibleExams()->with('lesson', 'classroom')->get();

        return Inertia::render('Dashboard/Reports/Index', [
            'exams' => $exams,
            'grades' => $this->gradesForExams($exams),
            'selectedExamId' => null,
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
        $data = $request->validate([
            'exam_id' => ['required', 'integer'],
        ]);

        $exams = $this->accessibleExams()->with('lesson', 'classroom')->get();

        //get exam
        $exam = $this->accessibleExams()->with('lesson', 'classroom')->find($data['exam_id']);

        if ($exam) {
            $grades = $this->gradesForExams(collect([$exam]));

        } else {
            $grades = [];
        }

        return Inertia::render('Dashboard/Reports/Index', [
            'exams' => $exams,
            'grades' => $grades,
            'selectedExamId' => $exam?->id,
        ]);
    }

    public function excel(Request $request)
    {
        [$exam, $grades] = $this->reportData($request);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Ujian');
        $sheet->fromArray([[
            'No', 'Ujian', 'Mata Pelajaran', 'Kelas', 'Semester', 'Sesi',
            'Nama Siswa', 'NISN', 'Nilai', 'Status Nilai', 'Selesai Pada',
        ]]);

        foreach ($grades as $index => $grade) {
            $sheet->fromArray([[
                $index + 1,
                $exam->title,
                $exam->lesson?->title ?: '-',
                $exam->classroom?->title ?: '-',
                $exam->semester ?: '-',
                $grade->exam_session?->title ?: '-',
                $grade->student?->name ?: '-',
                $grade->student?->nisn ?: '-',
                $grade->grade ?? 0,
                $grade->isReleased() ? 'Dipublikasikan' : 'Belum dipublikasikan',
                $grade->end_time ? \Illuminate\Support\Carbon::parse($grade->end_time)->format('d/m/Y H:i') : '-',
            ]], null, 'A' . ($index + 2));
        }

        $lastRow = max(1, $grades->count() + 1);
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F766E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A1:K{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle("A1:K{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle('thin');
        $sheet->freezePane('A2');
        foreach (['A' => 7, 'B' => 28, 'C' => 22, 'D' => 15, 'E' => 12, 'F' => 18, 'G' => 28, 'H' => 18, 'I' => 10, 'J' => 22, 'K' => 20] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'laporan-ujian-' . str()->slug($exam->title) . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function pdf(Request $request)
    {
        [$exam, $grades] = $this->reportData($request);

        return Pdf::loadView('reports.pdf', compact('exam', 'grades'))
            ->setPaper('a4', 'landscape')
            ->download('laporan-ujian-' . str()->slug($exam->title) . '.pdf');
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

    private function reportData(Request $request): array
    {
        $data = $request->validate([
            'exam_id' => ['required', 'integer'],
        ]);
        $exam = $this->accessibleExams()->with('lesson', 'classroom')->findOrFail($data['exam_id']);

        return [$exam, $this->gradesForExams(collect([$exam]))];
    }
}
