<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamGroup;
use App\Models\ExamSession;
use App\Models\Grade;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MonitoringController extends Controller
{
    public function index(Request $request)
    {
        $examSessions = ExamSession::with('exam.lesson', 'exam.classroom')
            ->whereIn('exam_id', $this->accessibleExams()->pluck('id'))
            ->latest('start_time')
            ->get();

        $selectedSessionId = $request->integer('exam_session_id') ?: $examSessions->first()?->id;
        $session = $examSessions->firstWhere('id', $selectedSessionId);
        $participants = collect();
        $classes = collect();

        if ($session) {
            $groups = ExamGroup::with('student.classroom')
                ->where('exam_id', $session->exam_id)
                ->where('exam_session_id', $session->id)
                ->get();

            $classes = $groups->pluck('student.classroom')->filter()->unique('id')->values();
            $grades = Grade::where('exam_id', $session->exam_id)
                ->where('exam_session_id', $session->id)
                ->get()
                ->keyBy('student_id');

            $participants = $groups->map(function ($group) use ($grades) {
                $grade = $grades->get($group->student_id);
                $status = $this->status($grade);

                return [
                    'id' => $group->id,
                    'student' => [
                        'name' => $group->student?->name,
                        'nisn' => $group->student?->nisn,
                    ],
                    'classroom' => $group->student?->classroom?->title,
                    'classroom_id' => $group->student?->classroom_id,
                    'status' => $status,
                    'start_time' => $grade?->start_time,
                    'end_time' => $grade?->end_time,
                    'grade' => $grade?->grade,
                    'is_locked' => (bool) ($grade?->is_locked ?? false),
                    'can_unlock' => (bool) ($grade?->is_locked && !$grade?->end_time),
                    'cheat_count' => (int) ($grade?->cheat_count ?? 0),
                    'last_activity' => $grade?->updated_at,
                ];
            });

            if ($request->filled('classroom_id')) {
                $participants = $participants->where('classroom_id', $request->integer('classroom_id'))->values();
            }
            if ($request->filled('status') && $request->status !== 'all') {
                $participants = $participants->where('status', $request->status)->values();
            }
        }

        return Inertia::render('Dashboard/Monitoring/Index', [
            'exam_sessions' => $examSessions,
            'selected_session' => $session,
            'classes' => $classes,
            'participants' => $participants,
            'filters' => [
                'exam_session_id' => $selectedSessionId,
                'classroom_id' => $request->integer('classroom_id') ?: '',
                'status' => $request->input('status', 'all'),
            ],
        ]);
    }

    public function unlock(ExamGroup $examGroup)
    {
        abort_unless($this->accessibleExams()->whereKey($examGroup->exam_id)->exists(), 403);

        $grade = Grade::where('exam_id', $examGroup->exam_id)
            ->where('exam_session_id', $examGroup->exam_session_id)
            ->where('student_id', $examGroup->student_id)
            ->where('is_locked', true)
            ->whereNull('end_time')
            ->firstOrFail();

        $remaining = $grade->expires_at
            ? max(0, (int) Carbon::now()->diffInMilliseconds($grade->expires_at, false))
            : max(0, (int) $grade->duration);
        $grade->update([
            'is_locked' => false,
            'duration' => $remaining,
            'expires_at' => Carbon::now()->addMilliseconds($remaining),
        ]);

        return back()->with('success', 'Kunci ujian siswa berhasil dibuka.');
    }

    private function status(?Grade $grade): string
    {
        if ($grade?->is_locked) return 'locked';
        if ($grade?->end_time) return 'finished';
        if ($grade?->start_time) return 'in_progress';
        return 'not_started';
    }

    private function accessibleExams()
    {
        return Exam::accessibleBy(auth()->user());
    }
}
