<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamReport;
use App\Models\ExamSession;
use App\Models\Grade;
use App\Models\Teacher;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ExamReportController extends Controller
{
    public function index(Request $request)
    {
        $sessions = $this->accessibleSessions()
            ->with(['exam.lesson', 'exam.classroom', 'exam_report.teacher.user'])
            ->withCount('exam_groups')
            ->latest('start_time')
            ->get();

        $selectedId = $request->integer('exam_session_id') ?: $sessions->first()?->id;
        $session = $sessions->firstWhere('id', $selectedId);
        $participants = collect();

        if ($session) {
            $groups = $session->exam_groups()->with('student.classroom')->get();
            $grades = Grade::where('exam_id', $session->exam_id)
                ->where('exam_session_id', $session->id)
                ->get()->keyBy('student_id');

            $participants = $groups->map(function ($group) use ($grades) {
                $grade = $grades->get($group->student_id);
                return [
                    'student_id' => $group->student_id,
                    'name' => $group->student?->name,
                    'nisn' => $group->student?->nisn,
                    'present' => (bool) ($grade?->start_time || $grade?->end_time),
                ];
            })->values();
        }

        $teachers = auth()->user()?->isTeacher()
            ? Teacher::with('user')->whereKey(auth()->user()->teacher?->id)->get()
            : Teacher::with('user')->orderBy('id')->get();

        return Inertia::render('Dashboard/ExamReports/Index', [
            'exam_sessions' => $sessions,
            'selected_session' => $session,
            'participants' => $participants,
            'teachers' => $teachers,
        ]);
    }

    public function store(Request $request, ExamSession $exam_session)
    {
        $this->ensureSessionAccess($exam_session);
        $data = $this->validated($request, $exam_session);
        $report = ExamReport::updateOrCreate(
            ['exam_session_id' => $exam_session->id],
            [...$data, 'exam_session_id' => $exam_session->id]
        );
        $exam_session->forceFill(['token_closed_at' => now()])->save();

        return to_route('exam_reports.index', ['exam_session_id' => $exam_session->id])
            ->with('success', 'Berita acara berhasil disimpan.');
    }

    public function update(Request $request, ExamReport $exam_report)
    {
        $exam_report->load('exam_session');
        $this->ensureSessionAccess($exam_report->exam_session);
        $exam_report->update($this->validated($request, $exam_report->exam_session));
        $exam_report->exam_session->forceFill(['token_closed_at' => now()])->save();

        return back()->with('success', 'Berita acara berhasil diperbarui.');
    }

    public function reopenToken(Request $request, ExamReport $exam_report)
    {
        $exam_report->load('exam_session');
        $this->ensureSessionAccess($exam_report->exam_session);
        $data = $request->validate([
            'reopen_until' => ['required', 'date'],
        ]);
        $until = Carbon::parse($data['reopen_until']);
        abort_if(!$until->isFuture(), 422, 'Waktu tutup token susulan harus setelah waktu sekarang.');

        $session = $exam_report->exam_session;
        $session->forceFill([
            'token' => ExamSession::generateToken(),
            'token_closed_at' => null,
            'end_time' => $until,
        ])->save();

        return back()->with('success', 'Token susulan berhasil dibuka dengan kode baru: ' . $session->token);
    }

    public function print(ExamReport $exam_report)
    {
        $data = $this->reportViewData($exam_report);
        $data['isPdf'] = false;

        return view('exam_reports.print', $data);
    }

    public function pdf(ExamReport $exam_report)
    {
        $data = $this->reportViewData($exam_report);
        $data['isPdf'] = true;
        $filename = 'berita-acara-' . str()->slug($data['report']->exam_session?->exam?->title ?: 'ujian') . '.pdf';

        return Pdf::loadView('exam_reports.print', $data)
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    private function validated(Request $request, ExamSession $session): array
    {
        $groupCount = $session->exam_groups()->count();
        $presentCount = $this->presentCount($session);
        $teacherRule = auth()->user()?->isTeacher()
            ? ['nullable', Rule::in([auth()->user()->teacher?->id])]
            : ['nullable', 'exists:teachers,id'];

        $data = $request->validate([
            'teacher_id' => $teacherRule,
            'exam_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after_or_equal:start_time'],
            'status' => ['required', Rule::in(['Draft', 'Final'])],
            'room' => ['nullable', 'string', 'max:255'],
            'important_events' => ['nullable', 'string'],
            'technical_issues' => ['nullable', 'string'],
            'follow_up' => ['nullable', 'string'],
        ]);

        return [...$data,
            'participant_count' => $groupCount,
            'present_count' => $presentCount,
            'absent_count' => max(0, $groupCount - $presentCount),
        ];
    }

    private function presentCount(ExamSession $session): int
    {
        return Grade::where('exam_id', $session->exam_id)
            ->where('exam_session_id', $session->id)
            ->where(function ($query) {
                $query->whereNotNull('start_time')->orWhereNotNull('end_time');
            })->count();
    }

    private function reportViewData(ExamReport $report): array
    {
        $report->load(['exam_session.exam.lesson', 'exam_session.exam.classroom', 'teacher.user']);
        $session = $report->exam_session;
        $groups = $session->exam_groups()->with('student.classroom')->get();
        $grades = Grade::where('exam_id', $session->exam_id)
            ->where('exam_session_id', $session->id)
            ->get()->keyBy('student_id');

        $participants = $groups->map(function ($group) use ($grades) {
            $grade = $grades->get($group->student_id);
            return [
                'name' => $group->student?->name,
                'nisn' => $group->student?->nisn,
                'classroom' => $group->student?->classroom?->title,
                'present' => (bool) ($grade?->start_time || $grade?->end_time),
                'start_time' => $grade?->start_time,
                'end_time' => $grade?->end_time,
            ];
        })->values();

        return compact('report', 'participants');
    }

    private function accessibleSessions()
    {
        return ExamSession::whereIn('exam_id', Exam::accessibleBy(auth()->user())->select('id'))
            ->whereHas('exam_groups')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('grades')
                    ->whereColumn('grades.exam_id', 'exam_sessions.exam_id')
                    ->whereColumn('grades.exam_session_id', 'exam_sessions.id')
                    ->whereNotNull('grades.end_time');
            });
    }

    private function ensureSessionAccess(ExamSession $session): void
    {
        abort_unless($this->accessibleSessions()->whereKey($session->id)->exists(), 403);
    }
}
