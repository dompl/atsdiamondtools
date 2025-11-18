// Import required modules
const gulp = require("gulp");
const clean = require("gulp-clean");
let gulpImagemin;

import("gulp-imagemin").then((module) => {
	gulpImagemin = module.default;
});

// Function to copy images to the build directory
function copyImages() {
	return gulp
		.src("./src/assets/images/**/*.+(jpg|jpeg|png|gif|svg|webp|ico)") // Source image files
		.pipe(gulp.dest("./build/assets/images")); // Destination directory
}

// Function to compress images for production
function compressImages() {
	return gulp
		.src("./src/assets/images/**/*.+(jpg|jpeg|png|gif|svg|webp|ico)") // Source image files
		.pipe(imagemin()) // Compress images
		.pipe(gulp.dest("./build/assets/images")); // Destination directory
}

// Export the functions
exports.copyImages = copyImages;
exports.compressImages = compressImages;
