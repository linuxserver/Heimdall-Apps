"use strict";

const test = require("node:test");
const assert = require("node:assert/strict");

const {
    parseIssueForm,
    getField,
    isChecked,
} = require("../lib/parse-issue-form");
const { extractFields, validateFields } = require("../lib/fields");

// A body shaped exactly like GitHub renders a submitted issue form:
// "### Label\n\nvalue\n\n" per field.
const VALID_BODY = [
    "### App name",
    "",
    "AdGuard Home",
    "",
    "### Website",
    "",
    "https://github.com/AdguardTeam/AdGuardHome",
    "",
    "### License",
    "",
    "GNU General Public License v3.0 only",
    "",
    "### Tile background",
    "",
    "light",
    "",
    "### Icon",
    "",
    "![adguardhome](https://github.com/user-attachments/assets/2b0f9c1a-1111-2222-3333-abcdefabcdef)",
    "",
    "### Description",
    "",
    "AdGuard Home is a network-wide software for blocking ads & tracking.",
    "",
    "### I intend to build this as an Enhanced app myself",
    "",
    "- [x] Yes, I plan to add Enhanced support in a follow-up",
    "",
].join("\n");

test("parseIssueForm splits fields on ### headings", () => {
    const parsed = parseIssueForm(VALID_BODY);
    assert.equal(getField(parsed, "App name"), "AdGuard Home");
    assert.equal(
        getField(parsed, "Website"),
        "https://github.com/AdguardTeam/AdGuardHome"
    );
    assert.equal(getField(parsed, "Tile background"), "light");
    assert.match(getField(parsed, "Icon"), /user-attachments\/assets/);
    assert.ok(isChecked(parsed, "I intend to build this as an Enhanced app myself"));
});

test("_No response_ becomes empty", () => {
    const body = "### Website\n\n_No response_\n";
    assert.equal(getField(parseIssueForm(body), "Website"), "");
});

test("CRLF line endings are handled", () => {
    const body = "### App name\r\n\r\nCRLF App\r\n";
    assert.equal(getField(parseIssueForm(body), "App name"), "CRLF App");
});

test("unchecked checkbox reads as false", () => {
    const body =
        "### I intend to build this as an Enhanced app myself\n\n- [ ] Yes\n";
    assert.ok(!isChecked(parseIssueForm(body), "I intend to build this as an Enhanced app myself"));
});

test("full extract + validate of a good request", () => {
    const parsed = parseIssueForm(VALID_BODY);
    const v = validateFields(extractFields(parsed));
    assert.ok(v.ok, `errors: ${v.errors}`);
    assert.equal(v.folder, "AdGuardHome");
    assert.equal(v.appid, "140902edbcc424c09736af28ab2de604c3bde936");
    assert.equal(v.clean.tile_background, "light");
    assert.equal(v.clean.enhanced, false); // scaffolded as foundation
    assert.equal(v.clean.wantsEnhanced, true);
    assert.match(v.iconUrl, /^https:\/\/github\.com\/user-attachments\/assets\//);
});

test("injected duplicate heading in description cannot shadow an earlier field", () => {
    // Attacker puts a fake "### Website" inside the description textarea. The
    // real Website field appears earlier, so first-occurrence wins.
    const body = [
        "### App name",
        "",
        "Evil App",
        "",
        "### Website",
        "",
        "https://good.example.com",
        "",
        "### License",
        "",
        "MIT License",
        "",
        "### Tile background",
        "",
        "dark",
        "",
        "### Icon",
        "",
        "https://github.com/user-attachments/assets/aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee",
        "",
        "### Description",
        "",
        "Nice app.",
        "",
        "### Website",
        "",
        "https://evil.example.com",
        "",
    ].join("\n");
    const parsed = parseIssueForm(body);
    assert.equal(getField(parsed, "Website"), "https://good.example.com");
    const v = validateFields(extractFields(parsed));
    assert.equal(v.clean.website, "https://good.example.com");
});

test("missing required fields are reported", () => {
    const body = "### App name\n\n\n\n### Website\n\n\n";
    const v = validateFields(extractFields(parseIssueForm(body)));
    assert.ok(!v.ok);
    assert.ok(v.errors.some((e) => /App name/.test(e)));
    assert.ok(v.errors.some((e) => /Website/.test(e)));
    assert.ok(v.errors.some((e) => /Description/.test(e)));
    assert.ok(v.errors.some((e) => /Tile background/.test(e)));
    assert.ok(v.errors.some((e) => /Icon/.test(e)));
});
