<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('email:retry-failed')->everyThirtyMinutes();
Schedule::command('auth:prune-expired --days=7')->daily();

// Otomasi Bulanan: Backup seluruh database & berkas LHU pada tanggal 1 setiap bulan pukul 01:00
Schedule::command('pengujian:backup-monthly')->monthlyOn(1, '01:00');

// Otomasi Bulanan: Pengarsipan pengujian lama (> 3 tahun) pada tanggal 1 setiap bulan pukul 02:00
Schedule::command('pengujian:archive --years=3')->monthlyOn(1, '02:00');
