export function mountTheme(document, window) {
    const buttons = document.querySelectorAll('[data-theme-toggle]');
    const system = window.matchMedia('(prefers-color-scheme: dark)');
    let preference;
    try { preference = window.localStorage.getItem('kody-theme'); } catch {}
    const explicit = (value) => value === 'light' || value === 'dark';
    const render = () => {
        const dark = explicit(preference) ? preference === 'dark' : system.matches;
        document.documentElement.dataset.theme = dark ? 'dark' : 'light';
        buttons.forEach((button) => {
            button.hidden = false;
            button.setAttribute('aria-pressed', String(dark));
            button.title = dark ? 'Switch to light mode' : 'Switch to dark mode';
            button.setAttribute('aria-label', dark ? 'Light mode' : 'Dark mode');
            const label = button.querySelector?.('[data-theme-label]');
            if (label) label.textContent = dark ? 'Light mode' : 'Dark mode';
            const icon = button.querySelector?.('[data-theme-icon]');
            if (icon) icon.setAttribute('href', `${icon.getAttribute('href').split('#')[0]}#${dark ? 'sun' : 'moon'}`);
        });
    };
    buttons.forEach((button) => button.addEventListener('click', () => {
        preference = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
        try { window.localStorage.setItem('kody-theme', preference); } catch {}
        render();
    }));
    system.addEventListener('change', render);
    window.addEventListener('storage', (event) => {
        if (event.key === 'kody-theme' || event.key === null) {
            preference = event.newValue;
            render();
        }
    });
    render();
}
