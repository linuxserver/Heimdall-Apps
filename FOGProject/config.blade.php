<h2>{{ __('app.apps.config') }} ({{ __('app.optional') }}) @include('items.enable')</h2>
<div class="items">
    <div class="input">
        <label>{{ strtoupper(__('app.url')) }}</label>
        {!! Form::text('config[override_url]', isset($item) ? $item->getconfig()->override_url : null, ['placeholder' => __('app.apps.override'), 'id' => 'override_url', 'class' => 'form-control']) !!}
    </div>
    <div class="input">
        <label>FOG API token</label>
        {!! Form::input('password', 'config[api_token]', isset($item) ? $item->getconfig()->api_token : null, ['placeholder' => 'FOG API token', 'data-config' => 'api_token', 'class' => 'form-control config-item']) !!}
    </div>
    <div class="input">
        <label>FOG user token</label>
        {!! Form::input('password', 'config[user_token]', isset($item) ? $item->getconfig()->user_token : null, ['placeholder' => 'FOG user token', 'data-config' => 'user_token', 'class' => 'form-control config-item']) !!}
    </div>
    <div class="input">
        <button style="margin-top: 32px;" class="btn test" id="test_config">Test</button>
    </div>
    {!! Form::hidden('config[dataonly]', '1') !!}
</div>
