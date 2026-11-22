import definitions from './arcade-pseudocode.json' with { type: 'json' };

const patterns = new Map(Object.entries(definitions).map(([template, rules]) => [template,
    rules.map(([pattern, canonical]) => [new RegExp('^' + pattern + '$', 'u'), canonical]),
]));

export function normalizeArcadeCommand(template, command) {
    if (typeof command !== 'string' || command.length > 100 || command.trim() !== command) return command;
    for (const [pattern, canonical] of patterns.get(template) ?? []) {
        if (pattern.test(command)) return command.replace(pattern, canonical);
    }
    return command;
}
