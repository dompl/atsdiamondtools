const gulp = require("gulp");
const clean = require("gulp-clean");
const iconfontPlugin = require("gulp-iconfont");
const iconfontCss = require("gulp-iconfont-css");
const svgmin = require("gulp-svgmin");
const through2 = require("through2");
const cheerio = require("cheerio");
const SvgPath = require("svgpath"); // <-- install via npm install svgpath

// Load your configuration
const config = require(process.cwd() + "/gulpconfig.js");

// Function to shift all path commands up by a given offsetY
function shiftPath(d, offsetY) {
	return new SvgPath(d).translate(0, offsetY).toString();
}

// Function to normalise an SVG: remove negative vertical offsets
function normalizeSVG() {
	return through2.obj(function (file, _, cb) {
		if (file.isBuffer()) {
			const $ = cheerio.load(file.contents.toString(), { xmlMode: true });
			const $svg = $("svg");

			// Get and modify the viewBox, if present
			let viewBox = $svg.attr("viewBox");
			if (viewBox) {
				const parts = viewBox.split(" ").map(parseFloat);
				// viewBox is usually [minX, minY, width, height]
				if (parts.length === 4) {
					let [minX, minY, width, height] = parts;

					// If minY is negative, shift everything so it starts at 0
					if (minY < 0) {
						console.log(`Fixing vertical alignment for: ${file.relative}, shifting by ${Math.abs(minY)}`);
						const offsetY = Math.abs(minY);

						// Update the viewBox so that minY = 0
						minY = 0;
						$svg.attr("viewBox", [minX, minY, width, height].join(" "));

						// Shift all <path> elements up by offsetY
						$("path").each((_, el) => {
							const pathData = $(el).attr("d");
							if (pathData) {
								const shifted = shiftPath(pathData, offsetY);
								$(el).attr("d", shifted);
							}
						});

						// Fix any existing translate(...) in transforms
						$("[transform]").each((_, el) => {
							let transformAttr = $(el).attr("transform");
							if (transformAttr) {
								// If we see something like translate(x, y) or translate(x y)
								transformAttr = transformAttr.replace(/translate\(\s*(-?\d+\.?\d*)[,\s]+(-?\d+\.?\d*)\s*\)/g, (match, tx, ty) => {
									const newTy = parseFloat(ty) + offsetY;
									return `translate(${tx},${newTy})`;
								});
								$(el).attr("transform", transformAttr);
							}
						});
					}
				}
			}

			// Save the updated file contents
			file.contents = Buffer.from($.xml());
		}
		cb(null, file);
	});
}

// Function to fix and save SVGs before generating the font
function fixSvgFiles() {
	return gulp
		.src("./src/assets/icons/**/*.svg")
		.pipe(svgmin()) // Optimise SVGs
		.pipe(normalizeSVG()) // Fix viewBox & reposition paths properly
		.pipe(gulp.dest("./src/assets/icons/")); // Save corrected SVGs
}

// Function to generate the font after fixing SVGs
function iconfont() {
	return gulp
		.src("./src/assets/icons/**/*.svg", { base: "./" })
		.pipe(
			iconfontCss({
				fontName: `${config.project.name}-font`,
				targetPath: "../../../src/assets/scss/styles/_icons.scss",
				path: "./src/assets/scss/abstracts/_icons_template.scss",
				fontPath: "assets/fonts/",
			})
		)
		.pipe(
			iconfontPlugin({
				fontName: `${config.project.name}-font`,
				formats: ["ttf", "eot", "woff", "woff2", "svg"],
				timestamp: Math.round(Date.now() / 1000),
				normalize: true,
				fontHeight: 1000,
				centerHorizontally: true,
			})
		)
		.pipe(gulp.dest("./build/assets/fonts"));
}

// Function to clean the fonts directory
function iconfont_wipe() {
	return gulp.src("./build/assets/fonts/*", { read: false, allowEmpty: true }).pipe(clean());
}

// Export the tasks
exports.fixSvgFiles = fixSvgFiles;
exports.iconfont = gulp.series(fixSvgFiles, iconfont);
exports.iconfont_wipe = iconfont_wipe;
