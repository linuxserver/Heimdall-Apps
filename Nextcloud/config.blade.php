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
        <label>{{ __('app.apps.password') }}</label>
        {!! Form::password('config[password]', isset($item) ? $item->getconfig()->password : null, ['placeholder' => __('app.apps.password'), 'data-config' => 'password', 'class' => 'form-control config-item']) !!}
        <small>Security &raquo; App Password</small>
    </div>
    <div class="input">
        <label>Stats to show</label>
        {!! Form::select('config[availablestats][]', App\SupportedApps\Nextcloud\Nextcloud::getAvailableStats(), isset($item) ? $item->getConfig()->availablestats ?? null : null, ['multiple' => 'multiple']) !!}
    </div>
    <div class="input">
        <button style="margin-top: 32px;" class="btn test" id="test_config">Test</button>
    </div>
</div>
