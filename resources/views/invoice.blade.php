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

    /* .card{
      position: relative;
      
    } */

    .card-body::before {
        content: "";
        background-image: url('{{ asset('storage/logo/' . $logo[0]->logo) }}');
        opacity: 0.1;
        background-size: cover;
        background-size: 150px;
        background-repeat: repeat;
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 999;
        /* transform: rotate(-45deg); */

    }
</style>

<body>
    <div class="container">
        <div class="row d-flex justify-content-center">
            <div class="col-lg-4 col-md-12 mt-5">
                <div class="card w-lg-50">
                    <div class="card-header d-flex justify-content-between">
                        <h5>Invoice</h5>
                        <h5>{{ $penarikan->uid }}</h5>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">Penarikan</h5>
                        <div class="d-flex justify-content-between">
                            <p>Nama :</p>
                            <p>{{ $penarikan->name }}</p>
                        </div>
                        <div class="d-flex justify-content-between">
                            <p>Amount :</p>
                            <p class="fw-bold">Rp{{ number_format($penarikan->amount, 0, ',', '.') }}</p>
                        </div>
                        <div class="d-flex justify-content-between">
                            <p>Status :</p>
                            <p class="fw-bold">{{ $penarikan->status }}</p>
                        </div>
                        <hr>
                        <h4>Pengirim</h4>

                        <div class="d-flex justify-content-between">
                            <p>Nama :</p>
                            <p>{{ $bankPengirim[0]->nama }}</p>
                        </div>
                        <div class="d-flex justify-content-between">
                            <p>Bank :</p>
                            <p>{{ $bankPengirim[0]->bank }}</p>
                        </div>
                        <div class="d-flex justify-content-between">
                            <p>Nomor Rek :</p>
                            <p>{{ $bankPengirim[0]->norek }}</p>
                        </div>

                        <hr>
                        <h4>Penerima</h4>
                        <div class="d-flex justify-content-between">
                            <p>Nama :</p>
                            <p>{{ $bankPenyewa->nama }}</p>
                        </div>
                        <div class="d-flex justify-content-between">
                            <p>Bank :</p>
                            <p>{{ $bankPenyewa->bank }}</p>
                        </div>
                        <div class="d-flex justify-content-between">
                            <p>Nomor Rek :</p>
                            <p>{{ $bankPenyewa->norek }}</p>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <button type="button" id="downloadButton" class="btn btn-success w-50 mx-2">Download</button>
                    <button id="printButton" class="btn btn-primary w-50 mx-2">Print</button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/js/html2canvas.min.js') }}"></script>
    <script>
       document.getElementById("printButton").addEventListener("click", function() {
        var elementToCapture = document.querySelector(".card");

        // Buat gambar canvas dari elemen HTML menggunakan html2canvas
        html2canvas(elementToCapture).then(function(canvas) {
            var canvasDataUrl = canvas.toDataURL("image/png");

            // Buat elemen gambar <img> untuk gambar canvas
            var img = new Image();
            img.src = canvasDataUrl;

            // Buka jendela pencetakan
            var printWindow = window.open('', '', 'width=600,height=600');
            printWindow.document.open();
            printWindow.document.write('<html><head><title>Cetak</title></head><body>');
            printWindow.document.write('<img src="' + img.src + '" onload="window.print();window.close();" />');
            printWindow.document.write('</body></html>');
            printWindow.document.close();
        });
    });
    
        document.getElementById("downloadButton").addEventListener("click", function() {

            // Temukan elemen yang akan diubah menjadi gambar canvas
            var elementToCapture = document.querySelector(".card"); // Ganti dengan kelas yang sesuai

            // Buat gambar canvas dari elemen HTML menggunakan html2canvas
            html2canvas(elementToCapture).then(function(canvas) {
                // Mengonversi gambar canvas menjadi URL data
                var canvasDataUrl = canvas.toDataURL("image/png");
                console.log(canvasDataUrl)
                // Buat elemen anchor untuk tautan unduh
                var a = document.createElement("a");
                a.href = canvasDataUrl;
                a.download = "Invoice.png"; // Nama file yang akan diunduh
                a.click();
            });
        });
    </script>




</body>

</html>
