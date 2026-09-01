<?php

namespace Tests\Feature;

use App\Models\Pengujian;
use App\Models\TokenAkses;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicTokenAccessTest extends TestCase
{
    use RefreshDatabase;

    private Pengujian $pengujian;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pengujian = Pengujian::create([
            'nomor_pengujian' => 'UJI-TOK-001',
            'nama_pemohon' => 'Budi',
            'email_pemohon' => 'budi@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
        ]);
    }

    /**
     * Test akses status ditolak jika tidak mengirimkan token (Rule 7).
     */
    public function test_akses_status_ditolak_tanpa_token(): void
    {
        $response = $this->getJson('/api/public/pengujian/status');

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Token akses tidak disediakan atau tidak valid.');
    }

    /**
     * Test akses status ditolak jika token tidak valid / salah format.
     */
    public function test_akses_status_ditolak_salah_format_token(): void
    {
        $response = $this->withHeaders([
            'X-Akses-Token' => 'salahformattoken'
        ])->getJson('/api/public/pengujian/status');

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Token akses tidak disediakan atau tidak valid.');
    }

    /**
     * Test akses status ditolak jika token sudah kedaluwarsa (Rule 7).
     */
    public function test_akses_status_ditolak_token_expired(): void
    {
        $key = Str::random(40);
        $token = TokenAkses::create([
            'pengujian_id' => $this->pengujian->id,
            'token_hash' => Hash::make($key),
            'expired_at' => now()->subMinutes(1), // Sudah lewat expired
        ]);

        $tokenPlainText = $token->id . '|' . $key;

        $response = $this->withHeaders([
            'X-Akses-Token' => $tokenPlainText
        ])->getJson('/api/public/pengujian/status');

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Sesi akses Anda telah berakhir. Silakan verifikasi OTP ulang.');
    }

    /**
     * Test akses status sukses jika token valid dan aktif.
     */
    public function test_akses_status_sukses_dengan_token_valid(): void
    {
        $key = Str::random(40);
        $token = TokenAkses::create([
            'pengujian_id' => $this->pengujian->id,
            'token_hash' => Hash::make($key),
            'expired_at' => now()->addMinutes(30), // Aktif
        ]);

        $tokenPlainText = $token->id . '|' . $key;

        $response = $this->withHeaders([
            'X-Akses-Token' => $tokenPlainText
        ])->getJson('/api/public/pengujian/status');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->pengujian->id,
                'nomor_pengujian' => 'UJI-TOK-001',
                'skm_diisi' => false,
            ]);
    }
}
