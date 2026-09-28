<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('teacher_lesson_classroom', function (Blueprint $table) {
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->primary(['teacher_id', 'lesson_id', 'classroom_id']);
        });

        $classroomIds = DB::table('classrooms')->pluck('id');
        foreach (DB::table('lesson_teacher')->get() as $assignment) {
            foreach ($classroomIds as $classroomId) {
                DB::table('teacher_lesson_classroom')->insertOrIgnore([
                    'teacher_id' => $assignment->teacher_id,
                    'lesson_id' => $assignment->lesson_id,
                    'classroom_id' => $classroomId,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_lesson_classroom');
    }
};
