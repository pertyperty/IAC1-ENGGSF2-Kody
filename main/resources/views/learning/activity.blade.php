@if($game['template'] === 'command-garden')
    @include('learning.game')
@else
    @include('learning.arcade-game')
@endif
