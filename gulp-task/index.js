// Import required modules
const gulp = require("gulp");

// Import task modules
require("./tasks/build.js");
require("./tasks/watch.js");
require("./tasks/dist.js");

// Import specific tasks from modules
const { updateGulpTasks } = require("./tasks/update.js");
/**
 * Define the default task, which runs a series of tasks in the following order:
 * 1. Update Gulp tasks from the submodule
 * 2. Build the project
 * 3. Watch for changes
 */
gulp.task("default", gulp.series(updateGulpTasks, "build", "watch"));
