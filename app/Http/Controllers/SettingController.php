<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\Process\Process;

class SettingController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard/Settings/Index', [
            'setting' => AppSetting::first() ?? new AppSetting(['cheat_limit' => 3]),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:255'],
            'school_name' => ['required', 'string', 'max:255'],
            'school_address' => ['required', 'string'],
            'cheat_limit' => ['required', 'integer', 'min:1', 'max:100'],
            'school_logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $setting = AppSetting::first() ?? new AppSetting();

        if ($request->hasFile('school_logo')) {
            if ($setting->school_logo) {
                Storage::disk('public')->delete($setting->school_logo);
            }

            $validated['school_logo'] = $request->file('school_logo')->store('settings', 'public');
        }

        $setting->fill($validated)->save();

        return back();
    }

    public function backup()
    {
        $connectionName = config('database.default');
        $connection = config("database.connections.{$connectionName}", []);

        if (!in_array($connectionName, ['mysql', 'mariadb'], true)) {
            return back()->with('error', 'Backup otomatis saat ini hanya tersedia untuk database MySQL/MariaDB.');
        }

        $database = $connection['database'] ?? null;
        if (!$database) {
            return back()->with('error', 'Nama database belum dikonfigurasi.');
        }

        $filename = Str::slug(config('app.name', 'application')) . '-backup-' . now()->format('Ymd-His') . '.sql';
        $relativePath = 'backups/' . $filename;
        $disk = Storage::disk('local');
        $disk->makeDirectory('backups');
        $backupPath = $disk->path($relativePath);

        $command = [
            config('database.dump_binary', 'mysqldump'),
            '--host=' . ($connection['host'] ?? '127.0.0.1'),
            '--port=' . ($connection['port'] ?? '3306'),
            '--user=' . ($connection['username'] ?? 'root'),
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--events',
            '--no-tablespaces',
            '--result-file=' . $backupPath,
            $database,
        ];

        try {
            $process = new Process(
                $command,
                base_path(),
                ['MYSQL_PWD' => (string) ($connection['password'] ?? '')],
                null,
                300
            );
            $process->run();

            if (!$process->isSuccessful() || !is_file($backupPath) || filesize($backupPath) === 0) {
                Log::error('Database backup failed.', [
                    'database' => $database,
                    'error' => trim($process->getErrorOutput()),
                ]);
                $disk->delete($relativePath);

                return back()->with('error', 'Backup database gagal. Pastikan mysqldump tersedia di server dan kredensial database benar.');
            }

            return response()->download($backupPath, $filename, [
                'Content-Type' => 'application/sql',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $exception) {
            $disk->delete($relativePath);
            Log::error('Database backup exception.', [
                'database' => $database,
                'message' => $exception->getMessage(),
            ]);

            return back()->with('error', 'Backup database gagal dijalankan. Periksa konfigurasi mysqldump di server.');
        }
    }
}
