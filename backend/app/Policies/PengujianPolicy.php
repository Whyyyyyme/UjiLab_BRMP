<?php

namespace App\Policies;

use App\Models\Petugas;
use App\Models\Pengujian;

class PengujianPolicy
{
    /**
     * Determine whether the user can delete the model (soft delete).
     */
    public function delete(Petugas $petugas, Pengujian $pengujian): bool
    {
        return $petugas->isAdmin();
    }
}
