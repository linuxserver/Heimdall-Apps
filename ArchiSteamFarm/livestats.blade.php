<ul class="livestats">
    @if (isset($time_left))
        <li>
            <span class="title">Time</span>
            <strong>{{ $time_left }}</strong>
        </li>
    @else
        <li>
            <span class="title">Connection failed</span>
            <strong></strong>
        </li>
    @endif
    @if (isset($cards_left))
        <li>
            <span class="title">Cards</span>
            <strong>{{ $cards_left }}</strong>
        </li>
    @endif
</ul>
