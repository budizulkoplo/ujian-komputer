<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // NISN adalah identitas, bukan angka untuk perhitungan. String menjaga nol di depan.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `students` MODIFY `nisn` VARCHAR(19) NOT NULL');
        } elseif (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE students ALTER COLUMN nisn TYPE VARCHAR(19) USING nisn::VARCHAR');
            DB::statement('ALTER TABLE students ALTER COLUMN nisn SET NOT NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `students` MODIFY `nisn` BIGINT NOT NULL');
        } elseif (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE students ALTER COLUMN nisn TYPE BIGINT USING nisn::BIGINT');
            DB::statement('ALTER TABLE students ALTER COLUMN nisn SET NOT NULL');
        }
    }
};
