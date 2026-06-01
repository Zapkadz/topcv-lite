(function () {
    function reindexSection(container, prefix) {
        container.querySelectorAll('.cv-repeat-row').forEach(function (row, index) {
            row.querySelectorAll('[name]').forEach(function (input) {
                var name = input.getAttribute('name');
                if (!name) {
                    return;
                }
                input.setAttribute(
                    'name',
                    name.replace(new RegExp('^' + prefix + '\\[\\d+\\]'), prefix + '[' + index + ']')
                );
            });
        });
    }

    function bindRepeatSection(containerId, prefix, emptyHtml) {
        var container = document.getElementById(containerId);
        if (!container) {
            return;
        }

        document.querySelector('[data-add-row="' + containerId + '"]')?.addEventListener('click', function () {
            var wrap = document.createElement('div');
            wrap.innerHTML = emptyHtml.trim();
            var row = wrap.firstElementChild;
            if (!row) {
                return;
            }
            var index = container.querySelectorAll('.cv-repeat-row').length;
            row.innerHTML = row.innerHTML.replace(/__INDEX__/g, String(index));
            container.appendChild(row);
            bindRemoveButtons(container, prefix);
        });

        bindRemoveButtons(container, prefix);
    }

    function bindRemoveButtons(container, prefix) {
        container.querySelectorAll('.js-remove-row').forEach(function (btn) {
            if (btn.dataset.bound === '1') {
                return;
            }
            btn.dataset.bound = '1';
            btn.addEventListener('click', function () {
                var row = btn.closest('.cv-repeat-row');
                if (!row) {
                    return;
                }
                row.remove();
                reindexSection(container, prefix);
            });
        });
    }

    var educationTemplate = document.getElementById('tpl-education-row');
    var experienceTemplate = document.getElementById('tpl-experience-row');
    var skillTemplate = document.getElementById('tpl-skill-row');

    if (educationTemplate) {
        bindRepeatSection('education-rows', 'educations', educationTemplate.innerHTML);
    }
    if (experienceTemplate) {
        bindRepeatSection('experience-rows', 'experiences', experienceTemplate.innerHTML);
    }
    if (skillTemplate) {
        bindRepeatSection('skill-rows', 'skills', skillTemplate.innerHTML);
    }
})();
