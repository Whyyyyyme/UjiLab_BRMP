<?php

namespace App\Exports;

use App\Models\Skm;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SkmExport implements FromCollection, WithHeadings, WithMapping
{
    protected ?int $bulan;
    protected ?int $tahun;
    protected ?int $triwulan;

    public function __construct(?int $bulan = null, ?int $tahun = null, ?int $triwulan = null)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->triwulan = $triwulan;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = Skm::query()
            ->join('pengujian', 'skm.pengujian_id', '=', 'pengujian.id')
            ->where('pengujian.is_deleted', false);

        if ($this->triwulan) {
            $startMonth = ($this->triwulan - 1) * 3 + 1;
            $endMonth = $this->triwulan * 3;
            $query->where(function ($q) use ($startMonth, $endMonth) {
                for ($m = $startMonth; $m <= $endMonth; $m++) {
                    $q->orWhereMonth('skm.tanggal_isi', $m);
                }
            });
        } elseif ($this->bulan) {
            $query->whereMonth('skm.tanggal_isi', $this->bulan);
        }

        if ($this->tahun) {
            $query->whereYear('skm.tanggal_isi', $this->tahun);
        }

        return $query->select('skm.*', 'pengujian.nomor_pengujian', 'pengujian.jenis_pengujian')
            ->orderBy('skm.created_at', 'desc')
            ->get();
    }

    /**
     * Judul kolom ekspor.
     */
    public function headings(): array
    {
        return [
            'ID',
            'Nomor Pengujian',
            'Jenis Pengujian',
            'Nama Responden',
            'Jenis Kelamin',
            'Pendidikan',
            'Usia',
            'Pekerjaan',
            'Penyandang Disabilitas',
            'U1 (Informasi Pelayanan)',
            'U2 (Kesesuaian Persyaratan)',
            'U3 (Standar Prosedur)',
            'U4 (Kemudahan Prosedur)',
            'U5 (Prosedur Tanpa Kecurangan)',
            'U6 (Jangka Waktu Layanan)',
            'U7 (Kesesuaian Biaya)',
            'U8 (Bebas Pungli)',
            'U9 (Bebas Percaloan)',
            'U10 (Kesesuaian Produk)',
            'U11 (Kecepatan Respon)',
            'U12 (Keramahan Petugas)',
            'U13 (Keadilan Layanan)',
            'U14 (Bebas Imbalan Ekstra)',
            'U15 (Kemudahan Pengaduan)',
            'U16 (Kenyamanan Sarpras)',
            'Tanggal Mengisi'
        ];
    }

    /**
     * Mapping baris data responden.
     */
    public function map($row): array
    {
        return $this->sanitizeRow([
            $row->id,
            $row->nomor_pengujian,
            $row->jenis_pengujian,
            $row->nama,
            $row->jenis_kelamin,
            $row->pendidikan,
            $row->usia,
            $row->pekerjaan,
            $row->disabilitas,
            $row->skor_1,
            $row->skor_2,
            $row->skor_3,
            $row->skor_4,
            $row->skor_5,
            $row->skor_6,
            $row->skor_7,
            $row->skor_8,
            $row->skor_9,
            $row->skor_10,
            $row->skor_11,
            $row->skor_12,
            $row->skor_13,
            $row->skor_14,
            $row->skor_15,
            $row->skor_16,
            $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : '-',
        ]);
    }

    /**
     * Sanitasi nilai sel untuk mencegah CSV/Excel Formula Injection (CWE-1236).
     */
    private function sanitizeRow(array $row): array
    {
        return array_map(function ($value) {
            if (is_string($value) && strlen($value) > 0 && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"])) {
                return "'" . $value;
            }
            return $value;
        }, $row);
    }
}
