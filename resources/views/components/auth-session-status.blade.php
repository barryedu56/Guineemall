@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-yellow-600 dark:text-yellow-400']) }}>
        {{ $status }}
    </div>
@endif
