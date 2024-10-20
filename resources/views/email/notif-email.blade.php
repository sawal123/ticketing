<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>

        <style>
            body{
  background: #e3e3e3;
}
.container{
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
            <strong>Terimakasih telah membeli tiket {{$event}} melalui GOTIK</strong>
            <p>Barcode dan kode ini bersifat privasi, jangan beritahu/berikan kepada orang lain.
                <br>
                Tunjukan barcode/kode kepada panitia untuk konfirmasi kehadiran.
            </p>
            <p>Tekan tombol dibawah untuk melihat detail tiket dan barcode anda!</p>
        </div>
        <div class="margin"></div>
        <a href="{{url('/generate-barcode/'. $barcode)}}" style="  color:white;" class="button">
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


</body>

</html>
