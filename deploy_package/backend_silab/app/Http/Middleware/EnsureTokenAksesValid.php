<?php

namespace App\Http\Middleware;

use App\Models\TokenAkses;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenAksesValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Akses-Token');

        if (!$token || !str_contains($token, '|')) {
            return response()->json([
                'message' => 'Token akses tidak disediakan atau tidak valid.'
            ], 401);
        }

        [$id, $key] = explode('|', $token, 2);

        $tokenAkses = TokenAkses::with('pengujian')->find($id);

        if (!$tokenAkses || $tokenAkses->expired_at->isPast()) {
            return response()->json([
                'message' => 'Sesi akses Anda telah berakhir. Silakan verifikasi OTP ulang.'
            ], 401);
        }

        // Verifikasi token key dengan token_hash (bcrypt)
        if (!Hash::check($key, $tokenAkses->token_hash)) {
            return response()->json([
                'message' => 'Token akses tidak valid.'
            ], 401);
        }

        // Verifikasi apakah data pengujian ada dan tidak dalam status dihapus (soft delete)
        if (!$tokenAkses->pengujian || $tokenAkses->pengujian->is_deleted) {
            return response()->json([
                'message' => 'Data pengujian tidak ditemukan atau telah dinonaktifkan.'
            ], 404);
        }

        // Ikat data token dan pengujian ke request
        $request->merge([
            'tokenAkses' => $tokenAkses,
            'pengujian' => $tokenAkses->pengujian
        ]);

        return $next($request);
    }
}
