<section data-quiz-editor>
    <p class="field-hint">Add 1–10 questions with 2–6 different choices each. Learners must answer every question correctly to complete this practice quiz. Answers and explanations are public practice material.</p>
    <div data-quiz-questions>
        @foreach(is_array($quizQuestions) ? array_values(array_slice($quizQuestions, 0, 10)) : [] as $questionIndex => $question)
            @include('games.quiz-question-fields')
        @endforeach
    </div>
    <button type="button" class="button button-dark" data-add-question>Add a question +</button>
    <p class="field-hint" data-quiz-editor-status role="status">Changes stay in your draft until you save and receive approval.</p>
    <template data-question-template>@include('games.quiz-question-fields', ['questionIndex' => 0, 'question' => ['id' => '', 'question' => '', 'options' => [['id' => 'a', 'label' => ''], ['id' => 'b', 'label' => '']], 'answer' => '', 'explanation' => '']])</template>
    <template data-option-template>@include('games.quiz-option-fields', ['questionIndex' => 0, 'optionIndex' => 0, 'option' => ['id' => '', 'label' => '']])</template>
    <noscript><p>Enable JavaScript to add or reorder questions and choices. You can edit and save the displayed questions without it.</p></noscript>
</section>
