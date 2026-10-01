<?php

namespace Tests\Feature;

use App\Models\Pengujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicVerificationPageTest extends TestCase
{
    use RefreshDatabase;

    private Pengujian $pengujian;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pengujian = Pengujian::create([
            'nomor_pengujian' => 'UJI-VER-001',
            'nama_pemohon' => 'Wahyu Arianto',
            'email_pemohon' => 'wahyu@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
            'status' => 'selesai',
        ]);
    }

    /**
     * Test verifikasi nomor pengujian sukses mengembalikan metadata dokumen (F-20, F-21).
     */
    public function test_verifikasi_dokumen_publik_ditemukan(): void
    {
        $response = $this->getJson("/api/public/verifikasi/UJI-VER-001");

        $response->assertStatus(200)
            ->assertJson([
                'nomor_pengujian' => 'UJI-VER-001',
                'jenis_pengujian' => 'Deteksi GMO',
                'nama_pemohon' => 'W***u A*****o', // Ter-masking demi UU PDP
                'status' => 'selesai',
            ]);
    }

    /**
     * Test verifikasi nomor pengujian dengan garis miring (slash) seperti format penomoran lab.
     */
    public function test_verifikasi_dokumen_publik_dengan_karakter_garis_miring(): void
    {
        Pengujian::create([
            'nomor_pengujian' => '012/LAB-BIO/IX/2026',
            'nama_pemohon' => 'Budi Santoso',
            'email_pemohon' => 'budi@example.com',
            'jenis_pengujian' => 'Kadar Air',
            'status' => 'selesai',
        ]);

        $response = $this->getJson("/api/public/verifikasi/012/LAB-BIO/IX/2026");

        $response->assertStatus(200)
            ->assertJson([
                'nomor_pengujian' => '012/LAB-BIO/IX/2026',
                'jenis_pengujian' => 'Kadar Air',
                'status' => 'selesai',
            ]);
    }

    /**
     * Test verifikasi nomor salah mengembalikan error 404 umum (anti-enumeration) (F-153).
     */
    public function test_verifikasi_dokumen_publik_salah_nomor_404(): void
    {
        $response = $this->getJson("/api/public/verifikasi/UJI-SALAH-123");

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Data pengujian tidak ditemukan atau tidak aktif.');
    }

    /**
     * Test rate limiting pada verifikasi dokumen publik (F-153).
     */
    public function test_verifikasi_dokumen_publik_rate_limiting(): void
    {
        // Lakukan 10 request beruntun (sesuai limit throttle:10,1)
        for ($i = 0; $i < 10; $i++) {
            $response = $this->getJson("/api/public/verifikasi/UJI-VER-001");
            $response->assertStatus(200);
        }

        // Request ke-11 harus diblokir dengan 429 Too Many Requests
        $response = $this->getJson("/api/public/verifikasi/UJI-VER-001");
        $response->assertStatus(429);
    }
}
