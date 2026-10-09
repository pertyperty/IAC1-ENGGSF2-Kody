import test from 'node:test';
import assert from 'node:assert/strict';
import { journeyValues, mountCourseJourney } from '../../resources/js/course-journey.js';

const saved = { course_id: 12, completed: 1, total: 2, finished: false, current_completed: true,
    next: { url: 'https://kody.test/learn/courses/12/modules/45', title: 'Next idea' } };

test('course continuation rejects another course, impossible counts and unsafe destinations', () => {
    assert.equal(journeyValues(saved, 12, 'https://kody.test').next.title, 'Next idea');
    for (const patch of [{ course_id: 13 }, { completed: 3 }, { completed: -1 }, { total: 0 }, { finished: true }, { current_completed: false },
        { next: { ...saved.next, url: 'https://other.test/learn/courses/12/modules/45' } },
        { next: { ...saved.next, url: '/learn/courses/13/modules/45' } },
        { next: { ...saved.next, url: 'javascript:alert(1)' } },
        { next: { ...saved.next, url: '/learn/courses/12/modules/45?user_id=99' } }]) {
        assert.equal(journeyValues({ ...saved, ...patch }, 12, 'https://kody.test'), null);
    }
});

test('only confirmed server course feedback updates the next action, with text rendering and no navigation jump', () => {
    const nodes = Object.fromEntries(['title', 'count', 'progress', 'note', 'next', 'overview'].map(name => [name, {
        textContent: 'old', hidden: true, setAttribute(name, value) { this[name] = value; }, removeAttribute(name) { delete this[name]; },
    }]));
    let listener;
    const root = { dataset: { courseJourney: '12' }, classList: { toggle() {} }, querySelector: selector => nodes[selector.match(/data-journey-(\w+)/)[1]] };
    mountCourseJourney(root, { addEventListener: (name, callback) => { listener = callback; } }, { location: { origin: 'https://kody.test' } });
    listener({ detail: { progressSaved: false, journey: saved } });
    assert.equal(nodes.count.textContent, 'old');
    listener({ detail: { progressSaved: true, journey: { ...saved, next: { ...saved.next, title: '<script>text</script>' } } } });
    assert.equal(nodes.count.textContent, '1 of 2 adventures completed.');
    assert.equal(nodes.next.hidden, false);
    assert.equal(nodes.next['aria-label'], 'Next adventure: <script>text</script>');
    listener({ detail: { progressSaved: true, journey: { ...saved, completed: 2, finished: true, next: null } } });
    assert.equal(nodes.title.textContent, 'Journey cleared!');
    assert.equal(nodes.next.hidden, true);
    assert.equal(nodes.next.href, undefined);
    assert.equal(nodes.overview.href, 'https://kody.test/learn/courses');
});
