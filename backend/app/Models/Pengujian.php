<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pengujian extends Model
{
    protected $table = 'pengujian';

    protected $fillable = [
        'nomor_pengujian',
        'nama_pemohon',
        'email_pemohon',
        'jenis_pengujian',
        'status',
        'file_laporan',
        'file_sertifikat',
        'hash_laporan',
        'hash_sertifikat',
        'versi',
        'is_deleted',
    ];

    protected $casts = [
        'versi' => 'integer',
        'is_deleted' => 'boolean',
    ];

    /**
     * Relasi ke SKM (One-to-One)
     */
    public function skm(): HasOne
    {
        return $this->hasOne(Skm::class, 'pengujian_id');
    }

    /**
     * Relasi ke OTP Verifikasi (One-to-Many)
     */
    public function otpVerifikasis(): HasMany
    {
        return $this->hasMany(OtpVerifikasi::class, 'pengujian_id');
    }

    /**
     * Relasi ke Token Akses (One-to-Many)
     */
    public function tokenAkseses(): HasMany
    {
        return $this->hasMany(TokenAkses::class, 'pengujian_id');
    }

    /**
     * Relasi ke Log Notifikasi (One-to-Many)
     */
    public function logNotifikasis(): HasMany
    {
        return $this->hasMany(LogNotifikasi::class, 'pengujian_id');
    }

    /**
     * Relasi ke Log Akses File (One-to-Many)
     */
    public function aksesFileLogs(): HasMany
    {
        return $this->hasMany(AksesFileLog::class, 'pengujian_id');
    }
}
