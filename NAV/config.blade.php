<h2>{{ __('app.apps.config') }} ({{ __('app.optional') }}) @include('items.enable')</h2>
<style>
.help-text { padding: 20px; }
.help-text div { margin: 8px 0; }
.help-text ul { list-style-type: none; font-size: 80%; }
.help-text li { margin: 0.25rem; }
.help-text .title { color: #9094a5; font-size: 80%; }
.help-text .endpoint { display: inline-block; text-align: center; color: #fff; background-color: #027bbb; padding: 0.25rem 0.5rem 0.25rem; font-size: 0.6875rem; }
</style>
<div class="help-text">
    <div><span class="title">{{ __('app.apps.apikey') }}: </span><small>User and API Administration &raquo; API Token List &raquo; Create new token</small></div>
    <div><span class="title">Supported endpoints: </span><span class="endpoint">alerts</span> <span class="endpoint">netbox</span> <span class="endpoint">interface</span></div>
</div>
<div class="items">
    <div class="input">
        <label>{{ strtoupper(__('app.url')) }}</label>
        {!! Form::text('config[override_url]', null, array('placeholder' => __('app.apps.override'), 'id' => 'override_url', 'class' => 'form-control')) !!}
    </div>
    <div class="input">
        <label>{{ __('app.apps.apikey') }}</label>
        {!! Form::text('config[apikey]', isset($item) ? $item->getconfig()->apikey : null, ['placeholder' => __('app.apps.apikey'), 'data-config' => 'apikey', 'id' => 'apikey', 'class' => 'form-control config-item']) !!}
    </div>
    <div class="input">
        <button style="margin-top: 32px;" class="btn test" id="test_config">Test</button>
    </div>
</div>
