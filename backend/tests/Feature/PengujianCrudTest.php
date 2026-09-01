<?php

namespace Tests\Feature;

use App\Models\Petugas;
use App\Models\Pengujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PengujianCrudTest extends TestCase
{
    use RefreshDatabase;

    private Petugas $petugas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->petugas = Petugas::create([
            'nama' => 'Petugas CRUD',
            'username' => 'petugascrud',
            'email' => 'crud@brmp.go.id',
            'password' => Hash::make('password123'),
            'role' => 'petugas_lab',
            'wajib_ganti_password' => false,
        ]);
    }

    /**
     * Test data pengujian dapat dicari & difilter (F-22).
     */
    public function test_list_pengujian_dengan_filter_dan_cari(): void
    {
        // Buat data dummy
        Pengujian::create([
            'nomor_pengujian' => 'UJI-001',
            'nama_pemohon' => 'Budi Santoso',
            'email_pemohon' => 'budi@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
            'status' => 'diproses',
        ]);

        Pengujian::create([
            'nomor_pengujian' => 'UJI-002',
            'nama_pemohon' => 'Siti Aminah',
            'email_pemohon' => 'siti@example.com',
            'jenis_pengujian' => 'Analisis SSR/RAPD',
            'status' => 'selesai',
        ]);

        // 1. Uji tanpa filter
        $response = $this->actingAs($this->petugas, 'sanctum')
            ->getJson('/api/admin/pengujian');
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        // 2. Uji filter pencarian
        $response = $this->actingAs($this->petugas, 'sanctum')
            ->getJson('/api/admin/pengujian?cari=Budi');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nomor_pengujian', 'UJI-001');

        // 3. Uji filter jenis pengujian
        $response = $this->actingAs($this->petugas, 'sanctum')
            ->getJson('/api/admin/pengujian?jenis_pengujian=Analisis SSR/RAPD');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nomor_pengujian', 'UJI-002');

        // 4. Uji filter status
        $response = $this->actingAs($this->petugas, 'sanctum')
            ->getJson('/api/admin/pengujian?status=diproses');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nomor_pengujian', 'UJI-001');
    }

    /**
     * Test tambah data pengujian sukses (F-11).
     */
    public function test_tambah_pengujian_sukses(): void
    {
        $response = $this->actingAs($this->petugas, 'sanctum')
            ->postJson('/api/admin/pengujian', [
                'nomor_pengujian' => 'UJI-999',
                'nama_pemohon' => 'Joko Widodo',
                'email_pemohon' => 'joko@example.com',
                'jenis_pengujian' => 'Deteksi GMO',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('pengujian', [
            'nomor_pengujian' => 'UJI-999',
            'nama_pemohon' => 'Joko Widodo',
            'email_pemohon' => 'joko@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
            'status' => 'diproses',
        ]);
    }

    /**
     * Test tambah data pengujian gagal karena validasi (nomor pengujian kembar).
     */
    public function test_tambah_pengujian_gagal_karena_nomor_duplikat(): void
    {
        Pengujian::create([
            'nomor_pengujian' => 'UJI-DUP',
            'nama_pemohon' => 'Budi',
            'email_pemohon' => 'budi@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
        ]);

        $response = $this->actingAs($this->petugas, 'sanctum')
            ->postJson('/api/admin/pengujian', [
                'nomor_pengujian' => 'UJI-DUP',
                'nama_pemohon' => 'Joko',
                'email_pemohon' => 'joko@example.com',
                'jenis_pengujian' => 'Deteksi GMO',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nomor_pengujian']);
    }

    /**
     * Test koreksi email pemohon manual (F-17).
     */
    public function test_koreksi_email_pemohon(): void
    {
        $pengujian = Pengujian::create([
            'nomor_pengujian' => 'UJI-100',
            'nama_pemohon' => 'Lani',
            'email_pemohon' => 'lani_lama@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
        ]);

        $response = $this->actingAs($this->petugas, 'sanctum')
            ->patchJson("/api/admin/pengujian/{$pengujian->id}/email", [
                'email_pemohon' => 'lani_baru@example.com',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('pengujian', [
            'id' => $pengujian->id,
            'email_pemohon' => 'lani_baru@example.com',
        ]);
    }

    /**
     * Test list pengujian dengan filter bulan dan tahun (F-22).
     */
    public function test_list_pengujian_dengan_filter_bulan_dan_tahun(): void
    {
        // 1. Buat data pengujian di bulan Agustus dan September
        $p1 = Pengujian::create([
            'nomor_pengujian' => 'UJI-AUG',
            'nama_pemohon' => 'Agustina',
            'email_pemohon' => 'agustina@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
        ]);
        $p1->created_at = \Carbon\Carbon::parse('2026-08-15 10:00:00');
        $p1->save();

        $p2 = Pengujian::create([
            'nomor_pengujian' => 'UJI-SEP',
            'nama_pemohon' => 'Septian',
            'email_pemohon' => 'septian@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
        ]);
        $p2->created_at = \Carbon\Carbon::parse('2026-09-20 10:00:00');
        $p2->save();

        // 2. Filter khusus Agustus 2026
        $response = $this->actingAs($this->petugas, 'sanctum')
            ->getJson('/api/admin/pengujian?bulan=8&tahun=2026');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nomor_pengujian', 'UJI-AUG');

        // 3. Filter khusus September 2026
        $response = $this->actingAs($this->petugas, 'sanctum')
            ->getJson('/api/admin/pengujian?bulan=9&tahun=2026');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nomor_pengujian', 'UJI-SEP');
    }

    /**
     * Test unduh berkas pengujian oleh petugas/admin (F-22).
     */
    public function test_download_berkas_pengujian_petugas(): void
    {
        // Simpan file dummy ke disk local
        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Storage::disk('local')->put('hasil_uji/dummy-laporan.pdf', '%PDF-1.4 dummy content');

        $pengujian = Pengujian::create([
            'nomor_pengujian' => 'UJI-DL-PETUGAS',
            'nama_pemohon' => 'Edo',
            'email_pemohon' => 'edo@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
            'file_laporan' => 'hasil_uji/dummy-laporan.pdf',
        ]);

        // Petugas mengunduh berkas laporan
        $response = $this->actingAs($this->petugas, 'sanctum')
            ->get("/api/admin/pengujian/{$pengujian->id}/download/laporan");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');

        // Pastikan akses tercatat ke log
        $this->assertDatabaseHas('akses_file_log', [
            'pengujian_id' => $pengujian->id,
            'tipe_file' => 'laporan',
            'akses_oleh' => 'petugas',
            'petugas_id' => $this->petugas->id,
        ]);
    }

    /**
     * Test parse PDF temporer sukses mengembalikan data autofill.
     */
    public function test_parse_pdf_berhasil_mengekstrak_data(): void
    {
        $this->mock(\App\Services\PdfExtractionService::class, function ($mock) {
            $mock->shouldReceive('extract')
                ->once()
                ->andReturn([
                    'nomor_pengujian' => 'UJI-MOCK-123',
                    'nama_pemohon' => 'Budi Mock',
                    'jenis_pengujian' => 'Deteksi GMO',
                ]);
        });

        // Buat file PDF dummy asli (dimulai dengan %PDF)
        \Illuminate\Support\Facades\Storage::fake('local');
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
            'laporan.pdf',
            "%PDF-1.4 dummy content"
        );

        $response = $this->actingAs($this->petugas, 'sanctum')
            ->postJson('/api/admin/pengujian/parse-pdf', [
                'file' => $file
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'nomor_pengujian' => 'UJI-MOCK-123',
                'nama_pemohon' => 'Budi Mock',
                'jenis_pengujian' => 'Deteksi GMO',
            ]);
    }

    /**
     * Test store pengujian baru langsung melampirkan berkas PDF hasil sekaligus.
     */
    public function test_store_pengujian_dengan_file_hasil_sekaligus(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Mail::fake();

        $fileLaporan = \Illuminate\Http\UploadedFile::fake()->createWithContent(
            'laporan.pdf',
            "%PDF-1.4 dummy laporan"
        );
        $fileSertifikat = \Illuminate\Http\UploadedFile::fake()->createWithContent(
            'sertifikat.pdf',
            "%PDF-1.4 dummy sertifikat"
        );

        $response = $this->actingAs($this->petugas, 'sanctum')
            ->postJson('/api/admin/pengujian', [
                'nomor_pengujian' => 'UJI-DIRECT-FILE',
                'nama_pemohon' => 'Lutfi',
                'email_pemohon' => 'lutfi@example.com',
                'jenis_pengujian' => 'Deteksi GMO',
                'file_laporan' => $fileLaporan,
                'file_sertifikat' => $fileSertifikat,
            ]);

        $response->assertStatus(201);

        // Pastikan status langsung selesai
        $this->assertDatabaseHas('pengujian', [
            'nomor_pengujian' => 'UJI-DIRECT-FILE',
            'status' => 'selesai',
        ]);

        // Ambil data pengujian dari DB untuk cek path file
        $pengujian = Pengujian::where('nomor_pengujian', 'UJI-DIRECT-FILE')->first();
        $this->assertNotNull($pengujian->file_laporan);
        $this->assertNotNull($pengujian->file_sertifikat);

        // Pastikan berkas tersimpan secara fisik di local disk
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($pengujian->file_laporan);
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($pengujian->file_sertifikat);

        // Pastikan email terkirim
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\HasilSiapMail::class, function ($mail) use ($pengujian) {
            return $mail->hasTo('lutfi@example.com') && $mail->pengujian->id === $pengujian->id;
        });

        // Pastikan log notifikasi tercatat terkirim
        $this->assertDatabaseHas('log_notifikasi', [
            'pengujian_id' => $pengujian->id,
            'status' => 'terkirim',
        ]);
    }
}

