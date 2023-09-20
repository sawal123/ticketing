<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">

        <style>
            body{
  background: #e3e3e3;
}
.container{
  margin: 0 200px 0 200px;
  background: white;
  border:1px solid black;
  border-radius: 10px;
  text-align:center
}

.button:hover{
  background: black;
}
.button{
  margin-top:20px;
  text-decoration:none;
  padding:20px 30px 20px 30px;
  background: #4018a4;
  border: none;
  border-radius:5px;
  color:white;
  width:150px;
  height:50px;
  font-size:14px;
}
p, h2{
  padding:0px;
}
.margin{
  height:20px;
}
        </style>

        
</head>


<body>
    <div class="container">
        <h3>Hi, {{ $name }}</h3>
        <hr>
        <div class="paragrap">
            <p>Barcode dan kode ini bersifat privasi, jangan beritahu/berikan kepada orang lain.
                <br>
                Tunjukan barcode/kode kepada panitia untuk konfirmasi kehadiran
            </p>
        </div>
        <div class="margin"></div>
        <a href="{{url('/generate-barcode/'. $barcode)}}" class="button">
            Tunjukan Barcode
          </a>
        <div class="margin"></div>
        <br>

        <p>Nomor Invoice</p>
        <h2>{{ $cart }}</h2>

        <p>
            powerdBy : GOTIK
        </p>

        <div class="margin"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous">
    </script>
</body>

</html>
