<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
        .container { max-width: 480px; margin: 20px auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: #1F5C4F; color: #fff; padding: 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 1.3rem; }
        .body { padding: 32px 24px; text-align: center; }
        .otp-box { background: #f0f7f5; border: 2px dashed #1F5C4F; border-radius: 8px; padding: 16px; margin: 20px 0; font-family: 'Courier New', monospace; font-size: 2rem; letter-spacing: 6px; color: #1F5C4F; font-weight: bold; }
        .footer { background: #fafafa; padding: 16px; text-align: center; font-size: 0.75rem; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏪 Derum — Belanja Deket Rumah</h1>
        </div>
        <div class="body">
            <p>Halo <strong>{{ $nama }}</strong>,</p>
            <p>Gunakan kode OTP berikut untuk verifikasi akun Derum Anda:</p>
            <div class="otp-box">{{ $otp }}</div>
            <p style="font-size: 0.85rem; color: #666;">Kode berlaku 5 menit. Jangan bagikan kode ini ke siapapun.</p>
        </div>
        <div class="footer">
            Email ini dikirim otomatis oleh Derum. Tidak perlu dibalas.
        </div>
    </div>
</body>
</html>