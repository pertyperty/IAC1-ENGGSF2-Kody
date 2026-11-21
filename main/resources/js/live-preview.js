// Previews use text nodes only and never publish or submit changes.
export function mountLivePreviews(doc, win) {
    doc.querySelectorAll('[data-live-preview]').forEach(root => {
        let pictureUrl;
        const render = () => {
            root.querySelectorAll('[data-preview-value]').forEach(output => {
                const input = root.querySelector(`[name="${output.dataset.previewValue}"]`);
                if (input) output.textContent = input.value.trim() || output.dataset.previewPlaceholder || '';
            });
        };
        root.addEventListener('input', render);
        const picture = root.querySelector('input[type="file"][name="picture"]');
        picture?.addEventListener('change', () => {
            if (pictureUrl) win.URL.revokeObjectURL(pictureUrl);
            const file = picture.files[0];
            const image = root.querySelector('[data-preview-picture]');
            if (image && file && ['image/jpeg', 'image/png'].includes(file.type) && file.size <= 2097152) {
                pictureUrl = win.URL.createObjectURL(file);
                image.src = pictureUrl;
                image.hidden = false;
            }
        });
        win.addEventListener('pagehide', () => { if (pictureUrl) win.URL.revokeObjectURL(pictureUrl); });
        render();
    });
}
