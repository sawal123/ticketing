<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0, user-scalable=0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Barcode Gotik">
    <meta name="author" content="Gotik">
    <link rel="shortcut icon" type="image/x-icon" href="../assets/images/brand/favicon.ico">
    {{-- <link rel="stylesheet" href="https://drive.google.com/uc?export=view&id=1yTLwNiCZhIdCWolQldwq4spHQkgZDqkG"> --}}
    <title>Barcode Gotik</title>

    <link id="style" href="{{ asset('/assets/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <style>
        * {
            color: #2c2c2c;
        }
        .p{
            font-size: 11px;
        }
    </style>
</head>

<body style="background: #F6F8FD">

    <div class="container">
        <div class="d-flex justify-content-center align-items-center  mt-5 mb-5">
            <div class="card" style="max-width: 400px">
                <div class="card-body">
                    <div class="text-center my-3">
                        <img class="" src="{{ asset('storage/logo/' . $logo[0]->logo) }}" height="60" 
                    alt="logo">
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <h6>Nama</h6>
                                <h6>{{ $userBarcode->name }}</h6>
                            </div>
                            <div class="d-flex justify-content-between">
                                <h6>Email</h6>
                                <h6>{{ $userBarcode->email }}</h6>
                            </div>
                        </div>
                    </div>
                    <div class="text-center my-3">
                        <h4>Tunjukan Barcode Kepada Panitia</h4>
                        {{ $barcodeData }}
                        <h5>{{ $invoice }}</h5>
                    </div>
                    <hr>
                    <div class="text-center">
                        <img src="{{ asset('storage/cover/' . $event->cover) }}" width="100%" alt="" class="img-thumbnail">
                        <h6 class="m-0">{{ $event->event }}</h6>
                        <p class="m-0">{{ $event->alamat }}</p>
                        <hr>
                    </div>
                    
                    
                    <h5>Detail Ticket</h5>
                    @foreach ($hargaC as $hc)
                        <div class="d-flex justify-content-between">
                            <h6>{{ $hc->kategori_harga }}</h6>
                            <h6>{{ $hc->quantity }}</h6>
                        </div>
                    @endforeach

                    <div class="card mt-5">
                        <div class="card-body">
                            <p class="p">
                                Barcode dan kode ini bersifat privasi, jangan beritahu/berikan kepada orang lain.
Tunjukan barcode/kode kepada panitia untuk konfirmasi kehadiran
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
