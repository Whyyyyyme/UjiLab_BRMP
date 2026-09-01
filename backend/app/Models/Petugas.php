<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Petugas extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'petugas';

    protected $fillable = [
        'nama',
        'username',
        'email',
        'password',
        'role',
        'wajib_ganti_password',
        'percobaan_login_gagal',
        'terkunci_hingga',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'wajib_ganti_password' => 'boolean',
            'percobaan_login_gagal' => 'integer',
            'terkunci_hingga' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Cek apakah user adalah admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Cek apakah user adalah petugas lab
     */
    public function isPetugasLab(): bool
    {
        return $this->role === 'petugas_lab';
    }
}
