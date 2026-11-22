<div class="quiz-option-editor" data-quiz-option-editor>
    <input type="hidden" data-option-field="id" name="quiz_questions[{{ $questionIndex }}][options][{{ $optionIndex }}][id]" value="{{ is_string($option['id'] ?? null) ? $option['id'] : chr(97 + $optionIndex) }}">
    <label><span data-option-heading>Choice {{ $optionIndex + 1 }}</span><textarea data-option-field="label" name="quiz_questions[{{ $questionIndex }}][options][{{ $optionIndex }}][label]" rows="2" maxlength="300">{{ is_string($option['label'] ?? null) ? $option['label'] : '' }}</textarea></label>
    <details @if(filled($option['wrong_hint'] ?? null)) open @endif><summary>Hint for this choice</summary>
        <label>Show when chosen incorrectly (optional)<textarea data-option-field="wrong_hint" name="quiz_questions[{{ $questionIndex }}][options][{{ $optionIndex }}][wrong_hint]" rows="2" maxlength="1000">{{ is_string($option['wrong_hint'] ?? null) ? $option['wrong_hint'] : '' }}</textarea></label>
    </details>
    <div class="studio-actions"><button type="button" class="game-reset" data-move-option="up">Move choice up <x-action-icon name="up"/></button><button type="button" class="game-reset" data-move-option="down">Move choice down <x-action-icon name="down"/></button><button type="button" class="game-reset" data-remove-option>Remove choice</button></div>
</div>
