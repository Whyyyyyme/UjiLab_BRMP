<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\GantiPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Models\LogAktivitas;
use App\Models\Petugas;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Handle login request for petugas/admin.
     */
    public function login(LoginRequest $request)
    {

        $petugas = Petugas::where('username', $request->username)->first();

        if (! $petugas) {
            return response()->json([
                'message' => 'Username atau password salah.',
            ], 401);
        }

        // Cek apakah akun sedang terkunci
        if ($petugas->terkunci_hingga && Carbon::parse($petugas->terkunci_hingga)->isFuture()) {
            $diffMinutes = Carbon::parse($petugas->terkunci_hingga)->diffInMinutes(now()) + 1;

            return response()->json([
                'message' => "Akun Anda terkunci sementara karena 5 kali gagal login. Silakan coba lagi dalam {$diffMinutes} menit.",
            ], 423);
        }

        // Jika kunci sudah kadaluarsa, reset status lockout
        if ($petugas->terkunci_hingga && Carbon::parse($petugas->terkunci_hingga)->isPast()) {
            $petugas->update([
                'percobaan_login_gagal' => 0,
                'terkunci_hingga' => null,
            ]);
        }

        // Verifikasi password
        if (Hash::check($request->password, $petugas->password)) {
            // Reset status lockout jika sukses
            $petugas->update([
                'percobaan_login_gagal' => 0,
                'terkunci_hingga' => null,
            ]);

            // Terbitkan token Sanctum
            $token = $petugas->createToken('petugas_token')->plainTextToken;

            // Catat log aktivitas jika ada model log aktivitas
            LogAktivitas::create([
                'petugas_id' => $petugas->id,
                'aksi' => 'Login',
                'detail' => 'Berhasil login ke sistem',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'token' => $token,
                'user' => [
                    'id' => $petugas->id,
                    'nama' => $petugas->nama,
                    'username' => $petugas->username,
                    'email' => $petugas->email,
                    'role' => $petugas->role,
                    'wajib_ganti_password' => $petugas->wajib_ganti_password,
                ],
            ]);
        } else {
            // Tambahkan hitungan gagal login
            $gagal = $petugas->percobaan_login_gagal + 1;
            $updateData = ['percobaan_login_gagal' => $gagal];

            if ($gagal >= 5) {
                $updateData['terkunci_hingga'] = now()->addMinutes(15);
                $petugas->update($updateData);

                // Catat log aktivitas lockout
                LogAktivitas::create([
                    'petugas_id' => $petugas->id,
                    'aksi' => 'Lockout',
                    'detail' => 'Akun terkunci karena 5 kali berturut-turut gagal login',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                return response()->json([
                    'message' => 'Akun Anda terkunci sementara karena 5 kali gagal login. Silakan coba lagi dalam 15 menit.',
                ], 423);
            }

            $petugas->update($updateData);
            $sisa = 5 - $gagal;

            return response()->json([
                'message' => "Username atau password salah. Sisa percobaan login: {$sisa} kali lagi.",
            ], 401);
        }
    }

    /**
     * Handle logout.
     */
    public function logout(Request $request)
    {
        $petugas = $request->user();

        if ($petugas) {
            // Hapus token saat ini jika ada
            $token = $petugas->currentAccessToken();
            if ($token) {
                $token->delete();
            }

            LogAktivitas::create([
                'petugas_id' => $petugas->id,
                'aksi' => 'Logout',
                'detail' => 'Berhasil logout dari sistem',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }

    /**
     * Handle ganti password profil sendiri (F-30).
     */
    public function gantiPassword(GantiPasswordRequest $request)
    {
        $petugas = $request->user();

        // Verifikasi password lama
        if (! Hash::check($request->password_lama, $petugas->password)) {
            return response()->json([
                'message' => 'Password lama tidak cocok.',
            ], 422);
        }

        // Update password baru
        $petugas->update([
            'password' => Hash::make($request->password_baru),
            'wajib_ganti_password' => false,
        ]);

        // Catat log aktivitas ganti password
        LogAktivitas::create([
            'petugas_id' => $petugas->id,
            'aksi' => 'Ubah Password',
            'detail' => 'Berhasil mengubah password secara mandiri',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Password berhasil diperbarui.',
        ]);
    }
}


