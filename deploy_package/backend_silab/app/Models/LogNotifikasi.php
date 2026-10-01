<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogNotifikasi extends Model
{
    protected $table = 'log_notifikasi';

    protected $fillable = [
        'pengujian_id',
        'email_tujuan',
        'tipe_notifikasi',
        'status',
        'percobaan_ke',
        'waktu_percobaan_berikutnya',
        'pesan_error',
    ];

    protected $casts = [
        'percobaan_ke' => 'integer',
        'waktu_percobaan_berikutnya' => 'datetime',
    ];

    /**
     * Relasi ke Pengujian
     */
    public function pengujian(): BelongsTo
    {
        return $this->belongsTo(Pengujian::class, 'pengujian_id');
    }
}
