"use strict";

const test = require("node:test");
const assert = require("node:assert/strict");
const fs = require("fs");
const path = require("path");

const {
    slugify,
    appid,
    deriveFolder,
    isValidFolder,
    deriveIconName,
} = require("../lib/slug");
const { scanRepo, appFolders } = require("../lib/repo");
const { APPID_EXCEPTIONS } = require("../../apps.tests");

const REPO_ROOT = path.resolve(__dirname, "..", "..");

test("slugify reproduces Laravel Str::slug(name, '')", () => {
    assert.equal(slugify("WLED"), "wled");
    assert.equal(slugify("AdGuard Home"), "adguardhome");
    assert.equal(slugify("Pi-hole"), "pihole"); // dash removed
    assert.equal(slugify("Folding@Home"), "foldingathome"); // @ => at dictionary
    assert.equal(slugify("2FAuth"), "2fauth");
    assert.equal(slugify("dash."), "dash");
    assert.equal(slugify("  Spaced   Out  "), "spacedout");
    assert.equal(slugify("Node-Red"), "nodered");
    assert.equal(slugify("RompЯ"), "rompya"); // cyrillic transliteration
    assert.equal(slugify("Café"), "cafe"); // accent stripped
});

test("appid is sha1 hex of the slug", () => {
    assert.equal(appid("WLED"), "ac894a3a9399f135f6eb87f27fb742c71189cc86");
    assert.equal(
        appid("AdGuard Home"),
        "140902edbcc424c09736af28ab2de604c3bde936"
    );
    assert.equal(appid("Bitwarden"), "8a846dca305866d821748c007cf6b64adf00ea22");
    assert.match(appid("Anything"), /^[0-9a-f]{40}$/);
});

test("deriveFolder strips spaces and punctuation like the CI rule", () => {
    assert.equal(deriveFolder("AdGuard Home"), "AdGuardHome");
    assert.equal(deriveFolder("Pi-hole"), "Pihole");
    assert.equal(deriveFolder("Folding@Home"), "FoldingHome");
    assert.equal(deriveFolder("ADS-B Exchange"), "ADSBExchange");
    // The derived folder equals the CI normalization for every real app.
    for (const dir of appFolders(REPO_ROOT)) {
        const json = JSON.parse(
            fs.readFileSync(path.join(REPO_ROOT, dir, "app.json"), "utf8")
        );
        assert.equal(
            json.name.replace(/[ -.:@]/g, ""),
            dir,
            `folder mismatch for ${dir}`
        );
    }
});

test("isValidFolder accepts alnum only", () => {
    assert.ok(isValidFolder("AdGuardHome"));
    assert.ok(isValidFolder("2FAuth"));
    assert.ok(!isValidFolder(""));
    assert.ok(!isValidFolder("../etc"));
    assert.ok(!isValidFolder("a/b"));
    assert.ok(!isValidFolder("RompЯ"));
    assert.ok(!isValidFolder("a b"));
});

test("deriveIconName is slug + lowercased extension", () => {
    assert.equal(deriveIconName("AdGuard Home", "png"), "adguardhome.png");
    assert.equal(deriveIconName("AdGuard Home", ".PNG"), "adguardhome.png");
    assert.equal(deriveIconName("Folding@Home", ".svg"), "foldingathome.svg");
});

test("appid reproduces every on-disk app (except grandfathered)", () => {
    const violators = [];
    for (const dir of appFolders(REPO_ROOT)) {
        const json = JSON.parse(
            fs.readFileSync(path.join(REPO_ROOT, dir, "app.json"), "utf8")
        );
        if (APPID_EXCEPTIONS.has(dir)) continue;
        if (appid(json.name) !== json.appid) violators.push(dir);
    }
    assert.deepEqual(violators, [], `unexpected appid mismatches: ${violators}`);
});

test("every grandfathered exception genuinely mismatches (no stale entries)", () => {
    const stale = [];
    for (const dir of APPID_EXCEPTIONS) {
        const p = path.join(REPO_ROOT, dir, "app.json");
        if (!fs.existsSync(p)) {
            stale.push(`${dir} (missing)`);
            continue;
        }
        const json = JSON.parse(fs.readFileSync(p, "utf8"));
        if (appid(json.name) === json.appid) stale.push(`${dir} (now matches)`);
    }
    assert.deepEqual(stale, [], `grandfathered list has stale entries: ${stale}`);
});

test("on-disk appids are unique", () => {
    const { appids } = scanRepo(REPO_ROOT);
    // scanRepo maps appid -> folder, so duplicates would have collapsed; verify
    // by counting instead.
    const seen = new Map();
    const dups = [];
    for (const dir of appFolders(REPO_ROOT)) {
        const json = JSON.parse(
            fs.readFileSync(path.join(REPO_ROOT, dir, "app.json"), "utf8")
        );
        if (seen.has(json.appid)) dups.push(`${json.appid}: ${seen.get(json.appid)} + ${dir}`);
        else seen.set(json.appid, dir);
    }
    assert.deepEqual(dups, []);
    assert.ok(appids.size > 500);
});

// Live reproduction against the deployed list.json. Skipped offline so CI does
// not depend on the network, but exercises the MANDATORY full-list assertion
// whenever a network is available.
test("appid reproduces the live list.json (except grandfathered)", async (t) => {
    if (typeof fetch !== "function") return t.skip("fetch unavailable");
    let list;
    try {
        const res = await fetch("https://appslist.heimdall.site/list.json", {
            signal: AbortSignal.timeout(8000),
        });
        if (!res.ok) return t.skip(`list.json HTTP ${res.status}`);
        list = await res.json();
    } catch (e) {
        return t.skip(`network unavailable: ${e.message}`);
    }
    const violators = [];
    for (const app of list.apps) {
        if (APPID_EXCEPTIONS.has(deriveFolder(app.name))) continue;
        if (appid(app.name) !== app.appid) violators.push(app.name);
    }
    assert.deepEqual(violators, [], `live appid mismatches: ${violators}`);
});
