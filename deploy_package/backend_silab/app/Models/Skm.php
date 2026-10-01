<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Skm extends Model
{
    protected $table = 'skm';

    protected $fillable = [
        'pengujian_id',
        'nama',
        'jenis_kelamin',
        'pendidikan',
        'usia',
        'pekerjaan',
        'disabilitas',
        'skor_1',
        'skor_2',
        'skor_3',
        'skor_4',
        'skor_5',
        'skor_6',
        'skor_7',
        'skor_8',
        'skor_9',
        'skor_10',
        'skor_11',
        'skor_12',
        'skor_13',
        'skor_14',
        'skor_15',
        'skor_16',
        'saran',
        'tanggal_isi',
    ];

    protected $casts = [
        'skor_1' => 'integer',
        'skor_2' => 'integer',
        'skor_3' => 'integer',
        'skor_4' => 'integer',
        'skor_5' => 'integer',
        'skor_6' => 'integer',
        'skor_7' => 'integer',
        'skor_8' => 'integer',
        'skor_9' => 'integer',
        'skor_10' => 'integer',
        'skor_11' => 'integer',
        'skor_12' => 'integer',
        'skor_13' => 'integer',
        'skor_14' => 'integer',
        'skor_15' => 'integer',
        'skor_16' => 'integer',
        'tanggal_isi' => 'date',
    ];

    /**
     * Relasi ke Pengujian
     */
    public function pengujian(): BelongsTo
    {
        return $this->belongsTo(Pengujian::class, 'pengujian_id');
    }
}
