<h2>{{ __('app.apps.config') }} ({{ __('app.optional') }}) @include('items.enable')</h2>
<div class="items">
    <div class="input">
        <label>{{ strtoupper(__('app.url')) }}</label>
        {!! Form::text('config[override_url]', isset($item) ? $item->getconfig()->override_url : null, ['placeholder' => __('app.apps.override'), 'id' => 'override_url', 'class' => 'form-control']) !!}
    </div>
    <div class="input">
        <label>{{ __('app.apps.apikey') }} (<a href="https://docs.gitea.com/next/development/api-usage#generating-and-listing-api-tokens" target="_blank">help?</a>)</label>
        {!! Form::text('config[apikey]', isset($item) ? $item->getconfig()->apikey : null, ['placeholder' => __('app.apps.apikey'), 'data-config' => 'apikey', 'class' => 'form-control config-item']) !!}
    </div>
    <div class="input">
        <label>Stats to show</label>
        {!! Form::select('config[availablestats][]', App\SupportedApps\Gitea\Gitea::getAvailableStats(), isset($item) ? $item->getConfig()->availablestats ?? null : null, ['multiple' => 'multiple']) !!}
    </div>
    <div class="input">
        <button style="margin-top: 32px;" class="btn test" id="test_config">Test</button>
    </div>
</div>
