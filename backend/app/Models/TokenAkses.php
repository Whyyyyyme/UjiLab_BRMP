<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TokenAkses extends Model
{
    protected $table = 'token_akses';

    protected $fillable = [
        'pengujian_id',
        'token_hash',
        'expired_at',
    ];

    protected $casts = [
        'expired_at' => 'datetime',
    ];

    /**
     * Relasi ke Pengujian
     */
    public function pengujian(): BelongsTo
    {
        return $this->belongsTo(Pengujian::class, 'pengujian_id');
    }
}
