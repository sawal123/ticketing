<!DOCTYPE html>
<html>

<head>
    <title>Barcode Generator</title>
</head>

<body>
    <h1>Barcode Generator</h1>
    <div class="visible-print text-center">
        {{-- {!! QrCode::size(100)->generate('https://nongkingopi.com') !!} --}}
        {{ $barcodeData }}
        <p>Scan me to return to the original page.</p>
    </div>
</body>

</html>
