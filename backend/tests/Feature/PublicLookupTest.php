<?php

namespace Tests\Feature;

use App\Models\Pengujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLookupTest extends TestCase
{
    use RefreshDatabase;

    private Pengujian $pengujian;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pengujian = Pengujian::create([
            'nomor_pengujian' => 'UJI-PUB-001',
            'nama_pemohon' => 'Budi Santoso',
            'email_pemohon' => 'budi.santoso@gmail.com',
            'jenis_pengujian' => 'Deteksi GMO',
            'status' => 'selesai',
            'file_laporan' => 'hasil_uji/dummy.pdf',
        ]);
    }

    /**
     * Test pencarian nomor pengujian yang terdaftar mengembalikan email tersamar (F-01, F-02).
     */
    public function test_pencarian_nomor_pengujian_ditemukan(): void
    {
        $response = $this->postJson('/api/public/pengujian/cari', [
            'nomor_pengujian' => 'UJI-PUB-001',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->pengujian->id,
                'nomor_pengujian' => 'UJI-PUB-001',
                'email_tersamar' => 'b**********o@gmail.com'
            ]);
    }

    /**
     * Test pencarian nomor yang tidak terdaftar mengembalikan error umum (anti-enumeration) (F-153).
     */
    public function test_pencarian_nomor_pengujian_tidak_ditemukan(): void
    {
        $response = $this->postJson('/api/public/pengujian/cari', [
            'nomor_pengujian' => 'UJI-SALAH-999',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Data pengujian tidak ditemukan atau tidak aktif.');
    }

    /**
     * Test pencarian nomor pengujian yang sudah di-soft delete mengembalikan error umum (F-153, Rule 4.5).
     */
    public function test_pencarian_nomor_pengujian_soft_deleted_tidak_ditemukan(): void
    {
        $deleted = Pengujian::create([
            'nomor_pengujian' => 'UJI-DEL-001',
            'nama_pemohon' => 'Toni',
            'email_pemohon' => 'toni@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
            'is_deleted' => true,
        ]);

        $response = $this->postJson('/api/public/pengujian/cari', [
            'nomor_pengujian' => 'UJI-DEL-001',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Data pengujian tidak ditemukan atau tidak aktif.');
    }

    /**
     * Test pencarian nomor pengujian yang belum selesai atau belum upload file ditolak.
     */
    public function test_pencarian_nomor_pengujian_belum_upload_ditolak(): void
    {
        Pengujian::create([
            'nomor_pengujian' => 'UJI-BELUM-001',
            'nama_pemohon' => 'Andi',
            'email_pemohon' => 'andi@gmail.com',
            'jenis_pengujian' => 'Deteksi GMO',
            'status' => 'diproses',
            'file_laporan' => null,
        ]);

        $response = $this->postJson('/api/public/pengujian/cari', [
            'nomor_pengujian' => 'UJI-BELUM-001',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'diproses');
    }
}

