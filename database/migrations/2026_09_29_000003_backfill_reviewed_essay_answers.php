<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('exam_answers')
            ->join('questions', 'questions.id', '=', 'exam_answers.question_id')
            ->where('questions.type', 'essay')
            ->where('exam_answers.score', '>', 0)
            ->update(['exam_answers.is_reviewed' => true]);
    }

    public function down(): void
    {
        // Existing review state should not be removed when rolling back this data fix.
    }
};
