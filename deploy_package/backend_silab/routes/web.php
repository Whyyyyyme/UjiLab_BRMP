<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'app' => config('app.name', 'SILAB Biogen API'),
        'status' => 'online',
        'message' => 'Layanan API SILAB BRMP Biogen Kementerian Pertanian RI berjalan normal.',
    ]);
});
