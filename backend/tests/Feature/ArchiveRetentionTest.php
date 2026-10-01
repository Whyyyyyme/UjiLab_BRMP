<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Pengujian;
use App\Models\Petugas;
use App\Models\LogAktivitas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class ArchiveRetentionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_backup_bulanan_command_berhasil_membuat_zip_dan_manifest(): void
    {
        // 1. Siapkan file dummy di storage
        Storage::disk('local')->put('hasil_uji/sample_laporan.pdf', '%PDF-1.4 sample content');

        Pengujian::create([
            'nomor_pengujian' => '001/LAB-TEST/2023',
            'nama_pemohon' => 'Budi Santoso',
            'email_pemohon' => 'budi@example.com',
            'jenis_pengujian' => 'Uji Mutu Benih',
            'status' => 'selesai',
            'file_laporan' => 'hasil_uji/sample_laporan.pdf',
            'hash_laporan' => hash('sha256', '%PDF-1.4 sample content'),
            'versi' => 1,
            'is_deleted' => false,
            'is_archived' => false,
        ]);

        // 2. Jalankan perintah artisan backup bulanan secara sinkron
        $exitCode = \Illuminate\Support\Facades\Artisan::call('pengujian:backup-monthly');
        $this->assertEquals(0, $exitCode);

        // 3. Verifikasi berkas zip backup terbentuk di disk local
        $backupFiles = Storage::disk('local')->files('backups/monthly');
        $this->assertNotEmpty($backupFiles, 'Folder backups/monthly harus berisi minimal 1 berkas zip.');
        $this->assertStringEndsWith('.zip', $backupFiles[0]);

        // 4. Verifikasi log aktivitas tercatat
        $this->assertDatabaseHas('log_aktivitas', [
            'aksi' => 'Backup Otomatis Bulanan',
        ]);
    }

    public function test_archive_command_mengarsipkan_pengujian_lama_dan_menghapus_file_fisik(): void
    {
        // Berkas untuk pengujian lama (4 tahun lalu)
        Storage::disk('local')->put('hasil_uji/lama.pdf', '%PDF-1.4 file lama 4 tahun lalu');
        $oldPengujian = Pengujian::create([
            'nomor_pengujian' => '002/LAB-OLD/2022',
            'nama_pemohon' => 'Ahmad Dahlan',
            'email_pemohon' => 'ahmad@example.com',
            'jenis_pengujian' => 'Uji DNA Tanaman',
            'status' => 'selesai',
            'file_laporan' => 'hasil_uji/lama.pdf',
            'hash_laporan' => hash('sha256', '%PDF-1.4 file lama 4 tahun lalu'),
            'versi' => 1,
            'is_deleted' => false,
            'is_archived' => false,
        ]);
        \Illuminate\Support\Facades\DB::table('pengujian')
            ->where('id', $oldPengujian->id)
            ->update(['created_at' => Carbon::now()->subYears(4)->toDateTimeString()]);

        // Berkas untuk pengujian baru (1 tahun lalu)
        Storage::disk('local')->put('hasil_uji/baru.pdf', '%PDF-1.4 file baru 1 tahun lalu');
        $newPengujian = Pengujian::create([
            'nomor_pengujian' => '003/LAB-NEW/2025',
            'nama_pemohon' => 'Siti Fatimah',
            'email_pemohon' => 'siti@example.com',
            'jenis_pengujian' => 'Uji DNA Tanaman',
            'status' => 'selesai',
            'file_laporan' => 'hasil_uji/baru.pdf',
            'hash_laporan' => hash('sha256', '%PDF-1.4 file baru 1 tahun lalu'),
            'versi' => 1,
            'is_deleted' => false,
            'is_archived' => false,
        ]);
        \Illuminate\Support\Facades\DB::table('pengujian')
            ->where('id', $newPengujian->id)
            ->update(['created_at' => Carbon::now()->subYears(1)->toDateTimeString()]);

        // Jalankan perintah pengarsipan dengan batas 3 tahun secara sinkron
        $exitCode = \Illuminate\Support\Facades\Artisan::call('pengujian:archive', ['--years' => 3]);
        $this->assertEquals(0, $exitCode);

        // Verifikasi pengujian lama: is_archived = true dan berkas fisik terhapus
        $oldPengujian->refresh();
        $this->assertTrue($oldPengujian->is_archived);
        $this->assertNotNull($oldPengujian->archived_at);
        $this->assertFalse(Storage::disk('local')->exists('hasil_uji/lama.pdf'), 'Berkas fisik pengujian lama harus terhapus untuk efisiensi disk.');

        // Verifikasi pengujian baru: tetap aktif dan berkas fisik utuh
        $newPengujian->refresh();
        $this->assertFalse($newPengujian->is_archived);
        $this->assertNull($newPengujian->archived_at);
        $this->assertTrue(Storage::disk('local')->exists('hasil_uji/baru.pdf'), 'Berkas pengujian aktif harus tetap ada.');
    }

    public function test_pencarian_publik_ditolak_jika_pengujian_telah_diarsipkan(): void
    {
        $archivedPengujian = Pengujian::create([
            'nomor_pengujian' => '004/LAB-ARCH/2021',
            'nama_pemohon' => 'Hendro Prasetyo',
            'email_pemohon' => 'hendro@example.com',
            'jenis_pengujian' => 'Uji Mikrobiologi',
            'status' => 'selesai',
            'versi' => 1,
            'is_deleted' => false,
            'is_archived' => true,
            'archived_at' => Carbon::now(),
        ]);

        $response = $this->postJson('/api/public/pengujian/cari', [
            'nomor_pengujian' => '004/LAB-ARCH/2021',
        ]);

        $response->assertStatus(410);
        $response->assertJsonFragment([
            'status' => 'archived',
        ]);
        $this->assertStringContainsString('telah melewati batas masa retensi digital', $response->json('message'));
    }

    public function test_verifikasi_qr_menampilkan_status_diarsipkan(): void
    {
        $archivedPengujian = Pengujian::create([
            'nomor_pengujian' => '005/LAB-QR/2020',
            'nama_pemohon' => 'Bambang Sudibyo',
            'email_pemohon' => 'bambang@example.com',
            'jenis_pengujian' => 'Uji Kandungan Kimia',
            'status' => 'selesai',
            'versi' => 1,
            'is_deleted' => false,
            'is_archived' => true,
            'archived_at' => Carbon::now()->subMonths(6),
        ]);

        $response = $this->getJson('/api/public/verifikasi/005/LAB-QR/2020');

        $response->assertStatus(200);
        $response->assertJson([
            'nomor_pengujian' => '005/LAB-QR/2020',
            'status_keaslian' => true,
            'is_archived' => true,
        ]);
    }
}
