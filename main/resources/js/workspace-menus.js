export function mountWorkspaceMenus(doc) {
    doc.querySelectorAll('.account-menu').forEach(menu => {
        const summary = menu.querySelector('summary');
        menu.addEventListener('keydown', event => {
            if (event.key === 'Escape' && menu.open) {
                event.preventDefault(); menu.open = false; summary.focus();
            }
        });
        doc.addEventListener('click', event => { if (!menu.contains(event.target)) menu.open = false; });
    });
}
