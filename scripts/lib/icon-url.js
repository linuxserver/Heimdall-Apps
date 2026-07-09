"use strict";

/**
 * Attachment-URL allow-listing for uploaded icons.
 *
 * An issue is attacker-controlled, so the icon URL we download MUST be
 * restricted to the two hosts GitHub uses for issue attachments:
 *   - https://github.com/user-attachments/assets/<uuid>
 *   - https://user-images.githubusercontent.com/<path>
 *
 * Anything else (an attacker-controlled host, an SSRF target, a redirect
 * trampoline) is rejected before any network request is made.
 */

// Matches a single allowed attachment URL. Anchored per-host, no wildcards
// that could match a look-alike host (e.g. github.com.evil.com).
const ALLOWED_PATTERNS = [
    /^https:\/\/github\.com\/user-attachments\/assets\/[A-Za-z0-9-]+$/,
    /^https:\/\/user-images\.githubusercontent\.com\/[A-Za-z0-9/_.~-]+$/,
];

// Non-anchored variants for scanning a free-text field for the first URL.
const SCAN_PATTERNS = [
    /https:\/\/github\.com\/user-attachments\/assets\/[A-Za-z0-9-]+/,
    /https:\/\/user-images\.githubusercontent\.com\/[A-Za-z0-9/_.~-]+/,
];

/**
 * @param {string} url
 * @returns {boolean}
 */
function isAllowedAttachmentUrl(url) {
    if (typeof url !== "string") return false;
    return ALLOWED_PATTERNS.some((re) => re.test(url));
}

/**
 * Find the first allow-listed attachment URL in a field value.
 * Handles markdown image / link syntax and bare URLs.
 * @param {string} value
 * @returns {string|null}
 */
function extractAttachmentUrl(value) {
    const text = String(value == null ? "" : value);
    for (const re of SCAN_PATTERNS) {
        const m = re.exec(text);
        if (m) return m[0];
    }
    return null;
}

module.exports = {
    isAllowedAttachmentUrl,
    extractAttachmentUrl,
    ALLOWED_PATTERNS,
};
