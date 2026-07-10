"use strict";

/**
 * Parser for GitHub issue-form bodies.
 *
 * GitHub renders a submitted issue form as a sequence of sections, one per
 * non-markdown field:
 *
 *     ### Field label
 *
 *     value text (may span multiple lines)
 *
 *     ### Next field label
 *
 *     value
 *
 * Empty optional fields render as the literal `_No response_`. Checkboxes
 * render as a `- [x]` / `- [ ]` task list under their heading.
 *
 * Security note: textarea values are attacker-controlled and may themselves
 * contain lines that look like `### heading`. We therefore return every
 * section in order and let callers resolve a label to its FIRST occurrence
 * (see getField). Because the form places every security-relevant field
 * before the free-text description, a `###` injected inside the description
 * can never shadow an earlier field. Callers must still validate values.
 */

const NO_RESPONSE = "_No response_";

/**
 * @param {string} body raw issue body
 * @returns {{sections: {label: string, value: string}[]}}
 */
function parseIssueForm(body) {
    const text = String(body == null ? "" : body).replace(/\r\n/g, "\n");
    const lines = text.split("\n");

    const sections = [];
    let current = null;

    for (const line of lines) {
        const m = /^###[ \t]+(.+?)[ \t]*$/.exec(line);
        if (m) {
            if (current) sections.push(current);
            current = { label: m[1].trim(), lines: [] };
        } else if (current) {
            current.lines.push(line);
        }
    }
    if (current) sections.push(current);

    for (const s of sections) {
        let value = s.lines.join("\n").trim();
        if (value === NO_RESPONSE) value = "";
        s.value = value;
        delete s.lines;
    }

    return { sections };
}

/**
 * First value for a label (case-sensitive exact match). Returns "" if absent.
 * First-occurrence wins so an injected duplicate heading cannot shadow a real
 * earlier field.
 * @param {{sections: {label: string, value: string}[]}} parsed
 * @param {string} label
 * @returns {string}
 */
function getField(parsed, label) {
    for (const s of parsed.sections) {
        if (s.label === label) return s.value;
    }
    return "";
}

/**
 * True when a checkboxes field has at least one ticked box.
 * @param {{sections: {label: string, value: string}[]}} parsed
 * @param {string} label
 * @returns {boolean}
 */
function isChecked(parsed, label) {
    const value = getField(parsed, label);
    return /^[ \t]*[-*][ \t]+\[[xX]\]/m.test(value);
}

module.exports = { parseIssueForm, getField, isChecked, NO_RESPONSE };
