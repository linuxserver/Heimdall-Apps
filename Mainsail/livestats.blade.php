<ul class="livestats">
    @if (isset($state))
        <li>
            <span class="title">State</span>
            <strong>{!! $state !!}</strong>
        </li>
    @else
        @if (isset($progress))
            <li>
                <span class="title">Progress</span>
                <strong>{!! $progress !!}<span>%</span></strong>
            </li>
        @endif
        @if (isset($completed_pct))
            <li class="right">
                <span class="title">Complete</span>
                <strong>{!! $completed_pct !!}</strong>
            </li>
        @endif        
        @if (isset($estimated))
            <li class="right">
                <span class="title">Finish</span>
                <strong>{!! $estimated !!}</strong>
            </li>
        @endif
    @endif
</ul>
