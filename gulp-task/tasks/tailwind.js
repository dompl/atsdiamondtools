// tasks/tailwind.js

const gulp = require("gulp");
const sass = require("gulp-sass")(require("sass"));
const postcss = require("gulp-postcss");
const sourcemaps = require("gulp-sourcemaps");
const rename = require("gulp-rename");
const { browserSync } = require("./browsersync");

const scssDir = "./src/assets/scss";

function tailwindCompile() {
	return gulp
		.src(`${scssDir}/tailwind.scss`)
		.pipe(sourcemaps.init())
		.pipe(sass().on("error", sass.logError))
		.pipe(postcss()) // This is all we need. It will now find and use the correct config.
		.pipe(rename("tailwind.css"))
		.pipe(sourcemaps.write("."))
		.pipe(gulp.dest("./build/"))
		.pipe(browserSync.stream());
}

module.exports = {
	tailwindCompile,
};