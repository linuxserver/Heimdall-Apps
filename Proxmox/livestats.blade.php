<ul class="livestats">
    <li>
        <span class="title">VM</span>
        <strong><span>{!! $vm_running !!}/{!! $vm_total !!}</span></strong>
    </li>
    <li>
        <span class="title">LXC</span>
        <strong><span>{!! $container_running !!}/{!! $container_total !!}</span></strong>
    </li>
    <li>
        <span class="title">CPU</span>
        <strong>{!! round($cpu_percent, 1) !!}%</strong>
    </li>
    <li>
        <span class="title">RAM</span>
        <strong>{!! round($memory_percent, 1) !!}%</strong>
    </li>
</ul>
