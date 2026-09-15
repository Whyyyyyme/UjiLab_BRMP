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
     * Test Admin bisa melakukan ganti password profil (F-30).
     */
    public function test_admin_ganti_password_mandiri(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/ganti-password', [
                'password_lama' => 'password123',
                'password_baru' => 'PasswordBaru123!',
                'password_baru_confirmation' => 'PasswordBaru123!',
            ]);

        $response->assertStatus(200);

        $this->admin->refresh();
        $this->assertFalse($this->admin->wajib_ganti_password);

        // Uji login memakai password baru
        $loginResponse = $this->postJson('/api/admin/login', [
            'username' => 'adminreset',
            'password' => 'PasswordBaru123!',
        ]);

        $loginResponse->assertStatus(200);
    }
}
