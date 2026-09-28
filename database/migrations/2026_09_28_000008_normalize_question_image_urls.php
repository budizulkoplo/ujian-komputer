<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $columns = ['question', 'option_1', 'option_2', 'option_3', 'option_4', 'option_5'];
        $oldUrls = array_unique([
            rtrim((string) config('app.url'), '/') . '/storage/',
            'http://localhost/storage/',
            'http://localhost:8000/storage/',
        ]);

        foreach ($columns as $column) {
            foreach ($oldUrls as $oldUrl) {
                if ($oldUrl === '/storage/') continue;

                DB::statement(
                    "UPDATE questions SET `{$column}` = REPLACE(`{$column}`, ?, ?) WHERE `{$column}` LIKE ?",
                    [$oldUrl, '/storage/', '%' . $oldUrl . '%']
                );
            }
        }
    }

    public function down(): void
    {
        // URL relatif tidak perlu dikembalikan menjadi URL host tertentu.
    }
};
