<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\AksesFileLog;
use App\Exports\AktivitasExport;
use App\Exports\UnduhanExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class AuditLogController extends Controller
{
    /**
     * Get paginated rekap aktivitas petugas (F-25).
     */
    public function indexAktivitas(Request $request)
    {
        $query = LogAktivitas::with('petugas');

        // Filter Petugas ID
        if ($request->filled('petugas_id')) {
            $query->where('petugas_id', $request->petugas_id);
        }

        // Filter Aksi / Tindakan
        if ($request->filled('aksi')) {
            $query->where('aksi', $request->aksi);
        }

        // Filter Date Range
        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_akhir')) {
            $mulai = Carbon::parse($request->tanggal_mulai)->startOfDay();
            $akhir = Carbon::parse($request->tanggal_akhir)->endOfDay();
            $query->whereBetween('created_at', [$mulai, $akhir]);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($logs);
    }

    /**
     * Ekspor rekap aktivitas ke berkas Excel (F-27).
     */
    public function eksporAktivitas(Request $request)
    {
        return Excel::download(new AktivitasExport, 'log_aktivitas.xlsx');
    }

    /**
     * Get paginated rekap unduhan berkas oleh publik (F-26).
     */
    public function indexUnduhan(Request $request)
    {
        $query = AksesFileLog::with('pengujian');

        // Filter Tipe File (laporan/sertifikat)
        if ($request->filled('tipe_file')) {
            $query->where('tipe_file', $request->tipe_file);
        }

        // Filter IP Address
        if ($request->filled('ip_address')) {
            $query->where('ip_address', 'like', "%{$request->ip_address}%");
        }

        // Filter Date Range
        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_akhir')) {
            $mulai = Carbon::parse($request->tanggal_mulai)->startOfDay();
            $akhir = Carbon::parse($request->tanggal_akhir)->endOfDay();
            $query->whereBetween('created_at', [$mulai, $akhir]);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($logs);
    }

    /**
     * Ekspor rekap unduhan berkas ke berkas Excel (F-27).
     */
    public function eksporUnduhan(Request $request)
    {
        return Excel::download(new UnduhanExport, 'log_unduhan.xlsx');
    }
}
