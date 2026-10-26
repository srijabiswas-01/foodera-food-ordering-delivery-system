(function () {
    'use strict';

    const selectors = [
        'main > section', '.home-offers', '.about', '.service-section',
        '.food-items', '.offer-card', '.foodera-blog-card',
        '.contact-box', '.contact-form', '.profile-card', '.cart-item',
        '.auth-shell', '.why-choose'
    ];
    const elements = Array.from(document.querySelectorAll(selectors.join(','))).filter(function (element) {
        // The gallery wall is intentionally tall; observing it as one reveal target can
        // leave it permanently transparent on mobile because it never reaches 12% visibility.
        return !element.closest('.carousel-item') && !element.matches('.home-offer-carousel') && !element.closest('.foodera-gallery-page');
    });

    if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        elements.forEach(function (element) { element.classList.add('is-visible'); });
        return;
    }

    elements.forEach(function (element, index) {
        element.setAttribute('data-foodera-reveal', '');
        element.style.setProperty('--foodera-reveal-delay', (index % 4) * 55 + 'ms');
    });

    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -24px' });

    elements.forEach(function (element) { observer.observe(element); });
}());
