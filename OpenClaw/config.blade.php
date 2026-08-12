@php
    $storedDeviceKey = isset($item) ? ($item->getconfig()->device_private_key ?? null) : null;
    $devicePrivateKey = App\SupportedApps\OpenClaw\OpenClaw::deviceKeypairFor($storedDeviceKey);
    $deviceId = App\SupportedApps\OpenClaw\OpenClaw::deviceIdFor($devicePrivateKey);
@endphp
<h2>{{ __('app.apps.config') }} ({{ __('app.optional') }}) @include('items.enable')</h2>
<div class="items">
    <div class="input">
        <label>{{ strtoupper(__('app.url')) }}</label>
        {!! Form::text('config[override_url]', isset($item) ? $item->getconfig()->override_url : null, ['placeholder' => __('app.apps.override'), 'data-config' => 'override_url', 'class' => 'form-control config-item']) !!}
    </div>
    <div class="input">
        <label>Gateway Token <small>(only one of Token / Password is required)</small></label>
        {!! Form::text('config[token]', isset($item) ? $item->getconfig()->token : null, ['placeholder' => 'Gateway Token', 'data-config' => 'token', 'class' => 'form-control config-item']) !!}
    </div>
    <div class="input">
        <label>{{ __('app.apps.password') }} <small>(only one of Token / Password is required)</small></label>
        {!! Form::input('password', 'config[password]', '', ['placeholder' => __('app.apps.password'), 'data-config' => 'password', 'class' => 'form-control config-item']) !!}
    </div>
    {!! Form::hidden('config[device_private_key]', $devicePrivateKey, ['data-config' => 'device_private_key', 'class' => 'config-item']) !!}
    <div class="input">
        <label>Device ID</label>
        {!! Form::text('device_id_display', $deviceId, ['class' => 'form-control', 'readonly' => 'readonly', 'onclick' => 'this.select()']) !!}
        @if (isset($item))
            <small>
                On the OpenClaw host, run <code>openclaw devices approve --latest</code> (match against <code>openclaw devices list</code> if more than one request is pending), then click Test.
            </small>
        @else
            <small>
                This Device ID is regenerated on every reload until you Save. Save first, then reopen this item's Edit page to see the Device ID you actually need to approve.
            </small>
        @endif
    </div>
    <div class="input">
        <button style="margin-top: 32px;" class="btn test" id="test_config">Test</button>
    </div>
</div>
