<ul class="livestats">
    <li>
        <span class="title">Pods</span>
        <strong>{!! $podcast_count ?? 0 !!}</strong>
    </li>
    <li style="border-left:1px solid rgb(255 255 255 / 15%);border-right:1px solid rgb(255 255 255 / 15%);padding:0 8px;">
        <span class="title">Used</span>
        <strong>{!! $disk_used ?? '0' !!}</strong>
    </li>
    <li>
        <span class="title">Free</span>
        <strong>{!! $disk_free ?? '0' !!}</strong>
    </li>
</ul>
