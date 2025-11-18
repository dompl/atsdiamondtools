const plugin = require("tailwindcss/plugin");

module.exports = {
	content: [
		"./src/**/*.php",
		"./src/assets/js/**/*.js",
		"./src/assets/scss/**/*.scss",
		"./node_modules/flowbite/**/*.js",
		"/var/www/vhosts/rfsdev.co.uk/httpdocs/skylinewp/wp-content/themes/skylinewp-dev-parent/src/**/*.php",
		"/var/www/vhosts/rfsdev.co.uk/httpdocs/skylinewp/wp-content/themes/skylinewp-dev-parent/src/assets/js/**/*.js",
		"/var/www/vhosts/rfsdev.co.uk/httpdocs/skylinewp/wp-content/themes/skylinewp-dev-parent/src/scss/**/*.scss",
	],

	safelist: [
    // Only include classes that are dynamically added via JavaScript
    // All other classes are automatically detected from PHP files
    'is-active',
    'hidden',
    'text-green-600',
    'text-red-600',
    'opacity-50',
    'cursor-not-allowed',
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          300: '#e8e4e6',
          500: '#f5f0f3',
          600: '#d4c0cd',
          700: '#b39aac',
          800: '#8b6b84',
          900: '#5a4857',
        },
        neutral: {
          500: '#9b9b9b',
          700: '#4a4a4a',
        },
        accent: {
          yellow: '#f4e500',
          green: '#3e7d52',
        },
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
      },
    },
  },
  plugins: [
    require('flowbite/plugin'),
    require('@tailwindcss/typography'),
  ],
}
