<?php

namespace Tests\Feature;

use App\Models\Petugas;
use App\Models\Pengujian;
use App\Models\Skm;
use App\Services\IkmCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class IkmCalculationTest extends TestCase
{
    use RefreshDatabase;

    private Petugas $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Petugas::create([
            'nama' => 'Admin IKM',
            'username' => 'adminikm',
            'email' => 'admin.ikm@brmp.go.id',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'wajib_ganti_password' => false,
        ]);
    }

    /**
     * Test perhitungan rumus IKM terkonversi dan kategori mutu pelayanan (F-23, Rule 7).
     */
    public function test_perhitungan_formula_ikm_terkonversi(): void
    {
        // 1. Buat 2 pengujian yang valid
        $p1 = Pengujian::create([
            'nomor_pengujian' => 'UJI-SKM-01',
            'nama_pemohon' => 'Ahmad',
            'email_pemohon' => 'ahmad@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
        ]);

        $p2 = Pengujian::create([
            'nomor_pengujian' => 'UJI-SKM-02',
            'nama_pemohon' => 'Bella',
            'email_pemohon' => 'bella@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
        ]);

        // 2. Simpan SKM untuk pengujian 1 (semua bernilai 4)
        Skm::create([
            'pengujian_id' => $p1->id,
            'nama' => 'Ahmad',
            'jenis_kelamin' => 'Laki-laki',
            'pendidikan' => 'D4/S1',
            'usia' => '26-34 tahun',
            'pekerjaan' => 'Swasta',
            'disabilitas' => 'Tidak',
            'skor_1' => 4, 'skor_2' => 4, 'skor_3' => 4, 'skor_4' => 4,
            'skor_5' => 4, 'skor_6' => 4, 'skor_7' => 4, 'skor_8' => 4, 'skor_9' => 4,
            'skor_10' => 4, 'skor_11' => 4, 'skor_12' => 4, 'skor_13' => 4, 'skor_14' => 4, 'skor_15' => 4, 'skor_16' => 4,
            'tanggal_isi' => now()->toDateString(),
        ]);

        // 3. Simpan SKM untuk pengujian 2 (semua bernilai 3)
        Skm::create([
            'pengujian_id' => $p2->id,
            'nama' => 'Bella',
            'jenis_kelamin' => 'Perempuan',
            'pendidikan' => 'D4/S1',
            'usia' => '26-34 tahun',
            'pekerjaan' => 'Swasta',
            'disabilitas' => 'Tidak',
            'skor_1' => 3, 'skor_2' => 3, 'skor_3' => 3, 'skor_4' => 3,
            'skor_5' => 3, 'skor_6' => 3, 'skor_7' => 3, 'skor_8' => 3, 'skor_9' => 3,
            'skor_10' => 3, 'skor_11' => 3, 'skor_12' => 3, 'skor_13' => 3, 'skor_14' => 3, 'skor_15' => 3, 'skor_16' => 3,
            'tanggal_isi' => now()->toDateString(),
        ]);

        // Hitung manual:
        // Rata-rata per unsur = (4 + 3) / 2 = 3.5
        // Rata-rata terbobot = 3.5 * (1/16) = 0.21875 -> round to 3 decimals = 0.219
        // Total terbobot = 3.5
        // IKM Terkonversi = 3.5 * 25 = 87.50
        // Mutu = A (Sangat Baik)

        $service = new IkmCalculationService();
        $stats = $service->calculateIkm();

        $this->assertEquals(2, $stats['total_responden']);
        $this->assertEquals(3.5, $stats['rata_rata_unsur']['u1']);
        $this->assertEquals(0.219, $stats['rata_rata_unsur_terbobot']['u1']);
        $this->assertEquals(87.50, $stats['ikm']);
        $this->assertEquals('B', $stats['mutu']);
        $this->assertEquals('Baik', $stats['kinerja']);

        // 4. Verifikasi endpoint admin rekap IKM
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/skm/ikm');

        $response->assertStatus(200)
            ->assertJsonPath('stats.ikm', 87.50)
            ->assertJsonPath('stats.mutu', 'B')
            ->assertJsonCount(2, 'responden.data');
    }

    /**
     * Test perhitungan IKM jika seluruh responden memberikan skor sempurna 4 (F-23, Rule 7).
     */
    public function test_perhitungan_formula_ikm_sempurna(): void
    {
        $p = Pengujian::create([
            'nomor_pengujian' => 'UJI-SKM-PERFECT',
            'nama_pemohon' => 'Chandra',
            'email_pemohon' => 'chandra@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
        ]);

        Skm::create([
            'pengujian_id' => $p->id,
            'nama' => 'Chandra',
            'jenis_kelamin' => 'Laki-laki',
            'pendidikan' => 'D4/S1',
            'usia' => '26-34 tahun',
            'pekerjaan' => 'Swasta',
            'disabilitas' => 'Tidak',
            'skor_1' => 4, 'skor_2' => 4, 'skor_3' => 4, 'skor_4' => 4,
            'skor_5' => 4, 'skor_6' => 4, 'skor_7' => 4, 'skor_8' => 4, 'skor_9' => 4,
            'skor_10' => 4, 'skor_11' => 4, 'skor_12' => 4, 'skor_13' => 4, 'skor_14' => 4, 'skor_15' => 4, 'skor_16' => 4,
            'tanggal_isi' => now()->toDateString(),
        ]);

        $service = new IkmCalculationService();
        $stats = $service->calculateIkm();

        $this->assertEquals(100.00, $stats['ikm']);
        $this->assertEquals('A', $stats['mutu']);
        $this->assertEquals('Sangat Baik', $stats['kinerja']);
    }

    /**
     * Test perhitungan IKM menggunakan filter bulan dan tahun (F-23).
     */
    public function test_perhitungan_formula_ikm_dengan_filter_tanggal(): void
    {
        // 1. Buat data pengujian di bulan Agustus dan September
        $p1 = Pengujian::create([
            'nomor_pengujian' => 'UJI-SKM-AUG',
            'nama_pemohon' => 'Agustina',
            'email_pemohon' => 'agustina@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
        ]);
        Skm::create([
            'pengujian_id' => $p1->id,
            'nama' => 'Agustina',
            'jenis_kelamin' => 'Perempuan',
            'pendidikan' => 'D4/S1',
            'usia' => '26-34 tahun',
            'pekerjaan' => 'Swasta',
            'disabilitas' => 'Tidak',
            'skor_1' => 4, 'skor_2' => 4, 'skor_3' => 4, 'skor_4' => 4,
            'skor_5' => 4, 'skor_6' => 4, 'skor_7' => 4, 'skor_8' => 4, 'skor_9' => 4,
            'skor_10' => 4, 'skor_11' => 4, 'skor_12' => 4, 'skor_13' => 4, 'skor_14' => 4, 'skor_15' => 4, 'skor_16' => 4,
            'tanggal_isi' => '2026-08-15',
        ]);

        $p2 = Pengujian::create([
            'nomor_pengujian' => 'UJI-SKM-SEP',
            'nama_pemohon' => 'Septian',
            'email_pemohon' => 'septian@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
        ]);
        Skm::create([
            'pengujian_id' => $p2->id,
            'nama' => 'Septian',
            'jenis_kelamin' => 'Laki-laki',
            'pendidikan' => 'D4/S1',
            'usia' => '26-34 tahun',
            'pekerjaan' => 'Swasta',
            'disabilitas' => 'Tidak',
            'skor_1' => 2, 'skor_2' => 2, 'skor_3' => 2, 'skor_4' => 2,
            'skor_5' => 2, 'skor_6' => 2, 'skor_7' => 2, 'skor_8' => 2, 'skor_9' => 2,
            'skor_10' => 2, 'skor_11' => 2, 'skor_12' => 2, 'skor_13' => 2, 'skor_14' => 2, 'skor_15' => 2, 'skor_16' => 2,
            'tanggal_isi' => '2026-09-20',
        ]);

        $service = new IkmCalculationService();

        // 2. Hitung khusus Agustus 2026
        $statsAug = $service->calculateIkm(8, 2026);
        $this->assertEquals(1, $statsAug['total_responden']);
        $this->assertEquals(100.00, $statsAug['ikm']);

        // 3. Hitung khusus September 2026
        $statsSep = $service->calculateIkm(9, 2026);
        $this->assertEquals(1, $statsSep['total_responden']);
        $this->assertEquals(50.00, $statsSep['ikm']);

        // 4. Verifikasi via endpoint API dengan filter bulan & tahun
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/skm/ikm?bulan=8&tahun=2026');

        $response->assertStatus(200)
            ->assertJsonPath('stats.total_responden', 1)
            ->assertJsonPath('stats.ikm', 100)
            ->assertJsonCount(1, 'responden.data');
    }

    /**
     * Test filter IKM berdasarkan Triwulan (Q1 s/d Q4).
     */
    public function test_filter_ikm_berdasarkan_triwulan(): void
    {
        $p1 = Pengujian::create([
            'nomor_pengujian' => 'UJI-TW-01',
            'nama_pemohon' => 'Q1 User',
            'email_pemohon' => 'q1@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
        ]);

        $p2 = Pengujian::create([
            'nomor_pengujian' => 'UJI-TW-02',
            'nama_pemohon' => 'Q3 User 1',
            'email_pemohon' => 'q3_1@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
        ]);

        $p3 = Pengujian::create([
            'nomor_pengujian' => 'UJI-TW-03',
            'nama_pemohon' => 'Q3 User 2',
            'email_pemohon' => 'q3_2@example.com',
            'jenis_pengujian' => 'Deteksi GMO',
        ]);

        // Responden 1: Februari (Triwulan 1)
        Skm::create([
            'pengujian_id' => $p1->id,
            'nama' => 'Q1 User',
            'jenis_kelamin' => 'Laki-laki',
            'pendidikan' => 'D4/S1',
            'usia' => '26-34 tahun',
            'pekerjaan' => 'PNS',
            'disabilitas' => 'Tidak',
            'skor_1' => 4, 'skor_2' => 4, 'skor_3' => 4, 'skor_4' => 4,
            'skor_5' => 4, 'skor_6' => 4, 'skor_7' => 4, 'skor_8' => 4,
            'skor_9' => 4, 'skor_10' => 4, 'skor_11' => 4, 'skor_12' => 4,
            'skor_13' => 4, 'skor_14' => 4, 'skor_15' => 4, 'skor_16' => 4,
            'tanggal_isi' => '2026-02-15',
        ]);

        // Responden 2: Juli (Triwulan 3)
        Skm::create([
            'pengujian_id' => $p2->id,
            'nama' => 'Q3 User 1',
            'jenis_kelamin' => 'Perempuan',
            'pendidikan' => 'S2/S3',
            'usia' => '35-44 tahun',
            'pekerjaan' => 'Dosen/Peneliti',
            'disabilitas' => 'Tidak',
            'skor_1' => 3, 'skor_2' => 3, 'skor_3' => 3, 'skor_4' => 3,
            'skor_5' => 3, 'skor_6' => 3, 'skor_7' => 3, 'skor_8' => 3,
            'skor_9' => 3, 'skor_10' => 3, 'skor_11' => 3, 'skor_12' => 3,
            'skor_13' => 3, 'skor_14' => 3, 'skor_15' => 3, 'skor_16' => 3,
            'tanggal_isi' => '2026-07-10',
        ]);

        // Responden 3: September (Triwulan 3)
        Skm::create([
            'pengujian_id' => $p3->id,
            'nama' => 'Q3 User 2',
            'jenis_kelamin' => 'Laki-laki',
            'pendidikan' => 'D4/S1',
            'usia' => '26-34 tahun',
            'pekerjaan' => 'Swasta',
            'disabilitas' => 'Tidak',
            'skor_1' => 4, 'skor_2' => 4, 'skor_3' => 4, 'skor_4' => 4,
            'skor_5' => 4, 'skor_6' => 4, 'skor_7' => 4, 'skor_8' => 4,
            'skor_9' => 4, 'skor_10' => 4, 'skor_11' => 4, 'skor_12' => 4,
            'skor_13' => 4, 'skor_14' => 4, 'skor_15' => 4, 'skor_16' => 4,
            'tanggal_isi' => '2026-09-05',
        ]);

        $service = new IkmCalculationService();

        // Triwulan 1: harus hanya ada 1 responden
        $statsTw1 = $service->calculateIkm(null, 2026, 1);
        $this->assertEquals(1, $statsTw1['total_responden']);

        // Triwulan 3: harus ada 2 responden (Juli & September)
        $statsTw3 = $service->calculateIkm(null, 2026, 3);
        $this->assertEquals(2, $statsTw3['total_responden']);

        // Endpoint API dengan filter triwulan
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/skm/ikm?triwulan=3&tahun=2026');

        $response->assertStatus(200)
            ->assertJsonPath('stats.total_responden', 2)
            ->assertJsonCount(2, 'responden.data');
    }
}
