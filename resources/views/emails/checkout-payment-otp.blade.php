<div style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6;">
    <h2 style="margin-bottom: 16px;">Kode Verifikasi Pembayaran</h2>
    <p>Halo {{ $name }},</p>
    <p>Gunakan kode OTP berikut untuk melanjutkan pembayaran event <strong>{{ $eventName }}</strong>:</p>
    <div style="margin: 20px 0; padding: 16px; background: #f3f4f6; border-radius: 8px; font-size: 28px; font-weight: 700; letter-spacing: 6px; text-align: center;">
        {{ $otpCode }}
    </div>
    <p>Kode berlaku selama 5 menit.</p>
    <p>Jangan berikan kode ini kepada siapa pun.</p>
</div>
