<h2>{{ __('app.apps.config') }} ({{ __('app.optional') }}) @include('items.enable')</h2>
<div class="items">
    <div class="input">
        <label>{{ strtoupper(__('app.url')) }}</label>
        {!! Form::text('config[override_url]', isset($item) ? $item->getconfig()->override_url ?? null : null, ['placeholder' => __('app.apps.override'), 'id' => 'override_url', 'class' => 'form-control']) !!}
    </div>
    <div class="input">
        <label>Unified API key</label>
        {!! Form::input('password', 'config[api_key]', isset($item) ? $item->getconfig()->api_key ?? null : null, ['placeholder' => 'Unified API key', 'data-config' => 'api_key', 'class' => 'form-control config-item', 'autocomplete' => 'off']) !!}
        <small>Use the unified key from FreeLLMAPI Settings.</small>
    </div>
    <div class="input">
        <button style="margin-top: 32px;" class="btn test" id="test_config">Test</button>
    </div>
</div>
