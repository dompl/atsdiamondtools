// Import required modules
const gulp = require("gulp");
const config = require(process.cwd() + "/gulpconfig.js");
const rollup = require("rollup");
const resolve = require("@rollup/plugin-node-resolve");
const commonjs = require("@rollup/plugin-commonjs");
const json = require("@rollup/plugin-json");
const fs = require("fs");
const path = require("path");
const modernizr = require("gulp-modernizr");
const multiEntry = require("@rollup/plugin-multi-entry");
const babel = require("gulp-babel");
const sourcemaps = require("gulp-sourcemaps");

/**
 * A generic function to handle the rollup process for JavaScript file bundling.
 * It reads the specified input file, processes it with rollup, and writes the output to a specified file.
 *
 * @param {Array} inputFiles - An array of relative paths to the input JavaScript files from the "src/assets/js" directory.
 * @param {string} outputFile - The name of the output JavaScript file.
 * @param {Function} done - A callback function provided by Gulp to signal the asynchronous task completion.
 */
function rollupCompile(inputFiles, outputFile, done) {
	// Changed inputFile to inputFiles
	// Construct the full path to the input files
	const filePaths = inputFiles.map((file) => path.join(process.cwd(), "src", "assets", "js", file));

	// Check if the input files exist
	for (let file of filePaths) {
		if (!fs.existsSync(file)) {
			const fileName = path.basename(file); // Extracts the file name from the path
			console.log(); // Empty line
			console.log("\x1b[33m%s\x1b[0m", `Note: The file ${fileName} does not exist.`); // Yellow text
			console.log(); // Empty line
			done(); // Signal task completion
			return;
		}
	}

	// Initialize Rollup with the input file and plugins
	return (
		rollup
			.rollup({
				input: filePaths, // pass array of file paths
				external: ["jquery", "util", "zlib", "https", "http", "stream", "events", "tty", "net", "assert", "url", "fs", "path", "os", "buffer"],
				plugins: [
					multiEntry(), // add this line
					resolve({
						preferBuiltins: false, // Set to false to prefer local modules over built-ins
						browser: true, // Enable browser compatibility
						dedupe: ["fs", "path", "os", "util"], // Specify deduplication for specific modules if needed
					}),
					commonjs(),
					json(), // Add JSON plugin to handle JSON imports
				],
				onwarn: (warning, warn) => {
					// Suppress specific warnings
					if (warning.code === "PREFER_BUILTINS") return;
					// Suppress Flowbite 'this' keyword warnings in ES modules
					if (warning.code === "THIS_IS_UNDEFINED" && warning.id && warning.id.includes("node_modules/flowbite")) return;
					warn(warning);
				},
			})
			// Generate the output bundle
			.then((bundle) =>
				bundle.write({
					file: `./build/assets/js/${outputFile}`,
					format: "iife",
					sourcemap: "inline",
					globals: { jquery: "jQuery" },
				})
			)
			// Signal task completion
			.then(() => done())
			// Handle any errors during Rollup
			.catch((err) => {
				console.error("\x1b[31mError during Rollup:\x1b[0m", err);
				done(); // Signal task completion
			})
	);
}

/**
 * A Gulp task to compile the main.js file using the rollupCompile function.
 *
 * @param {Function} done - A callback function provided by Gulp to signal the asynchronous task completion.
 */
function jsCompile(done) {
	const filePath = path.join(process.cwd(), "src", "assets", "js");
	let filesToCompile = ["main.js"];

	if (fs.existsSync(path.join(filePath, "modernizr.js"))) {
		filesToCompile.push("modernizr.js");
	}

	return rollupCompile(filesToCompile, "bundle.js", done);
}

/**
 * A Gulp task to compile the admin.js file using the rollupCompile function.
 *
 * @param {Function} done - A callback function provided by Gulp to signal the asynchronous task completion.
 */
function adminJsCompile(done) {
	return rollupCompile(["admin.js"], "admin.js", done); // Wrapped "admin.js" in an array
}

/**
 * A Gulp task to compile the tinymce.js file using the rollupCompile function.
 *
 * @param {Function} done - A callback function provided by Gulp to signal the asynchronous task completion.
 */
function TinyMceJsCompile(done) {
	return rollupCompile(["tinymce.js"], "tinymce.js", done); // Wrapped "tinymce.js" in an array
}

/**
 * A Gulp task to delete the modernizr.js file from the src folder.
 *
 * @param {Function} done - A callback function provided by Gulp to signal the asynchronous task completion.
 */
function deleteModernizr(done) {
	const modernizrPath = path.join(process.cwd(), "src", "assets", "js", "modernizr.js");

	if (fs.existsSync(modernizrPath)) {
		fs.unlink(modernizrPath, (err) => {
			if (err) throw err;
			done(); // Signal task completion
		});
	} else {
		done(); // Signal task completion if the file doesn't exist
	}
}

/**
 * A Gulp task to generate a custom Modernizr build based on the project's usage of Modernizr tests.
 * The configuration for Modernizr is read from gulpconfig.js.
 *
 * @param {Function} done - A callback function provided by Gulp to signal the asynchronous task completion.
 */
function modernizrBuild(done) {
	// Check if the modernizr object, options array, and tests array exist in the config
	if (!config.modernizr || !Array.isArray(config.modernizr.options) || !Array.isArray(config.modernizr.tests)) {
		console.log("\x1b[34mNote: Modernizr configuration is missing or incomplete in gulpconfig.js.\x1b[0m");
		done(); // Signal task completion
		return;
	}

	// Proceed with the Modernizr build
	return gulp
		.src(["src/assets/js/**/*.js", "src/assets/css/**/*.css"]) // Adjust paths to your project structure
		.pipe(
			modernizr({
				options: config.modernizr.options,
				tests: config.modernizr.tests, // Include tests configuration
			})
		)
		.pipe(gulp.dest("src/assets/js/")) // Change this line to point to the src folder
		.on("end", done);
}

/**
 * A Gulp task to transpile JavaScript files using Babel.
 * This includes adding sourcemaps for easier debugging.
 *
 * @param {Function} done - A callback function provided by Gulp to signal the asynchronous task completion.
 */
function babelTranspile(done) {
	return gulp
		.src("src/assets/js/**/*.js")
		.pipe(sourcemaps.init())
		.pipe(
			babel({
				presets: ["@babel/preset-env"],
			})
		)
		.pipe(sourcemaps.write("."))
		.pipe(gulp.dest("build/assets/js"))
		.on("end", done);
}

// Export the compile functions
module.exports = { jsCompile, adminJsCompile, TinyMceJsCompile, modernizrBuild, deleteModernizr, babelTranspile };

/**
 * A Gulp task to run all JavaScript-related tasks in series.
 * This will compile main.js, admin.js, tinymce.js, transpile JavaScript using Babel, and generate a custom Modernizr build, in that order.
 */
gulp.task("js", gulp.series(deleteModernizr, modernizrBuild, jsCompile, adminJsCompile, TinyMceJsCompile, babelTranspile));