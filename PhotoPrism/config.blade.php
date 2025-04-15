<h2>{{ __('app.apps.config') }} ({{ __('app.optional') }}) @include('items.enable')</h2>
<div class="items">
    <div class="input">
        <label>{{ strtoupper(__('app.url')) }}</label>
        {!! Form::text('config[override_url]', isset($item) ? $item->getconfig()->override_url : null, ['placeholder' => __('app.apps.override'), 'id' => 'override_url', 'class' => 'form-control']) !!}
        </div>
    <div class="input">
        <label title="">Session ID (<a href="https://docs.photoprism.app/developer-guide/api/#with-authentication" target="_blank">help?</a>)</label>
        {!! Form::text('config[session]', isset($item) ? $item->getconfig()->session : null, ['placeholder' => __('Session ID'), 'data-config' => 'session', 'class' => 'form-control config-item']) !!}
    </div>
    <div class="input">
        <button style="margin-top: 32px;" class="btn test" id="test_config">Test</button>
    </div>
</div>
