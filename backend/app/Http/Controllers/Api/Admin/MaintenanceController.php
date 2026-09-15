<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\OtpVerifikasi;
use App\Models\TokenAkses;
use App\Models\Pengujian;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\PersonalAccessToken;

class MaintenanceController extends Controller
{
    /**
     * Dapatkan ringkasan statistik data sampah & pemeliharaan sistem.
     */
    public function getStats(Request $request)
    {
        $now = Carbon::now();

        $expiredOtpCount = OtpVerifikasi::where('expired_at', '<', $now)->count();
        $totalOtpCount = OtpVerifikasi::count();

        $expiredTokenCount = TokenAkses::where('expired_at', '<', $now)->count();
        $totalTokenCount = TokenAkses::count();

        $softDeletedPengujian = Pengujian::where('is_deleted', true)->count();

        return response()->json([
            'otp_kedaluwarsa' => $expiredOtpCount,
            'total_otp' => $totalOtpCount,
            'token_kedaluwarsa' => $expiredTokenCount,
            'total_token' => $totalTokenCount,
            'pengujian_terhapus' => $softDeletedPengujian,
            'server_time' => $now->toIso8601String(),
        ]);
    }

    /**
     * Jalankan pembersihan data sampah kedaluwarsa secara on-demand.
     */
    public function prune(Request $request)
    {
        $days = (int) $request->input('days', 0); // Default bersihkan semua yang sudah kedaluwarsa saat ini
        $cutoff = $days > 0 ? Carbon::now()->subDays($days) : Carbon::now();

        // 1. Hapus OTP
        $deletedOtp = OtpVerifikasi::where(function ($query) use ($cutoff) {
            $query->where('expired_at', '<', $cutoff)
                  ->orWhere(function ($sub) use ($cutoff) {
                      $sub->whereIn('status', ['kadaluarsa', 'terpakai'])
                          ->where('updated_at', '<', $cutoff);
                  });
        })->delete();

        // 2. Hapus Token Akses Publik
        $deletedToken = TokenAkses::where('expired_at', '<', $cutoff)->delete();

        // 3. Hapus Sanctum Tokens kedaluwarsa jika ada
        $deletedSanctum = 0;
        if (class_exists(PersonalAccessToken::class)) {
            $deletedSanctum = PersonalAccessToken::where('expires_at', '<', $cutoff)->delete();
        }

        $totalCleaned = $deletedOtp + $deletedToken + $deletedSanctum;

        // Catat ke log aktivitas
        LogAktivitas::create([
            'petugas_id' => $request->user()->id,
            'aksi' => 'Pemeliharaan Sistem',
            'detail' => "Melakukan pembersihan data sampah (Garbage Collection). Dihapus: {$deletedOtp} OTP, {$deletedToken} Token Akses, {$deletedSanctum} Token Sesi.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => "Pembersihan berhasil. Sebanyak {$totalCleaned} data sampah kedaluwarsa telah dihapus dari basis data.",
            'rincian' => [
                'otp_dihapus' => $deletedOtp,
                'token_dihapus' => $deletedToken,
                'sesi_dihapus' => $deletedSanctum,
                'total_dibersihkan' => $totalCleaned,
            ]
        ]);
    }
}
