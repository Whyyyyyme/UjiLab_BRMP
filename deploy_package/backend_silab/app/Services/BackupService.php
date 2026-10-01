<?php

namespace App\Services;

use App\Models\Pengujian;
use App\Models\Skm;
use App\Models\LogAktivitas;
use App\Models\LogNotifikasi;
use App\Models\AksesFileLog;
use App\Models\Petugas;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BackupService
{
    /**
     * Jalankan pencadangan bulanan otomatis: database dump + berkas PDF LHU.
     */
    public function createMonthlyBackup(?string $customName = null): array
    {
        $timestamp = Carbon::now()->format('Y_m_d_His');
        $backupDir = 'backups/monthly';

        if (!Storage::disk('local')->exists($backupDir)) {
            Storage::disk('local')->makeDirectory($backupDir);
        }

        $zipFilename = $customName ? "backup_{$customName}_{$timestamp}.zip" : "backup_bulanan_{$timestamp}.zip";
        $zipRelativePath = "{$backupDir}/{$zipFilename}";

        $tempFile = tempnam(sys_get_temp_dir(), 'ujilab_backup_');
        $zip = new ZipArchive();
        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Gagal membuat arsip zip backup sementara.");
        }

        // 1. Export Data Database ke format JSON terstruktur
        $databaseData = [
            'meta' => [
                'created_at' => Carbon::now()->toIso8601String(),
                'version' => '1.0',
                'app' => 'SILAB BRMP Biogen',
            ],
            'pengujian' => Pengujian::all()->toArray(),
            'skm' => Skm::all()->toArray(),
            'log_aktivitas' => LogAktivitas::all()->toArray(),
            'log_notifikasi' => LogNotifikasi::all()->toArray(),
            'akses_file_log' => AksesFileLog::all()->toArray(),
            'petugas' => Petugas::select(['id', 'nama', 'username', 'email', 'role', 'created_at'])->get()->toArray(),
        ];

        $zip->addFromString('database_dump.json', json_encode($databaseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // 2. Arsipkan Berkas PDF LHU dari folder hasil_uji
        $manifest = [];
        $files = Storage::disk('local')->files('hasil_uji');
        $filesCount = 0;

        foreach ($files as $file) {
            $contents = Storage::disk('local')->get($file);
            $basename = basename($file);
            $zip->addFromString("hasil_uji/{$basename}", $contents);
            $manifest[$basename] = [
                'size' => strlen($contents),
                'sha256' => hash('sha256', $contents),
            ];
            $filesCount++;
        }

        $zip->addFromString('manifest.json', json_encode([
            'backup_date' => Carbon::now()->toIso8601String(),
            'total_files' => $filesCount,
            'files' => $manifest,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $zip->close();

        $sizeBytes = filesize($tempFile);
        Storage::disk('local')->put($zipRelativePath, file_get_contents($tempFile));
        @unlink($tempFile);

        // 3. Rotasi Backup: Simpan maksimal 12 backup bulanan terakhir (1 tahun retensi backup lokal)
        $this->rotateOldBackups($backupDir, 12);

        return [
            'success' => true,
            'filename' => $zipFilename,
            'relative_path' => $zipRelativePath,
            'full_path' => Storage::disk('local')->path($zipRelativePath),
            'size_bytes' => $sizeBytes,
            'size_readable' => $this->formatBytes($sizeBytes),
            'files_count' => $filesCount,
            'created_at' => Carbon::now()->toIso8601String(),
        ];
    }

    /**
     * Jalankan pengarsipan data pengujian lama (> X tahun, default 3 tahun).
     */
    public function archiveOldPengujian(int $years = 3): array
    {
        $cutoff = Carbon::now()->subYears($years);

        // Ambil data pengujian yang memenuhi syarat:
        // Selesai, belum diarsipkan, tidak dalam status terhapus, dan tanggal pembuatan < cutoff
        $pengujians = Pengujian::where('status', 'selesai')
            ->where('is_archived', false)
            ->where('is_deleted', false)
            ->where('created_at', '<', $cutoff)
            ->get();

        $archivedCount = 0;
        $freedBytes = 0;

        foreach ($pengujians as $item) {
            // Hapus berkas fisik LHU dari disk lokal aktif
            if ($item->file_laporan && Storage::disk('local')->exists($item->file_laporan)) {
                $freedBytes += Storage::disk('local')->size($item->file_laporan);
                Storage::disk('local')->delete($item->file_laporan);
            }

            if ($item->file_sertifikat && Storage::disk('local')->exists($item->file_sertifikat)) {
                $freedBytes += Storage::disk('local')->size($item->file_sertifikat);
                Storage::disk('local')->delete($item->file_sertifikat);
            }

            // Tandai data sebagai telah diarsipkan
            $item->update([
                'is_archived' => true,
                'archived_at' => Carbon::now(),
            ]);

            $archivedCount++;
        }

        return [
            'archived_count' => $archivedCount,
            'freed_bytes' => $freedBytes,
            'freed_readable' => $this->formatBytes($freedBytes),
            'cutoff' => $cutoff->toDateString(),
            'years' => $years,
        ];
    }

    /**
     * Ambil statistik retensi dan arsip data saat ini.
     */
    public function getArchiveStats(int $years = 3): array
    {
        $cutoff = Carbon::now()->subYears($years);

        $totalPengujian = Pengujian::where('is_deleted', false)->count();
        $totalAktif = Pengujian::where('is_deleted', false)->where('is_archived', false)->count();
        $totalDiarsipkan = Pengujian::where('is_deleted', false)->where('is_archived', true)->count();
        $siapDiarsipkan = Pengujian::where('is_deleted', false)
            ->where('status', 'selesai')
            ->where('is_archived', false)
            ->where('created_at', '<', $cutoff)
            ->count();

        // Dapatkan daftar berkas backup yang tersedia
        $backupFiles = Storage::disk('local')->files('backups/monthly');
        $backupsList = [];
        foreach ($backupFiles as $bf) {
            if (pathinfo($bf, PATHINFO_EXTENSION) === 'zip') {
                $size = Storage::disk('local')->size($bf);
                $backupsList[] = [
                    'name' => basename($bf),
                    'size' => $this->formatBytes($size),
                    'timestamp' => Storage::disk('local')->lastModified($bf),
                ];
            }
        }

        // Urutkan backup terbaru di atas
        usort($backupsList, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return [
            'total_pengujian' => $totalPengujian,
            'pengujian_aktif' => $totalAktif,
            'pengujian_diarsipkan' => $totalDiarsipkan,
            'siap_diarsipkan' => $siapDiarsipkan,
            'retensi_tahun' => $years,
            'cutoff_date' => $cutoff->toDateString(),
            'total_backups' => count($backupsList),
            'latest_backup' => $backupsList[0] ?? null,
            'backup_history' => array_slice($backupsList, 0, 5),
        ];
    }

    /**
     * Rotasi berkas backup lama jika melebihi batas simpan.
     */
    protected function rotateOldBackups(string $dir, int $maxKeep = 12): void
    {
        $files = Storage::disk('local')->files($dir);
        $zipFiles = [];

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
                $zipFiles[] = [
                    'path' => $file,
                    'time' => Storage::disk('local')->lastModified($file),
                ];
            }
        }

        // Urutkan dari terlama ke terbaru
        usort($zipFiles, fn($a, $b) => $a['time'] <=> $b['time']);

        // Jika jumlah berkas melebihi $maxKeep, hapus yang terlama
        while (count($zipFiles) > $maxKeep) {
            $oldest = array_shift($zipFiles);
            Storage::disk('local')->delete($oldest['path']);
        }
    }

    /**
     * Format byte ke satuan terbaca (KB, MB, GB).
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
