<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengujian;
use App\Models\LogAktivitas;
use App\Http\Requests\PengujianRequest;
use App\Http\Requests\UploadHasilRequest;
use App\Services\PdfExtractionService;
use App\Services\FileIntegrityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PengujianController extends Controller
{
    protected PdfExtractionService $pdfService;
    protected FileIntegrityService $integrityService;

    public function __construct(PdfExtractionService $pdfService, FileIntegrityService $integrityService)
    {
        $this->pdfService = $pdfService;
        $this->integrityService = $integrityService;
    }

    /**
     * Display a listing of pengujian (F-22).
     */
    public function index(Request $request)
    {
        $query = Pengujian::where('is_deleted', false);

        // Pencarian & Filter
        if ($request->filled('cari')) {
            $cari = $request->cari;
            $query->where(function ($subQuery) use ($cari) {
                $subQuery->where('nomor_pengujian', 'like', "%{$cari}%")
                    ->orWhere('nama_pemohon', 'like', "%{$cari}%");
            });
        }

        if ($request->filled('jenis_pengujian')) {
            $query->where('jenis_pengujian', $request->jenis_pengujian);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('bulan')) {
            $query->whereMonth('created_at', $request->bulan);
        }

        if ($request->filled('tahun')) {
            $query->whereYear('created_at', $request->tahun);
        }

        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_akhir')) {
            $mulai = Carbon::parse($request->tanggal_mulai)->startOfDay();
            $akhir = Carbon::parse($request->tanggal_akhir)->endOfDay();
            $query->whereBetween('created_at', [$mulai, $akhir]);
        }

        $pengujian = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json($pengujian);
    }

    /**
     * Store a newly created pengujian (F-11).
     */
    public function store(PengujianRequest $request)
    {
        // 1. Tentukan status awal
        $hasFiles = $request->hasFile('file_laporan') || $request->hasFile('file_sertifikat');
        $status = $hasFiles ? 'selesai' : 'diproses';

        $pengujian = Pengujian::create([
            'nomor_pengujian' => $request->nomor_pengujian,
            'nama_pemohon' => $request->nama_pemohon,
            'email_pemohon' => $request->email_pemohon,
            'jenis_pengujian' => $request->jenis_pengujian,
            'status' => $status,
            'versi' => 1,
            'is_deleted' => false,
        ]);

        // Buat folder hasil_uji di disk local jika belum ada
        if (!Storage::disk('local')->exists('hasil_uji')) {
            Storage::disk('local')->makeDirectory('hasil_uji');
        }

        $uploadDetail = [];

        // 2. Simpan file laporan jika ada
        if ($request->hasFile('file_laporan')) {
            $file = $request->file('file_laporan');
            $filename = Str::uuid() . '-laporan.pdf';
            $path = $file->storeAs('hasil_uji', $filename, 'local');
            $absolutePath = Storage::disk('local')->path($path);
            $hash = $this->integrityService->generateHash($absolutePath);

            $pengujian->file_laporan = $path;
            $pengujian->hash_laporan = $hash;
            $uploadDetail[] = "laporan";
        }

        // 3. Simpan file sertifikat jika ada
        if ($request->hasFile('file_sertifikat')) {
            $file = $request->file('file_sertifikat');
            $filename = Str::uuid() . '-sertifikat.pdf';
            $path = $file->storeAs('hasil_uji', $filename, 'local');
            $absolutePath = Storage::disk('local')->path($path);
            $hash = $this->integrityService->generateHash($absolutePath);

            $pengujian->file_sertifikat = $path;
            $pengujian->hash_sertifikat = $hash;
            $uploadDetail[] = "sertifikat";
        }

        if (count($uploadDetail) > 0) {
            $pengujian->save();

            // Kirim notifikasi email hasil selesai (F-16)
            try {
                \Illuminate\Support\Facades\Mail::to($pengujian->email_pemohon)
                    ->send(new \App\Mail\HasilSiapMail($pengujian));

                \App\Models\LogNotifikasi::create([
                    'pengujian_id' => $pengujian->id,
                    'email_tujuan' => $pengujian->email_pemohon,
                    'tipe_notifikasi' => 'hasil_siap',
                    'status' => 'terkirim',
                    'percobaan_ke' => 1,
                ]);
            } catch (\Exception $e) {
                \App\Models\LogNotifikasi::create([
                    'pengujian_id' => $pengujian->id,
                    'email_tujuan' => $pengujian->email_pemohon,
                    'tipe_notifikasi' => 'hasil_siap',
                    'status' => 'gagal',
                    'percobaan_ke' => 1,
                    'waktu_percobaan_berikutnya' => now()->addMinutes(15),
                    'pesan_error' => $e->getMessage()
                ]);
            }

            $detailString = implode(' & ', $uploadDetail);
            $aksi = 'Tambah Pengujian & Upload Hasil';
            $detailLog = "Menambahkan data pengujian baru dengan nomor: {$pengujian->nomor_pengujian} dan mengunggah berkas {$detailString}";
        } else {
            $aksi = 'Tambah Pengujian';
            $detailLog = "Menambahkan data pengujian baru dengan nomor: {$pengujian->nomor_pengujian}";
        }

        LogAktivitas::create([
            'petugas_id' => $request->user()->id,
            'aksi' => $aksi,
            'detail' => $detailLog,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json([
            'message' => 'Data pengujian berhasil ditambahkan.',
            'data' => $pengujian
        ], 201);
    }

    /**
     * Parse temporary PDF to get suggestions for autofill (during creation).
     */
    public function parsePdf(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf|max:10240'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Berkas harus berupa file PDF dengan ukuran maksimal 10MB.'
            ], 422);
        }

        $file = $request->file('file');
        
        // Memastikan format PDF asli (seperti pdfChecker)
        $handle = fopen($file->getPathname(), 'r');
        if ($handle) {
            $firstBytes = fread($handle, 4);
            fclose($handle);
            if ($firstBytes !== '%PDF') {
                return response()->json([
                    'message' => 'Konten berkas bukan PDF asli.'
                ], 422);
            }
        } else {
            return response()->json([
                'message' => 'Tidak dapat membaca berkas.'
            ], 422);
        }

        // Simpan sementara file untuk di-extract, lalu hapus
        $path = $file->store('temp');
        $absolutePath = Storage::path($path);

        try {
            $autofill = $this->pdfService->extract($absolutePath);
        } finally {
            Storage::delete($path);
        }

        return response()->json($autofill);
    }

    /**
     * Display the specified pengujian.
     */
    public function show(Request $request, $id)
    {
        $pengujian = Pengujian::where('is_deleted', false)->findOrFail($id);
        return response()->json($pengujian);
    }

    /**
     * Update the specified pengujian metadata.
     */
    public function update(PengujianRequest $request, $id)
    {
        $pengujian = Pengujian::where('is_deleted', false)->findOrFail($id);
        $oldNomor = $pengujian->nomor_pengujian;

        $pengujian->update([
            'nomor_pengujian' => $request->nomor_pengujian,
            'nama_pemohon' => $request->nama_pemohon,
            'email_pemohon' => $request->email_pemohon,
            'jenis_pengujian' => $request->jenis_pengujian,
        ]);

        LogAktivitas::create([
            'petugas_id' => $request->user()->id,
            'aksi' => 'Update Pengujian',
            'detail' => "Memperbarui metadata pengujian dari nomor: {$oldNomor} menjadi {$pengujian->nomor_pengujian}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json([
            'message' => 'Data pengujian berhasil diperbarui.',
            'data' => $pengujian
        ]);
    }

    /**
     * Handle upload hasil uji PDF (F-12, F-13, F-14, F-15, F-18).
     */
    public function upload(UploadHasilRequest $request, $id)
    {
        $pengujian = Pengujian::where('is_deleted', false)->findOrFail($id);

        $uploadDetail = [];
        $autofill = null;
        $fileType = null;
        
        // Buat folder hasil_uji di disk local jika belum ada
        if (!Storage::disk('local')->exists('hasil_uji')) {
            Storage::disk('local')->makeDirectory('hasil_uji');
        }

        // Tentukan apakah revisi versi
        $isRevision = !is_null($pengujian->file_laporan) || !is_null($pengujian->file_sertifikat);

        if ($request->hasFile('file_laporan')) {
            $file = $request->file('file_laporan');
            $fileType = 'laporan';
            
            // Simpan file ke disk local non-public dengan nama UUID
            $filename = Str::uuid() . '-laporan.pdf';
            $path = $file->storeAs('hasil_uji', $filename, 'local');
            $absolutePath = Storage::disk('local')->path($path);

            // Hitung hash integrity
            $hash = $this->integrityService->generateHash($absolutePath);

            // Ekstraksi data untuk autofill suggestion
            $autofill = $this->pdfService->extract($absolutePath);

            // Update database
            $pengujian->file_laporan = $path;
            $pengujian->hash_laporan = $hash;
            $uploadDetail[] = "laporan";
        }

        if ($request->hasFile('file_sertifikat')) {
            $file = $request->file('file_sertifikat');
            $fileType = $fileType ?: 'sertifikat'; // Jika belum diset
            
            $filename = Str::uuid() . '-sertifikat.pdf';
            $path = $file->storeAs('hasil_uji', $filename, 'local');
            $absolutePath = Storage::disk('local')->path($path);

            $hash = $this->integrityService->generateHash($absolutePath);

            // Ekstraksi suggestion jika belum ada dari laporan
            if (is_null($autofill)) {
                $autofill = $this->pdfService->extract($absolutePath);
            }

            $pengujian->file_sertifikat = $path;
            $pengujian->hash_sertifikat = $hash;
            $uploadDetail[] = "sertifikat";
        }

        if (count($uploadDetail) > 0) {
            // Increment versi jika file lama ditimpa (revisi)
            if ($isRevision) {
                $pengujian->versi = $pengujian->versi + 1;
            }

            // Ubah status pengujian menjadi 'selesai' setelah file diunggah
            $pengujian->status = 'selesai';
            $pengujian->save();

            // Kirim notifikasi email hasil selesai (F-16)
            try {
                \Illuminate\Support\Facades\Mail::to($pengujian->email_pemohon)
                    ->send(new \App\Mail\HasilSiapMail($pengujian));

                \App\Models\LogNotifikasi::create([
                    'pengujian_id' => $pengujian->id,
                    'email_tujuan' => $pengujian->email_pemohon,
                    'tipe_notifikasi' => 'hasil_siap',
                    'status' => 'terkirim',
                    'percobaan_ke' => 1,
                ]);
            } catch (\Exception $e) {
                \App\Models\LogNotifikasi::create([
                    'pengujian_id' => $pengujian->id,
                    'email_tujuan' => $pengujian->email_pemohon,
                    'tipe_notifikasi' => 'hasil_siap',
                    'status' => 'gagal',
                    'percobaan_ke' => 1,
                    'waktu_percobaan_berikutnya' => now()->addMinutes(15), // Coba lagi 15 menit berikutnya
                    'pesan_error' => $e->getMessage()
                ]);
            }

            $detailString = implode(' & ', $uploadDetail);
            $aksi = $isRevision ? 'Revisi Hasil Uji' : 'Upload Hasil Uji';

            LogAktivitas::create([
                'petugas_id' => $request->user()->id,
                'aksi' => $aksi,
                'detail' => "Mengunggah berkas {$detailString} hasil pengujian (versi {$pengujian->versi}) untuk nomor pengujian: {$pengujian->nomor_pengujian}",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return response()->json([
                'message' => 'Berkas berhasil diunggah.',
                'file_type' => $fileType,
                'versi' => $pengujian->versi,
                'autofill' => $autofill,
                'data' => $pengujian
            ]);
        }

        return response()->json([
            'message' => 'Tidak ada berkas yang diunggah.'
        ], 400);
    }

    /**
     * Koreksi email pemohon manual (F-17).
     */
    public function updateEmail(Request $request, $id)
    {
        $request->validate([
            'email_pemohon' => 'required|email|max:255'
        ]);

        $pengujian = Pengujian::where('is_deleted', false)->findOrFail($id);
        $oldEmail = $pengujian->email_pemohon;
        
        $pengujian->email_pemohon = $request->email_pemohon;
        $pengujian->save();

        LogAktivitas::create([
            'petugas_id' => $request->user()->id,
            'aksi' => 'Koreksi Email Pemohon',
            'detail' => "Mengoreksi email pemohon untuk nomor pengujian {$pengujian->nomor_pengujian} dari {$oldEmail} menjadi {$pengujian->email_pemohon}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json([
            'message' => 'Email pemohon berhasil dikoreksi.',
            'data' => $pengujian
        ]);
    }

    /**
     * Soft-delete data pengujian (F-19, Admin only).
     */
    public function destroy(Request $request, $id)
    {
        $pengujian = Pengujian::where('is_deleted', false)->findOrFail($id);

        // Otorisasi Policy
        if ($request->user()->cannot('delete', $pengujian)) {
            return response()->json([
                'message' => 'Akses ditolak. Hanya administrator yang dapat menghapus data pengujian.'
            ], 403);
        }

        $pengujian->is_deleted = true;
        $pengujian->save();

        LogAktivitas::create([
            'petugas_id' => $request->user()->id,
            'aksi' => 'Soft Delete Pengujian',
            'detail' => "Melakukan penghapusan (soft-delete) data pengujian dengan nomor: {$pengujian->nomor_pengujian}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json([
            'message' => 'Data pengujian berhasil dihapus.'
        ]);
    }

    /**
     * Download berkas hasil uji oleh petugas/admin (F-22).
     */
    public function download(Request $request, $id, $type)
    {
        $pengujian = Pengujian::where('is_deleted', false)->findOrFail($id);

        if ($type === 'laporan') {
            $filePath = $pengujian->file_laporan;
        } else {
            return response()->json([
                'message' => 'Tipe berkas tidak valid.'
            ], 400);
        }

        if (!$filePath) {
            return response()->json([
                'message' => 'Berkas belum diunggah atau tidak tersedia.'
            ], 404);
        }

        if (!Storage::disk('local')->exists($filePath)) {
            return response()->json([
                'message' => 'Berkas fisik tidak ditemukan di server.'
            ], 404);
        }

        $absolutePath = Storage::disk('local')->path($filePath);
        $fileName = ($type === 'laporan' ? 'Laporan_' : 'Sertifikat_') . $pengujian->nomor_pengujian . '.pdf';

        // Catat riwayat download ke tabel akses_file_log
        \App\Models\AksesFileLog::create([
            'pengujian_id' => $pengujian->id,
            'tipe_file' => $type,
            'akses_oleh' => 'petugas',
            'petugas_id' => $request->user()->id,
            'ip_address' => $request->ip(),
        ]);

        return response()->download($absolutePath, $fileName, [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'",
        ]);
    }
}


