<?php

namespace Tests\Feature;

use App\Models\Pengujian;
use App\Models\LogNotifikasi;
use App\Mail\HasilSiapMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RetryNotifikasiGagalTest extends TestCase
{
    use RefreshDatabase;

    private Pengujian $pengujian;
    private LogNotifikasi $failedLog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pengujian = Pengujian::create([
            'nomor_pengujian' => 'UJI-RETRY-01',
            'nama_pemohon' => 'Andi',
            'email_pemohon' => 'andi@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
            'status' => 'selesai',
        ]);

        $this->failedLog = LogNotifikasi::create([
            'pengujian_id' => $this->pengujian->id,
            'email_tujuan' => 'andi@example.com',
            'tipe_notifikasi' => 'hasil_siap',
            'status' => 'gagal',
            'percobaan_ke' => 1,
            'waktu_percobaan_berikutnya' => now()->subMinutes(10), // Sudah lewat waktu retry
            'pesan_error' => 'Mail server connection timeout',
        ]);
    }

    /**
     * Test console command email:retry-failed sukses mengirim ulang email (F-32).
     */
    public function test_retry_pengiriman_email_sukses(): void
    {
        Mail::fake();

        $this->artisan('email:retry-failed')
            ->assertExitCode(0);

        Mail::assertSent(HasilSiapMail::class, function ($mail) {
            return $mail->hasTo('andi@example.com');
        });

        $this->failedLog->refresh();
        $this->assertEquals('terkirim', $this->failedLog->status);
        $this->assertEquals(2, $this->failedLog->percobaan_ke);
        $this->assertNull($this->failedLog->pesan_error);
    }

    /**
     * Test jika pengiriman gagal lagi, percobaan_ke bertambah dan jeda berikutnya dijadwalkan (F-33).
     */
    public function test_retry_pengiriman_email_gagal_menambah_percobaan(): void
    {
        // Force mail to fail by throwing exception on mock send
        Mail::shouldReceive('to')
            ->andThrow(new \Exception('Connection refused'));

        $this->artisan('email:retry-failed')
            ->assertExitCode(0);

        $this->failedLog->refresh();
        $this->assertEquals('gagal', $this->failedLog->status);
        $this->assertEquals(2, $this->failedLog->percobaan_ke);
        $this->assertEquals('Connection refused', $this->failedLog->pesan_error);
        $this->assertNotNull($this->failedLog->waktu_percobaan_berikutnya);
    }

    /**
     * Test jika gagal mencapai 3 kali, status menjadi gagal_permanen (F-33).
     */
    public function test_retry_pengiriman_email_gagal_permanen_setelah_3_percobaan(): void
    {
        Mail::shouldReceive('to')
            ->andThrow(new \Exception('Connection refused'));

        // Ubah log ke percobaan ke-2
        $this->failedLog->update([
            'percobaan_ke' => 2,
        ]);

        $this->artisan('email:retry-failed')
            ->assertExitCode(0);

        $this->failedLog->refresh();
        $this->assertEquals('gagal_permanen', $this->failedLog->status);
        $this->assertEquals(3, $this->failedLog->percobaan_ke);
        $this->assertNull($this->failedLog->waktu_percobaan_berikutnya);
    }
}
