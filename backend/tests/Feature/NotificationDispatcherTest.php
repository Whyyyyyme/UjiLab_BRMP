<?php

namespace Tests\Feature;

use App\Models\Petugas;
use App\Models\Pengujian;
use App\Models\LogNotifikasi;
use App\Models\LogAktivitas;
use App\Mail\HasilSiapMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationDispatcherTest extends TestCase
{
    use RefreshDatabase;

    private Petugas $petugas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->petugas = Petugas::create([
            'nama' => 'Petugas Lab BRMP',
            'username' => 'petugaslab',
            'email' => 'petugas@brmp.go.id',
            'password' => Hash::make('password123'),
            'role' => 'petugas_lab',
            'wajib_ganti_password' => false,
        ]);
    }

    /**
     * Test mengedit email pada pengujian yang sudah selesai otomatis memicu pengiriman email LHU (tanpa tombol terpisah).
     */
    public function test_edit_email_pada_pengujian_selesai_otomatis_memicu_pengiriman_email(): void
    {
        Mail::fake();

        $pengujian = Pengujian::create([
            'nomor_pengujian' => 'UJI-AUTO-01',
            'nama_pemohon' => 'Bambang Kusuma',
            'email_pemohon' => 'lama@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
            'status' => 'selesai',
            'file_laporan' => 'hasil_uji/dummy-laporan.pdf',
        ]);

        $response = $this->actingAs($this->petugas, 'sanctum')
            ->putJson("/api/admin/pengujian/{$pengujian->id}", [
                'nomor_pengujian' => 'UJI-AUTO-01',
                'nama_pemohon' => 'Bambang Kusuma',
                'email_pemohon' => 'baru@example.com',
                'jenis_pengujian' => 'Deteksi GMO',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.email_pemohon', 'baru@example.com');

        // Pastikan email terkirim ke alamat email yang baru
        Mail::assertSent(HasilSiapMail::class, function ($mail) {
            return $mail->hasTo('baru@example.com');
        });

        // Pastikan dicatat ke LogNotifikasi
        $this->assertDatabaseHas('log_notifikasi', [
            'pengujian_id' => $pengujian->id,
            'email_tujuan' => 'baru@example.com',
            'tipe_notifikasi' => 'hasil_siap',
            'status' => 'terkirim',
        ]);
    }

    /**
     * Test koreksi email (PATCH /email) otomatis memicu pengiriman email jika selesai.
     */
    public function test_koreksi_email_endpoint_otomatis_memicu_pengiriman_email(): void
    {
        Mail::fake();

        $pengujian = Pengujian::create([
            'nomor_pengujian' => 'UJI-AUTO-02',
            'nama_pemohon' => 'Citra Lestari',
            'email_pemohon' => 'citra.lama@example.com',
            'jenis_pengujian' => 'Deteksi Virus secara Molekuler',
            'status' => 'selesai',
            'file_laporan' => 'hasil_uji/dummy-laporan.pdf',
        ]);

        $response = $this->actingAs($this->petugas, 'sanctum')
            ->patchJson("/api/admin/pengujian/{$pengujian->id}/email", [
                'email_pemohon' => 'citra.baru@example.com',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.email_pemohon', 'citra.baru@example.com');

        Mail::assertSent(HasilSiapMail::class, function ($mail) {
            return $mail->hasTo('citra.baru@example.com');
        });

        $this->assertDatabaseHas('log_notifikasi', [
            'pengujian_id' => $pengujian->id,
            'email_tujuan' => 'citra.baru@example.com',
            'status' => 'terkirim',
        ]);
    }

    /**
     * Test mengedit pengujian yang belum selesai tidak memicu pengiriman email LHU.
     */
    public function test_edit_email_pada_pengujian_belum_selesai_tidak_mengirim_email(): void
    {
        Mail::fake();

        $pengujian = Pengujian::create([
            'nomor_pengujian' => 'UJI-AUTO-03',
            'nama_pemohon' => 'Dedi Wijaya',
            'email_pemohon' => 'dedi.lama@example.com',
            'jenis_pengujian' => 'Uji Mutu Benih (ISTA)',
            'status' => 'diproses',
            'file_laporan' => null,
        ]);

        $response = $this->actingAs($this->petugas, 'sanctum')
            ->putJson("/api/admin/pengujian/{$pengujian->id}", [
                'nomor_pengujian' => 'UJI-AUTO-03',
                'nama_pemohon' => 'Dedi Wijaya',
                'email_pemohon' => 'dedi.baru@example.com',
                'jenis_pengujian' => 'Uji Mutu Benih (ISTA)',
            ]);

        $response->assertStatus(200);

        Mail::assertNothingSent();
    }

    /**
     * Test list pengujian menyertakan relasi latestNotifikasiHasil.
     */
    public function test_list_pengujian_menyertakan_latest_notifikasi_hasil(): void
    {
        $pengujian = Pengujian::create([
            'nomor_pengujian' => 'UJI-EMAIL-04',
            'nama_pemohon' => 'Dewi Sartika',
            'email_pemohon' => 'dewi@example.com',
            'jenis_pengujian' => 'Analisis SSR/RAPD',
            'status' => 'selesai',
            'file_laporan' => 'hasil_uji/dummy-laporan.pdf',
        ]);

        LogNotifikasi::create([
            'pengujian_id' => $pengujian->id,
            'email_tujuan' => $pengujian->email_pemohon,
            'tipe_notifikasi' => 'hasil_siap',
            'status' => 'terkirim',
            'percobaan_ke' => 1,
        ]);

        $response = $this->actingAs($this->petugas, 'sanctum')
            ->getJson('/api/admin/pengujian');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.latest_notifikasi_hasil.status', 'terkirim');
    }
}
