document.querySelectorAll('.admin-form').forEach(function (form) {
    form.querySelectorAll('input[type="password"]').forEach(function (input) {
        const wrapper = document.createElement('div');
        wrapper.className = 'admin-password-field';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'admin-password-toggle';
        button.setAttribute('aria-label', 'Show password');
        button.setAttribute('aria-pressed', 'false');
        button.title = 'Show password';
        button.innerHTML = '<i class="bi bi-eye" aria-hidden="true"></i>';
        wrapper.appendChild(button);
        button.addEventListener('click', function () {
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            button.setAttribute('aria-pressed', String(show));
            button.title = show ? 'Hide password' : 'Show password';
            button.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    });

    function validate(field) {
        const label = field.closest('label');
        let message = label?.querySelector('.field-error');
        field.setCustomValidity('');
        if (field.dataset.match) {
            const other = form.elements.namedItem(field.dataset.match);
            if (other && field.value !== other.value) field.setCustomValidity('Passwords do not match.');
        }
        if (field.type === 'file' && field.files.length) {
            const file = field.files[0];
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) field.setCustomValidity('Use a JPG, PNG, or WebP image.');
            else if (file.size > 4000000) field.setCustomValidity('Image must be under 4 MB.');
        }
        if (!message && label && !field.validity.valid) {
            message = document.createElement('small');
            message.className = 'field-error';
            message.setAttribute('aria-live', 'polite');
            label.appendChild(message);
        }
        if (message) message.textContent = field.validity.valid ? '' : field.validationMessage;
        field.setAttribute('aria-invalid', field.validity.valid ? 'false' : 'true');
        return field.validity.valid;
    }

    form.querySelectorAll('input, select, textarea').forEach(function (field) {
        if (field.type === 'hidden') return;
        field.addEventListener('input', function () {
            validate(field);
            form.querySelectorAll('[data-match="' + field.name + '"]').forEach(validate);
        });
        field.addEventListener('change', function () { validate(field); });
    });
    form.addEventListener('submit', function (event) {
        const invalid = Array.from(form.querySelectorAll('input:not([type="hidden"]), select, textarea')).find(function (field) { return !validate(field); });
        if (invalid) {
            event.preventDefault();
            invalid.focus();
            invalid.reportValidity();
        }
    });
});

document.querySelectorAll('input[type="file"][accept*="image"]').forEach(function (input) {
    let preview = input.dataset.previewTarget ? document.getElementById(input.dataset.previewTarget) : null;
    if (!preview) {
        preview = document.createElement('img');
        preview.className = 'admin-image-preview';
        preview.alt = 'Image preview';
        preview.hidden = true;
        input.closest('label').after(preview);
    }
    let url = null;
    input.addEventListener('change', function () {
        if (url) URL.revokeObjectURL(url);
        if (!input.files.length) return;
        url = URL.createObjectURL(input.files[0]);
        preview.src = url;
        preview.hidden = false;
        const placeholder = input.closest('form')?.querySelector('[data-preview-placeholder]');
        if (placeholder) placeholder.hidden = true;
    });
});

document.querySelectorAll('[data-preview-text]').forEach(function (input) {
    const target = document.getElementById(input.dataset.previewText);
    if (!target) return;
    const update = function () { target.textContent = input.value.trim() || target.dataset.placeholder || ' '; };
    target.dataset.placeholder = target.textContent;
    input.addEventListener('input', update);
    input.addEventListener('change', update);
});

document.querySelectorAll('[data-preview-price]').forEach(function (input) {
    const target = document.getElementById(input.dataset.previewPrice);
    if (!target) return;
    const update = function () { target.textContent = 'Rs ' + (input.value.trim() || '0'); };
    input.addEventListener('input', update);
    input.addEventListener('change', update);
});

document.querySelectorAll('[data-category-combobox]').forEach(function (combobox) {
    const input = combobox.querySelector('input[role="combobox"]');
    const value = combobox.querySelector('input[type="hidden"]');
    const list = combobox.querySelector('[role="listbox"]');
    const clear = combobox.querySelector('.admin-search-select-clear');
    const options = Array.from(combobox.querySelectorAll('[data-category-option]'));
    const empty = combobox.querySelector('.admin-search-select-empty');
    const preview = document.getElementById('menu-preview-category');
    let focusedIndex = -1;

    function visibleOptions() { return options.filter(function (option) { return !option.hidden; }); }
    function close() { list.hidden = true; input.setAttribute('aria-expanded', 'false'); focusedIndex = -1; options.forEach(function (option) { option.classList.remove('is-focused'); }); }
    function open() { list.hidden = false; input.setAttribute('aria-expanded', 'true'); }
    function filter() {
        const query = input.value.trim().toLowerCase();
        let count = 0;
        options.forEach(function (option) {
            const show = option.textContent.toLowerCase().includes(query);
            option.hidden = !show;
            if (show) count += 1;
        });
        empty.hidden = count > 0;
        focusedIndex = -1;
        open();
    }
    function choose(option) {
        value.value = option.dataset.value;
        input.value = option.dataset.value;
        options.forEach(function (item) { item.setAttribute('aria-selected', String(item === option)); });
        clear.hidden = false;
        if (preview) preview.textContent = option.dataset.value;
        input.setCustomValidity('');
        input.dispatchEvent(new Event('change', { bubbles: true }));
        close();
    }
    input.addEventListener('focus', filter);
    input.addEventListener('input', function () {
        value.value = '';
        clear.hidden = input.value === '';
        input.setCustomValidity(input.value === '' ? '' : 'Select a category from the list.');
        input.dispatchEvent(new Event('change', { bubbles: true }));
        filter();
    });
    input.addEventListener('keydown', function (event) {
        const visible = visibleOptions();
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            open();
            focusedIndex = event.key === 'ArrowDown' ? Math.min(focusedIndex + 1, visible.length - 1) : Math.max(focusedIndex - 1, 0);
            options.forEach(function (option) { option.classList.remove('is-focused'); });
            if (visible[focusedIndex]) { visible[focusedIndex].classList.add('is-focused'); visible[focusedIndex].scrollIntoView({ block: 'nearest' }); }
        } else if (event.key === 'Enter' && visible[focusedIndex]) {
            event.preventDefault();
            choose(visible[focusedIndex]);
        } else if (event.key === 'Escape') close();
    });
    options.forEach(function (option) { option.addEventListener('click', function () { choose(option); }); });
    clear.addEventListener('click', function () {
        value.value = '';
        input.value = '';
        input.setCustomValidity('');
        input.dispatchEvent(new Event('change', { bubbles: true }));
        clear.hidden = true;
        options.forEach(function (option) { option.setAttribute('aria-selected', 'false'); });
        if (preview) preview.textContent = preview.dataset.placeholder || 'Category';
        input.focus();
        filter();
    });
    document.addEventListener('click', function (event) { if (!combobox.contains(event.target)) close(); });
});

document.querySelectorAll('input[name="image_path"]').forEach(function (input) {
    const label = input.closest('label');
    if (label) label.remove();
});

document.querySelectorAll('input[name="image_file"]').forEach(function (input) {
    if (!input.closest('.admin-blog-layout')) return;
    const label = input.closest('label');
    const isNewPost = !(input.form && input.form.querySelector('input[name="post_id"]')?.value);
    input.required = isNewPost;
    if (label) {
        const text = Array.from(label.childNodes).find(function (node) { return node.nodeType === Node.TEXT_NODE; });
        if (text) text.nodeValue = isNewPost ? 'Post image' : 'Replace image (optional)';
    }
});
