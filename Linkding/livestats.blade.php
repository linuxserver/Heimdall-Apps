<ul class="livestats">
    <li>
        <span class="title">Bookmarks</span>
        @if ($bookmark_count >== 1000)
            <strong>{!! $bookmark_count !!}</strong>
        @else
            <strong class="text-danger">1000+</strong>
        @endif
    </li>
</ul>