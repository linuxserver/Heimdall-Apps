<h2>{{ __('app.apps.config') }} ({{ __('app.optional') }}) @include('items.enable')</h2>
<div class="items">
    <div class="input">
        <label>{{ strtoupper(__('app.url')) }}</label>
        {!! Form::text('config[override_url]', isset($item) ? $item->getconfig()->override_url : null, ['placeholder' => __('app.apps.override'), 'id' => 'override_url', 'class' => 'form-control']) !!}
    </div>
    <div class="input">
        <label>{{ __('app.apps.username') }}</label>
        {!! Form::text('config[username]', isset($item) ? $item->getconfig()->username ?? null : null, ['placeholder' => __('app.apps.username'), 'data-config' => 'username', 'class' => 'form-control config-item']) !!}
    </div>
    <div class="input">
        <label>{{ __('app.apps.password') }}</label>
        {!! Form::input('password', 'config[password]', '', ['placeholder' => __('app.apps.password'), 'data-config' => 'password', 'class' => 'form-control config-item']) !!}
    </div>
    <div class="input">
        <label>Stats to show</label>
        {{-- Plain select, because Form::select doesn't mark the saved values as selected --}}
        @php($selectedStats = (array) ((isset($item) ? $item->getConfig()->availablestats ?? null : null) ?: ['people', 'families']))
        <select name="config[availablestats][]" id="config[availablestats][]" multiple="multiple">
            @foreach (App\SupportedApps\GrampsWeb\GrampsWeb::getAvailableStats() as $key => $label)
                <option value="{{ $key }}" {{ in_array($key, $selectedStats) ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="input">
        <button style="margin-top: 32px;" class="btn test" id="test_config">Test</button>
    </div>
</div>
