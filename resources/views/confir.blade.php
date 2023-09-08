<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
</head>

<body>
    <div class="container  ">
        <div class="row">
            <div class="col ">
                <div class="text-center " style="margin-top: 150px">
                    @if (session('berhasil'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <strong>{{ session('berhasil') }}</strong>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                aria-label="Close"></button>
                        </div>
                    @endif
                    @if (session('gagal'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>{{ session('gagal') }}</strong>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                aria-label="Close"></button>
                        </div>
                    @endif
                    <div class="card">
                        <div class="card-header">
                            <h4>Konfirmasi Tiket</h4>
                        </div>
                        <div class="card-body">
                            <p>Nama : {{ $user->name }}</p>
                            <p>Invoice : {{ $cart->invoice }}</p>
                            <p>Status : {{ $cart->status }}</p>
                            <hr>
                            <div class="contaier">
                                @foreach ($harga as $hargaCart)
                                    <div class="card my-2">
                                        <div class="card-body">
                                            <h2>{{ $hargaCart->kategori_harga }}</h2>
                                            <h3>{{ $hargaCart->quantity }}</h3>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="alert alert-success">
                                <strong>{{ $cart->konfirmasi !== null ? 'Sudah Di Konfirmasi' : 'Belum Dikonfirmasi' }}</strong>
                            </div>
                            @if ($cart->konfirmasi === null)
                                <form action="{{ url('/confir/success') }}" method="post">
                                    @csrf
                                    <input type="hidden" value="{{ $cart->invoice }}" name="success">
                                    {{-- <button class="btn btn-primary" type="submit">Konfirmasi</button> --}}
                                    <x-button type="sumbit" color="primary" text="Konfirmasi" />
                                </form>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous">
    </script>
</body>

</html>
