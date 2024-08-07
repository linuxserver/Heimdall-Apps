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
        {!! Form::input('password', 'config[password]', '', ['placeholder' => __('app.apps.password'), 'data-config' => 'password', 'class' => 'form-control config-item']) !!}
    </div>
    <div class="input">
        <label>Stats to show</label>
        {!! Form::select('config[availablestats][]', App\SupportedApps\Monit\Monit::getAvailableStats(), isset($item) && isset($item->getconfig()->availablestats) ? $item->getconfig()->availablestats : null, ['multiple' => 'multiple', 'class' => 'form-control config-item']) !!}
    </div>
    <div class="input">
        <button style="margin-top: 32px;" class="btn test" id="test_config">Test</button>
    </div>
</div>

<script>
    document.getElementById('test_config').addEventListener('click', function() {
        var username = document.querySelector('[data-config="username"]').value;
        var password = document.querySelector('[data-config="password"]').value;
        var url = document.getElementById('override_url').value;

        console.log('Username:', username);
        console.log('Password:', password);
        console.log('URL:', url);

        if (!username || !password || !url) {
            alert('URL, Username, and Password are required');
            return;
        }

        fetch(url, {
            method: 'GET',
            headers: {
                'Authorization': 'Basic ' + btoa(username + ':' + password),
                'Content-Type': 'application/json',
            },
        })
        .then(response => {
            if (response.status === 200) {
                alert('Successfully communicated with the API');
            } else {
                alert('Failed: Invalid credentials');
            }
        })
        .catch(error => {
            alert('Error testing Monit configuration');
            console.error('Error:', error);
        });
    });
</script>
