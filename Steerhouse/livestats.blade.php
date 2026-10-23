<ul class="livestats">
    <li>
        <span class="title">Run</span>
        <strong>{!! $running ?? 0 !!}</strong>
    </li>
    <li>
        <span class="title">Queue</span>
        <strong>{!! $queued ?? 0 !!}</strong>
    </li>
    <li>
        <span class="title">Failed</span>
        <strong>{!! $failed ?? 0 !!}</strong>
    </li>
</ul>
