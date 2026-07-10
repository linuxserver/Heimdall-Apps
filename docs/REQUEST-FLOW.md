# App-request flow

This repo turns an **App request** issue into a scaffolded pull request, with a
maintainer approval gate in the middle. Nothing is written to the repo until a
maintainer explicitly approves.

## Sequence

```
Requester            GitHub                     Maintainer
    |  open App request issue  |                     |
    |------------------------->|  (auto-labels: app-request)
    |                          |
    |            app-request-validate.yml (issues: opened/edited)
    |                          |  parse + validate + collision check
    |<-- bot comment ----------|  labels: request-valid | request-invalid
    |  (edit to fix problems)  |
    |------------------------->|  (validate re-runs on every edit)
    |                          |
    |                          |<--- add `approved` label -----|
    |                          |
    |            app-request-scaffold.yml (issues: labeled == approved)
    |                          |  re-validate, download icon (allow-listed),
    |                          |  scaffold folder, open PR (Closes #N)
    |<-- bot comment: PR link -|
    |                          |         review + merge PR ---->|
    |                          |  main.yml deploys the app list to gh-pages
```

## The three moving parts

1. **`.github/ISSUE_TEMPLATE/app-request.yml`** — the issue form. Auto-applies
   the `app-request` label and collects name, website, license, tile
   background, icon (native `upload` field) and description, plus an "I intend
   to build this as an Enhanced app myself" checkbox.

2. **`.github/workflows/app-request-validate.yml`** (`issues: opened, edited`,
   `permissions: issues: write`). Runs `scripts/validate-request.js`, which
   parses the form body, computes the `appid`/folder, checks for collisions
   against the repo **and** the live `list.json`, comments the result, and sets
   `request-valid` / `request-invalid`.

3. **`.github/workflows/app-request-scaffold.yml`** (`issues: labeled`,
   `permissions: contents: write, pull-requests: write, issues: write`). Only
   proceeds when the triggering label is `approved` **and** the issue already
   carries `app-request`. Runs `scripts/scaffold-from-issue.js`, which
   re-validates, downloads the icon from an allow-listed GitHub attachment
   host, scaffolds a **foundation** app, and opens a PR via
   `peter-evans/create-pull-request` on branch `app-request/<Folder>` with the
   commit authored by the issue author and body `Closes #<issue>`.

Requests are always scaffolded as foundation apps. If the requester ticked the
Enhanced checkbox, the PR body notes it so a follow-up can add the Enhanced
code.

## Required repository setup

### Settings

- **Settings → Actions → General → Workflow permissions**: enable
  **"Allow GitHub Actions to create and approve pull requests."** Without it,
  `create-pull-request` cannot open the PR.
- Issue forms with the `upload` field must be available on the repo (it is part
  of GitHub's issue-forms schema; if a form ever fails to render, confirm the
  `upload` field type is still supported for the account/repo).

### Labels (create these once)

| Label | Applied by | Meaning |
| --- | --- | --- |
| `app-request` | issue form (auto) + scaffold PR | this issue/PR is an app request |
| `request-valid` | validate workflow | the form passed validation |
| `request-invalid` | validate workflow | the form has problems (see the comment) |
| `approved` | **maintainer, manually** | gate that triggers scaffolding |

The workflows tolerate a label that is not currently applied when toggling
`request-valid`/`request-invalid`, but all four labels must exist in the repo.

## Maintainer day-to-day

1. A new **App request** issue appears with a bot comment.
2. If it is `request-invalid`, read the comment — it lists exactly what is
   missing or duplicated. Ask the requester to edit the issue (validation
   re-runs automatically) or fix it yourself.
3. When it is `request-valid` and the metadata/icon look right, add the
   **`approved`** label.
4. The scaffold workflow opens a PR (`Add <Folder> …`, `Closes #<issue>`) and
   comments the link on the issue. If scaffolding aborts, the issue gets a
   comment explaining why; fix and re-apply `approved` to retry.
5. Review the PR (icon, metadata, generated PHP) and merge. `main.yml`
   regenerates the app list on `gh-pages`.

## Security model

Issue titles and bodies are attacker-controlled, so the workflows are written
defensively:

- No `${{ github.event.issue.* }}` or parsed value is ever interpolated into a
  `run:` script. The Node scripts read the event from `$GITHUB_EVENT_PATH`;
  everything else is passed through `env:` and quoted shell variables, `with:`
  action inputs, or files.
- Folder and file names are re-derived and validated against `^[A-Za-z0-9]+$`
  before any filesystem use.
- Every field is validated: URLs must parse as `http(s)`, tile ∈ {dark,light},
  the description is length-capped, the icon must be a real PNG/SVG (verified by
  magic bytes).
- The icon URL is allow-listed to `https://github.com/user-attachments/assets/…`
  and `https://user-images.githubusercontent.com/…` before download; anything
  else aborts the run with a comment.
- SVG icons are run through `svgo` before they land in the PR.
- The validate workflow has only `issues: write`. Only the scaffold workflow —
  gated on the maintainer-applied `approved` label — can write to the repo.
