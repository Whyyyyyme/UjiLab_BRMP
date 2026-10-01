<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SkmRequest;
use App\Models\Skm;
use App\Services\IkmCalculationService;
use App\Exports\SkmExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SkmController extends Controller
{
    protected IkmCalculationService $ikmService;

    public function __construct(IkmCalculationService $ikmService)
    {
        $this->ikmService = $ikmService;
    }

    /**
     * Menyimpan survei SKM publik (F-08, F-09).
     */
    public function store(SkmRequest $request)
    {
        // $pengujian dilekatkan oleh middleware EnsureTokenAksesValid
        $pengujian = $request->pengujian;

        // Cegah submit ganda untuk satu nomor pengujian (F-09)
        $exists = Skm::where('pengujian_id', $pengujian->id)->exists();
        if ($exists) {
            return response()->json([
                'message' => 'Anda sudah mengisi survei SKM untuk nomor pengujian ini.'
            ], 400);
        }

        $skm = Skm::create([
            'pengujian_id' => $pengujian->id,
            'nama' => $request->nama,
            'jenis_kelamin' => $request->jenis_kelamin,
            'pendidikan' => $request->pendidikan,
            'usia' => $request->usia,
            'pekerjaan' => $request->pekerjaan,
            'disabilitas' => $request->disabilitas,
            'skor_1' => $request->u1,
            'skor_2' => $request->u2,
            'skor_3' => $request->u3,
            'skor_4' => $request->u4,
            'skor_5' => $request->u5,
            'skor_6' => $request->u6,
            'skor_7' => $request->u7,
            'skor_8' => $request->u8,
            'skor_9' => $request->u9,
            'skor_10' => $request->u10,
            'skor_11' => $request->u11,
            'skor_12' => $request->u12,
            'skor_13' => $request->u13,
            'skor_14' => $request->u14,
            'skor_15' => $request->u15,
            'skor_16' => $request->u16,
            'tanggal_isi' => now()->toDateString(),
        ]);

        return response()->json([
            'message' => 'Survei SKM berhasil disimpan. Terima kasih atas partisipasi Anda.',
            'data' => $skm
        ], 201);
    }

    /**
     * Mengembalikan rekap nilai IKM & rekap responden (Admin only) (F-23).
     */
    public function getIkmStats(Request $request)
    {
        $bulan = $request->filled('bulan') ? (int) $request->bulan : null;
        $tahun = $request->filled('tahun') ? (int) $request->tahun : null;
        $triwulan = $request->filled('triwulan') ? (int) $request->triwulan : null;

        $stats = $this->ikmService->calculateIkm($bulan, $tahun, $triwulan);

        $query = Skm::query()
            ->join('pengujian', 'skm.pengujian_id', '=', 'pengujian.id')
            ->where('pengujian.is_deleted', false);

        if ($triwulan) {
            $startMonth = ($triwulan - 1) * 3 + 1;
            $endMonth = $triwulan * 3;
            $query->where(function ($q) use ($startMonth, $endMonth) {
                for ($m = $startMonth; $m <= $endMonth; $m++) {
                    $q->orWhereMonth('skm.tanggal_isi', $m);
                }
            });
        } elseif ($bulan) {
            $query->whereMonth('skm.tanggal_isi', $bulan);
        }

        if ($tahun) {
            $query->whereYear('skm.tanggal_isi', $tahun);
        }

        $respondenList = $query
            ->select('skm.*', 'pengujian.nomor_pengujian', 'pengujian.jenis_pengujian')
            ->orderBy('skm.created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'stats' => $stats,
            'responden' => $respondenList
        ]);
    }

    /**
     * Mengekspor rekapitulasi data responden ke file Excel (Admin only) (F-24).
     */
    public function ekspor(Request $request)
    {
        $bulan = $request->filled('bulan') ? (int) $request->bulan : null;
        $tahun = $request->filled('tahun') ? (int) $request->tahun : null;
        $triwulan = $request->filled('triwulan') ? (int) $request->triwulan : null;

        return Excel::download(new SkmExport($bulan, $tahun, $triwulan), 'rekapitulasi_skm.xlsx');
    }
}
