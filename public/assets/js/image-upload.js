document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.image-upload-field').forEach(function (field) {
        var preview = field.querySelector('.image-upload-preview');
        var fileInput = field.querySelector('.image-file-input');
        var deleteFlag = field.querySelector('.image-delete-flag');
        var currentInput = field.querySelector('[name$="_current"]');
        var btnUpload = field.querySelector('.image-btn-upload');
        var btnChange = field.querySelector('.image-btn-change');
        var btnDelete = field.querySelector('.image-btn-delete');

        function showPreview(src) {
            preview.classList.remove('is-empty');
            preview.innerHTML = '<img src="' + src + '" alt="">';
            if (btnUpload) btnUpload.style.display = 'none';
            if (btnChange) btnChange.style.display = '';
            if (btnDelete) btnDelete.style.display = '';
        }

        function clearPreview() {
            preview.classList.add('is-empty');
            preview.innerHTML = '<span class="image-placeholder">Aucune image</span>';
            if (currentInput) currentInput.value = '';
            if (deleteFlag) deleteFlag.value = '1';
            if (fileInput) fileInput.value = '';
            if (btnUpload) btnUpload.style.display = '';
            if (btnChange) btnChange.style.display = 'none';
            if (btnDelete) btnDelete.style.display = 'none';
        }

        if (btnUpload) {
            btnUpload.addEventListener('click', function () {
                fileInput.click();
            });
        }

        if (btnChange) {
            btnChange.addEventListener('click', function () {
                fileInput.click();
            });
        }

        if (btnDelete) {
            btnDelete.addEventListener('click', function () {
                if (confirm('Supprimer cette image ?')) {
                    clearPreview();
                }
            });
        }

        if (fileInput) {
            fileInput.addEventListener('change', function () {
                if (!fileInput.files || !fileInput.files[0]) return;
                var reader = new FileReader();
                reader.onload = function (e) {
                    showPreview(e.target.result);
                    if (deleteFlag) deleteFlag.value = '0';
                };
                reader.readAsDataURL(fileInput.files[0]);
            });
        }
    });
});
