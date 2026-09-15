<?php

namespace Tests\Feature;

use App\Models\Petugas;
use App\Models\Pengujian;
use App\Models\OtpVerifikasi;
use App\Models\TokenAkses;
use App\Models\LogAktivitas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PruneExpiredTokensTest extends TestCase
{
    use RefreshDatabase;

    private Petugas $petugas;
    private Pengujian $pengujian;

    protected function setUp(): void
    {
        parent::setUp();

        $this->petugas = Petugas::create([
            'nama' => 'Petugas Admin',
            'username' => 'adminpetugas',
            'email' => 'admin@brmp.go.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'wajib_ganti_password' => false,
        ]);

        $this->pengujian = Pengujian::create([
            'nomor_pengujian' => 'UJI-PRUNE-001',
            'nama_pemohon' => 'Rian Hidayat',
            'email_pemohon' => 'rian@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
            'status' => 'selesai',
        ]);
    }

    /**
     * Test console command auth:prune-expired membersihkan OTP dan Token kedaluwarsa.
     */
    public function test_artisan_prune_expired_membersihkan_otp_dan_token_kedaluwarsa(): void
    {
        // 1. OTP masih aktif
        $otpAktif = OtpVerifikasi::create([
            'pengujian_id' => $this->pengujian->id,
            'kode_hash' => Hash::make('123456'),
            'status' => 'aktif',
            'percobaan_gagal' => 0,
            'expired_at' => Carbon::now()->addMinutes(5),
        ]);

        // 2. OTP sudah kedaluwarsa 8 hari lalu
        $otpKedaluwarsa = OtpVerifikasi::create([
            'pengujian_id' => $this->pengujian->id,
            'kode_hash' => Hash::make('654321'),
            'status' => 'kadaluarsa',
            'percobaan_gagal' => 0,
            'expired_at' => Carbon::now()->subDays(8),
        ]);

        // 3. Token Akses masih aktif
        $tokenAktif = TokenAkses::create([
            'pengujian_id' => $this->pengujian->id,
            'token_hash' => Hash::make('token_aktif_hash'),
            'expired_at' => Carbon::now()->addHour(),
        ]);

        // 4. Token Akses sudah kedaluwarsa 10 hari lalu
        $tokenKedaluwarsa = TokenAkses::create([
            'pengujian_id' => $this->pengujian->id,
            'token_hash' => Hash::make('token_expired_hash'),
            'expired_at' => Carbon::now()->subDays(10),
        ]);

        // Jalankan artisan command dengan batas default 7 hari
        $this->artisan('auth:prune-expired', ['--days' => 7])
            ->assertExitCode(0);

        // Record yang aktif harus tetap ada
        $this->assertDatabaseHas('otp_verifikasi', ['id' => $otpAktif->id]);
        $this->assertDatabaseHas('token_akses', ['id' => $tokenAktif->id]);

        // Record yang kedaluwarsa > 7 hari harus terhapus
        $this->assertDatabaseMissing('otp_verifikasi', ['id' => $otpKedaluwarsa->id]);
        $this->assertDatabaseMissing('token_akses', ['id' => $tokenKedaluwarsa->id]);
    }

    /**
     * Test admin dapat melihat stats pemeliharaan dan menjalankan pruning via API.
     */
    public function test_admin_dapat_melihat_stats_dan_melakukan_pruning_via_api(): void
    {
        // Buat data kedaluwarsa
        OtpVerifikasi::create([
            'pengujian_id' => $this->pengujian->id,
            'kode_hash' => Hash::make('111222'),
            'status' => 'aktif',
            'percobaan_gagal' => 0,
            'expired_at' => Carbon::now()->subMinutes(10), // kedaluwarsa
        ]);

        TokenAkses::create([
            'pengujian_id' => $this->pengujian->id,
            'token_hash' => Hash::make('token_lama'),
            'expired_at' => Carbon::now()->subMinutes(30), // kedaluwarsa
        ]);

        // 1. Cek endpoint stats
        $statsResponse = $this->actingAs($this->petugas, 'sanctum')
            ->getJson('/api/admin/maintenance/stats');

        $statsResponse->assertStatus(200)
            ->assertJson([
                'otp_kedaluwarsa' => 1,
                'token_kedaluwarsa' => 1,
            ]);

        // 2. Jalankan pruning via API
        $pruneResponse = $this->actingAs($this->petugas, 'sanctum')
            ->postJson('/api/admin/maintenance/prune', ['days' => 0]);

        $pruneResponse->assertStatus(200)
            ->assertJsonPath('rincian.otp_dihapus', 1)
            ->assertJsonPath('rincian.token_dihapus', 1);

        // 3. Pastikan dicatat ke Log Aktivitas
        $this->assertDatabaseHas('log_aktivitas', [
            'petugas_id' => $this->petugas->id,
            'aksi' => 'Pemeliharaan Sistem',
        ]);
    }
}
