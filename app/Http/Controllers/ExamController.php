<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ExamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $search = trim((string) request('search', ''));
        $sort = (string) request('sort', 'created_at');
        $direction = request('direction') === 'asc' ? 'asc' : 'desc';
        $perPage = min(max((int) request('per_page', 10), 5), 50);
        $allowedSorts = ['title', 'lesson', 'classroom', 'semester', 'duration', 'question_count', 'created_at'];
        if (!in_array($sort, $allowedSorts, true)) $sort = 'created_at';

        $examQuery = $this->accessibleExams()
            ->when($search !== '', function ($exams) use ($search) {
                $exams->where(function ($query) use ($search) {
                    $query->where('title', 'like', '%' . $search . '%')
                        ->orWhereHas('lesson', fn ($lesson) => $lesson->where('title', 'like', '%' . $search . '%'))
                        ->orWhereHas('classroom', fn ($classroom) => $classroom->where('title', 'like', '%' . $search . '%'));
                });
            })
            ->with('lesson', 'classroom')
            ->withCount('questions');

        match ($sort) {
            'lesson' => $examQuery->orderBy(Lesson::select('title')->whereColumn('lessons.id', 'exams.lesson_id'), $direction),
            'classroom' => $examQuery->orderBy(Classroom::select('title')->whereColumn('classrooms.id', 'exams.classroom_id'), $direction),
            'question_count' => $examQuery->orderBy('questions_count', $direction),
            default => $examQuery->orderBy($sort, $direction),
        };

        $exams = $examQuery->paginate($perPage)->withQueryString();

        $lessons = $this->accessibleLessons()->get();

        $classrooms = $this->accessibleClassrooms()->get();
        $exams->getCollection()->each(function (Exam $exam) use ($classrooms) {
            $sourceGrade = $this->classroomGrade($exam->classroom->title);
            $exam->setAttribute('copy_classrooms', $classrooms->filter(function (Classroom $classroom) use ($exam, $sourceGrade) {
                return $classroom->id !== $exam->classroom_id
                    && $sourceGrade !== null
                    && $this->classroomGrade($classroom->title) === $sourceGrade
                    && $this->canTeachPair((int) $exam->lesson_id, (int) $classroom->id);
            })->values());
        });

        //render with inertia
        return Inertia::render('Dashboard/Exams/Index', [
            'exams' => $exams,
            'lessons' => $lessons,
            'classrooms' => $classrooms,
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //validate request
        $request->validate([
            'title' => 'required',
            'lesson_id' => 'required|integer',
            'classroom_id' => 'required|integer',
            'semester' => 'required|in:1,2',
            'duration' => 'required|integer',
            'description' => 'required',
            'random_question' => 'required',
            'random_answer' => 'required',
            'show_answer' => 'required',
        ]);

        abort_unless($this->canTeachPair((int) $request->lesson_id, (int) $request->classroom_id), 403);

        Exam::create([
            'title' => $request->title,
            'lesson_id' => $request->lesson_id,
            'classroom_id' => $request->classroom_id,
            'semester' => $request->semester,
            'duration' => $request->duration,
            'description' => $request->description,
            'random_question' => $request->random_question,
            'random_answer' => $request->random_answer,
            'show_answer' => $request->show_answer,
        ]);

        //redirect
        return back();
    }

    public function copy(Request $request, Exam $exam)
    {
        $this->ensureExamAccess($exam);
        $request->validate([
            'classroom_id' => ['required', 'integer', Rule::exists('classrooms', 'id')->whereNull('deleted_at')],
        ]);

        $classroom = Classroom::findOrFail($request->integer('classroom_id'));
        $sourceGrade = $this->classroomGrade($exam->classroom()->value('title'));
        if ($sourceGrade === null || $this->classroomGrade($classroom->title) !== $sourceGrade) {
            throw ValidationException::withMessages([
                'classroom_id' => 'Ujian hanya dapat disalin ke kelas pada tingkatan yang sama.',
            ]);
        }

        abort_unless($this->canTeachPair((int) $exam->lesson_id, (int) $classroom->id), 403);

        DB::transaction(function () use ($exam, $classroom) {
            $copy = $exam->replicate();
            $copy->classroom_id = $classroom->id;
            $copy->save();

            foreach ($exam->questions as $question) {
                $questionCopy = $question->replicate();
                $questionCopy->exam_id = $copy->id;
                $questionCopy->save();
            }
        });

        return back();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $exam = Exam::with('lesson', 'classroom')->findOrFail($id);
        $this->ensureExamAccess($exam);

        // Tampilkan 25 soal per halaman agar halaman tetap ringan saat jumlah soal banyak.
        $exam->setRelation('questions', $exam->questions()->paginate(25));

        //render with inertia
        return Inertia::render('Dashboard/Exams/Show', [
            'exam' => $exam,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Exam $exam)
    {
        $this->ensureExamAccess($exam);
        //validate request
        $request->validate([
            'title' => 'required',
            'lesson_id' => 'required|integer',
            'classroom_id' => 'required|integer',
            'semester' => 'required|in:1,2',
            'duration' => 'required|integer',
            'description' => 'required',
            'random_question' => 'required',
            'random_answer' => 'required',
            'show_answer' => 'required',
        ]);

        abort_unless($this->canTeachPair((int) $request->lesson_id, (int) $request->classroom_id), 403);

        $exam->update([
            'title' => $request->title,
            'lesson_id' => $request->lesson_id,
            'classroom_id' => $request->classroom_id,
            'semester' => $request->semester,
            'duration' => $request->duration,
            'description' => $request->description,
            'random_question' => $request->random_question,
            'random_answer' => $request->random_answer,
            'show_answer' => $request->show_answer,
        ]);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $exam = Exam::findOrFail($id);
        $this->ensureExamAccess($exam);

        //delete exam
        $exam->forceDelete();

        //redirect
        return back();
    }

    private function accessibleLessons()
    {
        $user = auth()->user();

        return $user?->isTeacher()
            ? Lesson::whereHas('teachers.assignments', fn ($query) => $query->where('teacher_id', $user->teacher->id))
            : Lesson::query();
    }

    private function accessibleExams()
    {
        $user = auth()->user();

        return Exam::accessibleBy($user);
    }

    private function accessibleClassrooms()
    {
        $user = auth()->user();
        if (!$user?->isTeacher()) return Classroom::query();

        return Classroom::whereExists(function ($query) use ($user) {
            $query->selectRaw('1')->from('teacher_lesson_classroom as tlc')
                ->whereColumn('tlc.classroom_id', 'classrooms.id')
                ->where('tlc.teacher_id', $user->teacher->id);
        });
    }

    private function canTeachPair(int $lessonId, int $classroomId): bool
    {
        $user = auth()->user();
        return !$user?->isTeacher() || $user->canTeach($lessonId, $classroomId);
    }

    private function ensureExamAccess(Exam $exam): void
    {
        abort_unless($this->accessibleExams()->whereKey($exam->id)->exists(), 403);
    }

    private function classroomGrade(string $title): ?string
    {
        if (!preg_match('/^\s*(?:kelas\s*)?(\d{1,2}|[ivx]+)(?=$|[\s._-]|[a-z])/iu', $title, $matches)) {
            return null;
        }

        return mb_strtolower($matches[1]);
    }
}
