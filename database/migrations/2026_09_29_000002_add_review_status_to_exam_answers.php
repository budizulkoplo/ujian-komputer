<?php

use App\Models\Question;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exam_answers', function (Blueprint $table) {
            $table->boolean('is_reviewed')->default(false)->after('score');
            $table->text('teacher_comment')->nullable()->after('is_reviewed');
        });

        DB::table('exam_answers')
            ->join('questions', 'questions.id', '=', 'exam_answers.question_id')
            ->where('questions.type', '!=', 'essay')
            ->update(['exam_answers.is_reviewed' => true]);
    }

    public function down(): void
    {
        Schema::table('exam_answers', function (Blueprint $table) {
            $table->dropColumn(['is_reviewed', 'teacher_comment']);
        });
    }
};
