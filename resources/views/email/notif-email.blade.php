<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>GOTIK Ticket Email</title>
</head>

<body style="margin:0; padding:0; background-color:#eef1f5; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#eef1f5; margin:0; padding:24px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:600px; background-color:#ffffff; border:1px solid #d9dee7; border-radius:16px;">
          <tr>
            <td style="padding:32px 32px 16px 32px; border-bottom:1px solid #e5e7eb;">
              <div style="font-size:30px; line-height:34px; font-weight:700; letter-spacing:1px; color:#111827;">GOTIK</div>
              <div style="margin-top:6px; font-size:12px; line-height:18px; letter-spacing:0.8px; text-transform:uppercase; color:#6b7280;">Ticketing &amp; Event Access</div>
            </td>
          </tr>

          <tr>
            <td style="padding:32px;">
              <div style="font-size:24px; line-height:30px; font-weight:700; color:#111827;">Hi, {{ $name }}</div>
              <div style="margin-top:12px; font-size:15px; line-height:24px; color:#4b5563;">
                @if($isResendTicket ?? false)
                  Barcode tiket Anda telah diperbarui. Barcode sebelumnya sudah tidak berlaku dan hanya barcode terbaru dari email ini yang dapat digunakan untuk akses masuk venue.
                @else
                  Terima kasih telah membeli tiket <strong style="color:#111827;">{{ $event->event }}</strong> melalui GOTIK. Barcode dan kode manual bersifat rahasia, jadi mohon jangan dibagikan kepada orang lain.
                @endif
              </div>

              @if($isResendTicket ?? false)
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:24px; background-color:#fff7ed; border:1px solid #fdba74; border-radius:12px;">
                  <tr>
                    <td style="padding:18px 20px;">
                      <div style="font-size:12px; line-height:18px; font-weight:700; letter-spacing:0.8px; text-transform:uppercase; color:#c2410c;">Status tiket</div>
                      <div style="margin-top:6px; font-size:20px; line-height:28px; font-weight:700; color:#9a3412;">Barcode Tiket Terbaru</div>
                      <div style="margin-top:8px; font-size:14px; line-height:22px; color:#7c2d12;">Barcode lama tidak berlaku lagi. Tunjukkan barcode terbaru atau kode manual dari email ini saat verifikasi kehadiran.</div>
                    </td>
                  </tr>
                </table>
              @endif

              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:24px; background-color:#f9fafb; border:1px solid #e5e7eb; border-radius:12px;">
                <tr>
                  <td style="padding:22px 24px;">
                    <div style="font-size:12px; line-height:18px; font-weight:700; letter-spacing:0.8px; text-transform:uppercase; color:#6b7280;">Event</div>
                    <div style="margin-top:6px; font-size:24px; line-height:32px; font-weight:700; color:#111827;">{{ $event->event }}</div>
                    <div style="margin-top:16px; font-size:12px; line-height:18px; font-weight:700; letter-spacing:0.8px; text-transform:uppercase; color:#6b7280;">Status tiket</div>
                    <div style="margin-top:6px; font-size:15px; line-height:24px; color:#111827;">
                      @if($isResendTicket ?? false)
                        Barcode aktif telah diperbarui dan siap digunakan.
                      @else
                        Barcode tiket Anda siap digunakan untuk masuk ke venue.
                      @endif
                    </div>
                  </td>
                </tr>
              </table>

              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:24px;">
                <tr>
                  <td align="center">
                    <a href="{{ $ticketUrl }}" style="display:inline-block; padding:16px 32px; background-color:#111827; color:#ffffff; text-decoration:none; font-size:16px; line-height:20px; font-weight:700; border-radius:10px;">Lihat Barcode Tiket</a>
                  </td>
                </tr>
              </table>

              <div style="margin-top:16px; font-size:13px; line-height:21px; color:#6b7280; text-align:center;">
                Simpan email ini dan gunakan tombol di atas untuk membuka tiket Anda kapan saja.
              </div>

              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:24px; background-color:#ffffff; border:1px solid #d9dee7; border-radius:12px;">
                <tr>
                  <td style="padding:20px 24px;">
                    <div style="font-size:12px; line-height:18px; font-weight:700; letter-spacing:0.8px; text-transform:uppercase; color:#6b7280;">Invoice</div>
                    <div style="margin-top:8px; font-size:22px; line-height:30px; font-weight:700; color:#111827;">{{ $cart }}</div>
                  </td>
                </tr>
              </table>

              @if($manualCode)
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:20px; background-color:#f8fafc; border:1px solid #cbd5e1; border-radius:12px;">
                  <tr>
                    <td style="padding:20px 24px;">
                      <div style="font-size:12px; line-height:18px; font-weight:700; letter-spacing:0.8px; text-transform:uppercase; color:#6b7280;">Kode Manual</div>
                      <div style="margin-top:10px; font-size:28px; line-height:34px; font-weight:700; letter-spacing:6px; color:#0f172a;">{{ $manualCode }}</div>
                    </td>
                  </tr>
                </table>
              @endif

              <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:20px; background-color:#f9fafb; border:1px solid #e5e7eb; border-radius:12px;">
                <tr>
                  <td style="padding:20px 24px;">
                    <div style="font-size:12px; line-height:18px; font-weight:700; letter-spacing:0.8px; text-transform:uppercase; color:#6b7280;">Informasi keamanan</div>
                    <div style="margin-top:8px; font-size:14px; line-height:22px; color:#4b5563;">
                      Barcode dan kode manual bersifat rahasia. Jangan bagikan kepada siapa pun. Kode manual digunakan jika barcode tidak dapat dipindai saat proses verifikasi.
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <tr>
            <td style="padding:20px 32px 28px 32px; border-top:1px solid #e5e7eb; text-align:center; font-size:12px; line-height:18px; color:#6b7280;">
              Powered by GOTIK
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>

</html>
