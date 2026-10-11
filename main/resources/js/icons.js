export function createIcon(doc, name) {
    const svg = doc.createElementNS('http://www.w3.org/2000/svg', 'svg');
    for (const [attribute, value] of Object.entries({ class: 'action-icon', viewBox: '0 0 24 24', width: '20', height: '20', fill: 'none', stroke: 'currentColor', 'stroke-width': '1.7', 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'aria-hidden': 'true' })) svg.setAttribute(attribute, value);
    const use = doc.createElementNS('http://www.w3.org/2000/svg', 'use');
    use.setAttribute('href', `${doc.body?.dataset.iconSprite ?? '/images/navigation.svg'}#${name}`);
    svg.append(use); return svg;
}

export function setIconLabel(element, name, label = '') {
    element.replaceChildren(createIcon(element.ownerDocument, name), element.ownerDocument.createTextNode(label ? ` ${label}` : ''));
}
