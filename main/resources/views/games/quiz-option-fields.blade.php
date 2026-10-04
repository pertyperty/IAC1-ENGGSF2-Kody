<div class="quiz-option-editor" data-quiz-option-editor>
    <input type="hidden" data-option-field="id" name="quiz_questions[{{ $questionIndex }}][options][{{ $optionIndex }}][id]" value="{{ is_string($option['id'] ?? null) ? $option['id'] : chr(97 + $optionIndex) }}">
    <label><span data-option-heading>Choice {{ $optionIndex + 1 }}</span><textarea data-option-field="label" name="quiz_questions[{{ $questionIndex }}][options][{{ $optionIndex }}][label]" rows="2" maxlength="300">{{ is_string($option['label'] ?? null) ? $option['label'] : '' }}</textarea></label>
    <div class="studio-actions"><button type="button" class="game-reset" data-move-option="up">Move choice up ↑</button><button type="button" class="game-reset" data-move-option="down">Move choice down ↓</button><button type="button" class="game-reset" data-remove-option>Remove choice</button></div>
</div>
