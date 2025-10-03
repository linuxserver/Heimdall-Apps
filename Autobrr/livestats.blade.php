<ul class="livestats">
    @if (isset($data['Filters']))
        <li>
            <span class="title">Filters</span>
            <strong>{{ $data['Filters'] }}</strong>
        </li>
    @endif
    @if (isset($data['IRC']))
        <li>
            <span class="title">IRC</span>
            <strong>{{ $data['IRC'] }}</strong>
        </li>
    @endif
</ul>

