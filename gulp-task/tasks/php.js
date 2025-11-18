// Import required modules
const gulp = require("gulp");
const changed = require("gulp-changed");
const fs = require("fs");

/**
 * Compiles and moves PHP files to the build directory.
 * Also handles an optional screenshot file.
 * @returns {Stream} - Gulp stream
 */
function PhpCompile() {
	const src = ["./src/**/*.php", "!./src/vendor/**/*"];
	const screenshotPath = "./src/screenshot.png";
	const dest = "./build";

	// Check if the screenshot file exists and add it to the source array
	if (fs.existsSync(screenshotPath)) {
		src.push(screenshotPath);
	} else {
		console.log(); // Empty line
		console.log("Screenshot file does not exist!");
		console.log(); // Empty line
	}

	return gulp
		.src(src)
		.pipe(changed(dest)) // Only pass through changed files
		.pipe(gulp.dest(dest));
}

/**
 * Watches and compiles PHP files in a specific directory.
 * @param {string} folderName - The name of the folder to watch and compile.
 * @returns {Stream} - Gulp stream
 */
function PhpWatch(folderName) {
	const src = [`./src/${folderName}/**/*.php`, `!./src/${folderName}/vendor/**/*`];
	const dest = `./build/${folderName}`;

	return gulp
		.src(src)
		.pipe(changed(dest)) // Only pass through changed files
		.pipe(gulp.dest(dest));
}

/**
 * Moves vendor files to the build directory.
 * @returns {Stream} - Gulp stream
 */
function PhpVendor() {
	return gulp
		.src(["./src/vendor/**/*"])
		.pipe(changed("./build/")) // Only pass through changed files
		.pipe(gulp.dest("./build/vendor/"));
}

// Export the PHP functions
module.exports = {
	PhpCompile,
	PhpWatch: {
		Classes: () => PhpWatch("Classes"),
		Functions: () => PhpWatch("functions"),
		Components: () => PhpWatch("components"),
		WooCommerce: () => PhpWatch("woocommerce"),
		MainFiles: () => PhpWatch("/"),
	},
	PhpVendor,
};

// Define Gulp tasks
gulp.task("php", gulp.series(PhpCompile));
gulp.task("php-vendor", gulp.series(PhpVendor));
