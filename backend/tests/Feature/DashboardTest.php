<?php

namespace Tests\Feature;

use App\Models\Petugas;
use App\Models\Pengujian;
use App\Models\LogAktivitas;
use App\Models\LogNotifikasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private Petugas $petugas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->petugas = Petugas::create([
            'nama' => 'Petugas Lab',
            'username' => 'petugaslab',
            'email' => 'petugaslab@brmp.go.id',
            'password' => Hash::make('password123'),
            'role' => 'petugas_lab',
            'wajib_ganti_password' => false,
        ]);

        // Buat data uji
        Pengujian::create([
            'nomor_pengujian' => 'UJI-DASH-01',
            'nama_pemohon' => 'Andi',
            'email_pemohon' => 'andi@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
            'status' => 'diproses',
        ]);

        $p2 = Pengujian::create([
            'nomor_pengujian' => 'UJI-DASH-02',
            'nama_pemohon' => 'Budi',
            'email_pemohon' => 'budi@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
            'status' => 'selesai',
        ]);

        LogNotifikasi::create([
            'pengujian_id' => $p2->id,
            'email_tujuan' => 'budi@example.com',
            'tipe_notifikasi' => 'hasil_siap',
            'status' => 'gagal_permanen',
        ]);

        LogAktivitas::create([
            'petugas_id' => $this->petugas->id,
            'aksi' => 'Upload Hasil Uji',
            'detail' => 'Mengunggah berkas...',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla',
        ]);
    }

    /**
     * Test data dashboard berhasil diakses oleh user terautentikasi (F-22).
     */
    public function test_akses_dashboard_user_dengan_recent_logs(): void
    {
        $response = $this->actingAs($this->petugas, 'sanctum')
            ->getJson('/api/admin/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'stats' => [
                    'total_pengujian' => 2,
                    'diproses' => 1,
                    'selesai' => 1,
                    'notifikasi_gagal' => 1,
                ],
            ])
            ->assertJsonCount(1, 'recent_logs');
    }

    /**
     * Test data dashboard berhasil diakses oleh admin (dengan recent_logs) (F-22).
     */
    public function test_akses_dashboard_admin_dengan_recent_logs(): void
    {
        $admin = Petugas::create([
            'nama' => 'Admin Utama',
            'username' => 'adminutama',
            'email' => 'admin.utama@brmp.go.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'wajib_ganti_password' => false,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'recent_logs');
    }

    /**
     * Test tamu ditolak mengakses dashboard (401).
     */
    public function test_tamu_tidak_bisa_akses_dashboard(): void
    {
        $response = $this->getJson('/api/admin/dashboard');
        $response->assertStatus(401);
    }
}
