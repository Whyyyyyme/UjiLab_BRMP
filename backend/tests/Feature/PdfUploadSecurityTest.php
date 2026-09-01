<?php

namespace Tests\Feature;

use App\Models\Petugas;
use App\Models\Pengujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfUploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Petugas $petugas;
    private Pengujian $pengujian;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('local');

        $this->petugas = Petugas::create([
            'nama' => 'Petugas Upload',
            'username' => 'petugasupload',
            'email' => 'upload@brmp.go.id',
            'password' => Hash::make('password123'),
            'role' => 'petugas_lab',
            'wajib_ganti_password' => false,
        ]);

        $this->pengujian = Pengujian::create([
            'nomor_pengujian' => 'UJI-UPLOAD-001',
            'nama_pemohon' => 'Budi',
            'email_pemohon' => 'budi@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
            'status' => 'diproses',
        ]);
    }

    /**
     * Membuat UploadedFile PDF asli dengan magic bytes %PDF.
     */
    private function createValidPdf(string $name): UploadedFile
    {
        $temp = tempnam(sys_get_temp_dir(), 'pdf_test');
        // Isi dengan string diawali %PDF
        file_put_contents($temp, "%PDF-1.4\nLaporan Hasil Uji\nNomor Pengujian: UJI-UPLOAD-001\nNama Pemohon: Budi\nJenis Pengujian: Deteksi GMO");
        return new UploadedFile($temp, $name, 'application/pdf', null, true);
    }

    /**
     * Membuat UploadedFile PDF palsu (tidak diawali %PDF).
     */
    private function createInvalidPdf(string $name): UploadedFile
    {
        $temp = tempnam(sys_get_temp_dir(), 'pdf_test_invalid');
        file_put_contents($temp, "BUKAN_PDF_ASLI_TAPI_TULISAN_BIASA");
        return new UploadedFile($temp, $name, 'application/pdf', null, true);
    }

    /**
     * Test upload file bukan PDF asli ditolak (Rule 4.8).
     */
    public function test_upload_bukan_pdf_asli_ditolak(): void
    {
        $filePalsu = $this->createInvalidPdf('laporan.pdf');

        $response = $this->actingAs($this->petugas, 'sanctum')
            ->postJson("/api/admin/pengujian/{$this->pengujian->id}/upload", [
                'file_laporan' => $filePalsu,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file_laporan']);
    }

    /**
     * Test upload file PDF asli diterima, di-rename UUID, dan hash SHA-256 tersimpan.
     */
    public function test_upload_pdf_asli_sukses_dan_secure(): void
    {
        $fileAsli = $this->createValidPdf('laporan.pdf');

        $response = $this->actingAs($this->petugas, 'sanctum')
            ->postJson("/api/admin/pengujian/{$this->pengujian->id}/upload", [
                'file_laporan' => $fileAsli,
            ]);

        $response->assertStatus(200);

        $this->pengujian->refresh();
        
        // Pastikan file_laporan terisi dan di-rename UUID
        $this->assertNotNull($this->pengujian->file_laporan);
        $this->assertStringEndsWith('-laporan.pdf', $this->pengujian->file_laporan);
        $this->assertNotEquals('laporan.pdf', basename($this->pengujian->file_laporan)); // UUID random name

        // Pastikan file disimpan di disk local
        Storage::disk('local')->assertExists($this->pengujian->file_laporan);

        // Pastikan hash SHA-256 terhitung dan tersimpan
        $this->assertNotNull($this->pengujian->hash_laporan);
        $this->assertEquals(64, strlen($this->pengujian->hash_laporan)); // SHA-256 length is 64 chars
        $this->assertEquals('selesai', $this->pengujian->status);
    }

    /**
     * Test revisi file hasil uji meningkatkan versi dan file lama tidak ditimpa (F-18).
     */
    public function test_revisi_file_meningkatkan_versi_dan_tidak_menimpa(): void
    {
        // 1. Upload Pertama (Versi 1)
        $file1 = $this->createValidPdf('laporan_v1.pdf');
        $response = $this->actingAs($this->petugas, 'sanctum')
            ->postJson("/api/admin/pengujian/{$this->pengujian->id}/upload", [
                'file_laporan' => $file1,
            ]);
        $response->assertStatus(200);
        
        $this->pengujian->refresh();
        $fileLaporanLama = $this->pengujian->file_laporan;
        $this->assertEquals(1, $this->pengujian->versi);

        // 2. Upload Kedua / Revisi (Versi 2)
        $file2 = $this->createValidPdf('laporan_v2.pdf');
        $response = $this->actingAs($this->petugas, 'sanctum')
            ->postJson("/api/admin/pengujian/{$this->pengujian->id}/upload", [
                'file_laporan' => $file2,
            ]);
        $response->assertStatus(200);

        $this->pengujian->refresh();
        $this->assertEquals(2, $this->pengujian->versi);
        $this->assertNotEquals($fileLaporanLama, $this->pengujian->file_laporan);

        // Pastikan file laporan lama MASIH ADA di disk local (tidak ditimpa/dihapus)
        Storage::disk('local')->assertExists($fileLaporanLama);
        Storage::disk('local')->assertExists($this->pengujian->file_laporan);
    }
}
