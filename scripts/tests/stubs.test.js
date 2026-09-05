"use strict";

const test = require("node:test");
const assert = require("node:assert/strict");

const { foundationPhp, enhancedPhp } = require("../lib/stubs");

// PSR12 wants PascalCase class names, but the class mirrors the folder, which
// mirrors the app name. Apps named in lowercase (alist, pfSense, ntfy) cannot
// satisfy the sniff, so the repo silences it on the class line; a scaffolded
// app has to do the same or it opens a PR that fails PHPCS.
const classLine = (php) =>
    php.split("\n").find((l) => l.startsWith("class "));

test("PascalCase folders get no phpcs annotation", () => {
    assert.equal(
        classLine(foundationPhp("Netbird")),
        "class Netbird extends \\App\\SupportedApps"
    );
    assert.equal(
        classLine(enhancedPhp("Netbird")),
        "class Netbird extends \\App\\SupportedApps implements \\App\\EnhancedApps"
    );
});

test("lower-case folders are annotated so PHPCS passes", () => {
    assert.equal(
        classLine(foundationPhp("reDirector")),
        "class reDirector extends \\App\\SupportedApps // phpcs:ignore"
    );
    assert.equal(
        classLine(foundationPhp("ntfy")),
        "class ntfy extends \\App\\SupportedApps // phpcs:ignore"
    );
    assert.equal(
        classLine(enhancedPhp("oVirt")),
        "class oVirt extends \\App\\SupportedApps implements " +
            "\\App\\EnhancedApps // phpcs:ignore"
    );
});

test("digit-initial folders are annotated too", () => {
    // e.g. "2FAuth" -> folder 2FAuth; not a valid PHP-PSR12 PascalCase start.
    assert.equal(
        classLine(foundationPhp("2FAuth")),
        "class 2FAuth extends \\App\\SupportedApps // phpcs:ignore"
    );
});

test("stubs remain free of trailing whitespace", () => {
    for (const php of [
        foundationPhp("Netbird"),
        foundationPhp("ntfy"),
        enhancedPhp("Netbird"),
        enhancedPhp("ntfy"),
    ]) {
        const offenders = php
            .split("\n")
            .filter((line) => /[ \t]+$/.test(line));
        assert.deepEqual(offenders, []);
    }
});
