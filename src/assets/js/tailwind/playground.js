(function () {
	const htmlEl = document.documentElement;
	const btn = document.getElementById('theme-toggle');
	const sunIcon = document.getElementById('icon-sun');
	const moonIcon = document.getElementById('icon-moon');

	// Initialise theme
	const savedTheme = localStorage.getItem('theme'); // 'light' or 'dark'
	const systemPref = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
	const theme = savedTheme || systemPref;
	htmlEl.classList.toggle('dark', theme === 'dark');
	updateIcons(theme);

	// Toggle handler
	btn.addEventListener('click', () => {
		const isDark = htmlEl.classList.toggle('dark');
		const newTheme = isDark ? 'dark' : 'light';
		localStorage.setItem('theme', newTheme);
		updateIcons(newTheme);
	});

	// Show/hide icons based on current mode
	function updateIcons(mode) {
		if (mode === 'dark') {
			moonIcon.classList.remove('hidden');
			sunIcon.classList.add('hidden');
		} else {
			sunIcon.classList.remove('hidden');
			moonIcon.classList.add('hidden');
		}
	}
})();
