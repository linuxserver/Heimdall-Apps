<h2>{{ __('app.apps.config') }} ({{ __('app.optional') }}) @include('items.enable')</h2>

<div class="items" style="flex-direction:column">
    <div style="display:flex;flex-direction:row;">
        <div class="input">
            <label>{{ strtoupper(__('app.url')) }}</label>
            {!! Form::text('config[override_url]', isset($item) ? $item->getconfig()->override_url : null, ['placeholder' => __('app.apps.override'), 'id' => 'override_url', 'class' => 'form-control']) !!}
        </div>
        <div class="input">
            <label>{{ __('app.apps.password') }} (secret token)</label>
            {!! Form::input('password', 'config[password]', '', ['placeholder' => __('app.apps.password'), 'data-config' => 'password', 'class' => 'form-control config-item']) !!}
        </div>
    </div>
    <div style="display:flex;flex-direction:row;">
        <div class="input">
            <label>Time Range</label>
            {!! Form::select(
    'config[selected_range]',
    ['1d' => '1 Day', 'wtd' => 'Week To Date', 'ytd' => 'Year To Date', 'max' => 'MAX'],
    isset($item) ? $item->getconfig()->selected_range : null,
    ['data-config' => 'selected_range', 'class' => 'form-control config-item'],
) !!}
        </div>
        <div class="input">
            <label>Stats to show</label>
            {!! Form::select('config[availablestats][]', App\SupportedApps\Ghostfolio\Ghostfolio::getAvailableStats(), isset($item) ? $item->getConfig()->availablestats ?? null : null, ['multiple' => 'multiple']) !!}
        </div>
        <dsclass="input">
            <button style="margin-top: 32px;" class="btn test" id="test_config">Test</button>
    </div>
</div>