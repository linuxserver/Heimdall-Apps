const { existsSync, readFileSync } = require("fs");
const { getAppJson, test, getIconSizePNG } = require("./testHelpers");

const ICON_WIDTH_MIN = 100;
const ICON_WIDTH_MAX = 275;
const ICON_HEIGHT_MIN = 100;
const ICON_HEIGHT_MAX = 275;

const testApp = (appDirectory) => {
    global.appUnderTest = `${appDirectory}`;

    const appJson = getAppJson(appDirectory);
    const iconName = appJson.icon || "";

    // Icon tests
    test("should have an icon file", () => {
        return existsSync(`${appDirectory} / ${iconName}`);
    });

    test("should have .png or .svg as icon file", () => {
        return iconName.endsWith(".svg") || iconName.endsWith(".png");
    });

    test("should have an Icon with max width 275px and max height 275px when PNG", () => {
        if (iconName.endsWith(".png")) {
            const iconSize = getIconSizePNG(`${appDirectory} / ${iconName}`);

            const isWidthTooBig = iconSize.width > ICON_WIDTH_MAX;
            const isHeightTooBig = iconSize.height > ICON_HEIGHT_MAX;
            if (isWidthTooBig || isHeightTooBig) {
                console.log("Icon is too big:", iconSize);
            }

            return !isWidthTooBig && !isHeightTooBig;
        }
        return true;
    });

    test("should have an Icon with min width 100px and min height 100px when PNG", () => {
        if (iconName.endsWith(".png")) {
            const iconSize = getIconSizePNG(`${appDirectory} / ${iconName}`);

            const isWidthTooSmall = iconSize.width < ICON_WIDTH_MIN;
            const isHeightTooSmall = iconSize.height < ICON_HEIGHT_MIN;
            if (isWidthTooSmall || isHeightTooSmall) {
                console.log("Icon is too small:", iconSize);
            }

            return !isWidthTooSmall && !isHeightTooSmall;
        }
        return true;
    });

    // app.json tests
    test("should have app.json file", () => {
        return existsSync(`${appDirectory} / app.json`);
    });

    test("should have name defined in app.json", () => {
        return typeof appJson.name === "string" && appJson !== "";
    });

    test("should have name matching the app directory name normalized", () => {
        return appJson.name.replaceAll(/[ -.:@]/g, "") === appDirectory;
    });

    // PHP file
    test("should have a php file", () => {
        return existsSync(`${appDirectory} / ${appDirectory}.php`);
    });

    test("should have a Class with same name as directory", () => {
        const phpFile = `${appDirectory} / ${appDirectory}.php`;
        if (!existsSync(phpFile)) {
            return false;
        }

        const phpFileContent = readFileSync(
            `${appDirectory} / ${appDirectory}.php`
        ).toString();

        return phpFileContent.indexOf(`class ${appDirectory}`) !== -1;
    });

    test("should have the namespace based on the class name", () => {
        const phpFile = `${appDirectory} / ${appDirectory}.php`;
        if (!existsSync(phpFile)) {
            return false;
        }

        const phpFileContent = readFileSync(
            `${appDirectory} / ${appDirectory}.php`
        ).toString();

        return (
            phpFileContent.indexOf(
                `namespace App\\SupportedApps\\${appDirectory}`
            ) !== -1
        );
    });
};

module.exports = {
    testApp,
};
