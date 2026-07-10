"use strict";

/**
 * Field definitions, extraction and validation for an app request.
 *
 * Shared by the validate workflow, the scaffold workflow and the unit tests.
 * Every value that reaches this module is attacker-controlled, so validation
 * is strict and total: callers act only on the returned `clean` object.
 */

const { deriveFolder, isValidFolder, appid, slugify } = require("./slug");
const { getField, isChecked } = require("./parse-issue-form");
const { extractAttachmentUrl } = require("./icon-url");

// Exact issue-form field labels. Keep in sync with
// .github/ISSUE_TEMPLATE/app-request.yml (label: values).
const LABELS = {
    name: "App name",
    website: "Website",
    license: "License",
    tile: "Tile background",
    icon: "Icon",
    description: "Description",
    enhanced: "I intend to build this as an Enhanced app myself",
};

// License dropdown: the ~15 most common licenses present in the live app list
// (canonical SPDX display names), plus catch-alls. Keep in sync with the form.
const LICENSES = [
    "MIT License",
    "Apache License 2.0",
    "GNU General Public License v3.0 only",
    "GNU General Public License v3.0 or later",
    "GNU General Public License v2.0 only",
    "GNU General Public License v2.0 or later",
    "GNU Affero General Public License v3.0",
    "GNU Affero General Public License v3.0 only",
    "GNU Affero General Public License v3.0 or later",
    "GNU Lesser General Public License v2.1 only",
    "GNU Lesser General Public License v3.0 only",
    'BSD 3-Clause "New" or "Revised" License',
    'BSD 2-Clause "Simplified" License',
    "Mozilla Public License 2.0",
    "The Unlicense",
    "Commercial/Proprietary",
    "Other",
];

const TILES = ["dark", "light"];

// Length caps (existing data maxes: name 32, website 76, description 936).
const LIMITS = {
    name: 60,
    website: 300,
    license: 120,
    description: 4000,
};

/**
 * Pull the raw string values out of a parsed issue form.
 * @param {object} parsed result of parseIssueForm
 * @returns {object}
 */
function extractFields(parsed) {
    return {
        name: getField(parsed, LABELS.name),
        website: getField(parsed, LABELS.website),
        license: getField(parsed, LABELS.license),
        tile: getField(parsed, LABELS.tile),
        description: getField(parsed, LABELS.description),
        iconRaw: getField(parsed, LABELS.icon),
        wantsEnhanced: isChecked(parsed, LABELS.enhanced),
    };
}

function isHttpUrl(value) {
    let u;
    try {
        u = new URL(value);
    } catch (e) {
        return false;
    }
    return u.protocol === "http:" || u.protocol === "https:";
}

// Collapse internal whitespace/newlines for single-line fields.
function oneLine(value) {
    return String(value == null ? "" : value)
        .replace(/\s+/g, " ")
        .trim();
}

/**
 * Validate the raw fields. Returns a total result; never throws.
 *
 * @param {object} raw output of extractFields
 * @returns {{
 *   ok: boolean,
 *   errors: string[],
 *   warnings: string[],
 *   clean: object|null,
 *   folder: string|null,
 *   appid: string|null,
 *   slug: string|null,
 *   iconUrl: string|null
 * }}
 */
function validateFields(raw) {
    const errors = [];
    const warnings = [];

    const name = oneLine(raw.name);
    const website = oneLine(raw.website);
    const license = oneLine(raw.license);
    const tile = oneLine(raw.tile).toLowerCase();
    const description = String(raw.description == null ? "" : raw.description).trim();
    const iconUrl = extractAttachmentUrl(raw.iconRaw);

    // Name -> folder/appid.
    let folder = null;
    let id = null;
    let slug = null;
    if (!name) {
        errors.push("**App name** is required.");
    } else if (name.length > LIMITS.name) {
        errors.push(`**App name** is too long (max ${LIMITS.name} characters).`);
    } else {
        folder = deriveFolder(name);
        if (!isValidFolder(folder)) {
            errors.push(
                "**App name** must contain letters or numbers and produce a " +
                    "folder name of only `A-Z a-z 0-9` after stripping spaces " +
                    "and punctuation (got `" +
                    folder +
                    "`)."
            );
            folder = null;
        } else {
            id = appid(name);
            slug = slugify(name);
        }
    }

    // Website.
    if (!website) {
        errors.push("**Website** is required.");
    } else if (website.length > LIMITS.website) {
        errors.push(`**Website** is too long (max ${LIMITS.website} characters).`);
    } else if (!isHttpUrl(website)) {
        errors.push("**Website** must be a valid http(s) URL.");
    }

    // License.
    if (!license) {
        errors.push("**License** is required.");
    } else if (license.length > LIMITS.license) {
        errors.push(`**License** is too long (max ${LIMITS.license} characters).`);
    }

    // Description.
    if (!description) {
        errors.push("**Description** is required.");
    } else if (description.length > LIMITS.description) {
        errors.push(
            `**Description** is too long (max ${LIMITS.description} characters).`
        );
    }

    // Tile background.
    if (!tile) {
        errors.push("**Tile background** is required.");
    } else if (!TILES.includes(tile)) {
        errors.push("**Tile background** must be `dark` or `light`.");
    }

    // Icon (presence only; download/sniffing happens in the scaffold step).
    if (!iconUrl) {
        errors.push(
            "**Icon** must be an uploaded `.svg` or `.png` attached to the issue."
        );
    }

    const ok = errors.length === 0;
    const clean = ok
        ? {
              name,
              website,
              license,
              description,
              tile_background: tile,
              enhanced: false, // requests always scaffold as foundation apps
              wantsEnhanced: raw.wantsEnhanced === true,
          }
        : null;

    return { ok, errors, warnings, clean, folder, appid: id, slug, iconUrl };
}

module.exports = {
    LABELS,
    LICENSES,
    TILES,
    LIMITS,
    extractFields,
    validateFields,
    isHttpUrl,
};
