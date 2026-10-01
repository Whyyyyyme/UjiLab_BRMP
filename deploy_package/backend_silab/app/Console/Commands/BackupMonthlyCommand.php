<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackupService;
use App\Models\LogAktivitas;

class BackupMonthlyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pengujian:backup-monthly {--name= : Nama kustom untuk berkas backup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Melakukan pencadangan bulanan otomatis database dan berkas PDF hasil uji lab';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService)
    {
        $this->info('Memulai pencadangan bulanan otomatis SILAB BRMP Biogen...');

        try {
            $customName = $this->option('name');
            $result = $backupService->createMonthlyBackup($customName);

            $this->info('Pencadangan bulanan berhasil diselesaikan:');
            $this->line("- Berkas: {$result['filename']}");
            $this->line("- Ukuran: {$result['size_readable']}");
            $this->line("- Total berkas LHU diarsipkan: {$result['files_count']}");
            $this->line("- Lokasi: {$result['relative_path']}");

            // Catat ke log aktivitas sistem
            LogAktivitas::create([
                'petugas_id' => null,
                'aksi' => 'Backup Otomatis Bulanan',
                'detail' => "Pencadangan bulanan otomatis berhasil: {$result['filename']} ({$result['size_readable']}, {$result['files_count']} berkas LHU).",
                'ip_address' => '127.0.0.1 (Scheduler/Cron)',
                'user_agent' => 'Laravel Artisan Scheduler',
            ]);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Gagal menjalankan pencadangan bulanan: {$e->getMessage()}");

            LogAktivitas::create([
                'petugas_id' => null,
                'aksi' => 'Backup Otomatis Bulanan Gagal',
                'detail' => "Pencadangan bulanan otomatis gagal: {$e->getMessage()}",
                'ip_address' => '127.0.0.1 (Scheduler/Cron)',
                'user_agent' => 'Laravel Artisan Scheduler',
            ]);

            return Command::FAILURE;
        }
    }
}
