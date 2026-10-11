@foreach(['flower', 'tree', 'cloud', 'satellite', 'planet'] as $zone => $icon)
    <div class="ascent-details ascent-details-{{ $zone }}">@for($detail = 0; $detail < 3; $detail++)<x-action-icon :name="$icon" class="ascent-detail ascent-detail-{{ $detail }}"/>@endfor</div>
@endforeach
