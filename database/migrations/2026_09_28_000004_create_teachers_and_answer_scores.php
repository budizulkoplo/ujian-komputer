<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exam_answers', function (Blueprint $table) {
            $table->decimal('score', 5, 2)->default(0)->after('is_correct');
        });

        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('nip')->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lesson_teacher', function (Blueprint $table) {
            $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->primary(['lesson_id', 'teacher_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_teacher');
        Schema::dropIfExists('teachers');
        Schema::table('exam_answers', function (Blueprint $table) {
            $table->dropColumn('score');
        });
    }
};
