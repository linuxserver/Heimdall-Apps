<h2>{{ __('app.apps.config') }} ({{ __('app.optional') }}) @include('items.enable')</h2>
<div class="items">
    <div class="input">
        <label>Stats to show</label>
        {!! Form::select('config[availablestats][]', App\SupportedApps\AVMFritzbox\AVMFritzbox::getAvailableStats(), isset($item) ? $item->getConfig()->availablestats ?? null : null, ['multiple' => 'multiple']) !!}
    </div>
    <div class="input">
        <button style="margin-top: 32px;" class="btn test" id="test_config">Test</button>
    </div>
</div>
