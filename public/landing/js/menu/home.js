// Slider
const slider = document.getElementById("homepage-slider");

if (slider) {
    const track = document.getElementById("track");
    const dotsEl = document.getElementById("dots");
    const prevBtn = document.getElementById("prev");
    const nextBtn = document.getElementById("next");
    const viewport = slider.querySelector(".slider-viewport");
    const originalSlides = Array.from(track.querySelectorAll(".slide"));
    const originalCount = originalSlides.length;
    const autoplayDelay = 4500;
    const transitionValue = "transform 0.65s cubic-bezier(0.4, 0, 0.2, 1)";
    let cloneCount = 0;
    let currentIndex = 0;
    let startX = 0;
    let resizeFrame = 0;
    let timer = null;
    let slides = [];

    const getGap = () => {
        const styles = window.getComputedStyle(track);
        return parseFloat(styles.gap || styles.columnGap || "0");
    };

    const getCloneCount = () => {
        if (originalCount <= 1) {
            return 0;
        }

        return window.innerWidth >= 1024 ? Math.min(2, originalCount) : 1;
    };

    const getRealIndex = (index = currentIndex) => {
        if (!originalCount) {
            return 0;
        }

        return ((index - cloneCount) % originalCount + originalCount) % originalCount;
    };

    const updateDots = () => {
        dotsEl?.querySelectorAll(".sl-dot").forEach((dot, index) => {
            dot.classList.toggle("active", index === getRealIndex());
        });
    };

    const getStep = () => {
        const activeSlide = slides[currentIndex] || slides[cloneCount] || originalSlides[0];
        return activeSlide ? activeSlide.getBoundingClientRect().width + getGap() : 0;
    };

    const setTransform = (animate = true) => {
        track.style.transition = animate ? transitionValue : "none";
        track.style.transform = `translate3d(-${currentIndex * getStep()}px, 0, 0)`;
    };

    const cloneSlide = (slide) => {
        const clone = slide.cloneNode(true);
        clone.dataset.clone = "true";
        return clone;
    };

    const buildDots = () => {
        if (!dotsEl) {
            return;
        }

        dotsEl.innerHTML = "";
        originalSlides.forEach((_, index) => {
            const dot = document.createElement("button");
            dot.className = "sl-dot";
            dot.type = "button";
            dot.setAttribute("aria-label", `Ke banner ${index + 1}`);
            dot.addEventListener("click", () => goTo(index));
            dotsEl.appendChild(dot);
        });
    };

    const rebuildTrack = (targetIndex = 0) => {
        cloneCount = getCloneCount();
        track.innerHTML = "";

        if (cloneCount > 0) {
            originalSlides
                .slice(-cloneCount)
                .map(cloneSlide)
                .forEach((slide) => track.appendChild(slide));
        }

        originalSlides.forEach((slide) => track.appendChild(slide));

        if (cloneCount > 0) {
            originalSlides
                .slice(0, cloneCount)
                .map(cloneSlide)
                .forEach((slide) => track.appendChild(slide));
        }

        slides = Array.from(track.children);
        currentIndex = targetIndex + cloneCount;
        setTransform(false);
        requestAnimationFrame(() => {
            track.style.transition = transitionValue;
        });
        updateDots();
    };

    function stepTo(index) {
        currentIndex = index;
        setTransform(true);
        updateDots();
    }

    function restartAutoplay() {
        if (timer) {
            clearInterval(timer);
        }

        if (originalCount <= 1) {
            return;
        }

        timer = window.setInterval(() => {
            stepTo(currentIndex + 1);
        }, autoplayDelay);
    }

    function goTo(index) {
        stepTo(index + cloneCount);
        restartAutoplay();
    }

    buildDots();
    rebuildTrack();

    if (originalCount <= 1) {
        prevBtn?.setAttribute("hidden", "hidden");
        nextBtn?.setAttribute("hidden", "hidden");
        dotsEl?.setAttribute("hidden", "hidden");
    }

    prevBtn?.addEventListener("click", () => {
        stepTo(currentIndex - 1);
        restartAutoplay();
    });

    nextBtn?.addEventListener("click", () => {
        stepTo(currentIndex + 1);
        restartAutoplay();
    });

    track.addEventListener("transitionend", () => {
        if (currentIndex >= originalCount + cloneCount) {
            currentIndex -= originalCount;
            setTransform(false);
        } else if (currentIndex < cloneCount) {
            currentIndex += originalCount;
            setTransform(false);
        }
    });

    viewport?.addEventListener("mouseenter", () => timer && clearInterval(timer));
    viewport?.addEventListener("mouseleave", restartAutoplay);

    viewport?.addEventListener(
        "touchstart",
        (event) => {
            startX = event.touches[0].clientX;
        },
        { passive: true },
    );

    viewport?.addEventListener("touchend", (event) => {
        const diff = startX - event.changedTouches[0].clientX;
        if (Math.abs(diff) > 40) {
            stepTo(currentIndex + (diff > 0 ? 1 : -1));
            restartAutoplay();
        }
    });

    window.addEventListener("resize", () => {
        const activeIndex = getRealIndex();
        window.cancelAnimationFrame(resizeFrame);
        resizeFrame = window.requestAnimationFrame(() => rebuildTrack(activeIndex));
    });

    window.addEventListener("load", () => setTransform(false));
    restartAutoplay();
}

// Scroll reveal
const obs = new IntersectionObserver(
    (entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add("in");
            }
        });
    },
    {
        threshold: 0.08,
    },
);
document.querySelectorAll(".reveal").forEach((el) => obs.observe(el));
