<h2>{{ __('app.apps.config') }} ({{ __('app.optional') }}) @include('items.enable')</h2>
<div class="items">
    <input type="hidden" data-config="dataonly" class="config-item" name="config[dataonly]" value="1" />
    <div class="input">
        <label>{{ strtoupper(__('app.url')) }}</label>
        {!! Form::text('config[override_url]', isset($item) ? $item->getconfig()->override_url : null, ['placeholder' => __('app.apps.override'), 'id' => 'override_url', 'class' => 'form-control']) !!}
    </div>
    <div class="input">
        <label title="">Plex Token (<a href="https://support.plex.tv/articles/204059436-finding-an-authentication-token-x-plex-token/" target="_blank">help?</a>)</label>
        {!! Form::text('config[token]', isset($item) ? $item->getconfig()->token : null, ['placeholder' => __('token'), 'data-config' => 'token', 'class' => 'form-control config-item']) !!}
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
