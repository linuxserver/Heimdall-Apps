<ul class="livestats">
    <li>
        <span class="title">VM</span>
        <strong>{!! $vm_running !!}<span>/{!! $vm_total !!}</span></strong>
    </li>
    <li>
        <span class="title">LXC</span>
        <strong>{!! $container_running !!}<span>/{!! $container_total !!}</span></strong>
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
