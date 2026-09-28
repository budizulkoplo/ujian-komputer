<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dateTime('expires_at')->nullable()->after('start_time');
        });

        Schema::table('app_settings', function (Blueprint $table) {
            $table->unsignedInteger('cheat_limit')->default(3)->after('school_address');
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });

        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn('cheat_limit');
        });
    }
};