const gulp = require("gulp");
const clean = require("./clean");
const { sassCompileStyle, sassCompileBuild, sassCompileTinyMCE, sassCompileAdmin, sassCompileWooCommerce } = require("./sass");
const { tailwindCompile } = require("./tailwind"); // <-- ADD THIS
const { PhpCompile, PhpVendor } = require("./php");
const { jsCompile, adminJsCompile, TinyMceJsCompile, modernizrBuild, deleteModernizr } = require("./js.js");
const icons = require("./icons");
const googlefonts = require("./googlefonts");
const images = require("./images");
const { updateGulpTasks } = require("./update.js");
const { copyJson } = require("./json");

gulp.task(
	"build",
	gulp.series(
		clean,
		googlefonts.downloadGoogleFonts,
		// SASS Tasks
		sassCompileStyle,
		sassCompileBuild,
		sassCompileTinyMCE,
		sassCompileAdmin,
		sassCompileWooCommerce,
		tailwindCompile,
		PhpCompile,
		PhpVendor,
		deleteModernizr,
		modernizrBuild,
		jsCompile,
		adminJsCompile,
		TinyMceJsCompile,
		icons.iconfont_wipe,
		icons.iconfont,
		images.copyImages,
		googlefonts.moveFontsToBuild,
		copyJson
	)
);