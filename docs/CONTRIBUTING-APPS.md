# Adding an app to Heimdall-Apps

There are two ways to add an app. Most people should use the **issue form**;
the **CLI** is for contributors who want to build the folder locally (and is
what the automation runs under the hood).

Every app lives in one folder at the repo root. The folder name is the app
name with spaces and punctuation stripped (e.g. `AdGuard Home` -> `AdGuardHome`),
and must be made up of ASCII letters and digits only.

## What a folder contains

A **foundation** app:

| File | Purpose |
| --- | --- |
| `app.json` | metadata (see below) |
| `<Folder>.php` | `class <Folder> extends \App\SupportedApps` |
| `<slug>.<ext>` | the icon (`.svg` preferred, or `.png` 100-275px) |

An **enhanced** app additionally ships:

| File | Purpose |
| --- | --- |
| `config.blade.php` | the configuration form |
| `livestats.blade.php` | the live-stats tile markup |

and its class implements `\App\EnhancedApps`, with `enhanced: true` in
`app.json`.

### `app.json`

```json
{
  "appid": "<sha1 of the name slug>",
  "name": "AdGuard Home",
  "website": "https://github.com/AdguardTeam/AdGuardHome",
  "license": "GNU General Public License v3.0 only",
  "description": "…",
  "enhanced": false,
  "tile_background": "dark",
  "icon": "adguardhome.png"
}
```

`appid` is **not** free-form: it is `sha1(slug(name))`, where `slug` reproduces
Laravel's `Str::slug(name, '')` — lower-case, ASCII-transliterate, map `@`→`at`,
then strip everything that is not a letter or number. The scaffolder computes
it for you; the CI test `should have appid equal to sha1 of the name slug`
enforces it. A handful of historical apps are grandfathered in
`apps.tests.js` (renamed apps that keep their original id); do not add to that
list without a matching real app.

## Option 1 — open an app-request issue (recommended)

1. Open a new issue and pick **App request**.
2. Fill in the name, website, license, tile background, description and attach
   an icon (`.svg` or `.png`).
3. A bot validates the request and comments back with the computed `appid`,
   folder name, and any problems (missing fields, duplicate app, no icon). It
   re-runs on every edit.
4. Once it looks good, a maintainer applies the `approved` label. The
   automation scaffolds the folder and opens a PR that closes your issue.

See [REQUEST-FLOW.md](REQUEST-FLOW.md) for the full pipeline.

## Option 2 — scaffold locally with the CLI

```sh
node scripts/scaffold.js "App Name" \
  --website https://example.com \
  --license "MIT License" \
  --description "What the app does." \
  --tile dark \
  --icon path/to/icon.svg \
  [--enhanced] [--out .]
```

This writes `<Folder>/` with `app.json`, `<Folder>.php` and the icon (renamed
to `<slug>.<ext>`). With `--enhanced` it also writes `config.blade.php` and
`livestats.blade.php` and makes the class implement `\App\EnhancedApps` — a
starting point you then fill in with the real API integration.

The icon's real type is detected from its bytes, not its name. SVGs should be
optimized before committing:

```sh
npx svgo --config .github/workflows/svgo.config.js <Folder>/<slug>.svg
```

`npm run scaffold -- "App Name" …` is an alias for the same command.

## Before you open a PR

Run the test suite; it must be green:

```sh
npm test            # app tests + unit tests
npm run test:apps   # just the per-app checks
npm run test:unit   # just the slug/parser/validation unit tests
```

The per-app checks verify the folder name, icon, `appid`, and that enhanced
apps are internally consistent (blades + interface) while foundation apps are
not.
