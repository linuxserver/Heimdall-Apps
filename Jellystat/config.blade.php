<h2>{{ __('app.apps.config') }} ({{ __('app.optional') }}) @include('items.enable')</h2>
<div class="items">
    <div class="input">
        <label>{{ strtoupper(__('app.url')) }}</label>
        {!! Form::text('config[override_url]', isset($item) ? $item->getconfig()->override_url : null, ['placeholder' => __('app.apps.override'), 'id' => 'override_url', 'class' => 'form-control']) !!}
    </div>
    <div class="input">
        <label>API token</label>
        {!! Form::input('text', 'config[x_api_token]', isset($item) ? $item->getconfig()->x_api_token : null, ['placeholder' => __('Api Token'), 'data-config' => 'x_api_token', 'class' => 'form-control config-item']) !!}
    </div>
    <div class="input">
        <label>Stats to show</label>
        <!--{!! Form::select('config[availablestats][]', App\SupportedApps\Jellystat\Jellystat::getAvailableStats(), isset($item) ? $item->getConfig()->availablestats ?? null : null, ['multiple' => 'multiple']) !!} -->
        {!! Form::select('config[availablestats][]', App\SupportedApps\Jellystat\Jellystat::getAvailableStats(), isset($item) ? $item->getConfig()->availablestats ?? null : null) !!}
    </div>
    <div class="input">
        <label>Debug (1 == yes)</label>
        {!! Form::select('config[debug][]', App\SupportedApps\Jellystat\Jellystat::getDebugStatus(), isset($item) ? $item->getconfig()->debug ?? null : null) !!}
    </div>
    <div class="input">
        <button style="margin-top: 32px;" class="btn test" id="test_config">Test</button>
    </div>
</div>
