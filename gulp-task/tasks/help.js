/**
 * Prints a colored message to the console.
 *
 * @param {string} message - The message to be printed.
 * @param {string} color - The color for the message ('yellow', 'red', 'blue', 'turquoise', 'green', 'white', 'purple').
 */
function printInColor(message, color) {
	let colorCode;

	switch (color.toLowerCase()) {
		case "yellow":
			colorCode = "\x1b[33m%s\x1b[0m";
			break;
		case "red":
			colorCode = "\x1b[31m%s\x1b[0m";
			break;
		case "blue":
			colorCode = "\x1b[34m%s\x1b[0m";
			break;
		case "turquoise":
			colorCode = "\x1b[36m%s\x1b[0m";
			break;
		case "green":
			colorCode = "\x1b[32m%s\x1b[0m";
			break;
		case "white":
			colorCode = "\x1b[37m%s\x1b[0m";
			break;
		case "purple":
			colorCode = "\x1b[35m%s\x1b[0m";
			break;
		default:
			colorCode = "\x1b[37m%s\x1b[0m"; // Default to white
	}
	console.log();
	console.log(colorCode, message);
	console.log();
}
module.exports.printInColor = printInColor;
