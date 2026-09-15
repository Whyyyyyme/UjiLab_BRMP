<?php

namespace Tests\Feature;

use App\Models\Petugas;
use App\Models\Pengujian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PengujianDeleteTest extends TestCase
{
    use RefreshDatabase;

    private Petugas $petugasLab;
    private Petugas $admin;
    private Pengujian $pengujian;

    protected function setUp(): void
    {
        parent::setUp();

        $this->petugasLab = Petugas::create([
            'nama' => 'Petugas Lab',
            'username' => 'petugaslab',
            'email' => 'lab@brmp.go.id',
            'password' => Hash::make('password123'),
            'role' => 'petugas_lab',
            'wajib_ganti_password' => false,
        ]);

        $this->admin = Petugas::create([
            'nama' => 'Admin User',
            'username' => 'adminuser',
            'email' => 'admin@brmp.go.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'wajib_ganti_password' => false,
        ]);

        $this->pengujian = Pengujian::create([
            'nomor_pengujian' => 'UJI-DELETE-01',
            'nama_pemohon' => 'Toni',
            'email_pemohon' => 'toni@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
            'status' => 'diproses',
        ]);
    }

    /**
     * Test pengguna terautentikasi sukses melakukan soft-delete data pengujian.
     */
    public function test_seluruh_admin_bisa_soft_delete(): void
    {
        $response = $this->actingAs($this->petugasLab, 'sanctum')
            ->deleteJson("/api/admin/pengujian/{$this->pengujian->id}");

        $response->assertStatus(200);
        $this->pengujian->refresh();
        $this->assertTrue($this->pengujian->is_deleted);
    }

    /**
     * Test role admin sukses melakukan soft-delete data pengujian (Rule 4.5).
     */
    public function test_admin_bisa_soft_delete(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/pengujian/{$this->pengujian->id}");

        $response->assertStatus(200);
        
        $this->pengujian->refresh();
        $this->assertTrue($this->pengujian->is_deleted);
        
        // Pastikan tercatat di log aktivitas
        $this->assertDatabaseHas('log_aktivitas', [
            'petugas_id' => $this->admin->id,
            'aksi' => 'Soft Delete Pengujian',
        ]);
    }
}
