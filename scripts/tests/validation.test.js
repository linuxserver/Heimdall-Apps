"use strict";

const test = require("node:test");
const assert = require("node:assert/strict");
const fs = require("fs");
const os = require("os");
const path = require("path");

const { deriveFolder, isValidFolder } = require("../lib/slug");
const { validateFields, isHttpUrl } = require("../lib/fields");
const {
    isAllowedAttachmentUrl,
    extractAttachmentUrl,
} = require("../lib/icon-url");
const {
    sniffImage,
    pngDimensions,
    downloadIcon,
    ICON_MIN_PX,
    ICON_MAX_PX,
} = require("../lib/download-icon");
const { scaffoldApp } = require("../scaffold");
const { authorIdentity } = require("../scaffold-from-issue");

// Minimal valid PNG (1x1) and a tiny SVG.
const PNG_1x1 = Buffer.from(
    "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=",
    "base64"
);
const SVG = Buffer.from('<svg xmlns="http://www.w3.org/2000/svg"><rect/></svg>');

test("malicious names never produce a usable folder", () => {
    const evil = [
        "../../etc/passwd",
        "..",
        "foo/bar",
        "foo\\bar",
        "$(rm -rf /)",
        "a; rm -rf ~",
        "a`whoami`",
        "app|nc evil 1",
        "app\nname",
        "app\u0000name", // NUL byte
        "  ", // whitespace only
        "!!!", // punctuation only -> empty
        "Ромп", // unicode-only -> stripped to empty
    ];
    for (const name of evil) {
        const folder = deriveFolder(name);
        assert.ok(
            !isValidFolder(folder),
            `expected unsafe folder for ${JSON.stringify(name)}, got ${JSON.stringify(folder)}`
        );
    }
});

test("validateFields rejects traversal / shell-metachar names", () => {
    const base = {
        website: "https://ok.example.com",
        license: "MIT License",
        description: "desc",
        tile: "dark",
        iconRaw: "https://github.com/user-attachments/assets/aaaaaaaa-bbbb",
        wantsEnhanced: false,
    };
    for (const name of ["../../evil", "a; ls", "foo/bar", "隣の客"]) {
        const v = validateFields({ ...base, name });
        assert.ok(!v.ok, `should reject name ${JSON.stringify(name)}`);
        assert.equal(v.folder, null);
    }
});

test("isHttpUrl only accepts http/https", () => {
    assert.ok(isHttpUrl("https://a.com"));
    assert.ok(isHttpUrl("http://a.com/x?y=1"));
    assert.ok(!isHttpUrl("ftp://a.com"));
    assert.ok(!isHttpUrl("javascript:alert(1)"));
    assert.ok(!isHttpUrl("file:///etc/passwd"));
    assert.ok(!isHttpUrl("not a url"));
    assert.ok(!isHttpUrl(""));
});

test("tile background must be dark or light", () => {
    const base = {
        name: "Tile Test",
        website: "https://ok.example.com",
        license: "MIT License",
        description: "desc",
        iconRaw: "https://github.com/user-attachments/assets/aaaaaaaa-bbbb",
        wantsEnhanced: false,
    };
    assert.ok(validateFields({ ...base, tile: "dark" }).ok);
    assert.ok(validateFields({ ...base, tile: "LIGHT" }).ok); // normalized
    assert.ok(!validateFields({ ...base, tile: "blue" }).ok);
    assert.ok(!validateFields({ ...base, tile: "" }).ok);
});

test("description length is capped", () => {
    const base = {
        name: "Cap Test",
        website: "https://ok.example.com",
        license: "MIT License",
        tile: "dark",
        iconRaw: "https://github.com/user-attachments/assets/aaaaaaaa-bbbb",
        wantsEnhanced: false,
    };
    assert.ok(validateFields({ ...base, description: "x".repeat(4000) }).ok);
    assert.ok(!validateFields({ ...base, description: "x".repeat(4001) }).ok);
});

