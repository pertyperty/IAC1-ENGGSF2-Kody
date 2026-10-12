@props(['amount' => null, 'short' => false])
<span {{ $attributes->class('kodebit-value') }}><x-kody-art name="kodebit" size="18"/>@if($amount !== null)<span>{{ is_int($amount) ? number_format($amount) : $amount }}</span> @endif<span>{{ $short ? 'KB' : 'KodeBits' }}</span></span>
