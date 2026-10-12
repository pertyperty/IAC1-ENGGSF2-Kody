// Presentation only: preserve numeric text, field values and creator lesson content.
export function decorateCurrency(doc, root = doc.body) {
    if (!root || !doc.body.dataset.kodebitIcon) return;
    const walker = doc.createTreeWalker(root, doc.defaultView.NodeFilter.SHOW_TEXT);
    const nodes = []; let current;
    while ((current = walker.nextNode())) nodes.push(current);
    for (const text of nodes) {
        const parent = text.parentElement;
        if (!parent || parent.closest('[data-currency-mention], .kodebit-value, .wallet-amount, .header-wallet, .progress-strip, script, style, textarea, select, option, pre, code, svg, .lesson-text, .document-pages, .arcade-output, [contenteditable], [data-coding-game], [data-arcade-game], [data-practice-quiz]')) continue;
        const short = Boolean(parent.closest('.wallet-page, .content-price, .access-settings'));
        const pattern = short ? /\b(KodeBits?|KB)\b/g : /\b(KodeBits?)\b/g;
        if (!pattern.test(text.textContent)) continue;
        pattern.lastIndex = 0;
        const fragment = doc.createDocumentFragment(); let end = 0;
        for (const match of text.textContent.matchAll(pattern)) {
            fragment.append(doc.createTextNode(text.textContent.slice(end, match.index)));
            const mark = doc.createElement('span'); mark.dataset.currencyMention = '';
            const icon = doc.createElement('img'); icon.src = doc.body.dataset.kodebitIcon; icon.alt = ''; icon.setAttribute('aria-hidden', 'true'); icon.className = 'kodebit-mention-icon'; icon.width = icon.height = 17;
            mark.append(icon, doc.createTextNode(match[0])); fragment.append(mark); end = match.index + match[0].length;
        }
        fragment.append(doc.createTextNode(text.textContent.slice(end))); text.replaceWith(fragment);
    }
}
