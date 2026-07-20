@php
    $activeSort = $sort ?? request('sort', $defaultSort ?? 'created_at');
    $activeDirection = $direction ?? request('direction', 'desc');
    $nextDirection = $activeSort === $column && $activeDirection === 'asc' ? 'desc' : 'asc';
    $icon = $activeSort === $column
        ? ($activeDirection === 'asc' ? 'solar:arrow-up-outline' : 'solar:arrow-down-outline')
        : 'solar:alt-arrow-down-outline';
@endphp

<a href="{{ route($route, array_merge(request()->except(['sort', 'direction', 'page']), ['sort' => $column, 'direction' => $nextDirection])) }}" class="iv-sort-link">
    <span>{{ $label }}</span>
    <iconify-icon icon="{{ $icon }}" class="fs-14"></iconify-icon>
</a>
