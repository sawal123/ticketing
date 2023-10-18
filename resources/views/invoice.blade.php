<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <link id="style" href="{{ asset('/assets/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
</head>

<style>
    p {
        margin-bottom: 0px;
    }

    .card-body::before {
      content: "";
       background-image: url('{{asset('storage/logo/'. $logo[0]->logo)}}'); 
       opacity: 0.1; 
       background-size: contain;
       background-repeat: repeat;
       position: absolute;
       top: 0;
       left: 0;
       width: 100%;
       height: 100%;
       z-index: 999;
    }
</style>

<body>
    <div class="container">
        <div class="row d-flex justify-content-center">
            <div class="col-lg-4 col-md-12 mt-5">
                <div class="card w-lg-50">
                    <div class="card-header d-flex justify-content-between">
                        <h5>Invoice</h5>
                        <h5>{{$penarikan->uid}}</h5>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">Penarikan</h5>
                        <div class="d-flex justify-content-between">
                            <p>Nama :</p>
                            <p>{{$penarikan->name}}</p>
                        </div>
                        <div class="d-flex justify-content-between">
                            <p>Amount :</p>
                            <p>Rp{{ number_format($penarikan->amount, 0, ',', '.') }}</p>
                        </div>
                        <div class="d-flex justify-content-between">
                            <p>Status :</p>
                            <p class="fw-bold">{{$penarikan->status}}</p>
                        </div>
                        <hr>
                        <h4>Pengirim</h4>

                        <div class="d-flex justify-content-between">
                            <p>Nama :</p>
                            <p>{{$bankPengirim[0]->nama}}</p>
                        </div>
                        <div class="d-flex justify-content-between">
                            <p>Bank :</p>
                            <p>{{$bankPengirim[0]->bank}}</p>
                        </div>
                        <div class="d-flex justify-content-between">
                            <p>Nomor Rek :</p>
                            <p>{{$bankPengirim[0]->norek}}</p>
                        </div>

                        <hr>
                        <h4>Penerima</h4>
                        <div class="d-flex justify-content-between">
                            <p>Nama :</p>
                            <p>{{$bankPenyewa->nama}}</p>
                        </div>
                        <div class="d-flex justify-content-between">
                            <p>Bank :</p>
                            <p>{{$bankPenyewa->bank}}</p>
                        </div>
                        <div class="d-flex justify-content-between">
                            <p>Nomor Rek :</p>
                            <p>{{$bankPenyewa->norek}}</p>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <a href="#" class="btn btn-success w-50 mx-2">Download</a>
                    <a href="#" class="btn btn-primary w-50 mx-2">Print</a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
