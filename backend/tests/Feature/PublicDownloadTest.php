<?php

namespace Tests\Feature;

use App\Models\Pengujian;
use App\Models\TokenAkses;
use App\Models\Skm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicDownloadTest extends TestCase
{
    use RefreshDatabase;

    private Pengujian $pengujian;
    private string $tokenHeader;
    private string $filePath;
    private string $fileHash;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        // Buat file laporan dummy di disk
        $this->filePath = 'hasil_uji/laporan_test.pdf';
        Storage::disk('local')->put($this->filePath, "%PDF-1.4\nIsi laporan hasil uji lab");
        $this->fileHash = hash_file('sha256', Storage::disk('local')->path($this->filePath));

        // Buat data pengujian
        $this->pengujian = Pengujian::create([
            'nomor_pengujian' => 'UJI-DL-001',
            'nama_pemohon' => 'Andi',
            'email_pemohon' => 'andi@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
            'status' => 'selesai',
            'file_laporan' => $this->filePath,
            'hash_laporan' => $this->fileHash,
        ]);

        // Terbitkan token akses
        $key = Str::random(40);
        $token = TokenAkses::create([
            'pengujian_id' => $this->pengujian->id,
            'token_hash' => Hash::make($key),
            'expired_at' => now()->addMinutes(30)
        ]);

        $this->tokenHeader = $token->id . '|' . $key;
    }

    /**
     * Test download ditolak (403) jika belum mengisi kuesioner SKM (Rule 4.3).
     */
    public function test_download_ditolak_jika_skm_belum_diisi(): void
    {
        $response = $this->withHeaders([
            'X-Akses-Token' => $this->tokenHeader
        ])->getJson("/api/public/pengujian/{$this->pengujian->id}/download/laporan");

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Akses ditolak. Anda wajib mengisi kuesioner SKM terlebih dahulu sebelum mengunduh berkas.');
    }

    /**
     * Test download sukses jika token valid dan SKM telah diisi (F-10, F-15).
     */
    public function test_download_sukses_jika_skm_diisi(): void
    {
        // Isi SKM
        Skm::create([
            'pengujian_id' => $this->pengujian->id,
            'skor_1' => 4, 'skor_2' => 4, 'skor_3' => 4, 'skor_4' => 4,
            'skor_5' => 4, 'skor_6' => 4, 'skor_7' => 4, 'skor_8' => 4, 'skor_9' => 4,
            'tanggal_isi' => now()->toDateString(),
        ]);

        $response = $this->withHeaders([
            'X-Akses-Token' => $this->tokenHeader
        ])->getJson("/api/public/pengujian/{$this->pengujian->id}/download/laporan");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');

        // Pastikan ter-log ke akses_file_log
        $this->assertDatabaseHas('akses_file_log', [
            'pengujian_id' => $this->pengujian->id,
            'tipe_file' => 'laporan',
            'akses_oleh' => 'publik',
        ]);
    }

    /**
     * Test download ditolak (400) dan mengembalikan pesan keamanan jika integritas file rusak (F-16).
     */
    public function test_download_gagal_jika_integritas_file_tidak_cocok(): void
    {
        // Isi SKM
        Skm::create([
            'pengujian_id' => $this->pengujian->id,
            'skor_1' => 4, 'skor_2' => 4, 'skor_3' => 4, 'skor_4' => 4,
            'skor_5' => 4, 'skor_6' => 4, 'skor_7' => 4, 'skor_8' => 4, 'skor_9' => 4,
            'tanggal_isi' => now()->toDateString(),
        ]);

        // Modifikasi berkas fisik secara ilegal (isi berkas diubah sehingga hash berbeda)
        Storage::disk('local')->put($this->filePath, "%PDF-1.4\nIsi berkas yang sudah dimodifikasi secara ilegal!");

        $response = $this->withHeaders([
            'X-Akses-Token' => $this->tokenHeader
        ])->getJson("/api/public/pengujian/{$this->pengujian->id}/download/laporan");

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Sistem mendeteksi berkas ini telah rusak atau dimodifikasi secara ilegal. Unduhan dibatalkan demi keamanan data.');
    }
}
