<h2>{{ __('app.apps.config') }} ({{ __('app.optional') }}) @include('items.enable')</h2>
<div class="items" style="flex-wrap: wrap;">
    <div class="input">
        <label>{{ strtoupper(__('app.url')) }}</label>
        {!! Form::text('config[override_url]', isset($item) ? $item->getconfig()->override_url : null, ['placeholder' => __('app.apps.override'), 'id' => 'override_url', 'class' => 'form-control']) !!}
    </div>
    <div class="input">
        <label>{{ __('app.apps.apikey') }}</label>
        {!! Form::text('config[apikey]', isset($item) ? $item->getconfig()->apikey : null, ['placeholder' => __('app.apps.apikey'), 'data-config' => 'apikey', 'class' => 'form-control config-item']) !!}
    </div>
    <div class="input">
        <button style="margin-top: 32px;" class="btn test" id="test_config">Test</button>
    </div>
    <div class="input">
        <label>Requests count</label>
        {!! Form::select('config[requests]', ['pending' => 'Pending', 'approved' => 'Approved', 'declined' => 'Declined', 'processing' => 'Processing', 'available' => 'Available', 'movie' => 'Movie', 'tv' => 'TV', 'total' => 'Total'], isset($item) && isset($item->getconfig()->requests) ? $item->getconfig()->requests : null, [
            'id' => 'requests',
            'class' => 'form-control config-item',
        ]) !!}
    </div>
    <div class="input">
        <label>Issues count</label>
        {!! Form::select('config[issues]', ['open' => 'Open', 'closed' => 'Closed', 'video' => 'Video', 'audio' => 'Audio', 'subtitles' => 'Subtitles', 'others' => 'Others', 'total' => 'Total'], isset($item) && isset($item->getconfig()->issues) ? $item->getconfig()->issues : null, [
            'id' => 'issues',
            'class' => 'form-control config-item',
        ]) !!}
    </div>
</div>
