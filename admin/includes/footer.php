</main><footer class="admin-footer"><span>&copy; <?php echo date('Y'); ?> Foodera Admin</span><a href="../home.php">View storefront <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a></footer>
<script src="js/forms.js"></script>
<script>
(function () {
    function paginationMarkup(pages, page) {
        if (pages <= 1) return '';
        var numbers = Array.from({ length: pages }, function (_, index) { return index + 1; }).filter(function (number) {
            return number === 1 || number === pages || Math.abs(number - page) <= 1;
        });
        var output = '<button type="button" class="admin-pagination-nav" data-page-action="previous"' + (page === 1 ? ' disabled' : '') + '><i class="bi bi-chevron-left" aria-hidden="true"></i><span>Previous</span></button>';
        numbers.forEach(function (number, index) {
            var previous = numbers[index - 1];
            if (previous && number - previous > 1) output += '<span class="admin-pagination-gap" aria-hidden="true">...</span>';
            output += '<button type="button" data-page="' + number + '"' + (number === page ? ' class="active" aria-current="page"' : '') + '>' + number + '</button>';
        });
        return output + '<button type="button" class="admin-pagination-nav" data-page-action="next"' + (page === pages ? ' disabled' : '') + '><span>Next</span><i class="bi bi-chevron-right" aria-hidden="true"></i></button>';
    }

    document.querySelectorAll('.admin-table').forEach(function (table) {
        var body = table.tBodies[0];
        var wrap = table.closest('.admin-table-wrap');
        if (!body || !wrap || table.id === 'admin-offer-table' || body.id === 'admin-menu-item-list' || table.dataset.paginationManual !== undefined || table.dataset.enhanced) return;
        var rows = Array.from(body.rows);
        if (!rows.length) return;
        table.dataset.enhanced = 'true';
        var tools = document.createElement('div');
        tools.className = 'admin-list-tools admin-table-tools';
        tools.innerHTML = '<label><i class="bi bi-search" aria-hidden="true"></i><input type="search" placeholder="Search this list" aria-label="Search this list" autocomplete="off"></label><button type="button" class="admin-btn secondary" hidden><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reset</button>';
        var search = tools.querySelector('input');
        var reset = tools.querySelector('button');
        var badges = Array.from(new Set(rows.map(function (row) { var badge = row.querySelector('.admin-badge,.admin-status-toggle,.admin-category-badge'); return badge ? badge.textContent.trim() : ''; }).filter(Boolean)));
        var filter = null;
        if (badges.length > 1 && badges.length <= 6) {
            filter = document.createElement('label');
            filter.className = 'admin-select-tool';
            filter.innerHTML = '<i class="bi bi-funnel" aria-hidden="true"></i><select aria-label="Filter this list"><option value="all">All statuses</option>' + badges.map(function (label) { return '<option value="' + label.replace(/"/g, '&quot;') + '">' + label + '</option>'; }).join('') + '</select>';
            tools.insertBefore(filter, reset);
        }
        wrap.parentNode.insertBefore(tools, wrap);
        var empty = document.createElement('p');
        empty.className = 'admin-category-empty';
        empty.hidden = true;
        empty.innerHTML = '<i class="bi bi-search" aria-hidden="true"></i> No records match this search or filter.';
        wrap.after(empty);
        var pager = document.createElement('nav');
        pager.className = 'admin-pagination';
        pager.setAttribute('aria-label', 'Table pagination');
        empty.after(pager);
        var page = 1;
        var pageSize = Math.max(1, Number(table.dataset.pageSize) || 10);
        function render() {
            var query = search.value.trim().toLowerCase();
            var status = filter ? filter.querySelector('select').value : 'all';
            var visible = rows.filter(function (row) {
                var badge = row.querySelector('.admin-badge,.admin-status-toggle,.admin-category-badge');
                var rowStatus = badge ? badge.textContent.trim() : '';
                return row.dataset.paginationMatch !== 'false' && row.textContent.toLowerCase().includes(query) && (status === 'all' || rowStatus === status);
            });
            var pages = Math.max(1, Math.ceil(visible.length / pageSize));
            if (page > pages) page = pages;
            rows.forEach(function (row) { row.hidden = true; });
            visible.slice((page - 1) * pageSize, page * pageSize).forEach(function (row) { row.hidden = false; });
            empty.hidden = visible.length > 0;
            reset.hidden = query === '' && status === 'all';
            pager.hidden = pages <= 1;
            pager.innerHTML = paginationMarkup(pages, page);
        }
        search.addEventListener('input', function () { page = 1; render(); });
        table.addEventListener('foodera-pagination-filter', function () { page = 1; render(); });
        if (filter) filter.querySelector('select').addEventListener('change', function () { page = 1; render(); });
        reset.addEventListener('click', function () { search.value = ''; if (filter) filter.querySelector('select').value = 'all'; page = 1; render(); search.focus(); });
        pager.addEventListener('click', function (event) {
            var button = event.target.closest('button');
            if (!button || button.disabled) return;
            if (button.dataset.pageAction === 'previous') page--;
            else if (button.dataset.pageAction === 'next') page++;
            else if (button.dataset.page) page = Number(button.dataset.page);
            render();
        });
        render();
    });

    function addNavigation(pager) {
        if (pager.querySelector('[data-page-action],[data-page-proxy]')) return;
        var pages = Array.from(pager.querySelectorAll('[data-page]'));
        if (pages.length < 2) return;
        var active = pager.querySelector('[aria-current="page"]');
        var current = active ? Number(active.dataset.page) : 1;
        var previous = document.createElement('button');
        previous.type = 'button'; previous.className = 'admin-pagination-nav'; previous.dataset.pageProxy = 'previous'; previous.disabled = current === 1;
        previous.innerHTML = '<i class="bi bi-chevron-left" aria-hidden="true"></i><span>Previous</span>';
        var next = document.createElement('button');
        next.type = 'button'; next.className = 'admin-pagination-nav'; next.dataset.pageProxy = 'next'; next.disabled = current === pages.length;
        next.innerHTML = '<span>Next</span><i class="bi bi-chevron-right" aria-hidden="true"></i>';
        pager.prepend(previous); pager.append(next);
    }
    document.querySelectorAll('.admin-pagination').forEach(addNavigation);
    document.addEventListener('click', function (event) {
        var proxy = event.target.closest('[data-page-proxy]');
        if (!proxy || proxy.disabled) return;
        var pager = proxy.closest('.admin-pagination');
        var active = pager.querySelector('[aria-current="page"]');
        var current = active ? Number(active.dataset.page) : 1;
        var target = proxy.dataset.pageProxy === 'previous' ? current - 1 : current + 1;
        var pageButton = pager.querySelector('[data-page="' + target + '"]');
        if (pageButton) pageButton.click();
    });
    new MutationObserver(function () { document.querySelectorAll('.admin-pagination').forEach(addNavigation); }).observe(document.body, { childList: true, subtree: true });
}());
</script></body></html>
