<?php

namespace Tests\Feature;

use App\Models\Petugas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private Petugas $petugas;

    private Petugas $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->petugas = Petugas::create([
            'nama' => 'Petugas Test',
            'username' => 'petugastest',
            'email' => 'petugastest@brmp.go.id',
            'password' => Hash::make('password123'),
            'role' => 'petugas_lab',
            'wajib_ganti_password' => false,
        ]);

        $this->admin = Petugas::create([
            'nama' => 'Admin Test',
            'username' => 'admintest',
            'email' => 'admintest@brmp.go.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'wajib_ganti_password' => true,
        ]);
    }

    /**
     * Test login dengan kredensial yang valid.
     */
    public function test_login_sukses(): void
    {
        $response = $this->postJson('/api/admin/login', [
            'username' => 'petugastest',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'nama',
                    'username',
                    'email',
                    'role',
                    'wajib_ganti_password',
                ],
            ])
            ->assertJsonPath('user.username', 'petugastest');
    }

    /**
     * Test login sukses menggunakan email / gmail.
     */
    public function test_login_sukses_menggunakan_email(): void
    {
        $response = $this->postJson('/api/admin/login', [
            'username' => 'petugastest@brmp.go.id',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'token',
                'user' => [
                    'id',
                    'nama',
                    'username',
                    'email',
                    'role',
                    'wajib_ganti_password',
                ],
            ])
            ->assertJsonPath('user.email', 'petugastest@brmp.go.id')
            ->assertJsonPath('user.username', 'petugastest');
    }

    /**
     * Test login dengan password salah.
     */
    public function test_login_gagal_password_salah(): void
    {
        $response = $this->postJson('/api/admin/login', [
            'username' => 'petugastest',
            'password' => 'passwordsalah',
        ]);

        $response->assertStatus(401)
            ->assertJsonStructure(['message']);
    }

    /**
     * Test ganti password mandiri sukses.
     */
    public function test_ganti_password_sukses(): void
    {
        $response = $this->actingAs($this->petugas, 'sanctum')
            ->postJson('/api/admin/ganti-password', [
                'password_lama' => 'password123',
                'password_baru' => 'passwordbaru123',
                'password_baru_confirmation' => 'passwordbaru123',
            ]);

        $response->assertStatus(200);

        // Pastikan password sudah ter-update
        $this->petugas->refresh();
        $this->assertTrue(Hash::check('passwordbaru123', $this->petugas->password));
        $this->assertFalse($this->petugas->wajib_ganti_password);
    }

    /**
     * Test ganti password dengan password lama salah.
     */
    public function test_ganti_password_gagal_password_lama_salah(): void
    {
        $response = $this->actingAs($this->petugas, 'sanctum')
            ->postJson('/api/admin/ganti-password', [
                'password_lama' => 'passwordsalah',
                'password_baru' => 'passwordbaru123',
                'password_baru_confirmation' => 'passwordbaru123',
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    /**
     * Test logout.
     */
    public function test_logout_sukses(): void
    {
        $response = $this->actingAs($this->petugas, 'sanctum')
            ->postJson('/api/admin/logout');

        $response->assertStatus(200);
    }

    /**
     * Test wajib ganti password memblokir akses ke dashboard/API lain.
     */
    public function test_wajib_ganti_password_memblokir_akses_api_lain(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/dashboard');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Anda wajib mengubah password bawaan sebelum dapat menggunakan sistem.'
            ]);
    }

    /**
     * Test wajib ganti password mengizinkan logout dan ganti password.
     */
    public function test_wajib_ganti_password_mengizinkan_logout_dan_ganti_password(): void
    {
        // Izinkan logout
        $responseLogout = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/logout');
        $responseLogout->assertStatus(200);

        // Izinkan ganti password (akan melempar 422 karena field kosong, bukan 403)
        $responseGanti = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/ganti-password', []);
        $responseGanti->assertStatus(422);
    }

    /**
     * Test rate limiting rute login admin (mencegah brute-force password) (throttle:10,1).
     */
    public function test_admin_login_route_rate_limiting(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/admin/login', [
                'username' => 'wronguser',
                'password' => 'wrongpass',
            ]);
            $this->assertNotEquals(429, $response->status());
        }

        // Request ke-11 harus diblokir (429)
        $response = $this->postJson('/api/admin/login', [
            'username' => 'wronguser',
            'password' => 'wrongpass',
        ]);
        $response->assertStatus(429);
    }

    /**
     * Test ganti password mencabut token sesi lain yang aktif (Security Patch Finding #3).
     */
    public function test_ganti_password_mencabut_seluruh_token_sesi_lain(): void
    {
        $token1 = $this->petugas->createToken('session_current');
        $token2 = $this->petugas->createToken('session_other');

        $this->assertEquals(2, $this->petugas->tokens()->count());

        $response = $this->withHeader('Authorization', 'Bearer ' . $token1->plainTextToken)
            ->postJson('/api/admin/ganti-password', [
                'password_lama' => 'password123',
                'password_baru' => 'newsecretpassword123',
                'password_baru_confirmation' => 'newsecretpassword123',
            ]);

        $response->assertStatus(200);

        // Token2 harus sudah terhapus, hanya token1 (sesi saat ini) yang tersisa
        $this->assertEquals(1, $this->petugas->tokens()->count());
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token2->accessToken->id,
        ]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $token1->accessToken->id,
        ]);
    }
}

