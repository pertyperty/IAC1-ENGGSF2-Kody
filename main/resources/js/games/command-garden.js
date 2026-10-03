const moves = Object.freeze({ up: [0, -1], down: [0, 1], left: [-1, 0], right: [1, 0] });
const key = ([x, y]) => `${x},${y}`;

// This interpreter understands movement data only; it never executes source code.
export function validateInstance(instance) {
    if (!instance || instance.template !== 'command-garden' || instance.version !== 1
        || !['sequence', 'loop', 'conditional'].includes(instance.mode)) throw new Error('Unsupported game template.');
    for (const dimension of ['width', 'height', 'maxCommands']) {
        if (!Number.isInteger(instance[dimension]) || instance[dimension] < 1 || instance[dimension] > 12) throw new Error('Invalid game limits.');
    }
    const point = (p) => Array.isArray(p) && p.length === 2 && p.every(Number.isInteger)
        && p[0] >= 0 && p[0] < instance.width && p[1] >= 0 && p[1] < instance.height;
    if (!point(instance.start) || !point(instance.goal) || key(instance.start) === key(instance.goal)
        || !Array.isArray(instance.path) || instance.path.length > 144 || !instance.path.every(point)
        || !Array.isArray(instance.crystals) || instance.crystals.length > 144 || !instance.crystals.every(point)) throw new Error('Invalid game board.');
    const path = new Set(instance.path.map(key));
    if (!path.has(key(instance.start)) || !path.has(key(instance.goal)) || !instance.crystals.every((p) => path.has(key(p)))
        || new Set(instance.crystals.map(key)).size !== instance.crystals.length) throw new Error('Invalid game objectives.');
    for (const field of ['title', 'concept', 'instructions', 'hint', 'learningIdea']) {
        if (typeof instance[field] !== 'string' || instance[field].length > 1000) throw new Error('Invalid game content.');
    }
    return instance;
}

export function runProgram(instance, program, { repeat = false, conditional = false } = {}) {
    validateInstance(instance);
    let position = [...instance.start];
    const crystals = new Set(instance.crystals.map(key));
    const trace = [];
    const result = (success, message) => ({ success, message, trace, position, crystals: [...crystals] });
    if (!Array.isArray(program) || program.length === 0) return result(false, 'Add a few arrows first. That will be your program.');
    if (program.length > instance.maxCommands || program.some((command) => !Object.hasOwn(moves, command))) return result(false, 'This program has too many steps or an unknown instruction.');
    if (instance.mode === 'loop' && !repeat) return result(false, 'Try repeating your pattern twice. That’s what a loop does.');
    const path = new Set(instance.path.map(key));
    for (let cycle = 0; cycle < (instance.mode === 'loop' && repeat ? 2 : 1); cycle++) {
        for (const [index, command] of program.entries()) {
            const move = moves[command];
            const next = [position[0] + move[0], position[1] + move[1]];
            if (!path.has(key(next))) return result(false, `Oops, step ${trace.length + 1} leaves the path. Change your arrows and try again!`);
            position = next;
            if (instance.mode === 'conditional' && conditional) crystals.delete(key(position));
            trace.push({ position: [...position], crystals: [...crystals], index });
        }
    }
    if (key(position) !== key(instance.goal)) return result(false, 'A good start! You haven’t reached the flag yet. Try changing your program.');
    if (crystals.size > 0) return result(false, 'You found the flag! Now add the crystal rule so your program collects the crystals along the way.');
    return result(true, 'You reached the flag! Your code brought this little world to life.');
}

const arrows = Object.freeze({ up: '↑', down: '↓', left: '←', right: '→' });

