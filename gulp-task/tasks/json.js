// json.js
const gulp = require("gulp");
const fs = require("fs");
const path = require("path");

/**
 * Copies JSON files from ./src/json to ./build/json if the folder exists.
 *
 * @param {Function} done - Callback to signal async completion.
 */
function copyJson(done) {
	const jsonFolderPath = path.join(process.cwd(), "src", "json");

	if (!fs.existsSync(jsonFolderPath)) {
		console.log("JSON folder does not exist. Skipping JSON copy task.");
		return done();
	}

	return gulp.src("./src/json/**/*").pipe(gulp.dest("./build/json"));
}

module.exports = {
	copyJson,
};
