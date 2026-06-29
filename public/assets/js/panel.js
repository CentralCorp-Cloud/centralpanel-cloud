(function () {
    const storageKey = 'theme';

    function getTheme() {
        return localStorage.getItem(storageKey) || 'light';
    }

    function setFileManagerTheme(theme) {
        const light = document.getElementById('file-manager-css');
        const dark = document.getElementById('file-manager-dark-css');

        if (!light || !dark) {
            return;
        }

        light.disabled = theme === 'dark';
        dark.disabled = theme !== 'dark';
    }

    function setTheme(theme) {
        const html = document.documentElement;
        const toggle = document.getElementById('themeToggle');
        const icon = document.getElementById('themeIcon');
        const isDark = theme === 'dark';

        html.setAttribute('data-bs-theme', theme);
        localStorage.setItem(storageKey, theme);
        setFileManagerTheme(theme);

        if (icon) {
            icon.className = isDark ? 'bi bi-sun' : 'bi bi-moon-stars';
        }

        if (toggle) {
            const label = isDark
                ? (toggle.dataset.themeLightLabel || 'Light theme')
                : (toggle.dataset.themeDarkLabel || 'Dark theme');
            toggle.setAttribute('aria-label', label);
            toggle.setAttribute('title', label);
            toggle.setAttribute('data-bs-original-title', label);

            const tooltip = window.bootstrap ? bootstrap.Tooltip.getInstance(toggle) : null;
            if (tooltip) {
                tooltip.setContent({ '.tooltip-inner': label });
            }
        }
    }

    function initTooltips() {
        if (!window.bootstrap) {
            return;
        }

        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
            bootstrap.Tooltip.getOrCreateInstance(el);
        });
    }

    function initConfirmableForms() {
        document.querySelectorAll('[data-confirm]').forEach((element) => {
            element.addEventListener('click', (event) => {
                const message = element.getAttribute('data-confirm');
                if (message && !window.confirm(message)) {
                    event.preventDefault();
                    event.stopPropagation();
                }
            });
        });

        document.querySelectorAll('form[data-loading-label]').forEach((form) => {
            form.addEventListener('submit', () => {
                const button = form.querySelector('[type="submit"]');
                if (!button) {
                    return;
                }

                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>' + form.dataset.loadingLabel;
            });
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        setTheme(getTheme());
        initTooltips();
        initConfirmableForms();

        const toggle = document.getElementById('themeToggle');
        if (toggle) {
            toggle.addEventListener('click', () => {
                const current = document.documentElement.getAttribute('data-bs-theme') || getTheme();
                setTheme(current === 'dark' ? 'light' : 'dark');
                const tooltip = window.bootstrap ? bootstrap.Tooltip.getInstance(toggle) : null;
                if (tooltip) {
                    tooltip.hide();
                }
            });
        }
    });
})();
