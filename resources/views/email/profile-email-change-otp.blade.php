<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode OTP Ganti Email</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f5f5f5; padding:24px;">
    <div style="max-width:520px;margin:auto;background:#ffffff;border-radius:8px;padding:24px;text-align:center;">
        <h2>Hi, {{ $name }}</h2>
        <p>Gunakan kode OTP berikut untuk mengganti email akun GOTIK Anda.</p>
        <div style="font-size:32px;font-weight:bold;letter-spacing:8px;margin:24px 0;">{{ $otp }}</div>
        <p>Kode berlaku 10 menit dan hanya dapat digunakan satu kali.</p>
        <p>Jika Anda tidak meminta perubahan email, abaikan email ini.</p>
    </div>
</body>
</html>
