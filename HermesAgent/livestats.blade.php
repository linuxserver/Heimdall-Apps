<ul class="livestats">
    <li>
        <span class="title">Gateway</span>
        <strong>{{ $status }}</strong>
    </li>
    @if ($runs !== null)
        <li>
            <span class="title">API Runs</span>
            <strong>{{ number_format($runs) }}</strong>
        </li>
    @endif
</ul>
