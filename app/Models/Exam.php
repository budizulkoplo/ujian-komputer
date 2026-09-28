<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Exam extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'lesson_id',
        'classroom_id',
        'duration',
        'description',
        'random_question',
        'random_answer',
        'show_answer',
    ];

    /**
     * lesson
     *
     * @return void
     */
    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * classroom
     *
     * @return void
     */
    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * questions
     *
     * @return void
     */
    public function questions()
    {
        return $this->hasMany(Question::class)
            ->orderByRaw('COALESCE(sort_order, id) ASC')
            ->orderBy('id', 'ASC');
    }

    public function scopeAccessibleBy(Builder $query, ?User $user): Builder
    {
        if (!$user || !$user->isTeacher()) return $query;

        return $query->whereExists(function ($assignment) use ($user) {
            $assignment->select(DB::raw(1))
                ->from('teacher_lesson_classroom as tlc')
                ->join('teachers as teacher', 'teacher.id', '=', 'tlc.teacher_id')
                ->whereColumn('tlc.lesson_id', 'exams.lesson_id')
                ->whereColumn('tlc.classroom_id', 'exams.classroom_id')
                ->where('teacher.user_id', $user->id)
                ->whereNull('teacher.deleted_at');
        });
    }
}
