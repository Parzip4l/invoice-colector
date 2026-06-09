@php
    $badgeClass = method_exists($value, 'badgeClass') ? $value->badgeClass() : 'bg-secondary-subtle text-secondary';
    $label = method_exists($value, 'label') ? $value->label() : (is_object($value) ? $value->value : str($value)->replace('_', ' ')->title());
@endphp

<span class="badge {{ $badgeClass }}">{{ $label }}</span>
