@props(['name' => 'mascot', 'size' => 48])
@if(config('branding.assets.'.$name))<img {{ $attributes->class(['kody-art', 'kody-art-'.$name]) }} src="{{ asset(config('branding.assets.'.$name)) }}" width="{{ $size }}" height="{{ $size }}" alt="" aria-hidden="true" decoding="async">@endif
