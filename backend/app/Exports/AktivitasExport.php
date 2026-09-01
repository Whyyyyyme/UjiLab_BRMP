<?php

namespace App\Exports;

use App\Models\LogAktivitas;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AktivitasExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return LogAktivitas::query()
            ->leftJoin('petugas', 'log_aktivitas.petugas_id', '=', 'petugas.id')
            ->select('log_aktivitas.*', 'petugas.nama as nama_petugas')
            ->orderBy('log_aktivitas.created_at', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID Log',
            'Nama Petugas',
            'Aksi / Tindakan',
            'Detail Tindakan',
            'IP Address',
            'User Agent',
            'Waktu Kejadian'
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->nama_petugas ?: 'Sistem / Publik',
            $row->aksi,
            $row->detail,
            $row->ip_address,
            $row->user_agent,
            $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : '-',
        ];
    }
}
