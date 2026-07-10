"use strict";

/**
 * Read the apps already present in the repository.
 * Shared by the validate workflow and the CI hardening tests.
 */

const fs = require("fs");
const path = require("path");

// Top-level entries that are not app folders.
const NON_APP = new Set([
    "node_modules",
    "scripts",
    "docs",
    "dist",
    ".git",
    ".github",
]);

/**
 * @param {string} root repo root
 * @returns {string[]} app folder names
 */
function appFolders(root) {
    return fs
        .readdirSync(root, { withFileTypes: true })
        .filter((e) => e.isDirectory())
        .map((e) => e.name)
        .filter((n) => !n.startsWith(".") && !NON_APP.has(n));
}

/**
 * @param {string} root repo root
 * @returns {{folders: Set<string>, appids: Map<string, string>, names: Set<string>}}
 *   appids maps appid -> folder; names is the set of lower-cased app names.
 */
function scanRepo(root) {
    const folders = new Set();
    const appids = new Map();
    const names = new Set();
    for (const folder of appFolders(root)) {
        folders.add(folder);
        let json;
        try {
            json = JSON.parse(
                fs.readFileSync(path.join(root, folder, "app.json"), "utf8")
            );
        } catch (e) {
            continue;
        }
        if (json.appid) appids.set(json.appid, folder);
        if (json.name) names.add(String(json.name).toLowerCase());
    }
    return { folders, appids, names };
}

module.exports = { appFolders, scanRepo, NON_APP };
