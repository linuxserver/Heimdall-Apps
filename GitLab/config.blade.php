<h2>{{ __('app.apps.config') }} ({{ __('app.optional') }}) @include('items.enable')</h2>
<div class="items">
    <div class="input">
        <label>{{ strtoupper('Health Token') }}</label>
        {!! Form::text('config[health_apikey]', null, array('placeholder' => 'Health Token', 'id' => 'health_apikey', 'class' => 'form-control config-item')) !!}
    </div>
    <div class="input">
        <label>{{ strtoupper('Private API-Read Token') }}</label>
        {!! Form::text('config[private_apikey]', null, array('placeholder' => __('app.apps.apikey'), 'id' => 'private_apikey', 'class' => 'form-control config-item')) !!}
    </div>
    <div class="input">
        <button style="margin-top: 32px;" class="btn test" id="test_config">Test</button>
    </div>
</div>

