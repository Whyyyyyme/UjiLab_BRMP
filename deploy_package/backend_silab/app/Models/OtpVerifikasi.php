<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpVerifikasi extends Model
{
    protected $table = 'otp_verifikasi';

    protected $fillable = [
        'pengujian_id',
        'kode_hash',
        'status',
        'percobaan_gagal',
        'expired_at',
    ];

    protected $casts = [
        'expired_at' => 'datetime',
        'percobaan_gagal' => 'integer',
    ];

    /**
     * Relasi ke Pengujian
     */
    public function pengujian(): BelongsTo
    {
        return $this->belongsTo(Pengujian::class, 'pengujian_id');
    }
}
