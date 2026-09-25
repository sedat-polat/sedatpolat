/* İşletmeBurada Panel — mobil menü çekmecesi ve açılır menüler. */
(function () {
	var body = document.body;
	var overlay;
	var DESKTOP = 1024;

	function isDesktop() {
		return window.innerWidth > DESKTOP;
	}

	function syncToggles() {
		var collapsed = body.classList.contains('ibp-sb-collapsed');
		var open = body.classList.contains('ibp-nav-open');
		document.querySelectorAll('[data-ibp-nav-toggle]').forEach(function (button) {
			if (isDesktop()) {
				button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
				button.setAttribute('aria-label', collapsed ? 'Menüyü genişlet' : 'Menüyü daralt');
			} else {
				button.setAttribute('aria-expanded', open ? 'true' : 'false');
				button.setAttribute('aria-label', open ? 'Menüyü kapat' : 'Menüyü aç');
			}
		});
	}

	function setCollapsed(collapsed) {
		body.classList.toggle('ibp-sb-collapsed', collapsed);
		try {
			localStorage.setItem('ibp:sb', collapsed ? '1' : '0');
		} catch (e) {}
		syncToggles();
	}

	function setNav(open) {
		body.classList.toggle('ibp-nav-open', open);
		syncToggles();
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
			if (isDesktop()) {
				setCollapsed(!body.classList.contains('ibp-sb-collapsed'));
			} else {
				setNav(!body.classList.contains('ibp-nav-open'));
			}
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
		if (isDesktop() && body.classList.contains('ibp-nav-open')) {
			setNav(false);
		}
		syncToggles();
	});

	// Sabit üst bar: sayfa kaydırılınca hafif gölge.
	var bars = document.querySelectorAll('.ibp-topbar');
	function onScroll() {
		var stuck = window.scrollY > 4;
		bars.forEach(function (bar) {
			bar.classList.toggle('is-stuck', stuck);
		});
	}
	if (bars.length) {
		window.addEventListener('scroll', onScroll, { passive: true });
		onScroll();
	}

	// Daraltılmış hâl masaüstünde hatırlanır; tablet/mobilde uygulanmaz.
	try {
		if (localStorage.getItem('ibp:sb') === '1' && isDesktop() && !body.classList.contains('elementor-editor-active')) {
			body.classList.add('ibp-sb-collapsed');
		}
	} catch (e) {}
	syncToggles();
})();
