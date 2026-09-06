{{-- Kotak abu-abu berkedip (animate-pulse) — menggantikan spinner saat data
     sedang di-fetch ulang setelah filter berubah. --}}
@props(['chart' => true, 'rows' => 3])
<div {{ $attributes->class(['card p-5']) }}>
    <div class="skeleton h-4 w-1/3 mb-4"></div>
    @if ($chart)
        <div class="skeleton h-40 w-full mb-3"></div>
    @endif
    <div class="space-y-2">
        @for ($i = 0; $i < $rows; $i++)
            <div class="skeleton h-3 w-full"></div>
        @endfor
    </div>
</div>
