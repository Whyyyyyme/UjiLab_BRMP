<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LogNotifikasi;
use App\Mail\HasilSiapMail;
use Illuminate\Support\Facades\Mail;

class RetryNotifikasiGagal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:retry-failed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mencoba kembali pengiriman email notifikasi hasil_siap yang gagal';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai pengecekan email notifikasi yang gagal...');

        // Cari notifikasi hasil_siap yang berstatus gagal, percobaan < 3, dan sudah melewati waktu percobaan berikutnya
        $failedLogs = LogNotifikasi::where('status', 'gagal')
            ->where('tipe_notifikasi', 'hasil_siap')
            ->where('percobaan_ke', '<', 3)
            ->where('waktu_percobaan_berikutnya', '<=', now())
            ->get();

        if ($failedLogs->isEmpty()) {
            $this->info('Tidak ada email gagal yang memenuhi syarat retry.');
            return 0;
        }

        $this->info('Ditemukan ' . $failedLogs->count() . ' email gagal. Memulai pengiriman ulang...');

        foreach ($failedLogs as $log) {
            $log->percobaan_ke += 1;
            
            try {
                $pengujian = $log->pengujian;
                if (!$pengujian) {
                    throw new \Exception("Data pengujian tidak ditemukan.");
                }

                Mail::to($log->email_tujuan)->send(new HasilSiapMail($pengujian));

                $log->status = 'terkirim';
                $log->pesan_error = null;
                $log->waktu_percobaan_berikutnya = null;

                $this->info("Email berhasil terkirim ke: {$log->email_tujuan} (Pengujian ID: {$log->pengujian_id})");
            } catch (\Exception $exception) {
                $log->pesan_error = $exception->getMessage();

                if ($log->percobaan_ke >= 3) {
                    $log->status = 'gagal_permanen';
                    $log->waktu_percobaan_berikutnya = null;
                    $this->error("Email ke {$log->email_tujuan} gagal permanen setelah 3 kali percobaan.");
                } else {
                    $delay = $log->percobaan_ke === 2 ? 15 : 30;
                    $log->waktu_percobaan_berikutnya = now()->addMinutes($delay);
                    $this->warn("Email ke {$log->email_tujuan} gagal lagi. Retry berikutnya dijadwalkan dalam {$delay} menit.");
                }
            }

            $log->save();
        }

        $this->info('Proses retry selesai.');
        return 0;
    }
}
