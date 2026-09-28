<?php

use App\Models\Question;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->nullable()->after('exam_id');
            $table->index(['exam_id', 'sort_order']);
        });

        Question::query()->select('id', 'exam_id')->orderBy('exam_id')->orderBy('id')->get()
            ->groupBy('exam_id')
            ->each(function ($questions) {
                foreach ($questions->values() as $index => $question) {
                    Question::whereKey($question->id)->update(['sort_order' => $index + 1]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex('questions_exam_id_sort_order_index');
            $table->dropColumn('sort_order');
        });
    }
};
