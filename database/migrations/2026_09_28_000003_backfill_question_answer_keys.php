<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('questions')->where('answer', '>', 0)->orderBy('id')->chunkById(100, function ($questions) {
            foreach ($questions as $question) {
                if ($question->answer_key === null) {
                    DB::table('questions')->where('id', $question->id)->update([
                        'answer_key' => (string) $question->answer,
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        // Existing answer values remain available in the legacy answer column.
    }
};
