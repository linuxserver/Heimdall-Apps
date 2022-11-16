const { testApp } = require("./apps.tests");
const { readdirSync } = require("fs");

const getDirectories = (source) =>
	readdirSync(source, { withFileTypes: true })
		.filter((dirent) => dirent.isDirectory())
		.map((dirent) => dirent.name)
		.filter((dir) => !dir.startsWith("."))
		.filter((dir) => dir !== "node_modules");
// .slice(0, 10);

global.passed = true;

function main() {
	const directories = getDirectories(process.cwd());

	console.log("Running Tests");
	directories.forEach(testApp);
	console.log("");

	if (!global.passed) {
		console.error("Some Tests Failed!");
		process.exit(1);
	}

	console.log("All Tests Passed!");
}

main();
