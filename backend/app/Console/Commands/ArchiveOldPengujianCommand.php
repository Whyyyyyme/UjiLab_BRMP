<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackupService;
use App\Models\LogAktivitas;

class ArchiveOldPengujianCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pengujian:archive {--years=3 : Batas usia pengujian dalam tahun sebelum diarsipkan (default 3 tahun)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mengarsipkan data pengujian lama yang telah melewati masa retensi (default 3 tahun) dan membebaskan penyimpanan';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService)
    {
        $years = (int) $this->option('years');
        if ($years <= 0) {
            $years = 3;
        }

        $this->info("Memulai proses pengarsipan pengujian dengan masa retensi > {$years} tahun...");

        try {
            $result = $backupService->archiveOldPengujian($years);

            $this->info("Proses pengarsipan selesai:");
            $this->line("- Tanggal cutoff: {$result['cutoff']}");
            $this->line("- Pengujian diarsipkan: {$result['archived_count']}");
            $this->line("- Ruang penyimpanan dibebaskan: {$result['freed_readable']}");

            // Catat ke log aktivitas sistem
            LogAktivitas::create([
                'petugas_id' => null,
                'aksi' => 'Pengarsipan Otomatis',
                'detail' => "Pengarsipan data lama (> {$years} tahun, sebelum {$result['cutoff']}) berhasil. {$result['archived_count']} pengujian diarsipkan, membebaskan {$result['freed_readable']}.",
                'ip_address' => '127.0.0.1 (Scheduler/Cron)',
                'user_agent' => 'Laravel Artisan Scheduler',
            ]);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Gagal menjalankan pengarsipan: {$e->getMessage()}");

            LogAktivitas::create([
                'petugas_id' => null,
                'aksi' => 'Pengarsipan Otomatis Gagal',
                'detail' => "Pengarsipan data lama gagal: {$e->getMessage()}",
                'ip_address' => '127.0.0.1 (Scheduler/Cron)',
                'user_agent' => 'Laravel Artisan Scheduler',
            ]);

            return Command::FAILURE;
        }
    }
}
