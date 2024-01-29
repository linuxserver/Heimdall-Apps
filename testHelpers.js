const {
    readFileSync,
    existsSync,
    openSync,
    closeSync,
    readSync,
} = require("fs");

const test = (description, callback) => {
    try {
        if (!callback()) {
            console.log(`\nTesting ${global.appUnderTest}`);
            console.log(`Failed: ${description}`);
            global.passed = false;
        }
    } catch (e) {
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

const getIconSizePNG = (fileName) => {
    const HEADER_LENGTH = 24;
    const OFFSET = 16;

    if (!existsSync(fileName)) {
        return { width: 0, height: 0 };
    }

    const fileDescriptor = openSync(fileName, "r");
    const myBuffer = Buffer.alloc(HEADER_LENGTH);

    try {
        readSync(fileDescriptor, myBuffer, 0, HEADER_LENGTH, 0);

        closeSync(fileDescriptor);
    } catch (e) {
        closeSync(fileDescriptor);
        return { width: 0, height: 0 };
    }

    return {
        width: myBuffer.readUInt32BE(OFFSET),
        height: myBuffer.readUInt32BE(4 + OFFSET),
    };
};

module.exports = {
    test,
    getAppJson,
    getIconSizePNG,
};
