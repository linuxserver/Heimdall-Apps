<h2>{{ __('app.apps.config') }} ({{ __('app.optional') }}) @include('items.enable')</h2>
<div class="items" style="flex-wrap: wrap;">
    <div class="input">
        <label>{{ strtoupper(__('app.url')) }}</label>
        {!! Form::text('config[override_url]', isset($item) ? $item->getconfig()->override_url : null, ['placeholder' => __('app.apps.override'), 'id' => 'override_url', 'class' => 'form-control']) !!}
    </div>
    <div class="input">
        <label>API Token</label>
        {!! Form::input('password', 'config[token]', isset($item) ? $item->getconfig()->token : null, ['placeholder' => 'dh_...', 'data-config' => 'token', 'class' => 'form-control config-item']) !!}
        <p style="font-size: .8em;">Create one in Dockhand under Settings &rarr; API Tokens. Leave empty if authentication is disabled on your instance.</p>
    </div>
    <div class="input">
        <label>Environment IDs</label>
        {!! Form::text('config[environments]', isset($item) ? $item->getconfig()->environments : null, ['placeholder' => 'Leave blank for all environments', 'data-config' => 'environments', 'class' => 'form-control config-item']) !!}
        <p style="font-size: .8em;">Comma separated list of environment IDs to count containers for.</p>
    </div>
    <div class="input">
        <label>Skip TLS verification</label>
        <div class="toggleinput" style="margin-top: 26px; padding-left: 15px;">
            {!! Form::hidden('config[ignore_tls]', 0, ['class' => 'config-item', 'data-config' => 'ignore_tls']) !!}
            <label class="switch">
                <?php
                $checked = false;
                if (isset($item) && !empty($item) && isset($item->getconfig()->ignore_tls)) {
                    $checked = $item->getconfig()->ignore_tls;
                }
                $set_checked = $checked ? ' checked="checked"' : '';
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
