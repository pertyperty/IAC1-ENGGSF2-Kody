import { mountModuleEditor } from './module-editor.js';
import { mountPresetEditor } from './preset-editor.js';
import { mountCourseComposer } from './course-composer.js';
import { mountTestCaseEditor } from './test-case-editor.js';
import { mountTowerEditor } from './tower-editor.js';
import { mountUnsavedChanges } from './unsaved-changes.js';
import { mountFileConstraints } from './file-constraints.js';

document.querySelectorAll('[data-module-editor]').forEach(mountModuleEditor);
document.querySelectorAll('[data-preset-editor]').forEach(mountPresetEditor);
document.querySelectorAll('[data-course-composer]').forEach(mountCourseComposer);
document.querySelectorAll('[data-test-case-editor]').forEach(mountTestCaseEditor);
document.querySelectorAll('[data-tower-editor]').forEach(mountTowerEditor);

// Capture defaults after editors initialize; preview controls remain excluded.
mountFileConstraints(document);
mountUnsavedChanges(document, window);
