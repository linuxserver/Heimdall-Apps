<ul class="livestats">
    <li>
        <span class="title">Total<br>Providers</span>
        <strong>{{ $providers === null ? 'Unavailable' : number_format($providers) }}</strong>
    </li>
    <li>
        <span class="title">Attention<br>Needed</span>
        <strong>{{ $attention === null ? 'Unavailable' : number_format($attention) }}</strong>
    </li>
</ul>
