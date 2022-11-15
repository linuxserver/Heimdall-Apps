const { readFileSync, existsSync } = require("fs");

const test = (description, callback) => {
	if (!callback()) {
		console.log(`\nTesting ${global.appUnderTest}`);
		console.log(`Failed: ${description}`);
		global.passed = false;
	}
};

const getAppJson = (appDirectory) => {
	const appJsonPath = `${appDirectory}/app.json`;
	if (!existsSync(appJsonPath)) {
		return {};
	}

	return JSON.parse(readFileSync(appJsonPath).toString());
};

module.exports = {
	test,
	getAppJson,
};
