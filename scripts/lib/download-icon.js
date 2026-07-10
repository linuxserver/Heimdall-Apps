"use strict";

/**
 * Download and content-sniff an uploaded icon.
 *
 * Hardening:
 *   - the URL is allow-listed to GitHub's two attachment hosts before any
 *     request is made (SSRF / arbitrary-fetch guard);
 *   - the response is size-capped;
 *   - the real file type is determined by magic bytes, not by the URL or a
 *     claimed extension, so a mislabelled payload cannot smuggle in a
 *     non-image (or the wrong image type).
 */

const { isAllowedAttachmentUrl } = require("./icon-url");

const DEFAULT_MAX_BYTES = 3 * 1024 * 1024; // 3 MiB

const PNG_MAGIC = Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]);

/**
 * Determine ".png" / ".svg" from the bytes, or null if it is neither.
 * @param {Buffer} buf
 * @returns {string|null}
 */
function sniffImage(buf) {
    if (buf.length >= 8 && buf.subarray(0, 8).equals(PNG_MAGIC)) {
        return ".png";
    }
    // SVG: XML/SVG text, possibly with a BOM or leading whitespace/comments.
    const head = buf.subarray(0, 1024).toString("utf8").replace(/^﻿/, "");
    if (/<svg[\s>]/i.test(head) || (/^\s*<\?xml/i.test(head) && /<svg/i.test(buf.toString("utf8")))) {
        return ".svg";
    }
    return null;
}

/**
 * @param {string} url allow-listed attachment URL
 * @param {{token?: string, maxBytes?: number, fetchImpl?: Function}} [opts]
 * @returns {Promise<{buffer: Buffer, ext: string}>}
 */
async function downloadIcon(url, opts = {}) {
    const maxBytes = opts.maxBytes || DEFAULT_MAX_BYTES;
    const doFetch = opts.fetchImpl || globalThis.fetch;

    if (!isAllowedAttachmentUrl(url)) {
        throw new Error(`Icon URL is not an allowed GitHub attachment: ${url}`);
    }
    if (typeof doFetch !== "function") {
        throw new Error("global fetch is unavailable (need Node 18+)");
    }

    const headers = { "User-Agent": "heimdall-apps-request-bot" };
    if (opts.token) headers.Authorization = `Bearer ${opts.token}`;

    const res = await doFetch(url, { redirect: "follow", headers });
    if (!res.ok) {
        throw new Error(`Icon download failed: HTTP ${res.status}`);
    }

    const declared = Number(res.headers.get("content-length") || "0");
    if (declared && declared > maxBytes) {
        throw new Error(`Icon is too large (${declared} bytes, max ${maxBytes}).`);
    }

    const buffer = Buffer.from(await res.arrayBuffer());
    if (buffer.length > maxBytes) {
        throw new Error(`Icon is too large (${buffer.length} bytes, max ${maxBytes}).`);
    }
    if (buffer.length === 0) {
        throw new Error("Icon download was empty.");
    }

    const ext = sniffImage(buffer);
    if (!ext) {
        throw new Error("Icon is not a valid PNG or SVG file.");
    }

    return { buffer, ext };
}

module.exports = { downloadIcon, sniffImage, DEFAULT_MAX_BYTES };
