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
    <title>Barcode Generator</title>

    <link id="style" href="{{ asset('/assets/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
</head>

<body style="background: blue">

    <div class="row p-2 m-0">
        <div class="col-12 col-md-12">
            <div class="text-center ">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 col-lg-6 col-xl-6 d-flex justify-content-center align-items-center">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <p class="mt-3" style="font-size: 30px; line-height: 100%">Tunjukan Barcode Ke
                                            Panitia</p>
                                        {{ $barcodeData }}
                                        <h1>{{ $invoice }}</h1>
                                    </div>

                                </div>
                                
                            </div>
                            
                            <div class="col-md-12 col-lg-6 col-xl-6">
                                <div class="container">
                                    {{-- <h3>Detail Event</h3> --}}
                                    <div class="card">
                                        <div class="card-body">
                                            <img src="{{ asset('storage/cover/' . $event->cover) }}" alt=""
                                                class="img-thumbnail">
                                            <h2>{{ $event->event }}</h2>
                                            <h2>{{ $event->alamat }}</h2>
                                            <h2>{{ $event->tanggal }}</h2>
                                            <button disabled="disabled"
                                                class="btn btn-success w-100">{{ $event->status }}</button>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
