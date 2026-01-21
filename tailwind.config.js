const plugin = require('tailwindcss/plugin');

module.exports = {
	content: [
		'./src/**/*.php',
		'./src/assets/js/**/*.js',
		'./src/assets/scss/**/*.scss',
		'./node_modules/flowbite/**/*.js',
		'/var/www/vhosts/rfsdev.co.uk/httpdocs/skylinewp/wp-content/themes/skylinewp-dev-parent/src/**/*.php',
		'/var/www/vhosts/rfsdev.co.uk/httpdocs/skylinewp/wp-content/themes/skylinewp-dev-parent/src/assets/js/**/*.js',
		'/var/www/vhosts/rfsdev.co.uk/httpdocs/skylinewp/wp-content/themes/skylinewp-dev-parent/src/scss/**/*.scss',
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
		container: {
			center: true,
			padding: '1rem',
			screens: {
				'2xl': '1536px',
			},
		},
		extend: {
			gridTemplateRows: {
				0: '0fr',
				1: '1fr',
			},
			colors: {
				primary: {
					300: '#D5C3CE', // light mauve
					500: '#9D8592',
					600: '#84536F',
					700: '#57434E',
					800: '#757575',
					900: '#373737', // darkest from the palette
				},
				neutral: {
					500: '#DEDEDE',
					700: '#757575',
				},
				accent: {
					yellow: '#FFD902',
					green: '#367A33',
				},
				ats: {
					yellow: '#FFD902',
					dark: '#373737',
					brand: '#594652',
					gray: '#DEDEDE',
					text: '#373737',
					footer: '#F4F1F3',
				},
				brand: {
					dark: '#382e34',
				},
			},
			fontFamily: {
				sans: ['Inter', 'sans-serif'],
			},
		},
	},

	plugins: [require('flowbite/plugin'), require('@tailwindcss/typography')],
};
