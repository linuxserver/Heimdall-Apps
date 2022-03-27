<h2>{{ __('app.apps.config') }} ({{ __('app.optional') }}) @include('items.enable')</h2>
<div class="items" style="flex-wrap: wrap;">
    <div class="input">
        <label>{{ strtoupper(__('app.url')) }}</label>
        {!! Form::text('config[override_url]', isset($item) ? $item->getconfig()->override_url : null, ['placeholder' => __('app.apps.override'), 'id' => 'override_url', 'class' => 'form-control']) !!}
    </div>
    <div class="input">
        <label>Node(s) (comma separated list)</label>
        {!! Form::text('config[nodes]', isset($item) ? $item->getconfig()->nodes : null, ['placeholder' => 'Leave blank for all nodes in cluster', 'data-config' => 'nodes', 'class' => 'form-control config-item']) !!}
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
        <label>API Token ID</label>
        {!! Form::text('config[token_id]', isset($item) ? $item->getconfig()->token_id : null, ['placeholder' => 'user@realm!token_name', 'data-config' => 'token_id', 'class' => 'form-control config-item']) !!}
    </div>
    <div class="input">
        <label>{{ __('app.apps.apikey') }}</label>
        {!! Form::input('password', 'config[token_value]', isset($item) ? $item->getconfig()->token_value : null, ['placeholder' => '01234567-89ab-cdef-0123-456789abcdef', 'data-config' => 'token_value', 'class' => 'form-control config-item']) !!}
        <p style="font-size: .8em;">Requires at least <code>Sys.Audit</code> permission for <code>/nodes</code> path (and propagated).</p>
    </div>
    <div class="input">
        <button style="margin-top: 32px;" class="btn test" id="test_config">Test</button>
    </div>
</div>
