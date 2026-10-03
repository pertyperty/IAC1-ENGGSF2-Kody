export function mountPresetEditor(root) {
    const basis = root.querySelector('[name="basis"]');
    const update = () => {
        root.querySelector('[data-garden-prompts]').hidden = basis.value === 'quiz';
        root.querySelector('[data-quiz-prompts]').hidden = basis.value !== 'quiz';
    };
    basis.addEventListener('change', update);
    update();
}
