<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OtpVerifikasi;
use App\Models\TokenAkses;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\PersonalAccessToken;

class PruneExpiredTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:prune-expired {--days=7 : Hapus token dan OTP yang telah kedaluwarsa lebih dari N hari (0 = semua yang sudah kedaluwarsa)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membersihkan data OTP, token akses publik, dan token sesi yang telah kedaluwarsa';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoff = $days > 0 ? Carbon::now()->subDays($days) : Carbon::now();

        $this->info("Memulai pembersihan data kedaluwarsa dengan batas waktu sebelum: {$cutoff->toDateTimeString()} ({$days} hari)...");

        // 1. Bersihkan OTP kedaluwarsa atau sudah terpakai/kadaluarsa yang sudah lewat batas
        $deletedOtp = OtpVerifikasi::where(function ($query) use ($cutoff) {
            $query->where('expired_at', '<', $cutoff)
                  ->orWhere(function ($sub) use ($cutoff) {
                      $sub->whereIn('status', ['kadaluarsa', 'terpakai'])
                          ->where('updated_at', '<', $cutoff);
                  });
        })->delete();

        // 2. Bersihkan Token Akses Publik yang telah kedaluwarsa
        $deletedToken = TokenAkses::where('expired_at', '<', $cutoff)->delete();

        // 3. Bersihkan Personal Access Token (Sanctum) yang kedaluwarsa jika ada
        $deletedSanctum = 0;
        if (class_exists(PersonalAccessToken::class)) {
            $deletedSanctum = PersonalAccessToken::where('expires_at', '<', $cutoff)->delete();
        }

        $totalCleaned = $deletedOtp + $deletedToken + $deletedSanctum;

        $this->info("Pembersihan selesai:");
        $this->line("- OTP kedaluwarsa dibersihkan: {$deletedOtp}");
        $this->line("- Token akses publik dibersihkan: {$deletedToken}");
        $this->line("- Token sesi Sanctum dibersihkan: {$deletedSanctum}");
        $this->info("Total data sampah dibersihkan: {$totalCleaned} baris.");

        return Command::SUCCESS;
    }
}
