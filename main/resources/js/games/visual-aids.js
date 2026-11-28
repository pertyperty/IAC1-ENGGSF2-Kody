// Pure visual/pedagogical helpers over bounded game states. Never executes source code or awards progress.

const TOKEN_PATTERN = /("(?:[^"\\]|\\.)*"|\b(?:kody|canvas|stack|inputs|items|value|text|repeat|times|if|not|ls|cat|cp|add|subtract|multiply|swap|paint|flip|push|pop|duplicate|upper|lower|reverse|trim|append)\b|\b[a-z_][a-z0-9_]*(?=\()|-?\b\d+\b|[=+\-*,()[\]:])/gi;

const KEYWORDS = new Set(['repeat', 'times', 'if', 'not', 'ls', 'cat', 'cp']);
const OBJECTS = new Set(['kody', 'canvas', 'stack', 'inputs', 'items', 'value', 'text']);
const OPS = new Set(['=', '+', '-', '*', ',', '(', ')', '[', ']', ':']);

export function tokenizeCode(text) {
    if (typeof text !== 'string' || !text.length) return [];
    const tokens = [];
    let lastIndex = 0;
    TOKEN_PATTERN.lastIndex = 0;
    let match;
    while ((match = TOKEN_PATTERN.exec(text)) !== null) {
        if (match.index > lastIndex) {
            tokens.push({ type: 'plain', value: text.slice(lastIndex, match.index) });
        }
        const value = match[0];
        const lower = value.toLowerCase();
        let type = 'plain';
        if (value.startsWith('"')) type = 'str';
        else if (/^-?\d+$/.test(value)) type = 'num';
        else if (OPS.has(value)) type = 'op';
        else if (KEYWORDS.has(lower)) type = 'kw';
        else if (OBJECTS.has(lower)) type = 'obj';
        else type = 'fn';
        tokens.push({ type, value });
        lastIndex = TOKEN_PATTERN.lastIndex;
    }
    if (lastIndex < text.length) {
        tokens.push({ type: 'plain', value: text.slice(lastIndex) });
    }
    return tokens;
}

export function renderHighlightedCode(doc, text) {
    const fragment = doc.createDocumentFragment();
    for (const token of tokenizeCode(text)) {
        if (token.type === 'plain') {
            fragment.append(doc.createTextNode(token.value));
        } else {
            const span = doc.createElement('span');
            span.className = `tok-${token.type}`;
            span.textContent = token.value;
            fragment.append(span);
        }
    }
    return fragment;
}

export function buildTruthTable(gate, inputs) {
    const count = inputs.length;
    const onCount = inputs.filter(Boolean).length;
    const evaluate = (vals) => {
        const on = vals.filter(Boolean).length;
        return gate === 'and' ? on === vals.length : gate === 'or' ? on > 0 : on % 2 === 1;
    };
    if (count === 2) {
        const combos = [[false, false], [false, true], [true, false], [true, true]];
        return {
            mode: 'full',
            headers: ['IN 1', 'IN 2', gate.toUpperCase()],
            rows: combos.map((combo) => ({
                cells: [combo[0] ? 'TRUE' : 'FALSE', combo[1] ? 'TRUE' : 'FALSE'],
                output: evaluate(combo),
                active: combo[0] === inputs[0] && combo[1] === inputs[1],
            })),
        };
    }
    if (gate === 'and') {
        return {
            mode: 'summary',
            headers: ['TRUE inputs', `AND (${count} inputs)`],
            rows: [
                { cells: ['0 TRUE'], output: false, active: onCount === 0 },
                { cells: [`1–${count - 1} TRUE`], output: false, active: onCount > 0 && onCount < count },
                { cells: [`All ${count} TRUE`], output: true, active: onCount === count },
            ],
        };
    }
    if (gate === 'or') {
        return {
            mode: 'summary',
            headers: ['TRUE inputs', `OR (${count} inputs)`],
            rows: [
                { cells: ['0 TRUE (all OFF)'], output: false, active: onCount === 0 },
                { cells: [`1–${count} TRUE (any ON)`], output: true, active: onCount > 0 },
            ],
        };
    }
    return {
        mode: 'summary',
        headers: ['TRUE count', `XOR (${count} inputs)`],
        rows: [
            { cells: ['Even count (0, 2, 4)'], output: false, active: onCount % 2 === 0 },
            { cells: ['Odd count (1, 3)'], output: true, active: onCount % 2 === 1 },
        ],
    };
}

