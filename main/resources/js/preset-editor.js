import { mountScenarioEditor } from './games/scenario-editor.js';

export function mountPresetEditor(root) {
    mountScenarioEditor(root, '[name="basis"]', { title: 'title', instructions: 'instructions', hint: 'hint', learningIdea: 'learning_idea' });
    const basis = root.querySelector('[name="basis"]');
    const update = () => {
        root.querySelector('[data-garden-prompts]').hidden = basis.value === 'quiz';
        root.querySelector('[data-quiz-prompts]').hidden = basis.value !== 'quiz';
    };
    basis.addEventListener('change', update);
    update();
}
