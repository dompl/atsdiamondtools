// Import required modules
const gulp = require("gulp");
const inject = require("gulp-inject");
const path = require("path");

/**
 * Function to inject SCSS imports into a target SCSS file.
 * @param {string} targetFile - The SCSS file into which imports will be injected.
 * @param {string} sourceFiles - The SCSS files to be imported.
 * @returns {Stream} - Gulp stream
 */
function injectScss(targetFile, sourceFiles) {
	// Read the target SCSS file
	const target = gulp.src(targetFile);

	// Read the source SCSS files to be injected
	const sources = gulp.src(sourceFiles, { read: false });

	// Perform the injection
	return target
		.pipe(
			inject(sources, {
				starttag: "// inject:imports",
				endtag: "// endinject",
				transform: function (filepath) {
					// Transform the file path to a relative path
					const relativePath = path.relative("/src/assets/scss", filepath);
					return `@import "${relativePath}";`;
				},
			})
		)
		.pipe(gulp.dest("./src/assets/scss")); // Save the modified target file
}

// Export the inject functions
module.exports = {
	injectStyleScss: function () {
		return injectScss("./src/assets/scss/style.scss", "./src/assets/scss/style/**/*.scss");
	},
	injectBuildScss: function () {
		return injectScss("./src/assets/scss/build.scss", "./src/assets/scss/build/**/*.scss");
	},
};
