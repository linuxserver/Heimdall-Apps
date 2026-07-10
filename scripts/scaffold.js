"use strict";

/**
 * Scaffold a Heimdall-Apps app folder.
 *
 * Usable two ways:
 *   1. as a module   -> const { scaffoldApp } = require('./scaffold');
 *   2. as a CLI      -> node scripts/scaffold.js "App Name" --website ... --icon ...
 *
 * The module never trusts its inputs blindly: the folder name is re-derived
 * and validated against /^[A-Za-z0-9]+$/ before any path is touched, and the
 * app is written under the resolved target directory only.
 */

const fs = require("fs");
const path = require("path");

const { deriveFolder, isValidFolder, deriveIconName, appid } = require("./lib/slug");
const {
    buildAppJson,
    foundationPhp,
    enhancedPhp,
    CONFIG_BLADE,
    LIVESTATS_BLADE,
} = require("./lib/stubs");
const { sniffImage } = require("./lib/download-icon");
const { TILES, isHttpUrl } = require("./lib/fields");

/**
 * Write an app folder.
 *
 * @param {object} req
 * @param {string} req.name
 * @param {string} req.website
 * @param {string} req.license
 * @param {string} req.description
 * @param {string} req.tile_background   "dark" | "light"
 * @param {boolean} [req.enhanced]
 * @param {Buffer} req.iconBuffer
 * @param {string} req.iconExt           ".png" | ".svg"
 * @param {string} req.targetDir         repo root to create the folder under
 * @returns {{folder: string, appid: string, iconName: string, dir: string, files: string[]}}
 */
function scaffoldApp(req) {
    const name = String(req.name == null ? "" : req.name).trim();
    if (!name) throw new Error("name is required");

    const folder = deriveFolder(name);
    if (!isValidFolder(folder)) {
        throw new Error(`Refusing unsafe folder name derived from "${name}": "${folder}"`);
    }
    if (!TILES.includes(req.tile_background)) {
        throw new Error(`tile_background must be one of ${TILES.join(", ")}`);
    }
    if (!isHttpUrl(req.website)) {
        throw new Error("website must be a valid http(s) URL");
    }
    if (!req.description || !String(req.description).trim()) {
        throw new Error("description is required");
    }
    if (!req.license || !String(req.license).trim()) {
        throw new Error("license is required");
    }
    if (req.iconExt !== ".png" && req.iconExt !== ".svg") {
        throw new Error(`iconExt must be .png or .svg (got ${req.iconExt})`);
    }
    if (!Buffer.isBuffer(req.iconBuffer) || req.iconBuffer.length === 0) {
        throw new Error("iconBuffer must be a non-empty Buffer");
    }

    const targetDir = path.resolve(req.targetDir || process.cwd());
    const dir = path.join(targetDir, folder);
    // Defence in depth: the resolved app dir must be an immediate child of the
    // target directory (folder is already alnum-validated, so this cannot fail,
    // but the assertion documents the invariant).
    if (path.dirname(dir) !== targetDir) {
        throw new Error("resolved app directory escaped the target directory");
    }

    const iconName = deriveIconName(name, req.iconExt);
    const enhanced = req.enhanced === true;

    fs.mkdirSync(dir, { recursive: true });

    const files = [];
    const write = (rel, data) => {
        fs.writeFileSync(path.join(dir, rel), data);
        files.push(path.join(folder, rel));
    };

    write("app.json", buildAppJson({
        name,
        website: req.website,
        license: req.license,
        description: req.description,
        enhanced,
        tile_background: req.tile_background,
        icon: iconName,
    }));
    write(`${folder}.php`, enhanced ? enhancedPhp(folder) : foundationPhp(folder));
    write(iconName, req.iconBuffer);
    if (enhanced) {
        write("config.blade.php", CONFIG_BLADE);
        write("livestats.blade.php", LIVESTATS_BLADE);
    }

    return { folder, appid: appid(name), iconName, dir, files };
}

// --------------------------------------------------------------------------
// CLI
// --------------------------------------------------------------------------

function parseArgs(argv) {
    const out = { _: [], flags: {} };
    for (let i = 0; i < argv.length; i++) {
        const a = argv[i];
        if (a === "--enhanced") {
            out.flags.enhanced = true;
        } else if (a.startsWith("--")) {
            const key = a.slice(2);
            const val = argv[i + 1];
            if (val === undefined || val.startsWith("--")) {
                throw new Error(`Missing value for --${key}`);
            }
            out.flags[key] = val;
            i++;
        } else {
            out._.push(a);
        }
    }
    return out;
}

const USAGE = `Usage:
  node scripts/scaffold.js "App Name" \\
    --website https://example.com \\
    --license "MIT License" \\
    --description "What the app does." \\
    --tile dark \\
    --icon path/to/icon.svg \\
    [--enhanced] [--out .]

Creates <out>/<Folder>/ with app.json, <Folder>.php and the icon
(plus config.blade.php + livestats.blade.php when --enhanced).`;

function main(argv) {
    let args;
    try {
        args = parseArgs(argv);
    } catch (e) {
        console.error(e.message);
        console.error("\n" + USAGE);
        process.exit(2);
    }

    const name = args._[0];
    const { website, license, description, tile, icon, out } = args.flags;

    if (!name || !website || !license || !description || !tile || !icon) {
        console.error("Missing required argument.\n");
        console.error(USAGE);
        process.exit(2);
    }

    let iconBuffer;
    try {
        iconBuffer = fs.readFileSync(icon);
    } catch (e) {
        console.error(`Cannot read icon file: ${icon}`);
        process.exit(1);
    }
    const iconExt = sniffImage(iconBuffer);
    if (!iconExt) {
        console.error("Icon must be a valid PNG or SVG file.");
        process.exit(1);
    }

    let result;
    try {
        result = scaffoldApp({
            name,
            website,
            license,
            description,
            tile_background: String(tile).toLowerCase(),
            enhanced: args.flags.enhanced === true,
            iconBuffer,
            iconExt,
            targetDir: out || process.cwd(),
        });
    } catch (e) {
        console.error(`Scaffold failed: ${e.message}`);
        process.exit(1);
    }

    console.log(`Created ${result.folder} (appid ${result.appid})`);
    result.files.forEach((f) => console.log(`  ${f}`));
    if (result.iconName.endsWith(".svg")) {
        console.log(
            `\nTip: optimize the SVG with\n  npx svgo --config .github/workflows/svgo.config.js ${path.join(
                result.folder,
                result.iconName
            )}`
        );
    }
}

if (require.main === module) {
    main(process.argv.slice(2));
}

module.exports = { scaffoldApp, parseArgs };
