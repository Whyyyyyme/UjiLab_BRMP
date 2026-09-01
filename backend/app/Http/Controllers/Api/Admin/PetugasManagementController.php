<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Petugas;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class PetugasManagementController extends Controller
{
    /**
     * Get list of petugas (F-28).
     */
    public function index()
    {
        $petugas = Petugas::orderBy('created_at', 'desc')->get();
        return response()->json($petugas);
    }

    /**
     * Create new petugas account (F-28).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:petugas,username',
            'email' => 'required|email|max:255|unique:petugas,email',
            'role' => 'required|string|in:admin,petugas_lab',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Input data tidak valid.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Akun petugas baru default password dan wajib ganti password di login pertama
        $petugas = Petugas::create([
            'nama' => $request->nama,
            'username' => $request->username,
            'email' => $request->email,
            'role' => $request->role,
            'password' => Hash::make('Password123!'),
            'wajib_ganti_password' => true,
        ]);

        LogAktivitas::create([
            'petugas_id' => $request->user()->id,
            'aksi' => 'Tambah Petugas',
            'detail' => "Membuat akun petugas baru: {$petugas->username} ({$petugas->role})",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json([
            'message' => 'Akun petugas berhasil dibuat.',
            'data' => $petugas
        ], 201);
    }

    /**
     * Update petugas account metadata (F-28).
     */
    public function update(Request $request, $id)
    {
        $petugas = Petugas::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'username' => "required|string|max:255|unique:petugas,username,{$id}",
            'email' => "required|email|max:255|unique:petugas,email,{$id}",
            'role' => 'required|string|in:admin,petugas_lab',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Input data tidak valid.',
                'errors' => $validator->errors()
            ], 422);
        }

        $oldUsername = $petugas->username;

        $petugas->update([
            'nama' => $request->nama,
            'username' => $request->username,
            'email' => $request->email,
            'role' => $request->role,
        ]);

        LogAktivitas::create([
            'petugas_id' => $request->user()->id,
            'aksi' => 'Update Petugas',
            'detail' => "Memperbarui akun petugas dari {$oldUsername} menjadi {$petugas->username} ({$petugas->role})",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json([
            'message' => 'Akun petugas berhasil diperbarui.',
            'data' => $petugas
        ]);
    }

    /**
     * Delete petugas account (F-28).
     */
    public function destroy(Request $request, $id)
    {
        $petugas = Petugas::findOrFail($id);

        // Proteksi self-deletion (Admin dilarang menghapus akunnya sendiri)
        if ($petugas->id == $request->user()->id) {
            return response()->json([
                'message' => 'Akses ditolak. Anda tidak diperkenankan menghapus akun Anda sendiri yang sedang aktif.'
            ], 400);
        }

        $username = $petugas->username;
        $petugas->delete();

        LogAktivitas::create([
            'petugas_id' => $request->user()->id,
            'aksi' => 'Hapus Petugas',
            'detail' => "Menghapus akun petugas: {$username}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json([
            'message' => 'Akun petugas berhasil dihapus.'
        ]);
    }

    /**
     * Reset petugas password to default (F-30).
     */
    public function resetPassword(Request $request, $id)
    {
        $petugas = Petugas::findOrFail($id);

        $petugas->update([
            'password' => Hash::make('Password123!'),
            'wajib_ganti_password' => true,
        ]);

        LogAktivitas::create([
            'petugas_id' => $request->user()->id,
            'aksi' => 'Reset Password Petugas',
            'detail' => "Mereset password petugas: {$petugas->username}. Password diset ke default dan diwajibkan ganti password pada login berikutnya.",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json([
            'message' => "Password petugas {$petugas->username} berhasil di-reset ke Password123!"
        ]);
    }
}
