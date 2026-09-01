<?php

namespace App\Services;

use App\Models\Pengujian;
use App\Models\OtpVerifikasi;
use App\Models\TokenAkses;
use App\Models\LogNotifikasi;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OtpService
{
    /**
     * Samarkan email pemohon demi kepatuhan UU PDP (F-02).
     */
    public function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return $email;
        }

        $name = $parts[0];
        $domain = $parts[1];
        $len = strlen($name);

        if ($len <= 2) {
            return $name . '@' . $domain;
        }

        // Contoh budi@gmail.com -> b**i@gmail.com
        return $name[0] . str_repeat('*', $len - 2) . $name[$len - 1] . '@' . $domain;
    }

    /**
     * Menerbitkan dan mengirim OTP baru ke email pemohon.
     */
    public function kirimOtp(Pengujian $pengujian): array
    {
        // 1. Kadaluarsakan OTP aktif sebelumnya untuk pengujian ini (Hanya kode terakhir yang valid)
        OtpVerifikasi::where('pengujian_id', $pengujian->id)
            ->where('status', 'aktif')
            ->update(['status' => 'kadaluarsa']);

        // 2. Generate kode 6 digit acak
        $kode = (string) random_int(100000, 999999);

        // 3. Simpan hash kode OTP ke database
        $otp = OtpVerifikasi::create([
            'pengujian_id' => $pengujian->id,
            'kode_hash' => Hash::make($kode),
            'status' => 'aktif',
            'percobaan_gagal' => 0,
            'expired_at' => now()->addMinutes(10), // Berlaku 10 menit
        ]);

        // 4. Kirim email OTP secara synchronous (Shared hosting worker constraints)
        try {
            Mail::to($pengujian->email_pemohon)->send(new OtpMail($kode, $pengujian));
            
            // Catat log notifikasi terkirim
            LogNotifikasi::create([
                'pengujian_id' => $pengujian->id,
                'email_tujuan' => $pengujian->email_pemohon,
                'tipe_notifikasi' => 'otp',
                'status' => 'terkirim',
                'percobaan_ke' => 1,
            ]);

            return [
                'success' => true,
                'message' => 'Kode OTP berhasil dikirim ke email terdaftar Anda.'
            ];
        } catch (\Exception $exception) {
            // Catat log notifikasi gagal untuk dicoba ulang oleh scheduler
            LogNotifikasi::create([
                'pengujian_id' => $pengujian->id,
                'email_tujuan' => $pengujian->email_pemohon,
                'tipe_notifikasi' => 'otp',
                'status' => 'gagal',
                'percobaan_ke' => 1,
                'waktu_percobaan_berikutnya' => now()->addMinutes(5), // Coba lagi 5 menit berikutnya
                'pesan_error' => $exception->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Pengiriman email gagal. Kode OTP Anda dijadwalkan untuk dicoba ulang.'
            ];
        }
    }

    /**
     * Memverifikasi kode OTP yang diinput pengguna jasa.
     */
    public function verifikasiOtp(Pengujian $pengujian, string $kode): array
    {
        // Cari OTP aktif yang belum expired
        $otpRow = OtpVerifikasi::where('pengujian_id', $pengujian->id)
            ->where('status', 'aktif')
            ->where('expired_at', '>', now())
            ->first();

        if (!$otpRow) {
            return [
                'success' => false,
                'message' => 'Kode OTP tidak ditemukan, sudah kedaluwarsa, atau tidak aktif.'
            ];
        }

        // Cek jika sudah melebihi 5 kali percobaan gagal
        if ($otpRow->percobaan_gagal >= 5) {
            $otpRow->update(['status' => 'kadaluarsa']);
            return [
                'success' => false,
                'message' => 'Kode OTP tidak berlaku lagi karena 5 kali salah input. Silakan kirim ulang OTP baru.'
            ];
        }

        // Verifikasi kode
        if (Hash::check($kode, $otpRow->kode_hash)) {
            // Sukses
            $otpRow->update(['status' => 'terpakai']);

            // Terbitkan Token Akses Publik sementara (30 menit)
            $key = Str::random(40);
            $tokenAkses = TokenAkses::create([
                'pengujian_id' => $pengujian->id,
                'token_hash' => Hash::make($key),
                'expired_at' => now()->addMinutes(30)
            ]);

            // Gabungkan ID dan Key mentah agar database query tetap instan & secure (bcrypt)
            $tokenPlainText = $tokenAkses->id . '|' . $key;

            return [
                'success' => true,
                'token' => $tokenPlainText
            ];
        } else {
            // Gagal
            $gagal = $otpRow->percobaan_gagal + 1;
            $sisa = 5 - $gagal;

            if ($gagal >= 5) {
                $otpRow->update([
                    'percobaan_gagal' => $gagal,
                    'status' => 'kadaluarsa'
                ]);
                return [
                    'success' => false,
                    'message' => 'Kode OTP tidak berlaku lagi karena 5 kali salah input. Silakan kirim ulang OTP baru.'
                ];
            }

            $otpRow->update(['percobaan_gagal' => $gagal]);

            return [
                'success' => false,
                'message' => "Kode OTP salah. Sisa percobaan: {$sisa} kali."
            ];
        }
    }
}
