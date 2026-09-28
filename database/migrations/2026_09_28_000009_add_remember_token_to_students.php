<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('students', 'remember_token')) {
            Schema::table('students', function (Blueprint $table) {
                $table->rememberToken();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('students', 'remember_token')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('remember_token');
            });
        }
    }
};
