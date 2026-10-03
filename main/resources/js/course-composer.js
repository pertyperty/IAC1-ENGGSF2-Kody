export function mountCourseComposer(root) {
    const slots = root.querySelector('[data-course-slots]');
    const template = root.querySelector('[data-course-slot-template]');
    const add = root.querySelector('[data-add-slot]');
    const renumber = () => {
        slots.querySelectorAll('[data-slot-number]').forEach((number, index) => { number.textContent = String(index + 1); });
        add.disabled = slots.children.length >= 100;
    };
    add.addEventListener('click', () => {
        if (slots.children.length >= 100) return;
        slots.append(template.content.cloneNode(true));
        renumber();
    });
    slots.addEventListener('click', (event) => {
        if (event.target.closest('[data-remove-slot]')) {
            event.target.closest('.course-slot').remove();
            renumber();
        }
    });
    renumber();
}
