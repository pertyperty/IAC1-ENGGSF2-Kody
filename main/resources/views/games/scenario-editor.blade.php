<section data-scenario-editor data-arcade-defaults="{{ json_encode(config('arcade'), JSON_THROW_ON_ERROR) }}">
    <h3>Shape the challenge</h3><p class="field-hint">Your scenario is saved with this revision. Learners play the approved version. Enable JavaScript to edit scenario fields and try a preview.</p>
    <input type="hidden" name="{{ $scenarioName }}" value="{{ old($scenarioName, isset($scenarioInstance['scenario']) ? json_encode($scenarioInstance['scenario'], JSON_THROW_ON_ERROR) : '') }}">
    <div data-scenario-panel="pixel-studio"><label>Target pixels<textarea data-scenario-pixels rows="4" maxlength="250" placeholder="1 1 mint"></textarea></label><p class="field-hint">One pixel per line: column row color. Coordinates 1–3. Colors mint, peach, lavender. Use 1–9 different cells.</p></div>
    <div data-scenario-panel="number-machine"><label>Starting number<input data-scenario-start type="number" min="-100" max="100" step="1"></label><label>Target number<input data-scenario-target type="number" min="-100" max="100" step="1"></label></div>
    <div data-scenario-panel="sort-lab"><label>Numbers to sort<input data-scenario-items maxlength="30" placeholder="3, 1, 2"></label><p class="field-hint">2–6 comma-separated integers, each 0–99. The objective is ascending order.</p></div>
    <div data-scenario-panel="terminal-quest"><label>Workspace files<textarea data-scenario-files rows="5" maxlength="2800" placeholder="hello.txt | Hello Kody!"></textarea></label><p class="field-hint">One file per line: filename | contents. Up to 8 files. Names start with a lowercase letter, with an extension; no paths. Contents up to 300 characters.</p><label>Destination filename<input data-scenario-destination maxlength="26"></label><label>Required contents<input data-scenario-content maxlength="300"></label><p class="field-hint">Choose contents from an existing file and a new destination name. Learners must copy and read the destination.</p></div>
    <button type="button" class="game-reset" data-scenario-reset>Reset to example</button>
    <button type="button" class="button button-dark" data-scenario-preview>Try your scenario</button>
    <p data-scenario-status class="field-hint" role="status"></p><div data-scenario-preview-host></div>
    <template>@include('learning.arcade-game', ['game' => config('arcade.pixel-studio'), 'completionUrl' => null])</template>
</section>
