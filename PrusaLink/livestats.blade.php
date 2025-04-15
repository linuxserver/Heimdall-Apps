<style>
strong { text-align: left }
span { text-align: left; margin-left: 0px; }
.livestats-container .livestats strong span { margin-left: 0px;}
</style>
<ul class="livestats">
@if ($state != 'OFFLINE')
    <li>
        <span class="title">Status/ETA</span>
        <strong>{!! $state !!}</strong>
        <strong>{!! $short_time_remaining !!}</strong>
    </li>
    <li>
        <span class="title">Temps</span>
        <strong><span>N: </span>{!! $temp_nozzle !!}</strong>
        <strong><span>B: </span>{!! $temp_bed !!}</strong>
    </li>
@else
    <li>
        <strong><span>STATUS: </span>{!! $state !!}</strong>
    </li>
@endif
</ul>