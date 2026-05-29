(function () {
    function normalize(str) {
        return (str || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function initLocationPicker(root) {
        const placeholder = root.dataset.placeholder || '-- Chọn khu vực --';
        const toggle = root.querySelector('.location-picker-toggle');
        const labelEl = root.querySelector('.location-picker-label');
        const panel = root.querySelector('.location-picker-panel');
        const search = root.querySelector('.location-picker-search');
        const hidden = root.querySelector('input[type="hidden"]');
        const emptyMsg = root.querySelector('.location-picker-empty');
        const options = Array.from(root.querySelectorAll('.location-picker-option'));

        function setLabel(text, isPlaceholder) {
            labelEl.textContent = text;
            labelEl.classList.toggle('is-placeholder', !!isPlaceholder);
        }

        function openPanel() {
            panel.hidden = false;
            root.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
            search.value = '';
            filterList();
            setTimeout(function () {
                search.focus();
            }, 0);
        }

        function closePanel() {
            panel.hidden = true;
            root.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
            search.value = '';
            filterList();
        }

        function setActiveOption(btn) {
            options.forEach(function (o) {
                o.classList.toggle('is-active', o === btn);
            });
        }

        function filterList() {
            const q = normalize(search.value.trim());
            let visible = 0;
            options.forEach(function (btn) {
                const name = normalize(btn.dataset.name || btn.textContent);
                const show = q === '' || name.indexOf(q) !== -1;
                btn.classList.toggle('is-hidden', !show);
                if (show) visible++;
            });
            if (emptyMsg) {
                emptyMsg.classList.toggle('d-none', visible > 0);
            }
        }

        function selectOption(btn) {
            hidden.value = btn.dataset.id;
            setLabel(btn.dataset.name || btn.textContent.trim(), false);
            setActiveOption(btn);
            root.classList.remove('is-invalid');
            hidden.setCustomValidity('');
            closePanel();
        }

        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (panel.hidden) {
                openPanel();
            } else {
                closePanel();
            }
        });

        panel.addEventListener('click', function (e) {
            e.stopPropagation();
        });

        options.forEach(function (btn) {
            btn.addEventListener('click', function () {
                selectOption(btn);
            });
        });

        search.addEventListener('input', filterList);

        search.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                closePanel();
                toggle.focus();
            }
        });

        document.addEventListener('click', function (e) {
            if (!root.contains(e.target)) {
                closePanel();
            }
        });

        const form = root.closest('form');
        if (form) {
            form.addEventListener('submit', function (e) {
                if (!hidden.value) {
                    e.preventDefault();
                    root.classList.add('is-invalid');
                    hidden.setCustomValidity('Vui lòng chọn địa điểm làm việc.');
                    hidden.reportValidity();
                    toggle.focus();
                }
            });
        }

        if (!hidden.value) {
            setLabel(placeholder, true);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-location-picker]').forEach(initLocationPicker);
    });
})();
