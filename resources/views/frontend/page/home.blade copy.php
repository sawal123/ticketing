@extends('frontend.index')

@section('content')
    {{-- @include('frontend.page.home.heroSection') --}}
    <!-- End fugu-hero-section -->
    {{-- <div class="" style="height: 150px; background-color: #13111A;"></div> --}}


    @include('frontend.page.home.slider')
    <!-- End slider section -->
    @include('frontend.page.home.menu')



    {{-- <div class="fugu-go-top">
        <img src="{{asset('landing/images/svg/arrow-black-right.svg')}}" alt="">
    </div> --}}


    <script>
        // ── Slider ──
        const track = document.getElementById("track");
        const slides = document.querySelectorAll(".slide");
        const dotsEl = document.getElementById("dots");
        let cur = 0;

        // Build dots
        slides.forEach((_, i) => {
            const d = document.createElement("button");
            d.className = "sl-dot" + (i === 0 ? " active" : "");
            d.onclick = () => goTo(i);
            dotsEl.appendChild(d);
        });

        function goTo(n) {
            cur = (n + slides.length) % slides.length;
            track.style.transform = `translateX(-${cur * 100}%)`;
            document
                .querySelectorAll(".sl-dot")
                .forEach((d, i) => d.classList.toggle("active", i === cur));
        }

        document.getElementById("prev").onclick = () => goTo(cur - 1);
        document.getElementById("next").onclick = () => goTo(cur + 1);

        let timer = setInterval(() => goTo(cur + 1), 4500);
        track.addEventListener("mouseenter", () => clearInterval(timer));
        track.addEventListener("mouseleave", () => {
            timer = setInterval(() => goTo(cur + 1), 4500);
        });

        // Touch swipe
        let startX = 0;
        track.addEventListener(
            "touchstart",
            (e) => {
                startX = e.touches[0].clientX;
            }, {
                passive: true,
            },
        );
        track.addEventListener("touchend", (e) => {
            const diff = startX - e.changedTouches[0].clientX;
            if (Math.abs(diff) > 40) goTo(cur + (diff > 0 ? 1 : -1));
        });

        // ── Scroll reveal ──
        const obs = new IntersectionObserver(
            (entries) => {
                entries.forEach((e) => {
                    if (e.isIntersecting) e.target.classList.add("in");
                });
            }, {
                threshold: 0.08,
            },
        );
        document.querySelectorAll(".reveal").forEach((el) => obs.observe(el));
    </script>
@endsection
