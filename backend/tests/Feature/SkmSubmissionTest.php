<?php

namespace Tests\Feature;

use App\Models\Pengujian;
use App\Models\TokenAkses;
use App\Models\Skm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SkmSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private Pengujian $pengujian;
    private string $tokenHeader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pengujian = Pengujian::create([
            'nomor_pengujian' => 'UJI-SKM-001',
            'nama_pemohon' => 'Rian',
            'email_pemohon' => 'rian@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
        ]);

        $key = Str::random(40);
        $token = TokenAkses::create([
            'pengujian_id' => $this->pengujian->id,
            'token_hash' => Hash::make($key),
            'expired_at' => now()->addMinutes(30)
        ]);

        $this->tokenHeader = $token->id . '|' . $key;
    }

    /**
     * Test submit SKM tanpa token akses ditolak (401) (Rule 7).
     */
    public function test_submit_skm_tanpa_token_akses_ditolak(): void
    {
        $response = $this->postJson('/api/public/skm', [
            'nama' => 'Rian',
            'jenis_kelamin' => 'Laki-laki',
            'pendidikan' => 'D4/S1',
            'usia' => '26-34 tahun',
            'pekerjaan' => 'Swasta',
            'disabilitas' => 'Tidak',
            'u1' => 4, 'u2' => 4, 'u3' => 4, 'u4' => 4,
            'u5' => 4, 'u6' => 4, 'u7' => 4, 'u8' => 4, 'u9' => 4,
            'u10' => 4, 'u11' => 4, 'u12' => 4, 'u13' => 4, 'u14' => 4, 'u15' => 4, 'u16' => 4,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test submit SKM dengan token akses sukses (F-08).
     */
    public function test_submit_skm_sukses(): void
    {
        $response = $this->withHeaders([
            'X-Akses-Token' => $this->tokenHeader
        ])->postJson('/api/public/skm', [
            'nama' => 'Rian',
            'jenis_kelamin' => 'Laki-laki',
            'pendidikan' => 'D4/S1',
            'usia' => '26-34 tahun',
            'pekerjaan' => 'Swasta',
            'disabilitas' => 'Tidak',
            'u1' => 4, 'u2' => 3, 'u3' => 4, 'u4' => 3,
            'u5' => 4, 'u6' => 3, 'u7' => 4, 'u8' => 3, 'u9' => 4,
            'u10' => 4, 'u11' => 3, 'u12' => 4, 'u13' => 3, 'u14' => 4, 'u15' => 3, 'u16' => 4,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('skm', [
            'pengujian_id' => $this->pengujian->id,
            'nama' => 'Rian',
            'jenis_kelamin' => 'Laki-laki',
            'skor_1' => 4,
            'skor_2' => 3,
            'skor_16' => 4,
        ]);
    }

    /**
     * Test submit SKM ganda ditolak (400) (F-09, Rule 7).
     */
    public function test_submit_skm_ganda_ditolak(): void
    {
        // Masukkan SKM pertama
        Skm::create([
            'pengujian_id' => $this->pengujian->id,
            'nama' => 'Rian',
            'jenis_kelamin' => 'Laki-laki',
            'pendidikan' => 'D4/S1',
            'usia' => '26-34 tahun',
            'pekerjaan' => 'Swasta',
            'disabilitas' => 'Tidak',
            'skor_1' => 4, 'skor_2' => 4, 'skor_3' => 4, 'skor_4' => 4,
            'skor_5' => 4, 'skor_6' => 4, 'skor_7' => 4, 'skor_8' => 4, 'skor_9' => 4,
            'skor_10' => 4, 'skor_11' => 4, 'skor_12' => 4, 'skor_13' => 4, 'skor_14' => 4, 'skor_15' => 4, 'skor_16' => 4,
            'tanggal_isi' => now()->toDateString(),
        ]);

        // Kirim SKM kedua (harus ditolak)
        $response = $this->withHeaders([
            'X-Akses-Token' => $this->tokenHeader
        ])->postJson('/api/public/skm', [
            'nama' => 'Rian',
            'jenis_kelamin' => 'Laki-laki',
            'pendidikan' => 'D4/S1',
            'usia' => '26-34 tahun',
            'pekerjaan' => 'Swasta',
            'disabilitas' => 'Tidak',
            'u1' => 3, 'u2' => 3, 'u3' => 3, 'u4' => 3,
            'u5' => 3, 'u6' => 3, 'u7' => 3, 'u8' => 3, 'u9' => 3,
            'u10' => 3, 'u11' => 3, 'u12' => 3, 'u13' => 3, 'u14' => 3, 'u15' => 3, 'u16' => 3,
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Anda sudah mengisi survei SKM untuk nomor pengujian ini.');
    }
}
