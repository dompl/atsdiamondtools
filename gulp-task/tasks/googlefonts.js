const gulp = require("gulp");
const googleWebFonts = require("gulp-google-webfonts");
const path = require("path");
const fs = require("fs");
const replace = require("gulp-replace");
const help = require("./help.js");

/**
 * Downloads Google Fonts based on a list and generates a corresponding SCSS file.
 */
function downloadGoogleFonts(done) {
	const options = {
		fontsDir: "./src/assets/fonts",
		cssDir: "./src/assets/scss/styles",
		cssFilename: "_google-fonts.scss",
		relativePaths: false,
	};

	const listFile = "./src/assets/fonts/google-fonts.list";
	const listFileContents = fs.existsSync(listFile) ? fs.readFileSync(listFile, "utf8").trim() : "";

	if (listFileContents.length === 0) {
		help.printInColor("No fonts to download. Skipping downloadGoogleFonts task.", "yellow");
		done();
		return;
	}

	return gulp
		.src(listFile)
		.pipe(googleWebFonts(options))
		.pipe(replace("src/assets/fonts/", "assets/fonts/"))
		.pipe(gulp.dest("."))
		.on("end", () => {
			const sourceFile = path.join(process.cwd(), options.cssDir, options.cssFilename);
			const destFile = path.join(process.cwd(), "src/assets/scss/styles", options.cssFilename);
			fs.renameSync(sourceFile, destFile);
		});
}

/**
 * Moves fonts from the src directory to the build directory.
 */
function moveFontsToBuild() {
	return gulp.src(["./src/assets/fonts/**/*", "!./src/assets/fonts/*.list"]).pipe(gulp.dest("./build/assets/fonts"));
}

module.exports = {
	downloadGoogleFonts,
	moveFontsToBuild,
};
