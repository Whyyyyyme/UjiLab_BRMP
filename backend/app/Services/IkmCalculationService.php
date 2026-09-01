<?php

namespace App\Services;

use App\Models\Skm;
use Illuminate\Support\Facades\DB;

class IkmCalculationService
{
    /**
     * Menghitung statistik dan rekapitulasi nilai IKM terkonversi berdasarkan unsur U1 - U9.
     *
     * @param int|null $bulan
     * @param int|null $tahun
     * @return array
     */
    public function calculateIkm(?int $bulan = null, ?int $tahun = null): array
    {
        // Hubungkan ke tabel pengujian untuk memastikan data yang di-softdelete (is_deleted) diabaikan
        $query = Skm::query()
            ->join('pengujian', 'skm.pengujian_id', '=', 'pengujian.id')
            ->where('pengujian.is_deleted', false);

        if ($bulan) {
            $query->whereMonth('skm.tanggal_isi', $bulan);
        }

        if ($tahun) {
            $query->whereYear('skm.tanggal_isi', $tahun);
        }

        $totalResponden = $query->count();

        // Default jika belum ada responden
        if ($totalResponden === 0) {
            return [
                'total_responden' => 0,
                'rata_rata_unsur' => array_fill_keys(['u1', 'u2', 'u3', 'u4', 'u5', 'u6', 'u7', 'u8', 'u9', 'u10', 'u11', 'u12', 'u13', 'u14', 'u15', 'u16'], 0.0),
                'rata_rata_unsur_terbobot' => array_fill_keys(['u1', 'u2', 'u3', 'u4', 'u5', 'u6', 'u7', 'u8', 'u9', 'u10', 'u11', 'u12', 'u13', 'u14', 'u15', 'u16'], 0.0),
                'ikm' => 0.0,
                'mutu' => 'D',
                'kinerja' => 'Tidak Baik'
            ];
        }

        // Hitung rata-rata tiap unsur U1 s/d U16
        $selects = [];
        for ($i = 1; $i <= 16; $i++) {
            $selects[] = "AVG(skm.skor_{$i}) as avg_u{$i}";
        }

        $averagesRaw = $query->select(DB::raw(implode(', ', $selects)))->first();

        $rataRataUnsur = [];
        $rataRataUnsurTerbobot = [];
        $totalTerbobot = 0.0;
        
        $bobot = 1 / 16; // Menggunakan pecahan presisi 1/16 untuk 16 unsur pelayanan BRMP Biogen

        for ($i = 1; $i <= 16; $i++) {
            $key = "u{$i}";
            $avgKey = "avg_u{$i}";
            $avgVal = round((float)$averagesRaw->$avgKey, 3);
            
            $rataRataUnsur[$key] = $avgVal;
            
            // Unsur Terbobot = Rata-rata Nilai Unsur * (1 / 16)
            $terbobotVal = round($avgVal * $bobot, 3);
            $rataRataUnsurTerbobot[$key] = $terbobotVal;
            
            $totalTerbobot += $avgVal * $bobot;
        }

        // IKM Terkonversi = Total Nilai Rata-rata Terbobot * 25
        $ikm = round($totalTerbobot * 25, 2);

        // Tentukan Kategori Mutu & Kinerja Layanan
        // Sesuai Permenpan RB No. 14/2017:
        // A (Sangat Baik): 81.26 - 100.00
        // B (Baik): 62.51 - 81.25
        // C (Kurang Baik): 43.76 - 62.50
        // D (Tidak Baik): 25.00 - 43.75
        if ($ikm >= 81.26) {
            $mutu = 'A';
            $kinerja = 'Sangat Baik';
        } elseif ($ikm >= 62.51) {
            $mutu = 'B';
            $kinerja = 'Baik';
        } elseif ($ikm >= 43.76) {
            $mutu = 'C';
            $kinerja = 'Kurang Baik';
        } else {
            $mutu = 'D';
            $kinerja = 'Tidak Baik';
        }

        return [
            'total_responden' => $totalResponden,
            'rata_rata_unsur' => $rataRataUnsur,
            'rata_rata_unsur_terbobot' => $rataRataUnsurTerbobot,
            'ikm' => $ikm,
            'mutu' => $mutu,
            'kinerja' => $kinerja
        ];
    }
}
