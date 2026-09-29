<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exam_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->unique()->constrained('exam_sessions')->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->date('exam_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['Draft', 'Final'])->default('Draft');
            $table->unsignedInteger('participant_count')->default(0);
            $table->unsignedInteger('present_count')->default(0);
            $table->unsignedInteger('absent_count')->default(0);
            $table->string('room')->nullable();
            $table->text('important_events')->nullable();
            $table->text('technical_issues')->nullable();
            $table->text('follow_up')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_reports');
    }
};
