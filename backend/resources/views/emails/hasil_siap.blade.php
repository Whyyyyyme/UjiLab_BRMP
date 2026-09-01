<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Uji Laboratorium Selesai - BRMP Biogen</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f1f5f9;
            margin: 0;
            padding: 0;
            color: #334155;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.03);
            border: 1px solid #e2e8f0;
            border-top: 5px solid #1B4D3E;
        }
        .header {
            background: linear-gradient(135deg, #1B4D3E 0%, #0F3328 100%);
            color: #ffffff;
            text-align: center;
            padding: 32px 24px;
        }
        .logo-img {
            height: 64px;
            width: auto;
            display: inline-block;
            margin-bottom: 12px;
        }
        .header h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 1px;
            color: #ffffff;
        }
        .header-sub {
            margin: 6px 0 0 0;
            font-size: 10px;
            font-weight: 700;
            color: #EAB308;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            line-height: 1.4;
        }
        .content {
            padding: 36px 30px;
            line-height: 1.6;
        }
        .content p {
            margin: 0 0 18px 0;
            font-size: 14px;
            color: #334155;
        }
        .info-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #1B4D3E;
            padding: 20px;
            border-radius: 12px;
            margin: 24px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            font-size: 13px;
        }
        .info-row:last-child {
            margin-bottom: 0;
        }
        .info-label {
            color: #64748b;
            font-weight: 600;
        }
        .info-val {
            color: #1e293b;
            font-weight: 700;
            text-align: right;
        }
        .info-val.highlight {
            color: #1B4D3E;
            font-size: 14px;
        }
        .btn-wrapper {
            text-align: center;
            margin: 32px 0 24px 0;
        }
        .btn-link {
            display: inline-block;
            background-color: #1B4D3E;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 4px 14px rgba(27, 77, 62, 0.25);
            transition: background-color 0.2s ease;
        }
        .warning-text {
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #f1f5f9;
            padding-top: 20px;
            margin-top: 28px;
            line-height: 1.5;
        }
        .footer {
            background-color: #f8fafc;
            text-align: center;
            padding: 20px 24px;
            font-size: 11px;
            color: #64748b;
            border-top: 1px solid #f1f5f9;
            line-height: 1.5;
        }
        .footer strong {
            color: #1B4D3E;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            @if(isset($message) && method_exists($message, 'embed'))
                <img src="{{ $message->embed(public_path('assets/logo-kementan.png')) }}" alt="Logo BRMP Biogen" class="logo-img" />
            @else
                <img src="{{ config('app.url') }}/assets/logo-kementan.png" alt="Logo BRMP Biogen" class="logo-img" />
            @endif
            <h2>BRMP BIOGEN</h2>
            <div class="header-sub">Balai Besar Perakitan dan Modernisasi Bioteknologi dan Sumber Daya Genetik Pertanian</div>
        </div>
        <div class="content">
            <p>Yth. Pemohon/Pengguna Jasa,</p>
            <p>Dengan hormat, kami menginformasikan bahwa proses pengujian laboratorium untuk sampel Anda telah <strong>selesai diproses</strong> oleh tim teknis BRMP Biogen Kementerian Pertanian RI.</p>
            
            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">Nomor Pengujian</span>
                    <span class="info-val highlight">{{ $pengujian->nomor_pengujian }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Jenis Pengujian</span>
                    <span class="info-val">{{ $pengujian->jenis_pengujian }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Nama Pemohon</span>
                    <span class="info-val">{{ $pengujian->nama_pemohon }}</span>
                </div>
            </div>

            <p>Sesuai dengan protokol keamanan data instansi, <strong>berkas hasil pengujian (PDF) tidak kami lampirkan secara langsung di email</strong>. Anda dapat mengunduh berkas Laporan Hasil Pengujian secara mandiri melalui portal resmi kami di bawah ini:</p>
            
            <div class="btn-wrapper">
                <a href="{{ config('app.frontend_url') }}/?nomor_pengujian={{ urlencode($pengujian->nomor_pengujian) }}" class="btn-link">
                    Ambil Hasil Pengujian
                </a>
            </div>

            <p>Setelah mengakses tautan di atas, Anda akan diminta melakukan verifikasi kode OTP yang dikirimkan ke email Anda dan mengisi kuesioner Survei Kepuasan Masyarakat (SKM) sebelum file hasil uji dapat diunduh.</p>
            
            <p class="warning-text">Pemberitahuan ini dikirimkan secara otomatis oleh Sistem Distribusi Hasil Uji BRMP Biogen Kementerian Pertanian RI. Mohon tidak membalas email ini.</p>
        </div>
        <div class="footer">
            <strong>BRMP BIOGEN KEMENTERIAN PERTANIAN RI</strong><br>
            Balai Besar Perakitan dan Modernisasi Bioteknologi dan Sumber Daya Genetik Pertanian<br>
            Jl. Tentara Pelajar No. 3A, Cimanggu, Bogor 16111 &copy; 2026
        </div>
    </div>
</body>
</html>
