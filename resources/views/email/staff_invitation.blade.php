<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Undangan Akses Staff</title>
</head>

<body
    style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">

    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #ddd;">
        <h2 style="color: #0d6efd; margin-top: 0;">Halo, {{ $name }}!</h2>

        <p>Anda telah diundang untuk bergabung sebagai <strong>Staff</strong> di sistem kami.</p>

        <p>Untuk mulai menggunakan akun Anda, silakan klik tombol di bawah ini untuk memverifikasi email dan mengatur
            password Anda.</p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $url }}"
                style="background-color: #0d6efd; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">
                Verifikasi & Atur Password
            </a>
        </div>

        <p style="font-size: 14px; color: #dc3545;">
            <em>*Penting: Link verifikasi ini bersifat rahasia dan hanya berlaku selama 24 jam.</em>
        </p>

        <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

        <p style="font-size: 12px; color: #6c757d;">
            Jika tombol di atas tidak berfungsi, salin dan tempel URL berikut ke browser Anda:<br>
            <a href="{{ $url }}" style="color: #0d6efd;">{{ $url }}</a>
        </p>
    </div>

</body>

</html>
