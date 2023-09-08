@extends('api.app')
@section('content')
    <div class="text-center">
        <div class="card">
            <div class="card-body">
                <p>Hi, {{ $name }}</p>
                <br>
                <p>Barcode dan kode ini bersifat privasi, jangan beritahu/berikan kepada orang lain.</p>
                <p>Tunjukan barcode/kode kepada panitia untuk konfirmasi kehadiran</p>
                <form action="{{ url('/generate-barcode/') }}" method="post">
                    <input type="hidden" name="barcode" value="{{ $barcode }}">
                    {{-- <button class="btn btn-primary">Download Barcode</button> --}}
                    <x-button type="primary" text="Download Barcode" />
                </form>
                <br>
                {{-- {!!  QrCode::size(150)->generate($barcode) !!} --}}
                <br>
                <p>Kode Barcode</p>
                <h4>{{ $cart }}</h4>
            </div>
        </div>
    </div>
@endsection