test("icon URL allow-list only trusts GitHub attachment hosts", () => {
    assert.ok(
        isAllowedAttachmentUrl(
            "https://github.com/user-attachments/assets/1234-abcd"
        )
    );
    assert.ok(
        isAllowedAttachmentUrl(
            "https://user-images.githubusercontent.com/1/2/3.png"
        )
    );
    // Rejections: look-alike host, wrong path, non-https, embedded creds.
    assert.ok(!isAllowedAttachmentUrl("https://github.com.evil.com/user-attachments/assets/x"));
    assert.ok(!isAllowedAttachmentUrl("http://github.com/user-attachments/assets/x"));
    assert.ok(!isAllowedAttachmentUrl("https://evil.com/user-attachments/assets/x"));
    assert.ok(!isAllowedAttachmentUrl("https://github.com/user-attachments/assets/../../secret"));
    assert.ok(!isAllowedAttachmentUrl("https://raw.githubusercontent.com/a/b/c.png"));
});

test("extractAttachmentUrl pulls the first allowed URL, ignores others", () => {
    const md =
        "![i](https://github.com/user-attachments/assets/abc-123) and https://evil.com/x";
    assert.equal(
        extractAttachmentUrl(md),
        "https://github.com/user-attachments/assets/abc-123"
    );
    assert.equal(extractAttachmentUrl("no url here"), null);
    assert.equal(extractAttachmentUrl("https://evil.com/a.png"), null);
});

// Build a PNG whose IHDR advertises the given dimensions. Only the header is
// needed for sniffing + dimension reads.
function makePng(width, height) {
    const buf = Buffer.alloc(24);
    Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]).copy(buf, 0);
    buf.write("IHDR", 12, "ascii");
    buf.writeUInt32BE(width, 16);
    buf.writeUInt32BE(height, 20);
    return buf;
}

// A fetch() stub that returns the given bytes as a successful response.
function fetchReturning(buffer) {
    return async () => ({
        ok: true,
        status: 200,
        headers: { get: () => String(buffer.length) },
        arrayBuffer: async () => buffer.buffer.slice(
            buffer.byteOffset,
            buffer.byteOffset + buffer.byteLength
        ),
    });
}

const ATTACHMENT_URL = "https://github.com/user-attachments/assets/abc-123";

test("pngDimensions reads IHDR width/height, null for non-PNG", () => {
    assert.deepEqual(pngDimensions(makePng(200, 150)), { width: 200, height: 150 });
    assert.equal(pngDimensions(SVG), null);
    assert.equal(pngDimensions(Buffer.from("short")), null);
});

test("downloadIcon rejects a PNG over the max dimension", async () => {
    await assert.rejects(
        downloadIcon(ATTACHMENT_URL, {
            fetchImpl: fetchReturning(makePng(ICON_MAX_PX + 1, ICON_MAX_PX + 1)),
        }),
        /too large/
    );
});

test("downloadIcon rejects a PNG under the min dimension", async () => {
    await assert.rejects(
        downloadIcon(ATTACHMENT_URL, {
            fetchImpl: fetchReturning(makePng(ICON_MIN_PX - 1, ICON_MIN_PX - 1)),
        }),
        /too small/
    );
});

test("downloadIcon accepts an in-range PNG and any SVG", async () => {
    const png = await downloadIcon(ATTACHMENT_URL, {
        fetchImpl: fetchReturning(makePng(ICON_MAX_PX, ICON_MAX_PX)),
    });
    assert.equal(png.ext, ".png");
    // SVGs scale and are exempt from the dimension check.
    const svg = await downloadIcon(ATTACHMENT_URL, {
        fetchImpl: fetchReturning(SVG),
    });
    assert.equal(svg.ext, ".svg");
});

