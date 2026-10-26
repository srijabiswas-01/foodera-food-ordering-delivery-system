document.addEventListener('DOMContentLoaded', function () {
    var carouselElement = document.getElementById('homeOfferCarousel');
    if (!carouselElement || !window.bootstrap || !window.bootstrap.Carousel) return;

    var slides = carouselElement.querySelectorAll('.carousel-item');
    if (slides.length < 2) return;

    var carousel = window.bootstrap.Carousel.getOrCreateInstance(carouselElement, {
        interval: 5000,
        ride: 'carousel',
        pause: 'hover',
        touch: true,
        wrap: true
    });

    carousel.cycle();
});
