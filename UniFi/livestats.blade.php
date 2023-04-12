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
			<span class="title">LAN</span>
			<strong>{!! $lan_users ?? '' !!} </strong>
		</li>
		<li>
			<span class="title">WLAN</span>
			<strong>{!! $wlan_users ?? '' !!}</strong>
		</li>
		<li>
			<span class="title">AP/DC</span>
			<strong>{!! $wlan_ap.'/'.$wlan_dc ?? '' !!} </strong>
		</li>
	@endif
</ul>
