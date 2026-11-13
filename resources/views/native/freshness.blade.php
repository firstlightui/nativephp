@if ($notice !== null)
    @if ($notice->actionLabel !== null)
        <firstlight:callout :message="$notice->message" :tone="$notice->tone" :action-label="$notice->actionLabel" @press="retryFreshness('{{ $notice->key }}')" />
    @else
        <firstlight:callout :message="$notice->message" :tone="$notice->tone" />
    @endif
@endif
