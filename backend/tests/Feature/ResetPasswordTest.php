<?php

namespace Tests\Feature;

use App\Models\Petugas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    private Petugas $admin;
    private Petugas $petugasLab;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Petugas::create([
            'nama' => 'Admin Reset',
            'username' => 'adminreset',
            'email' => 'adminreset@brmp.go.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'wajib_ganti_password' => false,
        ]);

        $this->petugasLab = Petugas::create([
            'nama' => 'Petugas Reset',
            'username' => 'petugasreset',
            'email' => 'petugasreset@brmp.go.id',
            'password' => Hash::make('password123'),
            'role' => 'petugas_lab',
            'wajib_ganti_password' => false,
        ]);
    }

    /**
     * Test Admin bisa melakukan reset password petugas ke default Password123! (F-30, Rule 7).
     */
    public function test_admin_reset_password_petugas(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/petugas/{$this->petugasLab->id}/reset-password");

        $response->assertStatus(200);

        $this->petugasLab->refresh();
        $this->assertTrue($this->petugasLab->wajib_ganti_password);

        // Uji login memakai password baru (default: Password123!)
        $loginResponse = $this->postJson('/api/admin/login', [
            'username' => 'petugasreset',
            'password' => 'Password123!',
        ]);

        $loginResponse->assertStatus(200)
            ->assertJsonPath('user.wajib_ganti_password', true);
    }
}
