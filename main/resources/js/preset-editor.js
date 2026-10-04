import { mountScenarioEditor } from './games/scenario-editor.js';
import { mountQuizEditor } from './games/quiz-editor.js';

export function mountPresetEditor(root) {
    mountQuizEditor(root.querySelector('[data-quiz-editor]'));
    mountScenarioEditor(root, '[name="basis"]', { title: 'title', instructions: 'instructions', hint: 'hint', learningIdea: 'learning_idea' });
    const basis = root.querySelector('[name="basis"]');
    const update = () => {
        root.querySelector('[data-garden-prompts]').hidden = basis.value === 'quiz';
        root.querySelector('[data-quiz-prompts]').hidden = basis.value !== 'quiz';
        for (const selector of ['[data-garden-prompts]', '[data-quiz-prompts]']) {
            const section = root.querySelector(selector);
            section.querySelectorAll('input, textarea, select').forEach((field) => { field.disabled = section.hidden; });
        }
    };
    basis.addEventListener('change', update);
    update();
}