export function analyzeSortArray(items) {
    const pairs = [];
    let inversions = 0;
    for (let i = 0; i < items.length - 1; i++) {
        const ordered = items[i] <= items[i + 1];
        pairs.push({ left: i, right: i + 1, ordered });
    }
    for (let i = 0; i < items.length; i++) {
        for (let j = i + 1; j < items.length; j++) {
            if (items[i] > items[j]) inversions++;
        }
    }
    return { pairs, inversions, adjacentErrors: pairs.filter((pair) => !pair.ordered).length };
}

export function numberLineMetrics(start, current, target) {
    const min = Math.min(start, current, target);
    const max = Math.max(start, current, target);
    const span = max - min || 1;
    const pad = span * 0.12;
    const low = min - pad;
    const high = max + pad;
    const pct = (value) => Math.max(4, Math.min(96, ((value - low) / (high - low)) * 100));
    const diff = target - current;
    const deltaLabel = diff === 0
        ? 'Exact target match'
        : diff > 0
            ? `+${diff} to reach target`
            : `${Math.abs(diff)} above target`;
    return {
        startPct: pct(start),
        currentPct: pct(current),
        targetPct: pct(target),
        diff,
        deltaLabel,
    };
}

const GARDEN_MOVES = Object.freeze({ up: [0, -1], down: [0, 1], left: [-1, 0], right: [1, 0] });
const ptKey = ([x, y]) => `${x},${y}`;

export function previewGardenRoute(instance, program, { repeat = false, conditional = false } = {}) {
    const pathSet = new Set(instance.path.map(ptKey));
    const crystals = new Set(instance.crystals.map(ptKey));
    const cycles = instance.mode === 'loop' && repeat ? 2 : 1;
    const segments = [];
    let position = [...instance.start];
    let collision = null;

    for (let cycle = 1; cycle <= cycles; cycle++) {
        for (let index = 0; index < program.length; index++) {
            const command = program[index];
            const delta = GARDEN_MOVES[command];
            if (!delta) break;
            const next = [position[0] + delta[0], position[1] + delta[1]];
            if (!pathSet.has(ptKey(next))) {
                collision = { from: [...position], to: next, step: segments.length + 1, cycle, command };
                return { segments, collision, endPosition: position, remainingCrystals: [...crystals] };
            }
            const collected = instance.mode === 'conditional' && conditional && crystals.has(ptKey(next));
            if (collected) crystals.delete(ptKey(next));
            segments.push({ from: [...position], to: [...next], step: segments.length + 1, index, cycle, command, collected });
            position = next;
        }
    }
    return { segments, collision, endPosition: position, remainingCrystals: [...crystals] };
}

export function splitQuizPrompt(text) {
    if (typeof text !== 'string') return { lead: '', code: '', question: '' };
    const trimmed = text.trim();
    if (!trimmed.includes('\n')) {
        return { lead: '', code: '', question: trimmed };
    }
    const lines = trimmed.split('\n').map((line) => line.trimEnd());
    const last = lines.at(-1) ?? '';
    const hasQuestionTail = /\?$/.test(last.trim()) || /^(What|Which|For |Write |Find |How )/i.test(last.trim());
    const codeLines = hasQuestionTail ? lines.slice(0, -1) : lines;
    const question = hasQuestionTail ? last.trim() : '';
    let lead = '';
    if (codeLines.length && /^Python:\s*/i.test(codeLines[0])) {
        lead = 'Python';
        codeLines[0] = codeLines[0].replace(/^Python:\s*/i, '');
    }
    return {
        lead,
        code: codeLines.join('\n').trim(),
        question,
    };
}
