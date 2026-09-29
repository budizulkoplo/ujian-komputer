<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->string('token', 5)->nullable()->unique()->after('end_time');
            $table->dateTime('token_closed_at')->nullable()->after('token');
        });

        $used = [];
        foreach (DB::table('exam_sessions')->pluck('id') as $id) {
            do {
                $token = chr(random_int(65, 90)) . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            } while (in_array($token, $used, true));
            $used[] = $token;
            DB::table('exam_sessions')->where('id', $id)->update(['token' => $token]);
        }
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropUnique(['token']);
            $table->dropColumn(['token', 'token_closed_at']);
        });
    }
};
