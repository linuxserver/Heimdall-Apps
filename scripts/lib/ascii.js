"use strict";

/**
 * ASCII transliteration.
 *
 * Approximates Laravel's `Str::ascii()` (which is backed by
 * voku/portable-ascii). The appid used across Heimdall-Apps is
 * `sha1(Str::slug($name, ''))`, so reproducing the transliteration is what
 * lets us regenerate the exact appid for every existing app.
 *
 * Strategy:
 *   1. Replace a curated map of common non-ASCII letters with their ASCII
 *      equivalents (matching voku/Laravel where it matters, e.g. Я -> Ya).
 *   2. Unicode-normalise (NFKD) and strip combining marks to catch any
 *      remaining accented Latin characters (é -> e, ü -> u, ...).
 *   3. Drop anything still outside the ASCII range.
 *
 * The full dataset (appslist.heimdall.site/list.json) contains exactly one
 * non-ASCII app name today (RompЯ -> "rompya"); the map below is deliberately
 * broad so future submissions transliterate sensibly too.
 */

// Curated transliteration map. Values are the ASCII replacement strings.
const MAP = {
    // Latin-1 supplement / common ligatures
    À: "A", Á: "A", Â: "A", Ã: "A", Ä: "A", Å: "A", Æ: "AE", Ç: "C",
    È: "E", É: "E", Ê: "E", Ë: "E", Ì: "I", Í: "I", Î: "I", Ï: "I",
    Ð: "D", Ñ: "N", Ò: "O", Ó: "O", Ô: "O", Õ: "O", Ö: "O", Ø: "O",
    Ù: "U", Ú: "U", Û: "U", Ü: "U", Ý: "Y", Þ: "TH", ß: "ss",
    à: "a", á: "a", â: "a", ã: "a", ä: "a", å: "a", æ: "ae", ç: "c",
    è: "e", é: "e", ê: "e", ë: "e", ì: "i", í: "i", î: "i", ï: "i",
    ð: "d", ñ: "n", ò: "o", ó: "o", ô: "o", õ: "o", ö: "o", ø: "o",
    ù: "u", ú: "u", û: "u", ü: "u", ý: "y", þ: "th", ÿ: "y",
    // Latin extended-A (selected)
    Œ: "OE", œ: "oe", Š: "S", š: "s", Ž: "Z", ž: "z", Đ: "D", đ: "d",
    Ħ: "H", ħ: "h", Ł: "L", ł: "l", ı: "i", İ: "I", ĳ: "ij", Ĳ: "IJ",
    // Greek (selected lowercase, uppercase)
    α: "a", β: "b", γ: "g", δ: "d", ε: "e", ζ: "z", η: "i", θ: "th",
    ι: "i", κ: "k", λ: "l", μ: "m", ν: "n", ξ: "ks", ο: "o", π: "p",
    ρ: "r", σ: "s", ς: "s", τ: "t", υ: "y", φ: "f", χ: "x", ψ: "ps", ω: "o",
    // Cyrillic (Russian). Matches voku/Laravel casing (e.g. Я -> Ya).
    А: "A", Б: "B", В: "V", Г: "G", Д: "D", Е: "E", Ё: "E", Ж: "Zh",
    З: "Z", И: "I", Й: "J", К: "K", Л: "L", М: "M", Н: "N", О: "O",
    П: "P", Р: "R", С: "S", Т: "T", У: "U", Ф: "F", Х: "H", Ц: "C",
    Ч: "Ch", Ш: "Sh", Щ: "Sch", Ъ: "", Ы: "Y", Ь: "", Э: "E", Ю: "Yu",
    Я: "Ya",
    а: "a", б: "b", в: "v", г: "g", д: "d", е: "e", ё: "e", ж: "zh",
    з: "z", и: "i", й: "j", к: "k", л: "l", м: "m", н: "n", о: "o",
    п: "p", р: "r", с: "s", т: "t", у: "u", ф: "f", х: "h", ц: "c",
    ч: "ch", ш: "sh", щ: "sch", ъ: "", ы: "y", ь: "", э: "e", ю: "yu",
    я: "ya",
};

/**
 * Transliterate a string to ASCII.
 * @param {string} input
 * @returns {string}
 */
function toAscii(input) {
    const str = String(input);
    let out = "";

    for (const ch of str) {
        if (Object.prototype.hasOwnProperty.call(MAP, ch)) {
            out += MAP[ch];
        } else {
            out += ch;
        }
    }

    // Strip combining diacritical marks left on any remaining Latin letters.
    out = out.normalize("NFKD").replace(/[̀-ͯ]/g, "");

    // Drop anything still outside the printable ASCII range.
    out = out.replace(/[^\x20-\x7e]/g, "");

    return out;
}

module.exports = { toAscii };
