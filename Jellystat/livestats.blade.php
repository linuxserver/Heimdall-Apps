<ul class="livestats">
    @foreach ($visiblestats as $stat)
    <li>
        <span class="title">{!! $stat->TotalPlays->title !!}</span>
        <strong>{!! $stat->TotalPlays->value !!}</strong>
    </li>
    <li>
        <span class="title">{!! $stat->TotalWatchTime->title !!}</span>
        <strong>{!! $stat->TotalWatchTime->value !!}</strong>
    </li>
    @endforeach
</ul>
