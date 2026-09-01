<?php

namespace Tests\Feature;

use App\Models\Petugas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Petugas $petugasLab;

    private Petugas $admin;

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

        // Daftarkan route testing dinamis
        Route::middleware(['auth:sanctum', 'role:admin'])->get('/_test/admin-only', function () {
            return response()->json(['message' => 'success']);
        });

        Route::middleware(['auth:sanctum', 'role:admin,petugas_lab'])->get('/_test/any-role', function () {
            return response()->json(['message' => 'success']);
        });
    }

    /**
     * Test role admin sukses mengakses endpoint khusus admin.
     */
    public function test_admin_bisa_akses_endpoint_admin(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/_test/admin-only');

        $response->assertStatus(200)
            ->assertJsonPath('message', 'success');
    }

    /**
     * Test role petugas_lab ditolak (403) saat mengakses endpoint khusus admin.
     */
    public function test_petugas_lab_ditolak_dari_endpoint_admin(): void
    {
        $response = $this->actingAs($this->petugasLab, 'sanctum')
            ->getJson('/_test/admin-only');

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Akses ditolak. Anda tidak memiliki izin untuk mengakses modul ini.');
    }

    /**
     * Test kedua role bisa mengakses endpoint bersama.
     */
    public function test_kedua_role_bisa_akses_endpoint_bersama(): void
    {
        // Test admin
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/_test/any-role');
        $response->assertStatus(200);

        // Test petugas lab
        $response = $this->actingAs($this->petugasLab, 'sanctum')
            ->getJson('/_test/any-role');
        $response->assertStatus(200);
    }
}
