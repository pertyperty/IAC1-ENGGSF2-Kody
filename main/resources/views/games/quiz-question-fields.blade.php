<fieldset class="quiz-question-editor" data-quiz-question-editor>
    <legend data-question-heading>Question {{ $questionIndex + 1 }}</legend>
    <input type="hidden" data-question-field="id" name="quiz_questions[{{ $questionIndex }}][id]" value="{{ is_string($question['id'] ?? null) ? $question['id'] : 'q'.($questionIndex + 1) }}">
    <label>Question<textarea data-question-field="question" name="quiz_questions[{{ $questionIndex }}][question]" maxlength="500" rows="2">{{ is_string($question['question'] ?? null) ? $question['question'] : '' }}</textarea></label>
    <div data-quiz-options>
        @foreach(is_array($question['options'] ?? null) ? array_values(array_slice($question['options'], 0, 6)) : [] as $optionIndex => $option)
            @include('games.quiz-option-fields')
        @endforeach
    </div>
    <button type="button" class="game-reset" data-add-option>Add a choice +</button>
    <label>Correct choice<select data-question-field="answer" name="quiz_questions[{{ $questionIndex }}][answer]">
        <option value="">Choose the correct answer</option>
        @foreach(is_array($question['options'] ?? null) ? array_values(array_slice($question['options'], 0, 6)) : [] as $optionIndex => $option)
            @if(is_string($option['id'] ?? null))<option value="{{ $option['id'] }}" @selected(($question['answer'] ?? '') === $option['id'])>Choice {{ $optionIndex + 1 }}</option>@endif
        @endforeach
    </select></label>
    <label>Feedback and explanation<textarea data-question-field="explanation" name="quiz_questions[{{ $questionIndex }}][explanation]" maxlength="1000" rows="2">{{ is_string($question['explanation'] ?? null) ? $question['explanation'] : '' }}</textarea></label>
    <div class="studio-actions"><button type="button" class="game-reset" data-move-question="up">Move question up ↑</button><button type="button" class="game-reset" data-move-question="down">Move question down ↓</button><button type="button" class="game-reset" data-remove-question>Remove question</button></div>
</fieldset>
