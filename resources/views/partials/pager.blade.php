@php
    $chevronLeft = '<svg class="tl-btn-icon" viewBox="0 0 16 16" aria-hidden="true"><path d="M10 3.5 5.5 8 10 12.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    $chevronRight = '<svg class="tl-btn-icon" viewBox="0 0 16 16" aria-hidden="true"><path d="M6 3.5 10.5 8 6 12.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
@endphp
<nav class="tl-pager" aria-label="Log pages">
    @if($newestUrl)
        <a class="tl-btn" href="{{ $newestUrl }}">Newest</a>
    @else
        <span class="tl-btn" aria-disabled="true">Newest</span>
    @endif
    @if($newerUrl)
        <a class="tl-btn" href="{{ $newerUrl }}" rel="prev">{!! $chevronLeft !!} Newer</a>
    @else
        <span class="tl-btn" aria-disabled="true">{!! $chevronLeft !!} Newer</span>
    @endif
    @if($olderUrl)
        <a class="tl-btn" href="{{ $olderUrl }}" rel="next">Older {!! $chevronRight !!}</a>
    @else
        <span class="tl-btn" aria-disabled="true">Older {!! $chevronRight !!}</span>
    @endif
</nav>
