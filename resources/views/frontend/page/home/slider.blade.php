<div class="fugu--slider-section" >
    <style>
        .slick-track::before{
           max-width: 700px !important;
           min-width: 367px !important;
        }
       
    </style>
    <div class="container"  >
        {{-- <div class="fugu--section-title">
            <div class="fugu--default-content content-sm" >
                <h2 >Beli Tiket Konser</h2>
                <p>Dengan pembayaran online lebih gampang</p>
            </div>
        </div> --}}
        <div class="fugu--slider-one ">
            
            @foreach ($slide as $slider)
            <a href="{{ $slider->url }}">
                <img src="{{ asset('storage/slide/' . $slider->gambar) }}" class="img-thumbnail img" alt="">
            </a>
        @endforeach
            
        </div>
    </div>
    <div class="fugu--shape1">
        {{-- <img src="assets/images/shape2/shape1.png" alt=""> --}}
    </div>
</div>