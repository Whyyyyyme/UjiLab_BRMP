<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAktivitas extends Model
{
    protected $table = 'log_aktivitas';

    // Nonaktifkan default timestamps karena tidak ada kolom updated_at
    public $timestamps = false;

    protected $fillable = [
        'petugas_id',
        'aksi',
        'detail',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Relasi ke Petugas
     */
    public function petugas(): BelongsTo
    {
        return $this->belongsTo(Petugas::class, 'petugas_id');
    }
}
