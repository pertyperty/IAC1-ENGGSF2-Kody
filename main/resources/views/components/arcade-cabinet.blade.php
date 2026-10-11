@props(['template'])
<svg viewBox="0 0 180 100" class="cabinet-art" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($template)
@case('pixel-studio') @foreach([[56,20],[82,20],[108,20],[56,46],[82,46],[108,46]] as $cell)<rect x="{{ $cell[0] }}" y="{{ $cell[1] }}" width="20" height="20" rx="4" fill="currentColor" opacity="{{ $loop->iteration % 3 ? '.8' : '.25' }}"/>@endforeach @break
@case('number-machine')<rect x="38" y="20" width="104" height="58" rx="10"/><path d="M58 40v18m-9-9h18m16-9 12 18m0-18L83 58m27-12h14m-14 8h14"/>@break
@case('sort-lab')<path d="M44 74h96"/><rect x="52" y="24" width="16" height="44" rx="4"/><rect x="82" y="48" width="16" height="20" rx="4"/><rect x="112" y="36" width="16" height="32" rx="4"/><path d="m64 14 22-6 26 10"/>@break
@case('terminal-quest')<rect x="32" y="18" width="116" height="66" rx="8"/><path d="M32 32h116m-98 12 12 10-12 10m27 0h20"/><circle cx="42" cy="25" r="1"/>@break
@case('logic-switchboard')<path d="M34 32h34v12m-34 24h34V56m43-6h26M68 34h12c42 0 42 32 0 32H68z"/><circle cx="34" cy="32" r="6"/><circle cx="34" cy="68" r="6"/><circle cx="141" cy="50" r="8" fill="currentColor"/>@break
@case('string-forge')<path d="m53 28-20 22 20 22m74-44 20 22-20 22m-38-35v26m-9-26h18m-29 14h9m26 0h9"/>@break
@case('memory-trail')<rect x="60" y="18" width="60" height="16" rx="4"/><rect x="60" y="40" width="60" height="16" rx="4"/><rect x="60" y="62" width="60" height="16" rx="4"/><path d="M44 36v30m-6-6 6 6 6-6"/>@break
@default<path d="m58 38 14 14 22-26M56 70h68"/><rect x="38" y="12" width="104" height="76" rx="12"/>
@endswitch
</svg>
