<?php

use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\PengujianController;
use App\Http\Controllers\Api\Public\PublicPengujianController;
use App\Http\Controllers\Api\SkmController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// === MODUL PUBLIK (PENGGUNA JASA) ===
Route::post('/public/pengujian/cari', [PublicPengujianController::class, 'cari'])->middleware('throttle:10,1');
Route::post('/public/otp/kirim', [PublicPengujianController::class, 'kirimOtp'])->middleware('throttle:30,1');
Route::post('/public/otp/verifikasi', [PublicPengujianController::class, 'verifikasiOtp']);
Route::get('/public/verifikasi/{nomor_pengujian}', [PublicPengujianController::class, 'verifikasi'])->middleware('throttle:10,1');

Route::middleware('token.akses.valid')->group(function () {
    Route::get('/public/pengujian/status', [PublicPengujianController::class, 'cekStatus']);
    Route::post('/public/skm', [SkmController::class, 'store']);
    Route::get('/public/pengujian/{id}/download/{type}', [PublicPengujianController::class, 'download']);
});

// === MODUL ADMIN ===
Route::post('/admin/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/admin/logout', [AuthController::class, 'logout']);
    Route::post('/admin/ganti-password', [AuthController::class, 'gantiPassword']);
    Route::put('/admin/profil', [AuthController::class, 'updateProfile']);

    Route::middleware('check.password.change')->group(function () {
        Route::get('/admin/dashboard', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'index']);

        // CRUD Data Pengujian
        Route::get('/admin/pengujian', [PengujianController::class, 'index']);
        Route::post('/admin/pengujian', [PengujianController::class, 'store']);
        Route::post('/admin/pengujian/parse-pdf', [PengujianController::class, 'parsePdf']);
        Route::get('/admin/pengujian/{id}', [PengujianController::class, 'show']);
        Route::put('/admin/pengujian/{id}', [PengujianController::class, 'update']);
        Route::post('/admin/pengujian/{id}/upload', [PengujianController::class, 'upload']);
        Route::get('/admin/pengujian/{id}/download/{type}', [PengujianController::class, 'download']);
        Route::patch('/admin/pengujian/{id}/email', [PengujianController::class, 'updateEmail']);
        Route::post('/admin/pengujian/{id}/kirim-notifikasi', [PengujianController::class, 'kirimUlangNotifikasi']);
        Route::delete('/admin/pengujian/{id}', [PengujianController::class, 'destroy']);

        // Rekap SKM & IKM
        Route::get('/admin/skm/ikm', [SkmController::class, 'getIkmStats']);
        Route::get('/admin/skm/ekspor', [SkmController::class, 'ekspor']);

        // Pelacakan Log Audit
        Route::get('/admin/logs/aktivitas', [\App\Http\Controllers\Api\Admin\AuditLogController::class, 'indexAktivitas']);
        Route::get('/admin/logs/aktivitas/ekspor', [\App\Http\Controllers\Api\Admin\AuditLogController::class, 'eksporAktivitas']);
        Route::get('/admin/logs/unduhan', [\App\Http\Controllers\Api\Admin\AuditLogController::class, 'indexUnduhan']);
        Route::get('/admin/logs/unduhan/ekspor', [\App\Http\Controllers\Api\Admin\AuditLogController::class, 'eksporUnduhan']);

        // Pemeliharaan Sistem / Garbage Collection (1 A 3)
        Route::get('/admin/maintenance/stats', [\App\Http\Controllers\Api\Admin\MaintenanceController::class, 'getStats']);
        Route::post('/admin/maintenance/prune', [\App\Http\Controllers\Api\Admin\MaintenanceController::class, 'prune']);

        // User info endpoint
        Route::get('/admin/user', function (Request $request) {
            return $request->user();
        });
    });
});



