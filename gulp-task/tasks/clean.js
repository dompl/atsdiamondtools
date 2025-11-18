// Import required modules
const gulp = require("gulp");
const { exec } = require("child_process");

// Define the 'cleanBuild' function
// This function removes the 'build' directory from the project root
function cleanBuild(done) {
	// Execute the shell command to remove the 'build' directory
	exec(`rm -rf ${process.cwd()}/build`, (err, stdout, stderr) => {
		// Handle errors, if any
		if (err) {
			console.error("Error executing cleanup command:", err);
			return done(err);
		}

		// Log standard output, if any
		if (stdout) {
			console.log(); // Empty line
			console.log(stdout);
			console.log(); // Empty line
		}

		// Log standard error output, if any
		if (stderr) {
			console.error(stderr);
		}

		// Signal async completion
		done();
	});
}

// Export the 'cleanBuild' function
module.exports = cleanBuild;
