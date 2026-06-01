(function () {
    var fileInput = document.getElementById('cv-avatar-file');
    var preview = document.getElementById('cv-avatar-preview');
    var pathInput = document.getElementById('cv-avatar-path');
    var removeFlag = document.getElementById('cv-remove-avatar');
    var removeBtn = document.getElementById('cv-avatar-remove-btn');
    var placeholder = 'https://ui-avatars.com/api/?name=CV&size=140&background=e9ecef&color=6c757d';

    if (!fileInput || !preview) {
        return;
    }

    fileInput.addEventListener('change', function () {
        var file = fileInput.files && fileInput.files[0];
        if (!file) {
            return;
        }
        if (removeFlag) {
            removeFlag.value = '0';
        }
        var reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            if (removeBtn) {
                removeBtn.disabled = false;
            }
        };
        reader.readAsDataURL(file);
    });

    if (removeBtn) {
        removeBtn.addEventListener('click', function () {
            if (removeFlag) {
                removeFlag.value = '1';
            }
            if (pathInput) {
                pathInput.value = '';
            }
            preview.src = placeholder;
            fileInput.value = '';
            removeBtn.disabled = true;
        });
    }
})();
