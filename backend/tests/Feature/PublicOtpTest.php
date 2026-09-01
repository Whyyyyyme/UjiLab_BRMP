<?php

namespace Tests\Feature;

use App\Models\Pengujian;
use App\Models\OtpVerifikasi;
use App\Mail\OtpMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicOtpTest extends TestCase
{
    use RefreshDatabase;

    private Pengujian $pengujian;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pengujian = Pengujian::create([
            'nomor_pengujian' => 'UJI-OTP-001',
            'nama_pemohon' => 'Budi',
            'email_pemohon' => 'budi@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
        ]);
    }

    /**
     * Test kirim OTP berhasil mengirim email (F-04).
     */
    public function test_kirim_otp_sukses(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/public/otp/kirim', [
            'pengujian_id' => $this->pengujian->id,
        ]);

        $response->assertStatus(200);
        
        // Pastikan record OTP ada di database
        $this->assertDatabaseHas('otp_verifikasi', [
            'pengujian_id' => $this->pengujian->id,
            'status' => 'aktif',
            'percobaan_gagal' => 0,
        ]);

        // Verifikasi email terkirim
        Mail::assertSent(OtpMail::class, function ($mail) {
            return $mail->hasTo('budi@example.com') && strlen($mail->otp) === 6;
        });
    }

    /**
     * Test cooldown 60 detik kirim ulang OTP (F-05).
     */
    public function test_cooldown_60_detik_kirim_ulang_otp(): void
    {
        Mail::fake();

        // Kirim pertama
        $this->postJson('/api/public/otp/kirim', ['pengujian_id' => $this->pengujian->id]);

        // Kirim kedua langsung (harus ditolak 429)
        $response = $this->postJson('/api/public/otp/kirim', ['pengujian_id' => $this->pengujian->id]);

        $response->assertStatus(429)
            ->assertJsonStructure(['message']);
    }

    /**
     * Test OTP baru terbit membatalkan OTP lama (OTP lama otomatis tidak berlaku) (F-05, Rule 7).
     */
    public function test_otp_baru_membatalkan_otp_lama(): void
    {
        Mail::fake();

        // 1. Minta OTP pertama
        $this->postJson('/api/public/otp/kirim', ['pengujian_id' => $this->pengujian->id]);
        $otpLama = OtpVerifikasi::where('pengujian_id', $this->pengujian->id)->latest()->first();
        $this->assertEquals('aktif', $otpLama->status);

        $otpLama->created_at = now()->subSeconds(65);
        $otpLama->save();

        // 2. Minta OTP kedua
        $this->postJson('/api/public/otp/kirim', ['pengujian_id' => $this->pengujian->id]);
        
        // Pastikan OTP lama statusnya jadi kadaluarsa
        $otpLama->refresh();
        $this->assertEquals('kadaluarsa', $otpLama->status);

        $otpBaru = OtpVerifikasi::where('pengujian_id', $this->pengujian->id)->latest()->first();
        $this->assertEquals('aktif', $otpBaru->status);
        $this->assertNotEquals($otpLama->id, $otpBaru->id);
    }

    /**
     * Test OTP lockout setelah 5x gagal berturut-turut (F-06, Rule 7).
     */
    public function test_otp_lockout_setelah_5x_salah_input(): void
    {
        Mail::fake();

        // Kirim OTP
        $this->postJson('/api/public/otp/kirim', ['pengujian_id' => $this->pengujian->id]);
        
        // Cek input salah 4 kali
        for ($i = 1; $i <= 4; $i++) {
            $response = $this->postJson('/api/public/otp/verifikasi', [
                'pengujian_id' => $this->pengujian->id,
                'kode' => '000000', // kode salah
            ]);

            $response->assertStatus(401);
            $sisa = 5 - $i;
            $response->assertJsonPath('message', "Kode OTP salah. Sisa percobaan: {$sisa} kali.");
        }

        // Percobaan ke-5 salah -> Lockout
        $response = $this->postJson('/api/public/otp/verifikasi', [
            'pengujian_id' => $this->pengujian->id,
            'kode' => '000000', // kode salah
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Kode OTP tidak berlaku lagi karena 5 kali salah input. Silakan kirim ulang OTP baru.');

        // OTP Row status harus menjadi 'kadaluarsa'
        $otpRow = OtpVerifikasi::where('pengujian_id', $this->pengujian->id)->latest()->first();
        $this->assertEquals('kadaluarsa', $otpRow->status);
    }

    /**
     * Test rate limit pengiriman OTP maksimal 5 kali per jam per nomor pengujian.
     */
    public function test_kirim_otp_rate_limit_per_nomor_pengujian(): void
    {
        Mail::fake();

        // Kirim OTP 5 kali, masing-masing digeser waktunya agar lolos cooldown 60 detik
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson('/api/public/otp/kirim', [
                'pengujian_id' => $this->pengujian->id,
            ]);
            $response->assertStatus(200);

            // Geser waktu created_at ke masa lalu agar tidak terblokir cooldown 60 detik
            $otp = OtpVerifikasi::where('pengujian_id', $this->pengujian->id)->latest()->first();
            $otp->created_at = now()->subSeconds(65);
            $otp->save();
        }

        // Percobaan ke-6 harus diblokir oleh rate limiter per nomor pengujian (429)
        $response = $this->postJson('/api/public/otp/kirim', [
            'pengujian_id' => $this->pengujian->id,
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('message', 'Batas pengiriman kode OTP untuk nomor pengujian ini telah tercapai (maksimal 5 kali per jam). Silakan coba lagi nanti.');
    }
}

