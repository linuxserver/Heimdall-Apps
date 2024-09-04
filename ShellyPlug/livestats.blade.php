<ul class="livestats">
    @foreach ($visiblestats as $stat)
        <li>
            @if (isset($stat->title))
            <span class="title">{!! $stat->title !!}</span>
            @endif
            <strong>{!! $stat->value !!}</strong>
        </li>
    @endforeach
</ul>
