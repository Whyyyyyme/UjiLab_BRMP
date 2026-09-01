<?php

namespace Tests\Feature;

use App\Models\Petugas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LockoutTest extends TestCase
{
    use RefreshDatabase;

    private Petugas $petugas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->petugas = Petugas::create([
            'nama' => 'Petugas Lockout',
            'username' => 'lockoutuser',
            'email' => 'lockout@brmp.go.id',
            'password' => Hash::make('password123'),
            'role' => 'petugas_lab',
            'wajib_ganti_password' => false,
        ]);
    }

    /**
     * Test lockout setelah 5x gagal login berturut-turut.
     */
    public function test_lockout_setelah_5x_gagal_login(): void
    {
        // 4x Gagal login pertama
        for ($i = 1; $i <= 4; $i++) {
            $response = $this->postJson('/api/admin/login', [
                'username' => 'lockoutuser',
                'password' => 'passwordsalah',
            ]);

            $response->assertStatus(401);
            $sisa = 5 - $i;
            $response->assertJsonFragment([
                'message' => "Username atau password salah. Sisa percobaan login: {$sisa} kali lagi.",
            ]);
        }

        // Gagal login ke-5 -> Akun harus terkunci
        $response = $this->postJson('/api/admin/login', [
            'username' => 'lockoutuser',
            'password' => 'passwordsalah',
        ]);

        $response->assertStatus(423);
        $response->assertJsonFragment([
            'message' => 'Akun Anda terkunci sementara karena 5 kali gagal login. Silakan coba lagi dalam 15 menit.',
        ]);

        // Percobaan ke-6 -> Masih terkunci meskipun password benar
        $response = $this->postJson('/api/admin/login', [
            'username' => 'lockoutuser',
            'password' => 'password123',
        ]);

        $response->assertStatus(423);
    }

    /**
     * Test lockout otomatis terbuka setelah durasi waktu terlewati.
     */
    public function test_lockout_terbuka_setelah_15_menit(): void
    {
        // Kunci akun secara paksa dengan memodifikasi data petugas
        $this->petugas->update([
            'percobaan_login_gagal' => 5,
            'terkunci_hingga' => now()->subMinutes(1), // Kunci kadaluarsa 1 menit yang lalu
        ]);

        // Login dengan password benar -> Harus sukses dan status lockout di-reset
        $response = $this->postJson('/api/admin/login', [
            'username' => 'lockoutuser',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);

        $this->petugas->refresh();
        $this->assertEquals(0, $this->petugas->percobaan_login_gagal);
        $this->assertNull($this->petugas->terkunci_hingga);
    }
}

