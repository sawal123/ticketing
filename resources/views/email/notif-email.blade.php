<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Document</title>

  <style>
    body {
      background: #e3e3e3;
    }

    .container {
      background: white;
      border: 1px solid black;
      border-radius: 10px;
      text-align: center
    }

    .button:hover {
      background: black;
    }

    .button {
      margin-top: 20px;
      text-decoration: none;
      padding: 20px 30px 20px 30px;
      background: #4018a4;
      border: none;
      border-radius: 5px;

      width: 150px;
      height: 50px;
      font-size: 14px;
    }

    p,
    h2 {
      padding: 0px;
    }

    .margin {
      height: 20px;
    }
  </style>


</head>


<body>
  <div class="container">
    <h3>Hi, {{ $name }}</h3>
    <hr>
    <div class="paragrap">
      @if($isResendTicket ?? false)
        <h2>Barcode Tiket Terbaru</h2>
        <strong>{{ $event->event }}</strong>
        <p>
          Demi keamanan sistem tiket GOTIK, barcode/QR code tiket sebelumnya telah diperbarui.
          Barcode lama tidak berlaku lagi. Gunakan barcode terbaru dari email ini untuk masuk ke venue.
        </p>
        <p>Tekan tombol di bawah untuk melihat tiket dan barcode terbaru Anda.</p>
      @else
        <strong>Terimakasih telah membeli tiket {{$event->event}} melalui GOTIK</strong>
        <p>Link dan barcode ini bersifat rahasia. Jangan bagikan kepada orang lain.
          <br>
          Tunjukan barcode/kode kepada panitia untuk konfirmasi kehadiran.
        </p>
        <p>Tekan tombol dibawah untuk melihat detail tiket dan barcode anda!</p>
      @endif
    </div>
    <div class="margin"></div>
    <a href="{{ $ticketUrl }}" style="  color:white;" class="button">
      Tunjukan Barcode
    </a>
    <div class="margin"></div>
    <br>

    <p>Nomor Invoice</p>
    <h2>{{ $cart }}</h2>

    @if($manualCode)
      <p>Kode Manual</p>
      <h2 style="letter-spacing: 4px;">{{ $manualCode }}</h2>
    @endif

    @if($isResendTicket ?? false)
      <p>
        Kode manual ini hanya digunakan apabila barcode tidak dapat dipindai oleh panitia.
        Jangan bagikan barcode atau kode manual kepada orang lain.
      </p>
    @endif

    <p>
      powerdBy : GOTIK
    </p>

    <div class="margin"></div>
  </div>


</body>

</html>
