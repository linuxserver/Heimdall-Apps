<h2>{{ __('app.apps.config') }} ({{ __('app.optional') }}) @include('items.enable')</h2>
<div class="items" style="flex-direction:column">
    <div style="display:flex;flex-direction:row;">
        <div class="input">
            <label>{{ strtoupper(__('app.url')) }}</label>
            {!! Form::text('config[override_url]', isset($item) ? $item->getconfig()->override_url : null, ['placeholder' => __('app.apps.override'), 'id' => 'override_url', 'class' => 'form-control']) !!}
        </div>
        <div class="input">
            <label>Access token</label>
            {!! Form::input('password', 'config[token]', isset($item) ? $item->getconfig()->token : null, ['placeholder' => __('Access token'), 'data-config' => 'token', 'class' => 'form-control config-item']) !!}
        </div>
    </div>
    <div style="display:flex;flex-direction:row;">
        <div class="input">
            <label>First stat title</label>
            {!! Form::text('config[first_stat_label]', isset($item) ? $item->getconfig()->first_stat_label : null, ['placeholder' => __('Default: Total lights'), 'data-config' => 'first_stat_label', 'class' => 'form-control config-item']) !!}
        </div>
        <div class="input">
            <label>First stat template</label>
            {!! Form::text('config[first_stat_template]', isset($item) ? $item->getconfig()->first_stat_template : null, [
                'placeholder' => __('Default: @{{ states.light | count }}'),
                'data-config' => 'first_stat_template',
                'class' => 'form-control config-item',
            ]) !!}
        </div>
    </div>
    <div style="display:flex;flex-direction:row;">
        <div class="input">
            <label>Second stat title</label>
            {!! Form::text('config[second_stat_label]', isset($item) ? $item->getconfig()->second_stat_label : null, ['placeholder' => __('Default: Total lights On'), 'data-config' => 'second_stat_label', 'class' => 'form-control config-item']) !!}
        </div>
        <div class="input">
            <label>Second stat template</label>
            {!! Form::text('config[second_stat_template]', isset($item) ? $item->getconfig()->second_stat_template : null, [
                'placeholder' => __('Default: @{{ states.light | selectattr(\'state\',\'equalto\',\'on\') | list | count }}'),
                'data-config' => 'second_stat_template',
                'class' => 'form-control config-item',
            ]) !!}
        </div>
    </div>
    <div class="input">
        <button style="margin-top: 32px;" class="btn test" id="test_config">Test</button>
    </div>
</div>
<a href="https://www.home-assistant.io/docs/configuration/templating" target="_blank" style="text-decoration:none;margin:20px;">Template documentation</a>
