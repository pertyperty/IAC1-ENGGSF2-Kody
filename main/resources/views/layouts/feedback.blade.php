<section class="toast-region" data-toast-region aria-label="Feedback notifications">
    @if(session('status'))
    <div class="toast toast-info" data-flash-toast role="status"><span>{{ session('status') }}</span><button type="button" data-toast-dismiss aria-label="Dismiss notification" hidden>×</button></div>
    @endif
    @if($errors->any())
    <div class="toast toast-error" data-flash-toast role="alert"><span>Please check the form. {{ $errors->first() }}</span><button type="button" data-toast-dismiss aria-label="Dismiss notification" hidden>×</button></div>
    @endif
</section>
