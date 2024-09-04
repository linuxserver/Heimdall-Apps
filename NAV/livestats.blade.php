<style>
.fa-exclamation-triangle:before { content: "\f071"; }
</style>
<ul class="livestats">
@if(count($stats) > 0)
@foreach($stats as $stat)
    <li>
    @if(count($stats) > 2)
        <span class="title">{!! $stat["short"] !!}</span>
    @else
        <span class="title">{!! $stat["title"] !!}</span>
    @endif
        <strong>{!! $stat["count"] !!}</strong>
    </li>
@endforeach
@else
    <li>
        <span class="title">Error</span>
        <strong>No data</strong>
    </li>
@endif
</ul>
