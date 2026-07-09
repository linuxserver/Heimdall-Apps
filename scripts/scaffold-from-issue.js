"use strict";

/**
 * Entry point for the app-request-scaffold workflow (runs only after a
 * maintainer applies the `approved` label).
 *
 * Reads the issue event from $GITHUB_EVENT_PATH, re-validates the form,
 * downloads the icon from an allow-listed GitHub attachment host, scaffolds a
 * foundation app folder and emits the metadata the workflow needs to open a
 * PR. All attacker-controlled data is validated here and passed to later
 * steps only through $GITHUB_OUTPUT / files -- never interpolated into shell.
 *
 * Outputs (on $GITHUB_OUTPUT):
 *   ok             "true" | "false"
 *   folder         validated app folder (alnum) -- only when ok
 *   branch         app-request/<folder>
 *   appid          sha1 appid
 *   icon_is_svg    "true" | "false"
 *   icon_path      "<folder>/<icon>"  (fixed, alnum-safe path for svgo)
 *   pr_title       PR title (no attacker free-text)
 *   commit_message commit subject
 *   author         "login <id+login@users.noreply.github.com>"
 * Files:
 *   $REQUEST_PR_BODY_FILE   PR body
 *   $REQUEST_COMMENT_FILE   failure comment (only when ok=false)
 */

const fs = require("fs");
const path = require("path");

const { parseIssueForm } = require("./lib/parse-issue-form");
const { extractFields, validateFields } = require("./lib/fields");
const { downloadIcon } = require("./lib/download-icon");
const { scaffoldApp } = require("./scaffold");

const LOGIN_RE = /^[A-Za-z0-9](?:[A-Za-z0-9-]{0,38})$/;

function setOutput(kv) {
    if (!process.env.GITHUB_OUTPUT) return;
    let out = "";
    for (const [k, v] of Object.entries(kv)) out += `${k}=${v}\n`;
    fs.appendFileSync(process.env.GITHUB_OUTPUT, out);
}

function fail(message) {
    const commentFile = process.env.REQUEST_COMMENT_FILE;
    const comment =
        "## App scaffold aborted\n\n" +
        message +
        "\n\nFix the issue and re-apply the `approved` label to retry.\n";
    if (commentFile) fs.writeFileSync(commentFile, comment);
    else process.stdout.write(comment);
    setOutput({ ok: "false" });
    console.log("Scaffold aborted:", message.replace(/\s+/g, " ").trim());
}

/**
 * Build a commit-author identity for the issue author.
 * @param {{login?: string, id?: number}} user
 * @returns {string}
 */
function authorIdentity(user) {
    const login = user && user.login;
    const id = user && user.id;
    if (typeof login === "string" && LOGIN_RE.test(login) && Number.isInteger(id)) {
        return `${login} <${id}+${login}@users.noreply.github.com>`;
    }
    // Fallback: never trust an unexpected login shape.
    return "github-actions[bot] <41898282+github-actions[bot]@users.noreply.github.com>";
}

async function main() {
    const eventPath = process.env.GITHUB_EVENT_PATH;
    if (!eventPath) throw new Error("GITHUB_EVENT_PATH is not set");
    const event = JSON.parse(fs.readFileSync(eventPath, "utf8"));
    const issue = event.issue || {};
    const number = issue.number;
    const body = issue.body || "";

    if (!Number.isInteger(number)) {
        fail("Could not determine the issue number.");
        return;
    }

    const parsed = parseIssueForm(body);
    const raw = extractFields(parsed);
    const v = validateFields(raw);

    if (!v.ok) {
        fail("The request failed validation:\n\n" + v.errors.map((e) => `- ${e}`).join("\n"));
        return;
    }

    // Download + sniff the icon (allow-listed host, size-capped, magic bytes).
    let icon;
    try {
        icon = await downloadIcon(v.iconUrl, { token: process.env.GITHUB_TOKEN });
    } catch (e) {
        fail(`Icon could not be used: ${e.message}`);
        return;
    }

    let result;
    try {
        result = scaffoldApp({
            name: v.clean.name,
            website: v.clean.website,
            license: v.clean.license,
            description: v.clean.description,
            tile_background: v.clean.tile_background,
            enhanced: false, // requests are always scaffolded as foundation apps
            iconBuffer: icon.buffer,
            iconExt: icon.ext,
            targetDir: process.cwd(),
        });
    } catch (e) {
        fail(`Scaffolding failed: ${e.message}`);
        return;
    }

    const author = authorIdentity(issue.user);
    const iconPath = path.join(result.folder, result.iconName);

    // PR body (kept in a file so no attacker text is interpolated anywhere).
    const bodyLines = [
        `Closes #${number}`,
        "",
        `Scaffolded from app request #${number}.`,
        "",
        "| Field | Value |",
        "| --- | --- |",
        `| Folder | \`${result.folder}\` |`,
        `| appid | \`${result.appid}\` |`,
        `| License | ${v.clean.license.replace(/[`|\r\n]/g, " ")} |`,
        `| Tile background | ${v.clean.tile_background} |`,
        `| Icon | \`${result.iconName}\` |`,
        `| Requester wants Enhanced | ${v.clean.wantsEnhanced ? "yes" : "no"} |`,
        "",
    ];
    if (v.clean.wantsEnhanced) {
        bodyLines.push(
            "> The requester intends to build Enhanced support. This PR " +
                "scaffolds a **foundation** app; Enhanced code can follow in a " +
                "separate change.",
            ""
        );
    }
    bodyLines.push("Please review the metadata and icon before merging.", "");

    if (process.env.REQUEST_PR_BODY_FILE) {
        fs.writeFileSync(process.env.REQUEST_PR_BODY_FILE, bodyLines.join("\n"));
    }

    setOutput({
        ok: "true",
        folder: result.folder,
        branch: `app-request/${result.folder}`,
        appid: result.appid,
        icon_is_svg: result.iconName.endsWith(".svg") ? "true" : "false",
        icon_path: iconPath,
        pr_title: `Add ${result.folder} (app request #${number})`,
        commit_message: `Add ${result.folder} app (closes #${number})`,
        author,
    });

    console.log(`Scaffolded ${result.folder} (appid ${result.appid})`);
    result.files.forEach((f) => console.log(`  ${f}`));
}

if (require.main === module) {
    main().catch((e) => {
        console.error(e.stack || String(e));
        // Surface a failure comment rather than crashing silently.
        try {
            fail("Unexpected error while scaffolding. See the workflow logs.");
        } catch (_) {
            /* ignore */
        }
        process.exit(1);
    });
}

module.exports = { authorIdentity };