test("sniffImage identifies PNG and SVG by content, not name", () => {
    assert.equal(sniffImage(PNG_1x1), ".png");
    assert.equal(sniffImage(SVG), ".svg");
    assert.equal(sniffImage(Buffer.from("<?xml version='1.0'?><svg></svg>")), ".svg");
    assert.equal(sniffImage(Buffer.from("GIF89a....")), null);
    assert.equal(sniffImage(Buffer.from("just text")), null);
});

test("authorIdentity validates the login shape", () => {
    assert.equal(
        authorIdentity({ login: "octocat", id: 583231 }),
        "octocat <583231+octocat@users.noreply.github.com>"
    );
    // Bad logins fall back to the bot identity.
    for (const user of [
        { login: "a b", id: 1 },
        { login: "a/b", id: 1 },
        { login: "x".repeat(40), id: 1 },
        { login: "ok", id: "1" },
        {},
    ]) {
        assert.match(authorIdentity(user), /github-actions\[bot\]/);
    }
});

test("scaffoldApp writes a foundation app under the target dir", () => {
    const tmp = fs.mkdtempSync(path.join(os.tmpdir(), "scaffold-"));
    try {
        const r = scaffoldApp({
            name: "Test App",
            website: "https://test.example.com",
            license: "MIT License",
            description: "A test app.",
            tile_background: "dark",
            iconBuffer: SVG,
            iconExt: ".svg",
            targetDir: tmp,
        });
        assert.equal(r.folder, "TestApp");
        const dir = path.join(tmp, "TestApp");
        const appJson = JSON.parse(fs.readFileSync(path.join(dir, "app.json"), "utf8"));
        assert.equal(appJson.name, "Test App");
        assert.equal(appJson.enhanced, false);
        assert.equal(appJson.icon, "testapp.svg");
        assert.equal(appJson.appid, r.appid);
        // Field order matches the repo convention.
        assert.deepEqual(Object.keys(appJson), [
            "appid",
            "name",
            "website",
            "license",
            "description",
            "enhanced",
            "tile_background",
            "icon",
        ]);
        const php = fs.readFileSync(path.join(dir, "TestApp.php"), "utf8");
        assert.match(php, /namespace App\\SupportedApps\\TestApp;/);
        assert.match(php, /class TestApp extends \\App\\SupportedApps/);
        assert.ok(!fs.existsSync(path.join(dir, "config.blade.php")));
    } finally {
        fs.rmSync(tmp, { recursive: true, force: true });
    }
});

test("scaffoldApp --enhanced writes blades and implements the interface", () => {
    const tmp = fs.mkdtempSync(path.join(os.tmpdir(), "scaffold-enh-"));
    try {
        const r = scaffoldApp({
            name: "Enh App",
            website: "https://enh.example.com",
            license: "MIT License",
            description: "Enhanced test.",
            tile_background: "light",
            enhanced: true,
            iconBuffer: PNG_1x1,
            iconExt: ".png",
            targetDir: tmp,
        });
        const dir = path.join(tmp, r.folder);
        assert.ok(fs.existsSync(path.join(dir, "config.blade.php")));
        assert.ok(fs.existsSync(path.join(dir, "livestats.blade.php")));
        const php = fs.readFileSync(path.join(dir, `${r.folder}.php`), "utf8");
        assert.match(php, /implements \\App\\EnhancedApps/);
        assert.equal(
            JSON.parse(fs.readFileSync(path.join(dir, "app.json"), "utf8")).enhanced,
            true
        );
    } finally {
        fs.rmSync(tmp, { recursive: true, force: true });
    }
});

test("scaffoldApp refuses an unsafe name", () => {
    assert.throws(
        () =>
            scaffoldApp({
                name: "../evil",
                website: "https://x.example.com",
                license: "MIT License",
                description: "x",
                tile_background: "dark",
                iconBuffer: SVG,
                iconExt: ".svg",
                targetDir: os.tmpdir(),
            }),
        /unsafe folder/
    );
});
