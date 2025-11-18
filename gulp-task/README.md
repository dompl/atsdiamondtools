# WordPress Gulp Tasks

This repository contains a collection of Gulp tasks designed to streamline the WordPress development workflow.

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
- [Tasks](#tasks)
  - [Build](#build)
  - [Watch](#watch)
  - [PHP](#php)
  - [Sass](#sass)
  - [JavaScript](#javascript)
  - [Images](#images)
  - [Icons](#icons)
  - [Google Fonts](#google-fonts)
  - [Clean](#clean)
  - [Inject](#inject)
  - [Update](#update)
- [Contributing](#contributing)
- [License](#license)

## Installation

Clone this repository and navigate into its directory, then run:

```bash
yarn install
```

## Usage

Run the default Gulp task:

```bash
yarn
```

## Tasks

### Build

The \`build\` task runs a series of tasks to prepare your project for development or production. It includes tasks for Sass compilation, PHP file handling, JavaScript bundling, and more.

### Watch

The \`watch\` task monitors your source files and triggers the appropriate tasks upon any changes. To run the watch task, use:

```bash
gulp
```

### PHP

- \`PhpCompile\`: Compiles PHP files and moves them to the build directory. Also handles an optional screenshot file.
- \`PhpWatch\`: Consolidated task that watches for changes in PHP files across different directories (\`Classes\`, \`functions\`, \`components\`) and updates the build directory accordingly.
- \`PhpVendor\`: Moves PHP vendor files to the build directory.

### Sass

- \`sassCompileStyle\`: Compiles main Sass files.
- \`sassCompileBuild\`: Compiles build-specific Sass files.
- \`sassCompileTinyMCE\`: Compiles TinyMCE-specific Sass files.

### JavaScript

- \`jsCompile\`: Compiles main JavaScript files.
- \`adminJsCompile\`: Compiles admin-specific JavaScript files.
- \`TinyMceJsCompile\`: Compiles TinyMCE-specific JavaScript files.
- \`modernizrBuild\`: Generates a custom Modernizr build based on the features used in the project.

### Modernizr Configuration

You can specify Modernizr configuration in the \`gulpconfig.js\` file. Here's an example:

```javascript
modernizr: {
    options: ["setClasses", "addTest", "html5printshiv", "testProp", "fnBind"],
    tests: ["webworkers", ["cssgrid", "cssgridlegacy"]],
}
```

### Images

- \`copyImages\`: Copies image files to the build directory.

### Icons

- \`iconfont\`: Generates an icon font from SVG files.
- \`iconfont_wipe\`: Cleans the icon font directory.

### Google Fonts

- \`downloadGoogleFonts\`: Downloads Google Fonts specified in a \`.list\` file.
- \`moveFontsToBuild\`: Moves font files to the build directory.

### Clean

- \`cleanBuild\`: Cleans the build directory.

### Inject

- \`injectStyleScss\`: Injects SCSS imports into the main style file.
- \`injectBuildScss\`: Injects SCSS imports into the build-specific style file.

### Update

- \`updateGulpTasks\`: Updates Gulp tasks from a Git submodule.

## Contributing

Contributions are welcome! Please read the [contributing guidelines](CONTRIBUTING.md) first.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.
