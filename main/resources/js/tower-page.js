import { mountTower } from './tower.js';

mountTower(document, window);

const proficiency = document.querySelector('[data-proficiency-prompt]');
if (proficiency) {
    proficiency.showModal();
    proficiency.querySelector('[data-proficiency-close]').addEventListener('click', () => proficiency.close());
}
