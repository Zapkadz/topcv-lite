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

    var sections = [
        ['education-rows', 'educations', 'tpl-education-row'],
        ['experience-rows', 'experiences', 'tpl-experience-row'],
        ['skill-rows', 'skills', 'tpl-skill-row'],
        ['activity-rows', 'activities', 'tpl-activity-row'],
        ['certificate-rows', 'certificates', 'tpl-certificate-row'],
        ['award-rows', 'awards', 'tpl-award-row'],
        ['reference-rows', 'references', 'tpl-reference-row'],
    ];

    sections.forEach(function (cfg) {
        var tpl = document.getElementById(cfg[2]);
        if (tpl) {
            bindRepeatSection(cfg[0], cfg[1], tpl.innerHTML);
        }
    });
})();
