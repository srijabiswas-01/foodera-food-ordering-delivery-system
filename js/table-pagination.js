document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('table[data-page-size]:not([data-no-pagination])').forEach(function (table) {
        var body = table.tBodies[0];
        if (!body || table.dataset.paginationReady === 'true') return;
        var rows = Array.from(body.rows);
        var pageSize = Math.max(1, Number(table.dataset.pageSize) || 10);
        if (rows.length <= pageSize) return;
        table.dataset.paginationReady = 'true';
        var pager = document.createElement('nav');
        pager.className = 'public-table-pagination';
        pager.setAttribute('aria-label', 'Table pagination');
        var wrapper = table.closest('.table-responsive, .public-table-wrap, .table-wrap');
        if (wrapper) wrapper.after(pager); else table.after(pager);
        var page = 1;
        function render() {
            var pages = Math.ceil(rows.length / pageSize);
            rows.forEach(function (row, index) { row.hidden = index < (page - 1) * pageSize || index >= page * pageSize; });
            pager.innerHTML = '<button type="button" data-action="previous"' + (page === 1 ? ' disabled' : '') + '><i class="bi bi-chevron-left" aria-hidden="true"></i><span>Previous</span></button><span>Page ' + page + ' of ' + pages + '</span><button type="button" data-action="next"' + (page === pages ? ' disabled' : '') + '><span>Next</span><i class="bi bi-chevron-right" aria-hidden="true"></i></button>';
        }
        pager.addEventListener('click', function (event) {
            var button = event.target.closest('button');
            if (!button || button.disabled) return;
            page += button.dataset.action === 'previous' ? -1 : 1;
            render();
        });
        render();
    });
});
