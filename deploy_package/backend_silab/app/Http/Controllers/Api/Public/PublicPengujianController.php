<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Pengujian;
use App\Models\OtpVerifikasi;
use App\Models\Skm;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PublicPengujianController extends Controller
{
    protected OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Cari nomor pengujian oleh publik (F-01, F-02, F-153).
     */
    public function cari(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nomor_pengujian' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Nomor pengujian wajib diisi.'
            ], 422);
        }

        // Cari nomor pengujian yang aktif (bukan soft-deleted)
        $pengujian = Pengujian::where('nomor_pengujian', $request->nomor_pengujian)
            ->where('is_deleted', false)
            ->first();

        // Pesan error digeneralisasi untuk mencegah enumerasi data (F-153)
        if (!$pengujian) {
            return response()->json([
                'message' => 'Data pengujian tidak ditemukan atau tidak aktif.'
            ], 404);
        }

        // Cek jika berkas pengujian telah melewati masa retensi dan diarsipkan
        if ($pengujian->is_archived) {
            return response()->json([
                'status' => 'archived',
                'message' => 'Berkas pengujian ini telah melewati batas masa retensi digital (> 3 tahun) dan telah dipindahkan ke repositori arsip BRMP Biogen. Silakan hubungi layanan pelanggan BRMP Biogen untuk pembukaan arsip fisik.'
            ], 410);
        }

        // Cek jika berkas hasil pengujian (PDF) belum diunggah atau status masih diproses
        if ($pengujian->status !== 'selesai' || empty($pengujian->file_laporan)) {
            return response()->json([
                'status' => 'diproses',
                'message' => 'Pengujian ini masih dalam proses pengerjaan di laboratorium dan berkas hasil uji (PDF) belum diunggah. Silakan hubungi petugas laboratorium atau coba kembali setelah hasil pengujian selesai.'
            ], 422);
        }


        return response()->json([
            'id' => $pengujian->id,
            'nomor_pengujian' => $pengujian->nomor_pengujian,
            'email_tersamar' => $this->otpService->maskEmail($pengujian->email_pemohon)
        ]);
    }

    /**
     * Kirim/Kirim ulang OTP (F-04, F-05).
     */
    public function kirimOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pengujian_id' => 'required|integer',
            'nomor_pengujian' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'ID dan nomor pengujian wajib diisi.'
            ], 422);
        }

        $pengujian = Pengujian::where('id', $request->pengujian_id)
            ->where('nomor_pengujian', $request->nomor_pengujian)
            ->where('is_deleted', false)
            ->first();

        if (!$pengujian) {
            return response()->json([
                'message' => 'Data pengujian tidak ditemukan.'
            ], 404);
        }

        if ($pengujian->is_archived) {
            return response()->json([
                'message' => 'Berkas pengujian ini telah melewati batas masa retensi digital dan telah diarsipkan.'
            ], 410);
        }

        if ($pengujian->status !== 'selesai' || empty($pengujian->file_laporan)) {
            return response()->json([
                'message' => 'Pengujian ini masih dalam proses pengerjaan di laboratorium dan berkas hasil uji (PDF) belum diunggah.'
            ], 422);
        }


        // Cek batas pengiriman OTP maksimal 5 kali per jam per nomor pengujian
        $otpCount = OtpVerifikasi::where('pengujian_id', $pengujian->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($otpCount >= 5) {
            return response()->json([
                'message' => 'Batas pengiriman kode OTP untuk nomor pengujian ini telah tercapai (maksimal 5 kali per jam). Silakan coba lagi nanti.'
            ], 429);
        }

        // Cek cooldown 60 detik kirim ulang OTP (F-05)
        $lastOtp = OtpVerifikasi::where('pengujian_id', $pengujian->id)
            ->latest()
            ->first();

        if ($lastOtp && $lastOtp->created_at->diffInSeconds(now()) < 60) {
            $sisaCooldown = 60 - $lastOtp->created_at->diffInSeconds(now());
            return response()->json([
                'message' => "Mohon tunggu {$sisaCooldown} detik sebelum meminta kode OTP kembali."
            ], 429);
        }

        // Jalankan pengiriman OTP
        $result = $this->otpService->kirimOtp($pengujian);

        if ($result['success']) {
            return response()->json([
                'message' => $result['message']
            ]);
        } else {
            return response()->json([
                'message' => $result['message']
            ], 200); // Kembalikan 200 karena OTP dijadwalkan retry secara sync
        }
    }

    /**
     * Verifikasi kode OTP (F-06, F-07).
     */
    public function verifikasiOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pengujian_id' => 'required|integer',
            'nomor_pengujian' => 'required|string',
            'kode' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'ID pengujian, nomor pengujian, dan kode OTP 6 digit wajib diisi.'
            ], 422);
        }

        $pengujian = Pengujian::where('id', $request->pengujian_id)
            ->where('nomor_pengujian', $request->nomor_pengujian)
            ->where('is_deleted', false)
            ->first();

        if (!$pengujian) {
            return response()->json([
                'message' => 'Data pengujian tidak ditemukan.'
            ], 404);
        }

        if ($pengujian->is_archived) {
            return response()->json([
                'message' => 'Berkas pengujian ini telah melewati batas masa retensi digital dan telah diarsipkan.'
            ], 410);
        }

        if ($pengujian->status !== 'selesai' || empty($pengujian->file_laporan)) {
            return response()->json([
                'message' => 'Pengujian ini masih dalam proses pengerjaan di laboratorium dan berkas hasil uji (PDF) belum diunggah.'
            ], 422);
        }


        $result = $this->otpService->verifikasiOtp($pengujian, $request->kode);

        if ($result['success']) {
            return response()->json([
                'message' => 'Verifikasi OTP berhasil.',
                'token' => $result['token']
            ]);
        } else {
            return response()->json([
                'message' => $result['message']
            ], 401);
        }
    }

    /**
     * Cek status pengujian & pengisian SKM (Butuh middleware EnsureTokenAksesValid).
     */
    public function cekStatus(Request $request)
    {
        // $pengujian diikat oleh middleware EnsureTokenAksesValid
        $pengujian = $request->pengujian;

        $skmDiisi = Skm::where('pengujian_id', $pengujian->id)->exists();

        return response()->json([
            'id' => $pengujian->id,
            'nomor_pengujian' => $pengujian->nomor_pengujian,
            'nama_pemohon' => $pengujian->nama_pemohon,
            'jenis_pengujian' => $pengujian->jenis_pengujian,
            'status' => $pengujian->status,
            'skm_diisi' => $skmDiisi,
            'file_laporan_ready' => !is_null($pengujian->file_laporan),
            'versi' => $pengujian->versi ?? 1,
            'hash_laporan' => $pengujian->hash_laporan,
            'tanggal_selesai' => ($pengujian->status === 'selesai' && $pengujian->updated_at) ? $pengujian->updated_at->format('Y-m-d H:i') : null,
            'tanggal_masuk' => $pengujian->created_at ? $pengujian->created_at->format('Y-m-d') : null,
        ]);
    }

    /**
     * Download berkas hasil uji (F-10, F-15, F-16).
     */
    public function download(Request $request, $id, $type)
    {
        // $pengujian diikat oleh middleware EnsureTokenAksesValid
        $pengujian = $request->pengujian;

        // Validasi pengujian ID cocok dengan token
        if ($pengujian->id != $id) {
            return response()->json([
                'message' => 'Token akses tidak valid untuk nomor pengujian ini.'
            ], 403);
        }

        // 1. Validasi SKM wajib sudah diisi (Rule 4.3)
        $skmExists = Skm::where('pengujian_id', $pengujian->id)->exists();
        if (!$skmExists) {
            return response()->json([
                'message' => 'Akses ditolak. Anda wajib mengisi kuesioner SKM terlebih dahulu sebelum mengunduh berkas.'
            ], 403);
        }

        // 2. Tentukan file path dan hash tersimpan
        if ($type === 'laporan') {
            $filePath = $pengujian->file_laporan;
            $storedHash = $pengujian->hash_laporan;
        } else {
            return response()->json([
                'message' => 'Tipe berkas tidak valid.'
            ], 400);
        }

        if ($pengujian->is_archived) {
            return response()->json([
                'message' => 'Berkas pengujian ini telah melewati batas masa retensi digital (> 3 tahun) dan telah dipindahkan ke repositori arsip BRMP Biogen. Silakan hubungi layanan pelanggan BRMP Biogen jika Anda membutuhkan salinan arsip fisik.'
            ], 410);
        }

        if (!$filePath) {
            return response()->json([
                'message' => 'Berkas belum diunggah atau tidak tersedia.'
            ], 404);
        }

        // Cek fisik file di disk local
        if (!\Illuminate\Support\Facades\Storage::disk('local')->exists($filePath)) {
            return response()->json([
                'message' => 'Berkas fisik tidak ditemukan di server.'
            ], 404);
        }

        $absolutePath = \Illuminate\Support\Facades\Storage::disk('local')->path($filePath);

        // 3. Validasi Integritas File: Hitung ulang SHA-256 (F-16)
        $currentHash = hash_file('sha256', $absolutePath);
        if ($currentHash !== $storedHash) {
            return response()->json([
                'message' => 'Sistem mendeteksi berkas ini telah rusak atau dimodifikasi secara ilegal. Unduhan dibatalkan demi keamanan data.'
            ], 400);
        }

        // 4. Catat riwayat download ke tabel akses_file_log
        \App\Models\AksesFileLog::create([
            'pengujian_id' => $pengujian->id,
            'tipe_file' => $type,
            'akses_oleh' => 'publik',
            'petugas_id' => null,
            'ip_address' => $request->ip(),
        ]);

        // 5. Stream file menggunakan BinaryFileResponse dengan Header Keamanan PDF
        $cleanNomor = str_replace(['/', '\\'], '_', $pengujian->nomor_pengujian);
        $fileName = ($type === 'laporan' ? 'Laporan_' : 'Sertifikat_') . $cleanNomor . '.pdf';
        return response()->download($absolutePath, $fileName, [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'",
        ]);
    }

    /**
     * Verifikasi keaslian dokumen publik (F-20, F-21).
     */
    public function verifikasi(Request $request, $nomor_pengujian)
    {
        // Cari data aktif
        $pengujian = Pengujian::where('nomor_pengujian', $nomor_pengujian)
            ->where('is_deleted', false)
            ->first();

        // Anti-enumeration: kembalikan 404 umum
        if (!$pengujian) {
            return response()->json([
                'message' => 'Data pengujian tidak ditemukan atau tidak aktif.'
            ], 404);
        }

        // Verifikasi keaslian file jika tersedia
        $statusLaporan = false;
        $statusSertifikat = false;

        if ($pengujian->file_laporan && \Illuminate\Support\Facades\Storage::disk('local')->exists($pengujian->file_laporan)) {
            $path = \Illuminate\Support\Facades\Storage::disk('local')->path($pengujian->file_laporan);
            $statusLaporan = hash_file('sha256', $path) === $pengujian->hash_laporan;
        }

        if ($pengujian->file_sertifikat && \Illuminate\Support\Facades\Storage::disk('local')->exists($pengujian->file_sertifikat)) {
            $path = \Illuminate\Support\Facades\Storage::disk('local')->path($pengujian->file_sertifikat);
            $statusSertifikat = hash_file('sha256', $path) === $pengujian->hash_sertifikat;
        }

        // Untuk status_keaslian umum, jika ada minimal satu file terverifikasi valid
        $statusKeaslian = false;
        if ($pengujian->file_laporan || $pengujian->file_sertifikat) {
            $statusKeaslian = ($pengujian->file_laporan ? $statusLaporan : true) && 
                               ($pengujian->file_sertifikat ? $statusSertifikat : true);
        }

        // Samarkan nama pemohon untuk kepatuhan UU PDP
        $parts = explode(' ', $pengujian->nama_pemohon);
        $maskedParts = array_map(function ($part) {
            $len = strlen($part);
            if ($len <= 2) return $part;
            return $part[0] . str_repeat('*', $len - 2) . $part[$len - 1];
        }, $parts);
        $namaMasked = implode(' ', $maskedParts);

        return response()->json([
            'nomor_pengujian' => $pengujian->nomor_pengujian,
            'jenis_pengujian' => $pengujian->jenis_pengujian,
            'nama_pemohon' => $namaMasked,
            'status' => $pengujian->status,
            'tanggal_selesai' => ($pengujian->status === 'selesai') ? $pengujian->updated_at->toDateString() : null,
            'status_keaslian' => $pengujian->is_archived ? true : $statusKeaslian,
            'laporan_valid' => $statusLaporan,
            'sertifikat_valid' => $statusSertifikat,
            'is_archived' => (bool) $pengujian->is_archived,
            'archived_at' => $pengujian->archived_at ? $pengujian->archived_at->toDateString() : null,
        ]);
    }
}


