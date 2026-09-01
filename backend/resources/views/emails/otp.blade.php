<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Kode OTP - BRMP Biogen</title>
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
        .otp-box {
            background-color: rgba(27, 77, 62, 0.04);
            border: 2px dashed #1B4D3E;
            padding: 24px 20px;
            text-align: center;
            border-radius: 14px;
            margin: 28px 0;
        }
        .otp-label {
            margin: 0 0 8px 0;
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .otp-code {
            font-size: 38px;
            font-weight: 800;
            letter-spacing: 8px;
            color: #1B4D3E;
            margin: 0;
            line-height: 1;
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
            <p>Kami menerima permintaan untuk mengakses dokumen hasil uji laboratorium untuk nomor pengujian <strong>{{ $pengujian->nomor_pengujian }}</strong>.</p>
            <p>Silakan gunakan kode OTP di bawah ini untuk memverifikasi identitas Anda dan melanjutkan pengisian Survei Kepuasan Masyarakat (SKM) sebelum mengunduh berkas Laporan Hasil Uji:</p>
            
            <div class="otp-box">
                <div class="otp-label">Kode OTP Verifikasi Anda</div>
                <div class="otp-code">{{ $otp }}</div>
            </div>

            <p>Kode OTP ini <strong>hanya berlaku selama 10 menit</strong> sejak email ini dikirimkan. Demi keamanan data Anda, jangan bagikan kode ini kepada pihak manapun.</p>
            
            <p class="warning-text">Jika Anda tidak merasa melakukan permintaan ini, harap abaikan email ini atau hubungi layanan bantuan BRMP Biogen Kementerian Pertanian RI.</p>
        </div>
        <div class="footer">
            <strong>BRMP BIOGEN KEMENTERIAN PERTANIAN RI</strong><br>
            Balai Besar Perakitan dan Modernisasi Bioteknologi dan Sumber Daya Genetik Pertanian<br>
            Jl. Tentara Pelajar No. 3A, Cimanggu, Bogor 16111 &copy; 2026
        </div>
    </div>
</body>
</html>
