<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('type')->default('multiple_choice')->after('question');
            $table->unsignedInteger('max_score')->default(10)->after('type');
            $table->text('answer_key')->nullable()->after('answer');
        });

        Schema::table('exam_answers', function (Blueprint $table) {
            $table->text('answer_value')->nullable()->after('answer');
        });
    }

    public function down(): void
    {
        Schema::table('exam_answers', function (Blueprint $table) {
            $table->dropColumn('answer_value');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['type', 'max_score', 'answer_key']);
        });
    }
};
