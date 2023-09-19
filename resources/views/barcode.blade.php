<!DOCTYPE html>
<html>

<head>
    <title>Barcode Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
</head>

<body>
   <div class="container">
    <div class="row d-flex justify-content-center align-items-center" >
        <div class="col-lg-4 col-md-12">
            <div class="visible-print text-center " style="margin-top: 200px" >
               <div class="card" >
                <div class="card-body" >
                    <h1>{{$invoice}}</h1>
                    {{-- {!! QrCode::size(100)->generate('https://nongkingopi.com') !!} --}}
                    {{ $barcodeData }}
                    <p class="mt-3" style="font-size: 30px">Tunjukan Barcode Ke Panitia</p>
                </div>
               </div>
            </div>
        </div>
    </div>
   </div>
</body>

</html>
