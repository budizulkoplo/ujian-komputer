<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LessonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // get lesson
        $lessons = $this->accessibleLessons()->when(request()->search, function ($lessons) {
            $lessons = $lessons->where('title', 'like', '%' . request()->search . '%');
        })->latest()->paginate(10);

        // append query string to pagination links
        $lessons->appends(request()->all());

        // render with inertia
        return Inertia::render('Dashboard/Lessons/Index', [
            'lessons' => $lessons
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
        abort_unless(!auth()->user()?->isTeacher(), 403);
        // validate request
        $request->validate([
            'title' => 'required|string|unique:lessons',
        ], [
            'title.unique' => 'Judul sudah ada, silahkan gunakan yang lain!',
            'title.required' => 'Judul harus diisi!'
        ]);

        // create lesson
        Lesson::create([
            'title' => $request->title,
        ]);

        // redirect back
        return back();
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
    public function edit(string $id)
    {

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        abort_unless(!auth()->user()?->isTeacher(), 403);
        // defining lesson
        $id = Lesson::findOrFail($id);

        //validate request
        $request->validate([
            'title' => 'required|string|unique:lessons,title,' . $id->title,
        ]);

        //update lesson
        $id->update([
            'title' => $request->title,
        ]);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        abort_unless(!auth()->user()?->isTeacher(), 403);
        // defining lesson
        $id = Lesson::findOrFail($id);

        // delete lesson
        $id->forceDelete();
    }

    private function accessibleLessons()
    {
        $user = auth()->user();
        if (!$user?->isTeacher()) return Lesson::query();

        return Lesson::whereHas('teachers', fn ($query) => $query->whereKey($user->teacher?->id));
    }
}
