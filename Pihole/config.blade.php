<h2>{{ __('app.apps.config') }} ({{ __('app.optional') }}) @include('items.enable')</h2>
<div class="items">
	<input type="hidden" data-config="dataonly" class="config-item" name="config[dataonly]" value="1" />
	<div class="input">
		<label>{{ strtoupper(__('app.url')) }}</label>
		{!! Form::text('config[override_url]', isset($item) ? $item->getconfig()->override_url : null, ['placeholder' => __('app.apps.override'), 'id' => 'override_url', 'class' => 'form-control']) !!}
	</div>
	<div class="input">
        <label>Api Key/App Password(v6)</label>
		{!! Form::text('config[apikey]', isset($item) && property_exists($item->getconfig(), 'apikey') ? $item->getconfig()->apikey : null, ['placeholder' => __('app.apps.apikey'), 'data-config' => 'apikey', 'class' => 'form-control config-item']) !!}
	</div>
<div class="items">
    <div class="input">
        <label>Version</label>
        {!! Form::select(
            'config[version]',
            ['5' => 'v5', '6' => 'v6'],
            isset($item) ? $item->getconfig()->version : null,
            ['data-config' => 'version', 'class' => 'form-control config-item'],
        ) !!}
    </div>
</div>
	<div class="input">
		<button style="margin-top: 32px;" class="btn test" id="test_config">Test</button>
	</div>
</div>
