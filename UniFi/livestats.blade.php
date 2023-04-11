<ul class="livestats">
    @if ($error === true)
        <li>
            <span class="title">Error!</span>
            <strong>No connection!</strong>
        </li>
    @else
        <li>
            <span class="title">WAN</span>
            <strong>{!! $wan_avail ?? '' !!}%</strong>
        </li>
        <li>
            <span class="title">WLAN</span>
            <strong title="WLAN users">{!! $wlan_users ?? '' !!}</strong>
        </li>
        <li>
            <span class="title">LAN</span>
            <strong title="LAN users">{!! $lan_users ?? '' !!} </strong>
        </li>
    @endif
</ul>