export function mountGarden(root) {
    const feedback = root.querySelector('[data-game-feedback]');
    let instance;
    try { instance = validateInstance(JSON.parse(root.dataset.codingGame)); }
    catch { feedback.textContent = 'This game is unavailable. Try another adventure.'; root.querySelectorAll('button').forEach((button) => { button.disabled = true; }); return; }
    const board = root.querySelector('[data-game-board]');
    const programTrack = root.querySelector('[data-game-program]');
    const success = root.querySelector('[data-game-success]');
    const progress = root.querySelector('[data-game-progress]');
    const program = [];
    let running = false;
    const tiles = [];
    board.style.setProperty('--columns', instance.width);
    const path = new Set(instance.path.map(key));
    for (let y = 0; y < instance.height; y++) {
        for (let x = 0; x < instance.width; x++) {
            const tile = document.createElement('div');
            tile.className = path.has(key([x, y])) ? 'garden-tile path-tile' : 'garden-tile grass-tile';
            const decoration = document.createElement('span');
            decoration.className = 'tile-decoration';
            decoration.textContent = path.has(key([x, y])) ? '' : ((x + y * 3) % 4 === 0 ? '✿' : '·');
            const actor = document.createElement('span');
            actor.className = 'tile-actor';
            tile.append(decoration, actor);
            board.append(tile);
            tiles.push({ tile, actor, point: [x, y] });
        }
    }
    const renderBoard = (position = instance.start, crystals = instance.crystals.map(key)) => {
        for (const { tile, actor, point } of tiles) {
            const isKody = key(point) === key(position);
            actor.className = `tile-actor${isKody ? ' garden-kody' : ''}`;
            actor.textContent = isKody ? '••' : key(point) === key(instance.goal) ? '⚑' : crystals.includes(key(point)) ? '◆' : '';
            tile.classList.toggle('goal-tile', key(point) === key(instance.goal));
            tile.classList.toggle('crystal-tile', crystals.includes(key(point)));
        }
        board.setAttribute('aria-label', `Kody is at column ${position[0] + 1}, row ${position[1] + 1}. Flag at column ${instance.goal[0] + 1}, row ${instance.goal[1] + 1}. ${crystals.length} crystals left.`);
    };
    const renderProgram = () => {
        programTrack.replaceChildren();
        if (program.length === 0) {
            const empty = document.createElement('span'); empty.textContent = 'Add an arrow to start your program'; programTrack.append(empty);
        }
        program.forEach((command, index) => {
            const step = document.createElement('span');
            step.className = 'program-step'; step.textContent = arrows[command]; step.setAttribute('aria-label', `Step ${index + 1}: ${command}`); programTrack.append(step);
        });
        progress.textContent = `${program.length} / ${instance.maxCommands} instructions`;
    };
    root.querySelectorAll('[data-command]').forEach((button) => button.addEventListener('click', () => {
        if (running) return;
        success.hidden = true;
        if (program.length >= instance.maxCommands) { feedback.textContent = `Keep it to ${instance.maxCommands} instructions. Undo a step to change your program.`; return; }
        program.push(button.dataset.command); renderProgram(); renderBoard(); feedback.textContent = 'Ready when you are. Run your code to see what happens.';
    }));
    root.querySelector('[data-game-undo]').addEventListener('click', () => { if (!running) { program.pop(); renderProgram(); renderBoard(); success.hidden = true; } });
    root.querySelector('[data-game-reset]').addEventListener('click', () => {
        if (running) return;
        program.length = 0; renderProgram(); renderBoard(); success.hidden = true;
        root.querySelectorAll('input').forEach((input) => { input.checked = false; });
        feedback.textContent = 'A fresh start. Build a new program and give it a try.';
    });
    root.querySelector('[data-game-hint]').addEventListener('click', () => { feedback.textContent = instance.hint; });
    root.querySelector('[data-game-run]').addEventListener('click', async () => {
        if (running) return;
        running = true; success.hidden = true; renderBoard();
        const result = runProgram(instance, program, { repeat: root.querySelector('[data-game-repeat]')?.checked, conditional: root.querySelector('[data-game-conditional]')?.checked });
        root.querySelectorAll('button, input').forEach((control) => { control.disabled = true; });
        feedback.textContent = 'Kody is following your instructions…';
        const delay = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 280;
        try {
            for (const frame of result.trace) {
                if (delay) await new Promise((resolve) => setTimeout(resolve, delay));
                renderBoard(frame.position, frame.crystals);
                programTrack.querySelectorAll('.program-step').forEach((step, index) => step.classList.toggle('executing', index === frame.index));
            }
            feedback.textContent = result.message; success.hidden = !result.success;
            progress.textContent = result.success ? 'Adventure complete ✦' : 'Try another idea';
        } finally {
            running = false;
            root.querySelectorAll('button, input').forEach((control) => { control.disabled = false; });
            programTrack.querySelectorAll('.program-step').forEach((step) => step.classList.remove('executing'));
        }
    });
    renderBoard();
}
