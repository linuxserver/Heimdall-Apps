<h2>{{ __('app.apps.config') }} ({{ __('app.optional') }}) @include('items.enable')</h2>
<div class="items">
    <div class="input">
        <label>{{ strtoupper(__('app.url')) }}</label>
        {!! Form::text('config[override_url]', isset($item) ? $item->getconfig()->override_url : null, ['placeholder' => __('app.apps.override'), 'id' => 'override_url', 'class' => 'form-control']) !!}
    </div>
    <div class="input">
        <label>{{ __('app.apps.username') }}</label>
        {!! Form::text('config[username]', isset($item) ? $item->getconfig()->username : null, ['placeholder' => __('app.apps.username'), 'data-config' => 'username', 'class' => 'form-control config-item']) !!}
    </div>
    <div class="input">
        <label>{{ __('app.apps.apikey') }}</label>
        {!! Form::text('config[apikey]', isset($item) ? $item->getconfig()->apikey : null, ['placeholder' => __('app.apps.apikey'), 'data-config' => 'apikey', 'class' => 'form-control config-item']) !!}
    </div>
    <div class="input">
        <label>{{ __('app.apps.category') }}</label>
        {!! Form::text('config[category]', isset($item->getconfig()->category) ? $item->getconfig()->category : null, ['placeholder' => __('app.optional'), 'data-config' => 'category', 'class' => 'form-control config-item']) !!}
    </div>
    <div class="input">
		<label class="name">{{ __('app.apps.embedded') }}</label>
		{!! Form::select('config[embedded]', array('false' => __('app.options.no'), 'true' => __('app.options.yes')), isset($item->getconfig()->embedded) ? $item->getconfig()->embedded : 'false') !!}
    </div>	
    <div class="input">
        <button style="margin-top: 32px;" class="btn test" id="test_config">Test</button>
    </div>
</div>
