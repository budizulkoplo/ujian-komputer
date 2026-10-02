<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamSession;
use App\Services\ExamParticipantSyncService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExamSessionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // get all exams
        $exams = $this->accessibleExams()->with('classroom', 'lesson')->get();

        //get exam_sessions
        $exam_sessions = ExamSession::whereIn('exam_id', $this->accessibleExams()->select('id'))->when(request()->search, function ($exam_sessions) {
            $exam_sessions = $exam_sessions->where('title', 'like', '%' . request()->search . '%');
        })->with('exam.classroom', 'exam.lesson', 'exam_groups')->latest()->paginate(5);

        //append query string to pagination links
        $exam_sessions->appends(['q' => request()->search]);

        //render with inertia
        return Inertia::render('Dashboard/ExamSessions/Index', [
            'exam_sessions' => $exam_sessions,
            'exams' => $exams,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //get exams
        $exams = $this->accessibleExams()->get();

        //render with inertia
        return Inertia::render('Dashboard/ExamSessions/Create', [
            'exams' => $exams,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //validate request
        $request->validate([
            'title' => 'required',
            'exam_id' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);
        $exam = $this->accessibleExams()->findOrFail($request->integer('exam_id'));

        //create exam_session
        $examSession = ExamSession::create([
            'title' => $request->title,
            'exam_id' => $exam->id,
            'start_time' => date('Y-m-d H:i:s', strtotime($request->start_time)),
            'end_time' => date('Y-m-d H:i:s', strtotime($request->end_time)),
        ]);

        $this->syncParticipants($examSession);

        //redirect
        return to_route('exam_sessions.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //get exam_session
        $exam_session = ExamSession::with('exam.classroom', 'exam.lesson')->findOrFail($id);
        $this->ensureAccess($exam_session);

        $this->syncParticipants($exam_session);

        // Daftar peserta dipaginasi dan dapat dicari berdasarkan nama atau NISN.
        $examGroups = $exam_session->exam_groups()
            ->with('student.classroom')
            ->when(request('search'), function ($query) {
                $search = request('search');
                $query->whereHas('student', fn ($student) => $student
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%"));
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();
        $exam_session->setRelation('exam_groups', $examGroups);

        //render with inertia
        return Inertia::render('Dashboard/ExamSessions/Show', [
            'exam_session' => $exam_session,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //get exam_session
        $exam_session = ExamSession::findOrFail($id);
        $this->ensureAccess($exam_session);

        //get exams
        $exams = $this->accessibleExams()->get();

        //render with inertia
        return Inertia::render('Admin/ExamSessions/Edit', [
            'exam_session' => $exam_session,
            'exams' => $exams,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ExamSession $exam_session)
    {
        $this->ensureAccess($exam_session);
        //validate request
        $request->validate([
            'title' => 'required',
            'exam_id' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        $exam = $this->accessibleExams()->findOrFail($request->integer('exam_id'));

        //update exam_session
        $exam_session->update([
            'title' => $request->title,
            'exam_id' => $exam->id,
            'start_time' => date('Y-m-d H:i:s', strtotime($request->start_time)),
            'end_time' => date('Y-m-d H:i:s', strtotime($request->end_time)),
        ]);

        $this->syncParticipants($exam_session);

        //redirect
        return to_route('exam_sessions.index');
    }

    public function reopenToken(Request $request, ExamSession $exam_session)
    {
        $this->ensureAccess($exam_session);
        $data = $request->validate([
            'reopen_until' => ['required', 'date'],
        ]);
        $until = Carbon::parse($data['reopen_until']);
        abort_if(!$until->isFuture(), 422, 'Waktu tutup token susulan harus setelah waktu sekarang.');

        $exam_session->forceFill([
            'token' => ExamSession::generateToken(),
            'token_closed_at' => null,
            'end_time' => $until,
        ])->save();

        return back()->with('success', 'Token susulan berhasil dibuka dengan kode baru: ' . $exam_session->token);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //get exam_session
        $exam_session = ExamSession::findOrFail($id);
        $this->ensureAccess($exam_session);

        //delete exam_session
        $exam_session->delete();

        //redirect
        return back();
    }

    private function syncParticipants(ExamSession $examSession): void
    {
        app(ExamParticipantSyncService::class)->syncSession($examSession);
    }

    private function accessibleExams()
    {
        return Exam::accessibleBy(auth()->user());
    }

    private function ensureAccess(ExamSession $examSession): void
    {
        abort_unless($this->accessibleExams()->whereKey($examSession->exam_id)->exists(), 403);
    }
}
