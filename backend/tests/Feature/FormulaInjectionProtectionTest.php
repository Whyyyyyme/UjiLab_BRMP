<?php

namespace Tests\Feature;

use App\Exports\AktivitasExport;
use App\Exports\SkmExport;
use App\Exports\UnduhanExport;
use App\Models\AksesFileLog;
use App\Models\LogAktivitas;
use App\Models\Pengujian;
use App\Models\Petugas;
use App\Models\Skm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FormulaInjectionProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_aktivitas_export_sanitizes_formula_injection_characters(): void
    {
        $admin = Petugas::create([
            'nama' => 'Admin Security',
            'username' => 'adminsec',
            'email' => 'adminsec@brmp.go.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'wajib_ganti_password' => false,
        ]);

        $log = LogAktivitas::create([
            'petugas_id' => $admin->id,
            'aksi' => 'Testing',
            'detail' => '=cmd|\' /C calc\'!A0',
            'ip_address' => '127.0.0.1',
            'user_agent' => '+alert(1)',
        ]);

        $export = new AktivitasExport();
        $mapped = $export->map($log);

        // Kolom detail (index 3) dan user_agent (index 5) harus disanitasi diawali kutip tunggal (')
        $this->assertStringStartsWith("'", $mapped[3]);
        $this->assertStringStartsWith("'", $mapped[5]);
        $this->assertEquals("'=cmd|' /C calc'!A0", $mapped[3]);
        $this->assertEquals("'+alert(1)", $mapped[5]);
    }

    public function test_skm_export_sanitizes_formula_injection_characters(): void
    {
        $pengujian = Pengujian::create([
            'nomor_pengujian' => 'UJI-CSV-001',
            'nama_pemohon' => 'Budi',
            'email_pemohon' => 'budi@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
        ]);

        $skm = Skm::create([
            'pengujian_id' => $pengujian->id,
            'nama' => '@SUM(1+1)*cmd',
            'jenis_kelamin' => 'Laki-laki',
            'pendidikan' => 'S1',
            'usia' => '30',
            'pekerjaan' => '-PNS',
            'disabilitas' => 'Tidak',
            'skor_1' => 4,
            'skor_2' => 4,
            'skor_3' => 4,
            'skor_4' => 4,
            'skor_5' => 4,
            'skor_6' => 4,
            'skor_7' => 4,
            'skor_8' => 4,
            'skor_9' => 4,
            'skor_10' => 4,
            'skor_11' => 4,
            'skor_12' => 4,
            'skor_13' => 4,
            'skor_14' => 4,
            'skor_15' => 4,
            'skor_16' => 4,
            'tanggal_isi' => now()->toDateString(),
        ]);

        $export = new SkmExport();
        $mapped = $export->map($skm);

        // Kolom nama (index 3) dan pekerjaan (index 7) harus diawali tanda kutip (')
        $this->assertStringStartsWith("'", $mapped[3]);
        $this->assertStringStartsWith("'", $mapped[7]);
        $this->assertEquals("'@SUM(1+1)*cmd", $mapped[3]);
        $this->assertEquals("'-PNS", $mapped[7]);
    }

    public function test_unduhan_export_sanitizes_formula_injection_characters(): void
    {
        $pengujian = Pengujian::create([
            'nomor_pengujian' => '=DDE("cmd";"calc")',
            'nama_pemohon' => 'Hacker',
            'email_pemohon' => 'hacker@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
        ]);

        $aksesLog = AksesFileLog::create([
            'pengujian_id' => $pengujian->id,
            'tipe_file' => 'laporan',
            'akses_oleh' => 'publik',
            'petugas_id' => null,
            'ip_address' => '127.0.0.1',
        ]);
        $aksesLog->nomor_pengujian = $pengujian->nomor_pengujian;

        $export = new UnduhanExport();
        $mapped = $export->map($aksesLog);

        // Kolom nomor_pengujian (index 1) harus diawali kutip (')
        $this->assertStringStartsWith("'", $mapped[1]);
        $this->assertEquals("'=DDE(\"cmd\";\"calc\")", $mapped[1]);
    }
}
