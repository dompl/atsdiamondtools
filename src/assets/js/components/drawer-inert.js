// Flowbite marks the closed off-canvas drawer aria-hidden="true" while its
// links stay focusable, which fails the aria-hidden-focus accessibility check.
// Mirror aria-hidden into the inert attribute so hidden really means hidden.
export function initDrawerInert() {
	const drawer = document.getElementById('ats-drawer');
	if (!drawer) return;
	const sync = () => {
		if (drawer.getAttribute('aria-hidden') === 'true') {
			drawer.setAttribute('inert', '');
		} else {
			drawer.removeAttribute('inert');
		}
	};
	sync();
	new MutationObserver(sync).observe(drawer, { attributes: true, attributeFilter: ['aria-hidden'] });
}
