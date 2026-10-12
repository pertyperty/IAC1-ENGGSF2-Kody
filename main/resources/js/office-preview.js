import { renderAsync } from 'docx-preview';
import { loadPresentation, getSlides } from '@office-kit/pptx';
import { renderSlideToSvg } from '@office-kit/pptx-preview';

async function render() {
    const source = document.querySelector('[data-office-source]');
    const host = document.querySelector('[data-office-pages]');
    const status = document.querySelector('[data-office-status]');
    if (!source || !host) return;
    try {
        const bytes = Uint8Array.from(atob(source.textContent.trim()), char => char.charCodeAt(0));
        source.remove();
        if (document.body.dataset.officeType === 'docx') {
            await renderAsync(bytes, host, null, { renderAltChunks: false, renderComments: false,
                ignoreLastRenderedPageBreak: false, useBase64URL: true });
            // Uploaded hyperlinks stay inert inside this private document preview.
            host.querySelectorAll('a').forEach(link => link.removeAttribute('href'));
        } else {
            const presentation = await loadPresentation(bytes);
            const slides = getSlides(presentation);
            if (slides.length > 100) throw new Error('Slide limit');
            for (const [index, slide] of slides.entries()) {
                status.textContent = `Rendering slide ${index + 1} of ${slides.length}…`;
                await new Promise(resolve => requestAnimationFrame(resolve));
                const section = document.createElement('section');
                const label = document.createElement('h2'); label.textContent = `Slide ${index + 1}`;
                const image = document.createElement('img'); image.alt = `Rendered slide ${index + 1}`;
                // SVG is an image, never active uploaded markup in the application DOM.
                const url = URL.createObjectURL(new Blob([renderSlideToSvg(presentation, slide)], { type: 'image/svg+xml' }));
                image.src = url;
                for (const event of ['load', 'error']) image.addEventListener(event, () => URL.revokeObjectURL(url), { once: true });
                section.append(label, image); host.append(section);
            }
        }
        status.textContent = 'Document preview. Complex formatting may differ from the original; download it for full fidelity.';
    } catch {
        host.replaceChildren();
        status.textContent = 'This file could not be rendered here. Download the original from the lesson toolbar.';
    }
    host.removeAttribute('aria-busy');
}
render();
