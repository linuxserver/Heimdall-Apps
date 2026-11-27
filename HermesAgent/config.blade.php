<h2>{{ __('app.apps.config') }} ({{ __('app.optional') }}) @include('items.enable')</h2>
<div class="items">
    <div class="input">
        <label for="override_url">Hermes API URL</label>
        {!! Form::text('config[override_url]', isset($item) ? $item->getconfig()->override_url ?? null : null, ['placeholder' => 'http://hermes:8642', 'id' => 'override_url', 'data-config' => 'override_url', 'class' => 'form-control config-item']) !!}
        <small>Use the API server base URL, without /v1. The tile still opens your dashboard URL.</small>
    </div>
    <div class="input">
        <label for="hermes_api_key">{{ __('app.apps.apikey') }}</label>
        {!! Form::input('password', 'config[apikey]', isset($item) ? $item->getconfig()->apikey ?? null : null, ['placeholder' => 'API_SERVER_KEY', 'id' => 'hermes_api_key', 'data-config' => 'apikey', 'class' => 'form-control config-item']) !!}
        <small>Enable the Hermes API server and enter its API_SERVER_KEY.</small>
    </div>
    <div class="input">
        <button style="margin-top: 32px;" class="btn test" id="test_config">Test</button>
    </div>
</div>
