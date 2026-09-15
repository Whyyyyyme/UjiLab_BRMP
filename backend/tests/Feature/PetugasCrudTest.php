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
    }

    /**
     * Test Admin bisa meng-update profil diri sendiri via /admin/profil.
     */
    public function test_admin_bisa_update_profil(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/admin/profil', [
                'nama' => 'Admin Diedit',
                'username' => 'adminedit',
                'email' => 'adminedit@brmp.go.id',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('petugas', [
            'id' => $this->admin->id,
            'nama' => 'Admin Diedit',
            'username' => 'adminedit',
            'email' => 'adminedit@brmp.go.id',
        ]);
    }
}
