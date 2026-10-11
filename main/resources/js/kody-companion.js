import { buildPageGuide, isGuideTargetVisible as visible } from './page-guide.js';
const TOUR_KEY = 'kody-tour-v1';
const PET_KEY = 'kody-companion-v1';

function read(win, key) { try { return win.localStorage.getItem(key); } catch { return null; } }
function write(win, key, value) { try { win.localStorage.setItem(key, value); } catch {} }

export function mountKodyCompanion(doc, win) {
    const dialog = doc.querySelector('[data-kody-tour]');
    if (!dialog?.showModal) return;
    const pet = doc.querySelector('[data-companion]');
    const handle = pet.querySelector('[data-companion-handle]');
    const bubble = pet.querySelector('[data-companion-bubble]');
    let preference;
    try { preference = JSON.parse(read(win, PET_KEY)); } catch {}
    let x = Number.isFinite(preference?.x) ? Math.max(0, Math.min(1, preference.x)) : .97;
    let y = Number.isFinite(preference?.y) ? Math.max(0, Math.min(1, preference.y)) : .78;
    let drag = null, suppressClick = false, previousFocus = null, steps = [], index = -1;
    const next = dialog.querySelector('[data-tour-next]');
    const back = dialog.querySelector('[data-tour-back]');
    const spot = dialog.querySelector('[data-tour-spot]');
    const card = dialog.querySelector('[data-tour-card]');
    const hole = dialog.querySelector('[data-tour-hole]');
    const savePet = () => write(win, PET_KEY, JSON.stringify({ x, y, hidden: pet.hidden }));
    const positionPet = () => {
        const top = Math.min(150, Math.max(72, (doc.querySelector('[data-scroll-navigation]')?.getBoundingClientRect().bottom ?? 72)));
        const left = 16 + x * Math.max(0, win.innerWidth - 112);
        const offsetTop = top + y * Math.max(0, win.innerHeight - top - 100);
        pet.style.left = `${left}px`;
        pet.style.top = `${offsetTop}px`;
        const width = Math.min(264, win.innerWidth - 32);
        bubble.style.left = `${Math.max(16, Math.min(win.innerWidth - width - 16, left)) - left}px`;
        bubble.style.right = 'auto';
        const above = offsetTop - 16, below = win.innerHeight - offsetTop - handle.clientHeight - 16;
        bubble.style.maxHeight = `${Math.max(80, Math.max(above, below) - 12)}px`;
        pet.classList.toggle('bubble-left', x > .5);
        pet.classList.toggle('bubble-below', below > above);
    };
    const setBubble = open => { bubble.hidden = !open; handle.setAttribute('aria-expanded', String(open)); };
    pet.hidden = preference?.hidden === true;
    positionPet();
    handle.addEventListener('pointerdown', event => {
        if (event.button !== 0) return;
        const box = handle.getBoundingClientRect();
        drag = { id: event.pointerId, startX: event.clientX, startY: event.clientY, offsetX: event.clientX - box.left, offsetY: event.clientY - box.top, moved: false };
        suppressClick = false; handle.setPointerCapture(event.pointerId);
    });
    handle.addEventListener('pointermove', event => {
        if (!drag || event.pointerId !== drag.id) return;
        if (Math.hypot(event.clientX - drag.startX, event.clientY - drag.startY) < 6 && !drag.moved) return;
        drag.moved = true; setBubble(false);
        const top = Math.min(150, Math.max(72, doc.querySelector('[data-scroll-navigation]')?.getBoundingClientRect().bottom ?? 72));
        x = Math.max(0, Math.min(1, (event.clientX - drag.offsetX - 16) / Math.max(1, win.innerWidth - 112)));
        y = Math.max(0, Math.min(1, (event.clientY - drag.offsetY - top) / Math.max(1, win.innerHeight - top - 100)));
        positionPet();
    });
    const endDrag = () => { if (drag) { suppressClick = drag.moved; drag = null; savePet(); } };
    handle.addEventListener('pointerup', endDrag); handle.addEventListener('pointercancel', endDrag);
    handle.addEventListener('lostpointercapture', endDrag);
    handle.addEventListener('click', () => { if (!suppressClick) setBubble(bubble.hidden); suppressClick = false; });
    handle.addEventListener('keydown', event => {
        if (event.key === 'Escape') setBubble(false);
        if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) return;
        event.preventDefault();
        x = Math.max(0, Math.min(1, x + (event.key === 'ArrowLeft' ? -.05 : event.key === 'ArrowRight' ? .05 : 0)));
        y = Math.max(0, Math.min(1, y + (event.key === 'ArrowUp' ? -.05 : event.key === 'ArrowDown' ? .05 : 0)));
        positionPet(); savePet();
    });
    pet.querySelector('[data-companion-hide]').addEventListener('click', () => {
        pet.hidden = true; setBubble(false); savePet(); doc.querySelector('.header-tour')?.focus();
    });
    let pageName = "this page";
    const positionTip = () => {
        if (!dialog.open || index < 0) return;
        const target = steps[index]?.target;
        if (!visible(target)) { spot.hidden = true; hole.setAttribute('r', '0'); return; }
        const box = target.getBoundingClientRect();
        const cx = Math.max(24, Math.min(win.innerWidth - 24, box.left + box.width / 2));
        const cy = Math.max(24, Math.min(win.innerHeight - 24, box.top + box.height / 2));
        const radius = Math.min(Math.max(34, Math.hypot(box.width, box.height) / 2 + 12), win.innerWidth * .48, win.innerHeight * .37);
        hole.setAttribute('cx', String(cx)); hole.setAttribute('cy', String(cy)); hole.setAttribute('r', String(radius));
        spot.hidden = false; spot.style.left = `${cx - radius}px`; spot.style.top = `${cy - radius}px`; spot.style.width = spot.style.height = `${radius * 2}px`;
        // Put the readable card on the opposite half, never over the highlighted control.
        card.classList.toggle('tour-card-top', cy > win.innerHeight * .5);
    };
    const renderTip = () => {
        dialog.classList.toggle('tour-intro', index < 0);
        const current = steps[index];
        dialog.querySelector('#tour-title').textContent = current?.title ?? `Let's explore ` + pageName;
        dialog.querySelector('#tour-message').textContent = current?.message ?? 'I’m Kody, your coding companion. This guide explains the page you are on. You can skip it or replay it whenever you like.';
        dialog.querySelector('[data-tour-count]').textContent = index < 0 ? 'WELCOME TO KODY' : `YOUR GUIDE · ${index + 1} / ${steps.length}`;
        next.textContent = index < 0 ? 'Click to begin' : index === steps.length - 1 ? 'Let’s explore' : 'Next tip →';
        back.hidden = index <= 0; spot.hidden = index < 0; hole.setAttribute('r', '0');
        current?.target.scrollIntoView({ block: 'center', behavior: 'instant' });
        positionTip(); next.focus({ preventScroll: true });
    };
    const finish = () => { write(win, TOUR_KEY, 'dismissed'); dialog.close(); };
    const advance = () => { if (index + 1 >= steps.length) finish(); else { index++; renderTip(); } };
    const open = () => {
        if (doc.querySelector('dialog[open]')) return;
        previousFocus = doc.activeElement; index = -1;
        doc.dispatchEvent(new win.Event('kody:tour-start'));
        ({ name: pageName, steps } = buildPageGuide(doc, dialog.dataset.tourPage ?? ''));
        setBubble(false); pet.hidden = false; savePet(); dialog.showModal(); renderTip();
    };
    next.addEventListener('click', advance); spot.addEventListener('click', advance);
    back.addEventListener('click', () => { index = Math.max(0, index - 1); renderTip(); });
    dialog.querySelector('[data-tour-skip]').addEventListener('click', finish);
    dialog.addEventListener('cancel', () => write(win, TOUR_KEY, 'dismissed'));
    dialog.addEventListener('close', () => { previousFocus?.focus?.({ preventScroll: true }); });
    doc.querySelectorAll('[data-tour-open]').forEach(button => { button.hidden = false; button.addEventListener('click', open); });
    win.addEventListener('resize', () => { positionPet(); positionTip(); });
    doc.addEventListener('scroll', positionTip, { capture: true, passive: true });
    // First public guest arrival gets the welcome screen; keep authentication/errors focused.
    if (dialog.dataset.guest === 'true' && !doc.body.classList.contains('account-page') && !doc.querySelector('.kody-error') && read(win, TOUR_KEY) !== 'dismissed') open();
}

export function mountPageLoader(doc, win) {
    const loader = doc.querySelector('[data-page-loader]');
    if (!loader) return;
    let timer = null;
    const reset = () => { loader.hidden = true; if (timer !== null) win.clearTimeout(timer); timer = null; };
    win.addEventListener('pageshow', reset); win.addEventListener('pagehide', reset);
    doc.addEventListener('click', event => {
        const link = event.target.closest('a[href]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.hasAttribute('download') || link.hasAttribute('data-navigation-download') || link.target && link.target !== '_self') return;
        const url = new URL(link.href, win.location.href);
        if (url.origin !== win.location.origin || url.pathname === win.location.pathname && url.search === win.location.search) return;
        // Only show on a genuine slow navigation, and release if navigation is cancelled.
        timer = win.setTimeout(() => { loader.hidden = false; timer = win.setTimeout(reset, 6000); }, 350);
    });
}
