<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamSession extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'exam_id',
        'title',
        'start_time',
        'end_time',
        'token',
        'token_closed_at',
    ];

    protected $casts = [
        'token_closed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ExamSession $session) {
            $session->token ??= static::generateToken();
        });
    }

    public static function generateToken(): string
    {
        do {
            $token = chr(random_int(65, 90)) . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (static::withTrashed()->where('token', $token)->exists());

        return $token;
    }

    /**
     * exam_groups
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function exam_groups()
    {
        return $this->hasMany(ExamGroup::class);
    }

    /**
     * exam
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function exam_report()
    {
        return $this->hasOne(ExamReport::class);
    }
}
