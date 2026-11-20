/*
 * APT site menu: a full-screen modal <dialog> (page becomes inert; Escape and
 * focus return are native). Each section is a disclosure button with a panel.
 * Desktop: exactly one panel is open, shown beside the sections (tabs).
 * Phones: an accordion, all closed except the current section.
 */
(function () {
	'use strict';

	var menu = document.getElementById('apt-menu');
	var openBtn = document.querySelector('.apt-menu-toggle');
	if (!menu || !openBtn || typeof menu.showModal !== 'function') {
		return;
	}
	var closeBtn = menu.querySelector('.apt-menu-close');
	var tabs = Array.prototype.slice.call(menu.querySelectorAll('button.apt-menu-tab'));
	var wide = window.matchMedia('(min-width: 768px)');

	function setOpen(tab, open) {
		tab.setAttribute('aria-expanded', open ? 'true' : 'false');
		document.getElementById(tab.getAttribute('aria-controls')).hidden = !open;
	}

	function reset() {
		tabs.forEach(function (tab) {
			var isDefault = tab.parentNode.getAttribute('data-open-default') === '1';
			// Phones: open only the section holding this page.
			setOpen(tab, wide.matches ? isDefault : isDefault && tab.classList.contains('is-here'));
		});
	}
	reset();
	if (wide.addEventListener) {
		wide.addEventListener('change', reset);
	}

	tabs.forEach(function (tab) {
		tab.addEventListener('click', function () {
			var open = tab.getAttribute('aria-expanded') === 'true';
			if (wide.matches) {
				if (open) {
					return; // a tab stays open until another is chosen
				}
				tabs.forEach(function (other) { setOpen(other, other === tab); });
			} else {
				setOpen(tab, !open);
			}
		});
	});

	openBtn.addEventListener('click', function () {
		reset();
		menu.showModal();
		openBtn.setAttribute('aria-expanded', 'true');
	});
	closeBtn.addEventListener('click', function () { menu.close(); });
	menu.addEventListener('close', function () {
		openBtn.setAttribute('aria-expanded', 'false');
	});

	// Links to a section of the current page: close so the page can scroll there.
	menu.addEventListener('click', function (event) {
		var link = event.target.closest('a[href*="#"]');
		if (link && link.pathname === window.location.pathname && link.hash) {
			menu.close();
		}
	});
})();

/*
 * Search dialog (#apt-search) fallbacks. Opening and closing are declarative
 * (command="show-modal" / command="close", Invoker Commands, Baseline since
 * Dec 2025) and click-outside uses closedby="any" (not in Safari yet). These
 * small fallbacks cover browsers without either, instead of loading polyfills
 * from a third-party CDN.
 */
(function () {
	'use strict';

	var dialog = document.getElementById('apt-search');
	if (!dialog || typeof dialog.showModal !== 'function') {
		return;
	}

	if (!('commandForElement' in HTMLButtonElement.prototype)) {
		document.addEventListener('click', function (event) {
			var button = event.target.closest('button[commandfor="apt-search"]');
			if (!button) {
				return;
			}
			var command = button.getAttribute('command');
			if (command === 'show-modal' && !dialog.open) {
				dialog.showModal();
			} else if (command === 'close' && dialog.open) {
				dialog.close();
			}
		});
	}

	if (!('closedBy' in HTMLDialogElement.prototype)) {
		// Test the pointer position, not event.target: the dialog is also the
		// target for its own padding. Require press and release outside, so a
		// text-selection drag that ends on the backdrop does not close it.
		var outside = function (event) {
			var r = dialog.getBoundingClientRect();
			return event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom;
		};
		var pressedOutside = false;
		dialog.addEventListener('pointerdown', function (event) { pressedOutside = outside(event); });
		dialog.addEventListener('pointerup', function (event) {
			if (pressedOutside && outside(event)) {
				dialog.close();
			}
		});
	}
})();
