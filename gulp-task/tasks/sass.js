// Import required modules
const gulp = require("gulp");
const sass = require("gulp-sass")(require("sass"));
const autoprefixer = require("gulp-autoprefixer");
const sourcemaps = require("gulp-sourcemaps");
const config = require(process.cwd() + "/gulpconfig.js");
// Import the same BrowserSync instance from browsersync.js instead of creating a new one
const { browserSync } = require("./browsersync");
const sassGlobImporter = require("sass-glob-importer");
const cached = require("gulp-cached");
const remember = require("gulp-remember");
const path = require("path");
const fs = require("fs");
const scssDir = "./src/assets/scss";

// Suppress deprecation warnings from Sass by overriding process.stderr.write
const originalStderrWrite = process.stderr.write;
process.stderr.write = function (msg, encoding, fd) {
	if (typeof msg === "string" && (msg.includes("Deprecation Warning") || msg.includes("repetitive deprecation warnings omitted"))) {
		return;
	}
	originalStderrWrite.apply(process.stderr, arguments);
};

/**
 * Compiles SASS files into CSS with source maps, autoprefixing, and BrowserSync stream injection.
 * @param {Array<string>} src - An array of glob patterns for source SASS files.
 * @param {string} dest - The directory where the compiled CSS files should be saved.
 * @returns {Stream} - Returns a Gulp stream.
 */
function sassCompile(src, dest, cacheId = "sass-default") {
	let pipeline = gulp.src(src).pipe(sourcemaps.init());

	if (config.settings && config.settings.useGulpSassGraph) {
		const sassGraph = require("gulp-sass-graph");
		pipeline = pipeline.pipe(sassGraph(src));
	}

	return pipeline
		.pipe(cached(cacheId)) // only changed files
		.pipe(remember(cacheId)) // fulfill the cache
		.pipe(
			sass({
				importer: sassGlobImporter(),
				includePaths: ["node_modules", "node_modules/breakpoint-slicer", path.resolve(__dirname, "../../src/assets/scss")],
				compiler: "libsass",
				precision: 10,
				sourceComments: false,
				outputStyle: "expanded",
				quietDeps: true,
				onError: () => {},
			})
		)
		.pipe(autoprefixer(config.autoprefixer ? config.autoprefixer : { overrideBrowserslist: ["last 2 versions", "> 1%"] }))
		.pipe(sourcemaps.write("./"))
		.pipe(gulp.dest(dest))
		.pipe(browserSync.stream());
}

const sassConfigs = [
	{ name: "sassCompileStyle", mainFile: `${scssDir}/style.scss`, src: [`${scssDir}/styles/**/*.scss`] },
	{ name: "sassCompileBuild", mainFile: `${scssDir}/build.scss`, src: [`${scssDir}/builds/**/*.scss`] },
	{ name: "sassCompileTinyMCE", mainFile: `${scssDir}/tinymce.scss`, src: [`${scssDir}/tinymces/**/*.scss`] },
	{ name: "sassCompileAdmin", mainFile: `${scssDir}/admin.scss`, src: [`${scssDir}/admins/**/*.scss`] },
	{ name: "sassCompileWooCommerce", mainFile: `${scssDir}/woocommerce.scss`, src: [`${scssDir}/woocommerces/**/*.scss`] },
];

const taskExports = {};

// Create individual tasks for each Sass configuration
sassConfigs.forEach((conf) => {
	taskExports[conf.name] = function compileSassTask(done) {
		if (!fs.existsSync(conf.mainFile)) {
			return done();
		}
		return sassCompile([...conf.src, conf.mainFile], "./build");
	};
});

// Create dynamic task names for gulp.task (if needed)
const dynamicTaskNames = [];
sassConfigs.forEach((conf, index) => {
	const taskName = `${conf.name}-${index}`;
	dynamicTaskNames.push(taskName);
	gulp.task(taskName, function compileSassTask(done) {
		if (!fs.existsSync(conf.mainFile)) {
			return done();
		}
		return sassCompile([...conf.src, conf.mainFile], "./build");
	});
});

// Create a combined "sass" task that runs all dynamic tasks in series
gulp.task("sass", gulp.series(...dynamicTaskNames));

module.exports = taskExports;
