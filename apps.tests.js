const { existsSync, readFileSync } = require("fs");
const { getAppJson, test } = require("./testHelpers");

const testApp = (appDirectory) => {
	global.appUnderTest = `${appDirectory}`;

	const appJson = getAppJson(appDirectory);
	const iconName = appJson.icon || "";

	// Icon tests
	test("should have an icon file", () => {
		return existsSync(`${appDirectory}/${iconName}`);
	});

	test("should have .png or .svg as icon file", () => {
		return iconName.endsWith(".svg") || iconName.endsWith(".png");
	});

	// app.json tests
	test("should have app.json file", () => {
		return existsSync(`${appDirectory}/app.json`);
	});

	test("should have name defined in app.json", () => {
		return typeof appJson.name === "string" && appJson !== "";
	});

	// PHP file
	test("should have a php file", () => {
		return existsSync(`${appDirectory}/${appDirectory}.php`);
	});

	test("should have a Class with same name as directory", () => {
		const phpFile = `${appDirectory}/${appDirectory}.php`;
		if (!existsSync(phpFile)) {
			return false;
		}

		const phpFileContent = readFileSync(
			`${appDirectory}/${appDirectory}.php`
		).toString();

		return phpFileContent.indexOf(`class ${appDirectory}`) !== -1;
	});

	test("should have the namespace based on the class name", () => {
		const phpFile = `${appDirectory}/${appDirectory}.php`;
		if (!existsSync(phpFile)) {
			return false;
		}

		const phpFileContent = readFileSync(
			`${appDirectory}/${appDirectory}.php`
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
