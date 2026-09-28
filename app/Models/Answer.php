<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Answer extends Model
{
    use HasFactory, SoftDeletes;

    // Jawaban ujian disimpan pada tabel khusus agar tidak bentrok dengan
    // tabel/relasi lain bernama `answers`.
    protected $table = 'exam_answers';

    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'exam_id',
        'exam_session_id',
        'question_id',
        'student_id',
        'question_order',
        'answer_order',
        'answer',
        'answer_value',
        'is_correct',
        'score',
    ];

    protected $casts = [
        'score' => 'decimal:2',
    ];

    /**
     * question
     *
     * @return void
     */
    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
