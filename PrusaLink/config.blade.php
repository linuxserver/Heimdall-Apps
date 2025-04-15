<h2>{{ __('app.apps.config') }} ({{ __('app.optional') }}) @include('items.enable')</h2>
<div class="items">
    <div class="input">
        <label>{{ strtoupper(__('app.url')) }}</label>
        {!! Form::text('config[override_url]', isset($item) ? $item->getconfig()->override_url : null, ['placeholder' => __('app.apps.override'), 'id' => 'override_url', 'class' => 'form-control']) !!}
    </div>
    <div class="input">
        <label>{{ __('app.apps.apikey') }}</label>
        {!! Form::input('password', 'config[apikey]', isset($item) ? $item->getconfig()->apikey : null, ['placeholder' => __('app.apps.apikey'), 'data-config' => 'apikey', 'class' => 'form-control config-item']) !!}
    </div>
    <div class="input">
        <button style="margin-top: 32px;" class="btn test" id="test_config">Test</button>
    </div>
</div>
<label style="text-decoration:none;margin:20px;">To obtain the API key: On your printer, navigate to Settings > Network > Prusalink to get the username and password. The API key is the password shown on this screen.</label>