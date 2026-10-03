import { mountGardenDesigner } from './games/garden-designer.js';

export function mountModuleEditor(root) {
    const kind = root.querySelector('[name="assessment_kind"]');
    const type = root.querySelector('[name="type"]');
    const preset = root.querySelector('[name="game_preset"]');
    const presets = JSON.parse(root.dataset.gamePresets);
    mountGardenDesigner(root, presets);
    let previousPreset = preset.value;
    preset.addEventListener('change', () => {
        for (const [name, slot] of Object.entries({ game_instructions: 'instructions', game_hint: 'hint', game_learning_idea: 'learningIdea' })) {
            const field = root.querySelector(`[name="${name}"]`);
            // Preserve custom prompts while updating untouched defaults for the new trail.
            if (field.value === presets[previousPreset][slot]) field.value = presets[preset.value][slot];
        }
        previousPreset = preset.value;
    });
    const update = () => {
        root.querySelector('[data-game-fields]').hidden = kind.value !== 'game';
        root.querySelector('[data-quiz-fields]').hidden = kind.value !== 'quiz';
        root.querySelector('[data-preset-fields]').hidden = kind.value !== 'preset';
        root.querySelector('[data-video-fields]').hidden = type.value !== 'Video';
    };
    kind.addEventListener('change', update);
    type.addEventListener('change', update);
    update();
}
