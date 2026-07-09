"use strict";

/**
 * File templates for a scaffolded app.
 *
 * Stubs are based on the canonical Heimdall request-site templates
 * (storage/templates/{foundation,enhanced,config,livestats}) cross-checked
 * against shipped apps (WLED / Bitwarden foundation, Sonarr / AdGuardHome
 * enhanced). Trailing whitespace has been removed so the generated PHP passes
 * the repo's PSR12 phpcs / php-lint checks.
 *
 * The class/namespace name is always the validated folder name, which is
 * guaranteed to match /^[A-Za-z0-9]+$/ by the caller.
 */

const { appid } = require("./slug");

/**
 * app.json with fields in the repo's canonical order.
 * @param {object} a
 * @returns {string} JSON text with a trailing newline (2-space indent)
 */
function buildAppJson(a) {
    const obj = {
        appid: appid(a.name),
        name: a.name,
        website: a.website,
        license: a.license,
        description: a.description,
        enhanced: a.enhanced === true,
        tile_background: a.tile_background,
        icon: a.icon,
    };
    return JSON.stringify(obj, null, 2) + "\n";
}

/**
 * Foundation PHP class.
 * @param {string} folder
 * @returns {string}
 */
function foundationPhp(folder) {
    return `<?php

namespace App\\SupportedApps\\${folder};

class ${folder} extends \\App\\SupportedApps
{
}
`;
}

/**
 * Enhanced PHP class stub (a starting point for a live-stats integration).
 * @param {string} folder
 * @returns {string}
 */
function enhancedPhp(folder) {
    return `<?php

namespace App\\SupportedApps\\${folder};

class ${folder} extends \\App\\SupportedApps implements \\App\\EnhancedApps
{
    public $config;

    //protected $login_first = true; // Uncomment if api requests need to be authed first
    //protected $method = 'POST';  // Uncomment if requests to the API should be set by POST

    public function __construct()
    {
        //$this->jar = new \\GuzzleHttp\\Cookie\\CookieJar; // Uncomment if cookies need to be set
    }

    public function test()
    {
        $test = parent::appTest($this->url('status'));
        echo $test->status;
    }

    public function livestats()
    {
        $status = 'inactive';
        $res = parent::execute($this->url('status'));
        $details = json_decode($res->getBody());

        $data = [];
        return parent::getLiveStats($status, $data);
    }

    public function url($endpoint)
    {
        $api_url = parent::normaliseurl($this->config->url) . $endpoint;
        return $api_url;
    }
}
`;
}

// config.blade.php stub (URL + username/password), from the canonical template.
const CONFIG_BLADE = `<h2>{{ __('app.apps.config') }} ({{ __('app.optional') }}) @include('items.enable')</h2>
<div class="items">
    <div class="input">
        <label>{{ strtoupper(__('app.url')) }}</label>
        {!! Form::text('config[override_url]', null, array('placeholder' => __('app.apps.override'), 'id' => 'override_url', 'class' => 'form-control')) !!}
    </div>
    <div class="input">
        <label>{{ __('app.apps.username') }}</label>
        {!! Form::text('config[username]', null, array('placeholder' => __('app.apps.username'), 'data-config' => 'username', 'class' => 'form-control config-item')) !!}
    </div>
    <div class="input">
        <label>{{ __('app.apps.password') }}</label>
        {!! Form::input('password', 'config[password]', '', ['placeholder' => __('app.apps.password'), 'data-config' => 'password', 'class' => 'form-control config-item']) !!}
    </div>
    <div class="input">
        <button style="margin-top: 32px;" class="btn test" id="test_config">Test</button>
    </div>
</div>
`;

// livestats.blade.php stub, from the canonical template.
const LIVESTATS_BLADE = `<ul class="livestats">
    <li>
        <span class="title">Queue</span>
        <strong>{!! $queue_size !!}</strong>
    </li>
    <li>
        <span class="title">Speed</span>
        <strong>{!! $current_speed !!}</strong>
    </li>
</ul>
`;

module.exports = {
    buildAppJson,
    foundationPhp,
    enhancedPhp,
    CONFIG_BLADE,
    LIVESTATS_BLADE,
};
