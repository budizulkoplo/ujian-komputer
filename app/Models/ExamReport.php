<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_session_id',
        'teacher_id',
        'exam_date',
        'start_time',
        'end_time',
        'status',
        'participant_count',
        'present_count',
        'absent_count',
        'room',
        'important_events',
        'technical_issues',
        'follow_up',
    ];

    protected $casts = [
        'exam_date' => 'date:Y-m-d',
        'participant_count' => 'integer',
        'present_count' => 'integer',
        'absent_count' => 'integer',
    ];

    public function exam_session()
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
