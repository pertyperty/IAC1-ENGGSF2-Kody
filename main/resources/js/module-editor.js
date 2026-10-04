import { mountGardenDesigner } from './games/garden-designer.js';
import { mountScenarioEditor } from './games/scenario-editor.js';

export function mountModuleEditor(root) {
    const kind = root.querySelector('[name="assessment_kind"]');
    const type = root.querySelector('[name="type"]');
    const preset = root.querySelector('[name="game_preset"]');
    const presets = JSON.parse(root.dataset.gamePresets);
    mountGardenDesigner(root, presets);
    mountScenarioEditor(root, '[name="game_preset"]', { title: 'game_title', instructions: 'game_instructions', hint: 'game_hint', learningIdea: 'game_learning_idea' });
    let previousPreset = preset.value;
    preset.addEventListener('change', () => {
        const allPresets = { ...presets, ...JSON.parse(root.querySelector('[data-scenario-editor]').dataset.arcadeDefaults) };
        for (const [name, slot] of Object.entries({ game_title: 'title', game_instructions: 'instructions', game_hint: 'hint', game_learning_idea: 'learningIdea' })) {
            const field = root.querySelector(`[name="${name}"]`);
            // Preserve custom prompts while updating untouched defaults for the new trail.
            if (field.value === allPresets[previousPreset][slot] || (name === 'game_title' && previousPreset === 'sequences' && field.value === 'My Logic Garden')) field.value = allPresets[preset.value][slot];
        }
        previousPreset = preset.value;
    });
    const update = () => {
        root.querySelector('[data-game-fields]').hidden = kind.value !== 'game';
        root.querySelector('[data-quiz-fields]').hidden = kind.value !== 'quiz';
        root.querySelector('[data-preset-fields]').hidden = kind.value !== 'preset';
        root.querySelector('[data-video-fields]').hidden = type.value !== 'Video';
        root.querySelectorAll('[data-game-fields] input, [data-game-fields] textarea, [data-game-fields] select').forEach((field) => {
            field.disabled = kind.value !== 'game' || ['[data-garden-designer]', '[data-scenario-editor]', '[data-scenario-panel]']
                .some((selector) => field.closest(selector)?.hidden);
        });
    };
    kind.addEventListener('change', update);
    type.addEventListener('change', update);
    update();
}
