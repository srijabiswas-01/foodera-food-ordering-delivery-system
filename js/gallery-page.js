(function () {
    'use strict';
    const wall = document.querySelector('.foodera-gallery-wall');
    if (!wall) return;

    const tiles = Array.from(wall.querySelectorAll('.foodera-gallery-tile'));
    const filters = Array.from(document.querySelectorAll('[data-gallery-filter]'));
    const search = document.querySelector('[data-gallery-search]');
    const more = document.querySelector('[data-gallery-more]');
    const empty = document.querySelector('[data-gallery-empty]');
    const pageSize = 15;
    let activeFilter = 'all';
    let visibleLimit = pageSize;

    function render() {
        const term = (search ? search.value : '').trim().toLowerCase();
        const matches = tiles.filter(function (tile) {
            const categoryMatches = activeFilter === 'all' || tile.dataset.galleryCategory === activeFilter;
            return categoryMatches && tile.dataset.gallerySearchText.indexOf(term) !== -1;
        });

        tiles.forEach(function (tile) { tile.hidden = true; });
        matches.slice(0, visibleLimit).forEach(function (tile) { tile.hidden = false; });
        if (empty) empty.hidden = matches.length > 0;
        if (more) {
            more.hidden = matches.length <= visibleLimit;
            if (!more.hidden) more.innerHTML = '<i class="bi bi-arrow-repeat" aria-hidden="true"></i> Load ' + Math.min(pageSize, matches.length - visibleLimit) + ' more photos';
        }
    }

    filters.forEach(function (filter) {
        filter.addEventListener('click', function () {
            activeFilter = filter.dataset.galleryFilter;
            visibleLimit = pageSize;
            filters.forEach(function (item) {
                const selected = item === filter;
                item.classList.toggle('is-active', selected);
                item.setAttribute('aria-pressed', selected ? 'true' : 'false');
            });
            render();
        });
    });
    if (search) search.addEventListener('input', function () { visibleLimit = pageSize; render(); });
    if (more) more.addEventListener('click', function () { visibleLimit += pageSize; render(); });
    render();
}());
