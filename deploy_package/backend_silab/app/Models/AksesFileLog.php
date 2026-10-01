<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AksesFileLog extends Model
{
    protected $table = 'akses_file_log';

    // Nonaktifkan default timestamps karena hanya memiliki kolom created_at
    public $timestamps = false;

    protected $fillable = [
        'pengujian_id',
        'tipe_file',
        'akses_oleh',
        'petugas_id',
        'ip_address',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Relasi ke Pengujian
     */
    public function pengujian(): BelongsTo
    {
        return $this->belongsTo(Pengujian::class, 'pengujian_id');
    }

    /**
     * Relasi ke Petugas
     */
    public function petugas(): BelongsTo
    {
        return $this->belongsTo(Petugas::class, 'petugas_id');
    }
}
