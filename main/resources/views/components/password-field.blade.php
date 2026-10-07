@props(['name', 'label', 'autocomplete' => 'new-password', 'minlength' => null, 'maxlength' => 32, 'hint' => null])
<div class="password-field" data-password-field>
    <label for="field-{{ $name }}">{{ $label }}</label>
    <div class="password-control">
        <input id="field-{{ $name }}" type="password" name="{{ $name }}" autocomplete="{{ $autocomplete }}" @if($minlength) minlength="{{ $minlength }}" @endif maxlength="{{ $maxlength }}" @if($hint) aria-describedby="hint-{{ $name }}" @endif required>
        <button type="button" data-password-toggle aria-label="Show {{ mb_strtolower($label) }}" aria-controls="field-{{ $name }}" aria-pressed="false" hidden>Show</button>
    </div>
    @if($hint)<small id="hint-{{ $name }}">{{ $hint }}</small>@endif
</div>
