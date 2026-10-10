<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Grade extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'exam_id',
        'exam_session_id',
        'student_id',
        'duration',
        'start_time',
        'expires_at',
        'end_time',
        'total_correct',
        'grade',
        'results_released',
        'is_locked',
        'cheat_count',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
        'results_released' => 'boolean',
        'cheat_count' => 'integer',
        'expires_at' => 'datetime',
    ];

    /**
     * exam
     *
     * @return void
     */
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * exam_session
     *
     * @return void
     */
    public function exam_session()
    {
        return $this->belongsTo(ExamSession::class);
    }

    /**
     * student
     *
     * @return void
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function isReleased(): bool
    {
        return (bool) $this->results_released && $this->isReadyForRelease();
    }

    public function isReadyForRelease(): bool
    {
        if (!$this->end_time) return false;

        return !Answer::where('exam_id', $this->exam_id)
            ->where('exam_session_id', $this->exam_session_id)
            ->where('student_id', $this->student_id)
            ->whereHas('question', fn ($query) => $query->where('type', 'essay'))
            ->where(function ($query) {
                $query->where('is_reviewed', false)->orWhereNull('is_reviewed');
            })->exists();
    }
}
