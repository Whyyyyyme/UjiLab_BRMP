<?php

namespace Tests\Feature;

use App\Models\Petugas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PetugasCrudTest extends TestCase
{
    use RefreshDatabase;

    private Petugas $admin;
    private Petugas $petugasLab;
    private Petugas $targetPetugas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Petugas::create([
            'nama' => 'Admin Utama',
            'username' => 'adminutama',
            'email' => 'adminutama@brmp.go.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'wajib_ganti_password' => false,
        ]);

        $this->petugasLab = Petugas::create([
            'nama' => 'Petugas Lab',
            'username' => 'petugaslab',
            'email' => 'petugaslab@brmp.go.id',
            'password' => Hash::make('password123'),
            'role' => 'petugas_lab',
            'wajib_ganti_password' => false,
        ]);

        $this->targetPetugas = Petugas::create([
            'nama' => 'Budi Operator',
            'username' => 'budiop',
            'email' => 'budiop@brmp.go.id',
            'password' => Hash::make('password123'),
            'role' => 'petugas_lab',
            'wajib_ganti_password' => false,
        ]);
    }

    /**
     * Test Admin bisa melakukan CRUD petugas (F-28).
     */
    public function test_admin_bisa_crud_petugas(): void
    {
        // 1. List
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/petugas');
        $response->assertStatus(200)
            ->assertJsonCount(3); // admin, petugasLab, targetPetugas

        // 2. Create
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/petugas', [
                'nama' => 'Petugas Baru',
                'username' => 'petugasbaru',
                'email' => 'baru@brmp.go.id',
                'role' => 'petugas_lab',
            ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('petugas', [
            'username' => 'petugasbaru',
            'wajib_ganti_password' => true, // Harus true demi keamanan
        ]);

        // 3. Update
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/petugas/{$this->targetPetugas->id}", [
                'nama' => 'Budi Diedit',
                'username' => 'budiedit',
                'email' => 'budiop@brmp.go.id',
                'role' => 'admin', // ganti role
            ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('petugas', [
            'id' => $this->targetPetugas->id,
            'nama' => 'Budi Diedit',
            'role' => 'admin',
        ]);

        // 4. Delete
        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/petugas/{$this->targetPetugas->id}");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('petugas', [
            'id' => $this->targetPetugas->id,
        ]);
    }

    /**
     * Test Admin dilarang menghapus akunnya sendiri (lockout lockout check).
     */
    public function test_admin_tidak_bisa_hapus_diri_sendiri(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/petugas/{$this->admin->id}");

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Akses ditolak. Anda tidak diperkenankan menghapus akun Anda sendiri yang sedang aktif.');

        $this->assertDatabaseHas('petugas', [
            'id' => $this->admin->id,
        ]);
    }

    /**
     * Test Petugas Lab ditolak (403) saat mencoba CRUD petugas (Rule 7).
     */
    public function test_petugas_lab_tidak_bisa_crud_petugas(): void
    {
        // Create
        $response = $this->actingAs($this->petugasLab, 'sanctum')
            ->postJson('/api/admin/petugas', [
                'nama' => 'Ditolak',
                'username' => 'ditolak',
                'email' => 'ditolak@brmp.go.id',
                'role' => 'petugas_lab',
            ]);
        $response->assertStatus(403);

        // Delete
        $response = $this->actingAs($this->petugasLab, 'sanctum')
            ->deleteJson("/api/admin/petugas/{$this->targetPetugas->id}");
        $response->assertStatus(403);
    }
}
