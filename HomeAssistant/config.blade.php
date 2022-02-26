<h2>{{ __('app.apps.config') }} ({{ __('app.optional') }}) @include('items.enable')</h2>
<div class="items">
    <div class="input">
        <label>{{ strtoupper(__('app.url')) }}</label>
        {!! Form::text('config[override_url]', (isset($item) ? $item->getconfig()->override_url : null), array('placeholder' => __('app.apps.override'), 'id' => 'override_url', 'class' => 'form-control')) !!}
    </div>
    <div class="input">
        <label>Access token</label>
        {!! Form::input('password', 'config[token]', (isset($item) ? $item->getconfig()->token : null), array('placeholder' =>  __('Access token'), 'data-config' => 'token', 'class' => 'form-control config-item')) !!}
    </div>
    <div class="input">
        <label>First stat title</label>
        {!! Form::text('config[first_stat_label]', (isset($item) ? $item->getconfig()->first_stat_label : null), array('placeholder' => __('Default: Total lights'), 'data-config' => 'first_stat_label', 'class' => 'form-control config-item')) !!}
    </div>
    <div class="input">
        <label>First stat template (<a href="https://www.home-assistant.io/docs/configuration/templating" target="_blank">template documentation</a>)</label>
        {!! Form::text('config[first_stat_template]', (isset($item) ? $item->getconfig()->first_stat_template : null), array('placeholder' => __('Default: \{\{ states.light \| count\}\}'), 'data-config' => 'first_stat_template', 'class' => 'form-control config-item')) !!}
    </div>
    <div class="input">
        <label>Second stat title</label>
        {!! Form::text('config[second_stat_label]', (isset($item) ? $item->getconfig()->second_stat_label : null), array('placeholder' => __('Default: Total lights On'), 'data-config' => 'second_stat_label', 'class' => 'form-control config-item')) !!}
    </div>
    <div class="input">
        <label>Second stat template (<a href="https://www.home-assistant.io/docs/configuration/templating" target="_blank">template documentation</a>)</label>
        {!! Form::text('config[second_stat_template]', (isset($item) ? $item->getconfig()->second_stat_template : null), array('placeholder' => __('Default: \{\{ states.switch \| selectattr(\'state\',\'equalto\',\'on\') \| list \| count \}\}'), 'data-config' => 'second_stat_template', 'class' => 'form-control config-item')) !!}
    </div>
    <div class="input">
        <button style="margin-top: 32px;" class="btn test" id="test_config">Test</button>
    </div>
</div>