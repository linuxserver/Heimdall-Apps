<h2>{{ __('app.apps.config') }} ({{ __('app.optional') }}) @include('items.enable')</h2>
<div class="items">
	<input type="hidden" data-config="dataonly" class="config-item" name="config[dataonly]" value="1" />
	<div class="input">
		<label>{{ strtoupper(__('app.url')) }}</label>
		{!! Form::text('config[override_url]', isset($item) ? $item->getconfig()->override_url : null, ['placeholder' => __('app.apps.override'), 'id' => 'override_url', 'class' => 'form-control']) !!}
	</div>
	<div class="input">
		<label>Skip TLS verification</label>
		<div class="toggleinput" style="margin-top: 26px; padding-left: 15px;">
			{!! Form::hidden('config[ignore_tls]', 0, ['class' => 'config-item', 'data-config' => 'ignore_tls']) !!}
		<label class="switch">
			checked="checked"' : '';
                ?>
                <input type="checkbox" class="config-item" data-config="ignore_tls" name="config[ignore_tls]" value="1" <?php echo $set_checked; ?> />
			<span class="slider round"></span>
		</label>
	</div>
</div>
<div class="input">
	<button style="margin-top: 32px;" class="btn test" id="test_config">Test</button>
</div>
</div>
