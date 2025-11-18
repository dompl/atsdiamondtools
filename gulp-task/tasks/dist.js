require("./build.js");
const gulp = require("gulp");
const clean = require("gulp-clean");
const uglify = require("gulp-uglify");
const rename = require("gulp-rename");
const csso = require("gulp-csso");
const concat = require("gulp-concat");
const phpMinify = require("gulp-php-minify");
const config = require(process.cwd() + "/gulpconfig.js");
const { updateGulpTasks } = require("./update.js");
const exec = require("child_process").exec;
const replace = require("gulp-replace");
const insert = require("gulp-insert");
const download = require("gulp-download-stream");
const path = require("path");
const order = require("gulp-order");
const fs = require("fs");

const dist = config.project.parent === true ? config.project.name : config.project.name + "-child";

function incrementVersion(done) {
	// ... (This function is unchanged)
	const configPath = path.join(process.cwd(), "gulpconfig.js");
	const configData = require(configPath);
	const versionParts = configData.theme.version.split(".");
	const patchVersion = parseInt(versionParts[2], 10) + 1;
	versionParts[2] = patchVersion.toString();
	const newVersion = versionParts.join(".");
	configData.theme.version = newVersion;
	const updatedConfig = `module.exports = ${JSON.stringify(configData, null, 2)};`;
	fs.writeFileSync(configPath, updatedConfig);
	config.theme.version = newVersion;
	console.log(`\x1b[33m${config.theme.name} Theme Version incremented to ${newVersion}\x1b[0m`);
	exec(
		`git add gulpconfig.js && git commit -m "Increment theme version to ${newVersion}" && git tag v${newVersion} && git push origin master && git push origin v${newVersion}`,
		function (err, stdout, stderr) {
			if (err) {
				console.error(`\x1b[32mGit operations failed: ${stderr}\x1b[0m`);
			} else {
				console.log(`\x1b[32mGit operations successful: ${stdout.trim()}\x1b[0m`);
			}
			done();
		}
	);
}

function CleanDist() {
	return gulp.src("../" + dist, { read: false, allowEmpty: true }).pipe(clean({ force: true }));
}

function CopyFromBuild() {
	return gulp.src(["./build/**/*", "!./build/**/*.css"]).pipe(gulp.dest("../" + dist));
}

function DistributionPHP() {
	return gulp
		.src(["./build/**/*.php", "!./build/vendor/**/*"])
		.pipe(phpMinify())
		.pipe(gulp.dest("../" + dist));
}

function DistributionJS() {
	return gulp
		.src("./build/assets/js/*.js")
		.pipe(uglify())
		.pipe(gulp.dest("../" + dist + "/assets/js"));
}

// ** MODIFIED FUNCTION **
function DistributionCSS() {
	return gulp
		.src("./build/**/*.css") // Grab all CSS files from the build folder
		.pipe(
			order([
				"style.css", // Your main SASS file (for theme header)
				"tailwind.css", // The new Tailwind/Flowbite CSS
				"**/*.css", // All other CSS files
			])
		)
		.pipe(concat("style.css")) // Combine them all into a single style.css
		.pipe(replace(/\.rfs-ref[a-zA-Z0-9_-]*\s*\{[^}]*\}/g, '')) // Remove rfs-ref prefixed CSS classes
		.pipe(replace(/\.rfs-ref[a-zA-Z0-9_-]*(?=[\s,\.])/g, '')) // Remove rfs-ref class references in selectors
		.pipe(csso({ comments: false, restructure: false }))
		.pipe(gulp.dest("../" + dist));
}

function CleanupFiles() {
	return gulp.src(["../" + dist + "/**/*.map"], { read: false }).pipe(clean({ force: true }));
}

function CopyComposerFile() {
	return gulp.src("./src/composer.json").pipe(gulp.dest("../" + dist));
}

function DumpAutoload() {
	return exec("composer dump-autoload -o", { cwd: `../${dist}` }, function (err, stdout, stderr) {
		console.log(
			`\x1b[31m${
				err ? `composer dump-autoload failed: ${stderr}` : `composer dump-autoload completed: ${stdout}`
			}\x1b[0m`
		);
	});
}

function ReplaceInStyleCSS() {
	// ... (This function is unchanged)
	if (!config.project.parent_name) {
		console.error(`Error: "parent_name" is not defined in your gulpconfig.js file.`);
		process.exit(1);
	}
	const templateLine = config.project.parent === false ? `Template: ${config.project.parent_name}\n` : "";
	const prependText = `/*!
Theme Name: ${config.theme.name}
Theme URI: ${config.theme.url}
Description: ${config.theme.description}
Author: Dom Kapelewski
Author URI: https://github.com/dompl
${templateLine}Version: ${config.theme.version}
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: ${config.project.name}
*/\n`;
	return gulp
		.src("../" + dist + "/style.css")
		.pipe(insert.prepend(prependText))
		.pipe(gulp.dest("../" + dist));
}

function GetThemeImage() {
	return download("https://ks-projects.s3-eu-west-2.amazonaws.com/redfrogstudio/screenshot.png").pipe(
		gulp.dest("../" + dist)
	);
}

function ActivateTheme() {
	const activateParent =
		config.project.parent && config.project.parent !== false ? `wp theme activate ${config.project.name} && ` : "";
	const activateCommand = `${activateParent}wp theme activate ${dist}`;
	return exec(activateCommand, function (err, stdout, stderr) {
		if (err) {
			console.error(`\x1b[31mActivation error: ${stderr}\x1b[0m`);
		} else {
			console.log(`\x1b[32mThe theme is now active.\x1b[0m`);
		}
	});
}

// ** MODIFIED EXPORTS AND TASK **
exports.CopyFromBuild = CopyFromBuild;
exports.DistributionJS = DistributionJS;
exports.DistributionCSS = DistributionCSS; // <-- MODIFIED
exports.CopyComposerFile = CopyComposerFile;
exports.ActivateTheme = ActivateTheme;
exports.DumpAutoload = DumpAutoload;
exports.CleanupFiles = CleanupFiles;
exports.CleanDist = CleanDist;
exports.GetThemeImage = GetThemeImage;
exports.ReplaceInStyleCSS = ReplaceInStyleCSS;

gulp.task(
	"dist",
	gulp.series(
		updateGulpTasks,
		CleanDist,
		"build",
		CopyFromBuild,
		DistributionJS,
		DistributionCSS,
		CleanupFiles,
		GetThemeImage,
		ReplaceInStyleCSS,
		incrementVersion,
		ActivateTheme
	)
); // <-- MODIFIED
