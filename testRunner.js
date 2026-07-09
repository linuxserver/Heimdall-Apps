const { testApp, testGlobals } = require("./apps.tests");
const { readdirSync } = require("fs");

// Non-app top-level folders that must not be treated as app directories.
const NON_APP_DIRS = new Set(["node_modules", "scripts", "docs", "dist"]);

const getDirectories = (source) =>
    readdirSync(source, { withFileTypes: true })
        .filter((dirent) => dirent.isDirectory())
        .map((dirent) => dirent.name)
        .filter((dir) => !dir.startsWith("."))
        .filter((dir) => !NON_APP_DIRS.has(dir));
// .slice(0, 10);

global.passed = true;

function main() {
    const directories = getDirectories(process.cwd());

    console.log("Running Tests");
    directories.forEach(testApp);
    testGlobals(directories);
    console.log("");

    if (!global.passed) {
        console.error("Some Tests Failed!");
        process.exit(1);
    }

    console.log("All Tests Passed!");
}

main();
