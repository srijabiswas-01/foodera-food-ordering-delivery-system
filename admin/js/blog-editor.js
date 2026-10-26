document.addEventListener('DOMContentLoaded', function () {
    var source = document.getElementById('blog-content');
    var editorElement = document.getElementById('blog-content-editor');
    var form = source ? source.closest('form') : null;
    if (!source || !editorElement || !form || typeof Quill === 'undefined') return;

    var csrf = form.querySelector('input[name="csrf"]');
    var quill = new Quill(editorElement, {
        theme: 'snow',
        placeholder: 'Write the story for your customers...',
        modules: {
            toolbar: {
                container: [
                    [{ header: [2, 3, 4, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['blockquote', 'link', 'image'],
                    ['clean']
                ],
                handlers: { image: selectImage }
            }
        }
    });

    if (source.value.trim()) quill.clipboard.dangerouslyPasteHTML(source.value, 'silent');

    function syncContent() {
        source.value = quill.root.innerHTML;
    }

    quill.on('text-change', syncContent);

    function selectImage() {
        var picker = document.createElement('input');
        picker.type = 'file';
        picker.accept = 'image/jpeg,image/png,image/webp';
        picker.addEventListener('change', function () {
            var file = picker.files && picker.files[0];
            if (!file) return;
            if (file.size > 4000000) { window.alert('Choose a JPG, PNG, or WebP image under 4 MB.'); return; }
            var data = new FormData();
            data.append('csrf', csrf ? csrf.value : '');
            data.append('image', file);
            fetch('blog-image-upload.php', { method: 'POST', body: data, credentials: 'same-origin' })
                .then(function (response) { return response.json().then(function (body) { return { ok: response.ok, body: body }; }); })
                .then(function (result) {
                    if (!result.ok || !result.body.ok) throw new Error(result.body.message || 'Could not upload image.');
                    var range = quill.getSelection(true);
                    quill.insertEmbed(range.index, 'image', result.body.url, 'user');
                    quill.setSelection(range.index + 1, 0, 'silent');
                })
                .catch(function (error) { window.alert(error.message || 'Could not upload image.'); });
        });
        picker.click();
    }

    form.addEventListener('submit', function (event) {
        if (!quill.getText().trim()) {
            event.preventDefault();
            source.setCustomValidity('Write the story content before publishing.');
            source.reportValidity();
            return;
        }
        source.setCustomValidity('');
        syncContent();
    });
});
