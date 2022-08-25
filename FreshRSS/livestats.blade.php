@if ($embed == null)
<ul id="rssfeeds" class="livestats">
    <li>
        <span class="title" style="color:orange;">{!! $unread !!} </span>
    </li>
</ul>
@endif
@if ($embed != null)
<div id="rssfeeds" class="rssfeed" style="position: relative;left: -90px;top: 35px;">
	<div style="position: fixed;overflow: hidden;">
		<div style="display: inline-block;background-color: #161b1f;width: 279px;background-image: linear-gradient(90deg, rgba(255, 255, 255, 0), rgba(255, 255, 255, 0.25));border-radius: 0px 0px 6px 6px;">
		@foreach ($feed as $feedEntry)
		<ul style="margin-left: -25px;width: 295px;overflow: hidden;">
			<li style="list-style-type:none;">
			<a href='{!! $feedEntry["link"] !!}' target="_blank" rel="noopener noreferrer" style="background-color: transparent;text-decoration: none !important;">
				<span class="title" style="color:orange !important;white-space:nowrap;">{!! $feedEntry["title"] !!} </span>
			</a>
			</li>
		</ul>
		@endforeach
	</div>
</div>
@endif
<script>
@if ($category != null)
$("#rssfeeds").parents("div.details").children("div.title.white").html("{!! $category !!}");	
$("#rssfeeds").parents("div.details").children("div.title.white").css("width","141px");
$("#rssfeeds").parents("div.details").children("div.title.white").css("white-space","nowrap");
$("#rssfeeds").parents("div.details").children("div.title.white").css("overflow","hidden");
@endif
@if ($embed != null)
$("#rssfeeds").parents("div.item").css( "border-radius", "6px 6px 0px 0px" );
@endif
</script>


