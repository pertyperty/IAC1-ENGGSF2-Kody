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
    const visible = element => element && element.getClientRects().length && !element.closest('[hidden]');
    const definitions = [
        ['.play-brand', 'Your way home', 'The Kody logo brings you back to your main adventure or workspace.'],
        ['[data-tower-stop="1"] .tower-node', 'One discovery at a time', 'Choose an unlocked numbered floor to play. Guests can try floors 1–3; level 4 invites you to join.'],
        ['[data-tower-present]', 'Find your next step', 'The target button brings you back to your current floor. The arrows explore higher and lower floors.'],
        ['.app-primary-nav a[href$="/learn"]', 'Find something to learn', 'Browse creator lessons and see your saved progress. Guests sign in before opening lessons.'],
        ['.app-primary-nav a[href$="/learn/courses"]', 'Follow a learning path', 'Courses connect lessons into longer journeys. Your enrollment keeps the approved lesson edition and progress.'],
        ['.app-primary-nav a[href$="/help"]', 'A little help', 'Find searchable guides grouped by topic. You can replay a guided tour from the Kody button on any page.'],
        ['.header-join', 'Save your adventure', 'Create an account to save validated progress. You can keep trying the first three Tower floors as a guest.'],
        ['.mobile-workspace > summary', 'Your workspace in one tap', 'Open this menu to find the tools and destinations available to your account.'],
        ['[data-navigation-rail] [data-rail-toggle]', 'A little more room', 'Pin the side menu to keep labels visible. Hover or focus a collapsed button to pull its label into view.'],
        ['.app-primary-nav a[href$="/create/modules"], .app-primary-nav a[href$="/create/challenges"]', 'Your creator workspace', 'Create opens the studio available to your role. Save a draft, preview it and submit the saved version for staff review.'],
        ['.header-updates', 'Stay in the loop', 'Updates collect your review decisions, account notices and learning messages. Open an item to see its details.'],
        ['.account-menu > summary', 'Your account and security', 'Open your account menu for your profile, security settings, Help and sign out.'],
        ['[data-progress-strip]', 'Your saved progress', 'Streaks and XP come from eligible server-validated achievements. Local practice and reading do not award play rewards.'],
        ['.editor-jumps', 'Build in manageable steps', 'This pinned step menu moves between the sections of your editor. Changing sections does not save or publish your work.'],
        ['#lesson-edit', 'Make the idea clear', 'Give your lesson a title, explain the idea and add resources. Write for someone discovering this topic for the first time.'],
        ['#assessment-edit', 'Give the idea a playground', 'Choose a reusable game, quiz or preset, then customize its prompts and scenario. Preview play never saves learner progress.'],
        ['#quest-checks', 'Check the important cases', 'Sample tests help learners understand the problem. Hidden tests check edge cases and remain private to the creator and reviewers.'],
        ['#tower-prompts legend', 'Shape this Tower floor', 'Edit the story and stages, review source notes and try the saved preview. Publishing keeps earlier cleared floors intact.'],
        ['.staff-workspace h2', 'Your staff workspace', 'These tools follow your current role. Review content, support accounts and open the management tools available to you.'],
        ['.governance-filters input[name="q"]', 'Find the right account', 'Filter by username, role or status, then open an account to inspect its history before taking action. This guide makes no account changes.'],
        ['.wallet-summary h2', 'Know what is available', 'Your wallet distinguishes available and reserved KodeBits. Read transaction history for recorded changes; purchases become available after payment setup.'],
        ['[data-resource-workspace] h1', 'A place to learn', 'Read the lesson, open its resources alongside it and try the assessment. A confirmed result shows the next available lesson.'],
        ['[data-coding-game], [data-arcade-game], [data-practice-quiz]', 'Try, adjust, then run', 'Explore the controls, build your answer and run when ready. Practice previews stay separate from saved achievements.'],
        ['.help-search', 'Find a helpful answer', 'Search or choose a topic. Expand a guide to read its answer, or open it on its own page.'],
        ['.profile-picture-link', 'Make your profile yours', 'Choose your picture to open its secure editor. Personal details and security tools are grouped beside your profile.'],
        ['#profile-picture', 'Your picture, your choice', 'Choose a JPEG or PNG and confirm the upload. Images are processed privately and never change your account role.'],
        ['[data-theme-toggle]', 'Make yourself comfortable', 'Switch between light and dark. Use the small Kody button to replay this page’s guide and show the companion again.'],
    ];
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
        dialog.querySelector('#tour-title').textContent = current?.title ?? 'A little play. A lot of possibility.';
        dialog.querySelector('#tour-message').textContent = current?.message ?? 'I’m Kody, your coding companion. Your adventure is ready. Let’s find our way around.';
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
        steps = definitions.map(([selector, title, message]) => ({ target: doc.querySelector(selector), title, message })).filter(step => visible(step.target));
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
