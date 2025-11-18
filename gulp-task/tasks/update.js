const { exec } = require("child_process");

function updateGulpTasks(done) {
	// Execute the command to update the submodule
	exec("git submodule update --remote --merge gulp-task", (err, stdout, stderr) => {
		// Check for errors
		if (err) {
			console.error(`\x1b[31mFailed to update submodule: ${stderr}\x1b[0m`);
			done(err); // Signal task completion with error
			return;
		}

		// Check if there are any updates
		if (stdout) {
			console.log(); // Empty line
			console.log(`\x1b[32mSubmodule updated: ${stdout}\x1b[0m`);
			console.log(); // Empty line
			// Create a commit to indicate that the submodule was updated
			exec("git add . && git commit -m 'Updated gulp-task submodule to the latest version'", (commitErr, commitStdout, commitStderr) => {
				if (commitErr) {
					console.error(`\x1b[31mFailed to create commit: ${commitStderr}\x1b[0m`);
					done(commitErr); // Signal task completion with error
					return;
				}
				console.log(); // Empty line
				console.log(`\x1b[32mCommit created: ${commitStdout}\x1b[0m`);
				console.log(); // Empty line
				done(); // Signal task completion
			});
		} else {
			console.log(); // Empty line
			console.log("\x1b[33mAttempted to update submodule. No updates found.\x1b[0m");
			console.log(); // Empty line
			done(); // Signal task completion
		}
	});
}

// Export the task so it can be used in gulp.series or gulp.parallel
exports.updateGulpTasks = updateGulpTasks;
