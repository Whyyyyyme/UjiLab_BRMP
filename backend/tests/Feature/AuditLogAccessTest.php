<?php

namespace Tests\Feature;

use App\Models\Petugas;
use App\Models\LogAktivitas;
use App\Models\AksesFileLog;
use App\Models\Pengujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuditLogAccessTest extends TestCase
{
    use RefreshDatabase;

    private Petugas $admin;
    private Petugas $petugasLab;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Petugas::create([
            'nama' => 'Admin Log',
            'username' => 'adminlog',
            'email' => 'adminlog@brmp.go.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'wajib_ganti_password' => false,
        ]);

        $this->petugasLab = Petugas::create([
            'nama' => 'Petugas Log',
            'username' => 'petugaslog',
            'email' => 'petugaslog@brmp.go.id',
            'password' => Hash::make('password123'),
            'role' => 'petugas_lab',
            'wajib_ganti_password' => false,
        ]);

        // Buat dummy logs
        LogAktivitas::create([
            'petugas_id' => $this->admin->id,
            'aksi' => 'Uji Coba',
            'detail' => 'Melakukan testing',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla',
        ]);

        $p = Pengujian::create([
            'nomor_pengujian' => 'UJI-LOG-01',
            'nama_pemohon' => 'Budi',
            'email_pemohon' => 'budi@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
        ]);

        AksesFileLog::create([
            'pengujian_id' => $p->id,
            'tipe_file' => 'laporan',
            'akses_oleh' => 'publik',
            'ip_address' => '127.0.0.1'
        ]);
    }

    /**
     * Test Admin sukses mengakses rekap log aktivitas & unduhan (F-25, F-26).
     */
    public function test_admin_bisa_akses_audit_logs(): void
    {
        // 1. Log Aktivitas
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/logs/aktivitas');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');

        // 2. Log Unduhan
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/logs/unduhan');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test Petugas Lab ditolak (403) saat mengakses log aktivitas & unduhan (Rule 7).
     */
    public function test_petugas_lab_tidak_bisa_akses_audit_logs(): void
    {
        // 1. Log Aktivitas
        $response = $this->actingAs($this->petugasLab, 'sanctum')
            ->getJson('/api/admin/logs/aktivitas');
        $response->assertStatus(403);

        // 2. Log Unduhan
        $response = $this->actingAs($this->petugasLab, 'sanctum')
            ->getJson('/api/admin/logs/unduhan');
        $response->assertStatus(403);
    }
}
