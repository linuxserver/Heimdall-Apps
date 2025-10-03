<h2>{{ __('app.apps.config') }} ({{ __('app.optional') }}) @include('items.enable')</h2>
<div class="items">
    <div class="input">
        <label>Moonraker Port</label>
        {!! Form::input('text', 'config[moonraker_port]', '', ['placeholder' => '7125', 'data-config' => 'moonraker_port', 'class' => 'form-control config-item']) !!}
    </div>
    <div class="input">
        <button style="margin-top: 32px;" class="btn test" id="test_config">Test</button>
    </div>
</div>
