// Import required modules
const browserSync = require("browser-sync").create();
const config = require(process.cwd() + "/gulpconfig.js");
const hostname = require("os").hostname().toLowerCase();

// Debug: log hostname and config to verify

// Initialize default BrowserSync configuration
const BrowserSyncConfig = {
	files: [
		"./build/**",
		"!./build/vendor/**/*",
		"!./build/**/*.map",
		"!./build/custom.css",
		"!./build/**/*is-custom*.css", // Exclude all 'is-custom' CSS files
		"!./src/vendor/**/*",
	],
	https: false,
	notify: false,
	open: false,
	ghostMode: true,
	port: 3000,
	proxy: "",
	host: "", // This will be set based on hostname
};

// Determine proxy, port, and host based on hostname
if (hostname.includes("silly-wilson")) {
	BrowserSyncConfig.proxy = `${config.project.name}.onfrog.co.uk`;
	BrowserSyncConfig.port = 8915;
	BrowserSyncConfig.host = `${config.project.name}.onfrog.co.uk`;
} else if (hostname.includes("laughing-curran")) {
	BrowserSyncConfig.proxy = `${config.project.name}.rfsdev.co.uk`;
	BrowserSyncConfig.port = 8917;
	BrowserSyncConfig.host = `${config.project.name}.rfsdev.co.uk`; // Set the host explicitly
} else {
	BrowserSyncConfig.proxy = `${config.project.name}.test`;
	BrowserSyncConfig.port = 3000;
	BrowserSyncConfig.host = `${config.project.name}.test`;
}

// Export configuration
module.exports = {
	BrowserSyncConfig,
	browserSync,
};
