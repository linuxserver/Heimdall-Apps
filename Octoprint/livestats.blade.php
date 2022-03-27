<ul class="livestats">
    @if (isset($error))
        <li>
            <span class="title">ERROR</span>
            <strong>{!! $error !!}</strong>
        </li>
    @else
        @if (isset($progress))
            <li>
                <span class="title">Progress</span>
                <strong>{!! $progress !!}<span>%</span></strong>
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
