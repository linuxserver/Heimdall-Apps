<h2>{{ __('app.apps.config') }} ({{ __('app.optional') }}) @include('items.enable')</h2>
<div class="items">
    <input type="hidden" data-config="dataonly" class="config-item" name="config[dataonly]" value="1" />
    <div class="input">
        <label>{{ strtoupper(__('app.url')) }}</label>
        {!! Form::text('config[override_url]', isset($item) ? $item->getconfig()->override_url : null, ['placeholder' => __('app.apps.override'), 'id' => 'override_url', 'class' => 'form-control']) !!}
    </div>
	<div class="input">
		<label>Username</label>
		{!! Form::input('text', 'config[username]', isset($item) ? $item->getconfig()->username : null, ['placeholder' => __('Username'), 'data-config' => 'username', 'class' => 'form-control config-item']) !!}
	</div>
	<div class="input">
		<label>Password</label>
		{!! Form::input('password', 'config[password]', isset($item) ? $item->getconfig()->password : null, ['placeholder' => __('Password'), 'data-config' => 'password', 'class' => 'form-control config-item']) !!}
	</div>
    <div class="input">
        <button style="margin-top: 32px;" class="btn test" id="test_config">Test</button>
    </div>
</div>
