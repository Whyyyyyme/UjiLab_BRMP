<?php

namespace App\Exports;

use App\Models\AksesFileLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UnduhanExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return AksesFileLog::query()
            ->join('pengujian', 'akses_file_log.pengujian_id', '=', 'pengujian.id')
            ->select('akses_file_log.*', 'pengujian.nomor_pengujian')
            ->orderBy('akses_file_log.created_at', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID Log',
            'Nomor Pengujian',
            'Tipe File',
            'Akses Oleh',
            'IP Address',
            'Waktu Unduh'
        ];
    }

    public function map($row): array
    {
        return $this->sanitizeRow([
            $row->id,
            $row->nomor_pengujian,
            ucfirst($row->tipe_file),
            ucfirst($row->akses_oleh),
            $row->ip_address,
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
