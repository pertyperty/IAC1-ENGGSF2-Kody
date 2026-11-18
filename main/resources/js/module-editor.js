import { mountGarden } from './games/command-garden.js';
import { mountArcade } from './games/arcade-games.js';
import { mountGardenDesigner } from './games/garden-designer.js';
import { mountScenarioEditor } from './games/scenario-editor.js';
import { mountQuizDesigner } from './games/quiz-designer.js';
import { mountQuizEditor } from './games/quiz-editor.js';

export function mountModuleEditor(root) {
    const kind = root.querySelector('[name="assessment_kind"]');
    const type = root.querySelector('[name="type"]');
    const preset = root.querySelector('[name="game_preset"]');
    const presets = JSON.parse(root.dataset.gamePresets);
    mountGardenDesigner(root, presets);
    mountQuizEditor(root.querySelector('[data-quiz-editor]'));
    mountQuizDesigner(root);
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
    const challenge = root.querySelector('[name=game_challenge]');
    const tasks = JSON.parse(root.dataset.assessmentChallenges);
    const custom = root.querySelector('[data-custom-game]');
    const guided = root.querySelector('[data-guided-summary]');
    const update = () => {
        const task = tasks[challenge.value];
        custom.hidden = Boolean(task);
        guided.hidden = !task;
        root.querySelector('[data-guided-goal]').textContent = task?.instructions ?? '';
        root.querySelector('[data-guided-outcome]').textContent = task ? `Assesses: ${task.learningIdea}` : '';
        root.querySelector('[data-game-fields]').hidden = kind.value !== 'game';
        root.querySelector('[data-quiz-fields]').hidden = kind.value !== 'quiz';
        root.querySelector('[data-preset-fields]').hidden = kind.value !== 'preset';
        root.querySelector('[data-video-fields]').hidden = false;
        root.querySelector('[name="video_url"]').required = type.value === 'Video';
        if (type.value === 'Video') root.querySelector('.creator-media-panel').open = true;
        for (const selector of ['[data-quiz-fields]', '[data-preset-fields]', '[data-video-fields]']) {
            const section = root.querySelector(selector);
            section.querySelectorAll('input, textarea, select').forEach((field) => { field.disabled = section.hidden; });
        }
        root.querySelectorAll('[data-game-fields] input, [data-game-fields] textarea, [data-game-fields] select').forEach((field) => {
            field.disabled = kind.value !== 'game' || Boolean(field.closest('[data-custom-game]')?.hidden) || ['[data-garden-designer]', '[data-scenario-editor]', '[data-scenario-panel]']
                .some((selector) => field.closest(selector)?.hidden);
        });
    };
    challenge.addEventListener('change', () => { root.querySelector('[data-guided-preview-host]').replaceChildren(); update(); });
    root.querySelector('[data-guided-preview]').addEventListener('click', () => {
        const game = tasks[challenge.value];
        if (!game) return;
        const garden = game.template === 'command-garden';
        const fragment = root.ownerDocument.querySelector(garden ? '[data-guided-garden]' : '[data-guided-arcade]').content.cloneNode(true);
        const preview = fragment.querySelector(garden ? '[data-coding-game]' : '[data-arcade-game]');
        if (garden) preview.dataset.codingGame = JSON.stringify(game); else preview.dataset.arcadeGame = JSON.stringify(game);
        preview.querySelector('h2').textContent = game.title;
        preview.querySelector('.game-kicker').textContent = game.concept;
        preview.querySelector('.activity-goal p').textContent = game.instructions;
        preview.querySelector('.activity-reference > div').textContent = game.hint;
        preview.querySelectorAll('[data-game-repeat], [data-game-conditional]').forEach(input => {
            input.closest('label').hidden = input.hasAttribute('data-game-repeat') ? game.mode !== 'loop' : game.mode !== 'conditional';
        });
        root.querySelector('[data-guided-preview-host]').replaceChildren(fragment);
        if (garden) {
            preview.querySelector('[data-game-success] span').textContent = game.learningIdea;
            mountGarden(preview);
        } else mountArcade(preview);
    });
    kind.addEventListener('change', update);
    type.addEventListener('change', update);
    update();
}
