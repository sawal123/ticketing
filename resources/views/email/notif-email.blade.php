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
    <div class="text-center">
        <div class="card">
            <div class="card-body">
                <p>Hi, {{$name}}</p>
                <br>
                <p>Barcode dan kode ini bersifat privasi, jangan beritahu/berikan kepada orang lain.</p>
                <p>Tunjukan barcode/kode kepada panitia untuk konfirmasi kehadiran</p>
                <a href="#" class="btn btn-primary">Download Barcode</a>
                <br>
                <p>Kode Barcode</p>
                <h4>{{$cart}}</h4>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous">
    </script>
</body>

</html>
