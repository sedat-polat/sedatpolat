/* İşletmeBurada Panel — mobil menü çekmecesi ve açılır menüler. */
(function () {
	var body = document.body;
	var overlay;

	function setNav(open) {
		body.classList.toggle('ibp-nav-open', open);
		document.querySelectorAll('[data-ibp-nav-toggle]').forEach(function (button) {
			button.setAttribute('aria-expanded', open ? 'true' : 'false');
			button.setAttribute('aria-label', open ? 'Menüyü kapat' : 'Menüyü aç');
		});
		if (open && !overlay) {
			overlay = document.createElement('div');
			overlay.className = 'ibp-overlay';
			overlay.addEventListener('click', function () { setNav(false); });
			body.appendChild(overlay);
		}
	}

	function closeDropdowns(except) {
		document.querySelectorAll('[data-ibp-dropdown][aria-expanded="true"]').forEach(function (button) {
			if (button === except) {
				return;
			}
			button.setAttribute('aria-expanded', 'false');
			var menu = document.getElementById(button.getAttribute('data-ibp-dropdown'));
			if (menu) {
				menu.hidden = true;
			}
		});
	}

	document.addEventListener('click', function (event) {
		var toggle = event.target.closest('[data-ibp-nav-toggle]');
		if (toggle) {
			setNav(!body.classList.contains('ibp-nav-open'));
			return;
		}

		var trigger = event.target.closest('[data-ibp-dropdown]');
		if (trigger) {
			var menu = document.getElementById(trigger.getAttribute('data-ibp-dropdown'));
			var open = trigger.getAttribute('aria-expanded') !== 'true';
			closeDropdowns(trigger);
			trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
			if (menu) {
				menu.hidden = !open;
			}
			return;
		}

		if (!event.target.closest('.ibp-dd')) {
			closeDropdowns(null);
		}
		// Çekmecedeki bir bağlantıya tıklanınca çekmeceyi kapat.
		if (body.classList.contains('ibp-nav-open') && event.target.closest('.ibp-sidebar a')) {
			setNav(false);
		}
	});

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape') {
			setNav(false);
			closeDropdowns(null);
		}
	});

	window.addEventListener('resize', function () {
		if (window.innerWidth > 1024 && body.classList.contains('ibp-nav-open')) {
			setNav(false);
		}
	});
})();
