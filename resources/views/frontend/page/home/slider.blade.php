{{-- <div class="fugu--slider-section">
    <style>
        .slick-track::before {
            max-width: 700px !important;
            min-width: 367px !important;
        }
    </style>
    <div class="container">
        
        <div class="fugu--slider-one ">

            @foreach ($slide as $slider)
                <a href="{{ $slider->url }}">
                    <img src="{{ asset('storage/slide/' . $slider->gambar) }}" class="img-thumbnail img" alt="">
                </a>
            @endforeach

        </div>h
    </div>
    <div class="fugu--shape1">
       
    </div>
</div> --}}

<div class="slider-section" style="aspect-ratio: 21/10.5 !important;">
    <div class="slider-track" id="track">

        @foreach ($slide as $slider)
            <div class="slide">
                <div class=" sp-1">
                    <img class="slide-img " style="object-fit: cover !important;"
                        src="{{ asset('storage/slide/' . $slider->gambar) }}" alt="Slide">
                </div>
                <!-- CTA — muncul karena data-url ada -->
            </div>
        @endforeach

    </div>

    <!-- Arrows -->
    <button class="sl-arrow prev" id="prev" aria-label="Sebelumnya">
        <svg viewBox="0 0 24 24" fill="none">
            <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" />
        </svg>
    </button>
    <button class="sl-arrow next" id="next" aria-label="Berikutnya">
        <svg viewBox="0 0 24 24" fill="none">
            <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" />
        </svg>
    </button>

    <!-- Dots -->
    <div class="sl-dots" id="dots"></div>
</div>
