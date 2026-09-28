<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherAssignment extends Model
{
    public $timestamps = false;
    protected $table = 'teacher_lesson_classroom';
    protected $fillable = ['teacher_id', 'lesson_id', 'classroom_id'];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }
}
