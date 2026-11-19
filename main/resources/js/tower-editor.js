import { editTowerStage, previewTowerStage } from './tower-stage-editor.js';

export function mountTowerEditor(root) {
    const field = root.querySelector('[name="stages_json"]');
    const summary = root.querySelector('[data-tower-stage-summary]');
    const render = () => {
        summary.replaceChildren();
        try {
            const stages = JSON.parse(field.value);
            if (!Array.isArray(stages) || stages.length > 4) throw new Error();
            stages.forEach((stage, index) => {
                const row = document.createElement('div'); row.className = 'tower-stage-summary';
                const title = document.createElement('b'); title.textContent = `${index + 1}. ${stage.title ?? 'Untitled stage'}`;
                const kind = document.createElement('span'); kind.textContent = stage.template ?? 'Choose a template';
                const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'button button-secondary button-small'; remove.textContent = 'Remove stage';
                remove.addEventListener('click', () => { stages.splice(index, 1); field.value = JSON.stringify(stages, null, 2); render(); });
                const host = document.createElement('div'); host.className = 'tower-draft-preview';
                const preview = document.createElement('button'); preview.type = 'button'; preview.className = 'button button-play button-small'; preview.textContent = 'Try unsaved stage';
                const status = document.createElement('p'); status.setAttribute('role', 'status'); status.className = 'field-hint';
                preview.addEventListener('click', () => {
                    try { previewTowerStage(root, stage, host); status.textContent = 'Preview only. Publication also checks reachability and data limits on the server.'; }
                    catch { status.textContent = 'Check the stage fields before previewing. Your edits are preserved.'; host.replaceChildren(); }
                });
                row.append(title, kind, remove, preview);
                editTowerStage(row, stage, index, () => { field.value = JSON.stringify(stages, null, 2); status.textContent = 'Unsaved changes. Try the stage again before publishing.'; }, host);
                row.append(status, host); summary.append(row);
            });
        } catch { summary.textContent = 'Check the JSON syntax before adding another stage. Your text is preserved.'; }
    };
    root.querySelectorAll('[data-tower-template]').forEach(button => button.addEventListener('click', () => {
        try {
            const stages = JSON.parse(field.value);
            if (!Array.isArray(stages) || stages.length >= 4) { summary.textContent = 'A level supports up to four stages.'; return; }
            stages.push(JSON.parse(button.dataset.towerTemplate));
            field.value = JSON.stringify(stages, null, 2); render();
        } catch { summary.textContent = 'Fix the stage JSON first. Your existing configuration is preserved.'; }
    }));
    field.addEventListener('input', render); render();
}
