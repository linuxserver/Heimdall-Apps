<ul class="livestats">
	@if($error === true)
		<li>
			<span class="title">Error</span>
			<strong>{!! $statusCode !!}</strong>
		</li>
	@else
		<li>
			<span class="title">Users</span>
			<strong>{!! $users !!}</strong>
		</li>
		<li>
			<span class="title">Items</span>
			<strong>{!! $items !!}</strong>
		</li>
	@endif
</ul>
