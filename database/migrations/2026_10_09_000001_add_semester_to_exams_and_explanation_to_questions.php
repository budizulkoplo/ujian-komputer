<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->string('semester', 1)->nullable()->after('classroom_id');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->text('explanation')->nullable()->after('question');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('explanation');
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn('semester');
        });
    }
};
