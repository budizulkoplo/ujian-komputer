<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->boolean('is_locked')->default(false)->after('grade');
            $table->unsignedInteger('cheat_count')->default(0)->after('is_locked');
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropColumn(['is_locked', 'cheat_count']);
        });
    }
};
