"use strict";

/**
 * Entry point for the app-request-validate workflow.
 *
 * Reads the issue event (from $GITHUB_EVENT_PATH -- never from a shell
 * interpolation of the attacker-controlled body), parses and validates the
 * form, checks for folder/appid collisions against both the repo and the live
 * app list, writes a Markdown comment to $REQUEST_COMMENT_FILE and reports
 * `is_valid` on $GITHUB_OUTPUT. The workflow posts the comment and toggles the
 * request-valid / request-invalid labels; this script performs no mutations.
 */

const fs = require("fs");

const { parseIssueForm } = require("./lib/parse-issue-form");
const { extractFields, validateFields } = require("./lib/fields");
const { scanRepo } = require("./lib/repo");

const LIVE_LIST_URL = "https://appslist.heimdall.site/list.json";

// Escape a value for safe inline display inside a Markdown table cell.
function mdCell(value) {
    return String(value == null ? "" : value)
        .replace(/[`|]/g, (c) => (c === "|" ? "\\|" : "'"))
        .replace(/[\r\n]+/g, " ")
        .trim();
}

/**
 * Build the validation report. Pure and unit-testable.
 *
 * @param {object} args
 * @param {string} args.body issue body
 * @param {{folders: Set<string>, appids: Map<string,string>}} args.repoScan
 * @param {{apps: {appid: string, name: string}[]}|null} args.liveList
 * @returns {{isValid: boolean, comment: string, folder: string|null, appid: string|null}}
 */
function buildReport({ body, repoScan, liveList }) {
    const parsed = parseIssueForm(body);
    const raw = extractFields(parsed);
    const v = validateFields(raw);

    const lines = [];
    lines.push("## App request validation");
    lines.push("");

    // Collisions (only meaningful once we have a folder + appid).
    let folderCollision = false;
    let appidCollision = false;
    let collisionWith = null;
    if (v.folder && v.appid) {
        folderCollision = repoScan.folders.has(v.folder);
        if (repoScan.appids.has(v.appid)) {
            appidCollision = true;
            collisionWith = repoScan.appids.get(v.appid);
        }
        if (!appidCollision && liveList && Array.isArray(liveList.apps)) {
            const hit = liveList.apps.find((a) => a.appid === v.appid);
            if (hit) {
                appidCollision = true;
                collisionWith = hit.name;
            }
        }
    }
    const duplicate = folderCollision || appidCollision;
    const isValid = v.ok && !duplicate;

    lines.push("| Field | Value |");
    lines.push("| --- | --- |");
    lines.push(`| App name | ${v.clean ? mdCell(v.clean.name) : "_(missing)_"} |`);
    lines.push(`| Folder | ${v.folder ? "`" + v.folder + "`" : "_(n/a)_"} |`);
    lines.push(`| Slug | ${v.slug ? "`" + v.slug + "`" : "_(n/a)_"} |`);
    lines.push(`| appid | ${v.appid ? "`" + v.appid + "`" : "_(n/a)_"} |`);
    lines.push(`| Icon attached | ${v.iconUrl ? "yes" : "**no**"} |`);
    lines.push(
        `| Enhanced (self) | ${raw.wantsEnhanced ? "yes" : "no"} |`
    );
    lines.push("");

    if (v.errors.length) {
        lines.push("### Problems");
        lines.push("");
        for (const e of v.errors) lines.push(`- ${e}`);
        lines.push("");
    }

    if (duplicate) {
        lines.push("### Duplicate");
        lines.push("");
        if (folderCollision) {
            lines.push(`- A folder named \`${v.folder}\` already exists.`);
        }
        if (appidCollision) {
            lines.push(
                `- This appid already belongs to an existing app (\`${mdCell(
                    collisionWith
                )}\`).`
            );
        }
        lines.push("");
    }

    lines.push("---");
    lines.push("");
    if (isValid) {
        lines.push(
            "✅ **Looks good.** A maintainer can add the `approved` label to " +
                "scaffold the app and open a PR automatically."
        );
    } else {
        lines.push(
            "❌ **Not ready yet.** Please edit the issue to fix the problems " +
                "above; this check re-runs on every edit."
        );
    }

    return { isValid, comment: lines.join("\n") + "\n", folder: v.folder, appid: v.appid };
}

async function fetchLiveList() {
    try {
        if (typeof globalThis.fetch !== "function") return null;
        const res = await globalThis.fetch(LIVE_LIST_URL, {
            headers: { "User-Agent": "heimdall-apps-request-bot" },
        });
        if (!res.ok) return null;
        return await res.json();
    } catch (e) {
        return null;
    }
}

async function main() {
    const eventPath = process.env.GITHUB_EVENT_PATH;
    if (!eventPath) throw new Error("GITHUB_EVENT_PATH is not set");
    const event = JSON.parse(fs.readFileSync(eventPath, "utf8"));
    const body = (event.issue && event.issue.body) || "";

    const repoScan = scanRepo(process.cwd());
    const liveList = await fetchLiveList();

    const { isValid, comment } = buildReport({ body, repoScan, liveList });

    const commentFile = process.env.REQUEST_COMMENT_FILE;
    if (commentFile) fs.writeFileSync(commentFile, comment);
    else process.stdout.write(comment);

    if (process.env.GITHUB_OUTPUT) {
        fs.appendFileSync(
            process.env.GITHUB_OUTPUT,
            `is_valid=${isValid ? "true" : "false"}\n`
        );
    }
    console.log(`Validation result: ${isValid ? "valid" : "invalid"}`);
}

if (require.main === module) {
    main().catch((e) => {
        console.error(e.stack || String(e));
        process.exit(1);
    });
}

module.exports = { buildReport, mdCell };
