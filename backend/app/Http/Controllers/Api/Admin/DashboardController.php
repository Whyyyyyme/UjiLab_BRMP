<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengujian;
use App\Models\LogAktivitas;
use App\Models\LogNotifikasi;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get dashboard summary stats & recent logs (F-21, F-22).
     */
    public function index()
    {
        $totalPengujian = Pengujian::where('is_deleted', false)->count();
        $diproses = Pengujian::where('is_deleted', false)->where('status', 'diproses')->count();
        $selesai = Pengujian::where('is_deleted', false)->where('status', 'selesai')->count();
        
        // hitung log notifikasi yang gagal_permanen
        $notifikasiGagal = LogNotifikasi::where('status', 'gagal_permanen')->count();

        // Ambil 5 aktivitas terkini khusus untuk Admin
        $user = auth()->user();
        $recentLogs = [];

        if ($user) {
            $recentLogs = LogAktivitas::with('petugas')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'petugas_nama' => $log->petugas ? $log->petugas->nama : 'Sistem / Publik',
                        'aksi' => $log->aksi,
                        'detail' => $log->detail,
                        'ip_address' => $log->ip_address,
                        'created_at' => $log->created_at->toIso8601String()
                    ];
                });
        }

        return response()->json([
            'stats' => [
                'total_pengujian' => $totalPengujian,
                'diproses' => $diproses,
                'selesai' => $selesai,
                'notifikasi_gagal' => $notifikasiGagal
            ],
            'recent_logs' => $recentLogs
        ]);
    }
}
