/* İşletmeBurada — ana sayfa: arama adımları, sekmeler ve belirme animasyonu. */
(function () {
	// Ekranda zaten görünen bölümler hemen görünür kalsın; yalnız aşağıdakiler belirerek gelsin.
	document.querySelectorAll('.ibp-reveal').forEach(function (el) {
		if (el.getBoundingClientRect().top < window.innerHeight) {
			el.classList.add('is-visible');
		}
	});
	document.documentElement.classList.add('ibp-js');

	/* Arama: 1) kategori, 2) şehir, 3) arama sayfasına git ----------------- */
	document.querySelectorAll('[data-ibp-finder]').forEach(function (finder) {
		var chosen = { cat: '', catName: '', city: '' };
		var steps = finder.querySelectorAll('[data-step]');
		var panels = finder.querySelectorAll('[data-panel]');
		var filter = finder.querySelector('[data-finder-filter]');
		var empty = finder.querySelector('[data-finder-empty]');

		function go() {
			var url = new URL(finder.dataset.search, window.location.href);
			if (chosen.cat) {
				url.searchParams.set(finder.dataset.catParam, chosen.cat);
			}
			if (chosen.city) {
				url.searchParams.set(finder.dataset.cityParam, chosen.city);
			}
			window.location.href = url.toString();
		}

		function show(n) {
			steps.forEach(function (step) {
				var on = step.dataset.step === String(n);
				step.classList.toggle('is-on', on);
				step.setAttribute('aria-selected', on ? 'true' : 'false');
			});
			panels.forEach(function (panel) {
				panel.hidden = panel.dataset.panel !== String(n);
			});
		}

		function pickCategory(tile) {
			chosen.cat = tile.dataset.cat;
			chosen.catName = tile.dataset.name;
			finder.querySelectorAll('.ibp-h-tile').forEach(function (t) {
				t.classList.toggle('is-on', t === tile);
			});
			var first = steps[0];
			first.classList.add('is-done');
			first.querySelector('[data-step-note]').textContent = chosen.catName;
			steps[1].disabled = false;
			steps[2].disabled = false;
			show(2);
		}

		finder.addEventListener('click', function (event) {
			var tile = event.target.closest('.ibp-h-tile');
			if (tile) {
				event.preventDefault();
				pickCategory(tile);
				return;
			}
			var city = event.target.closest('[data-city]');
			if (city) {
				chosen.city = city.dataset.city;
				go();
				return;
			}
			var step = event.target.closest('[data-step]');
			if (step && !step.disabled) {
				if (step.dataset.step === '3') {
					go();
				} else {
					show(step.dataset.step);
				}
			}
		});

		var select = finder.querySelector('[data-finder-city]');
		if (select) {
			select.addEventListener('change', function () {
				if (select.value) {
					chosen.city = select.value;
					go();
				}
			});
		}

		if (filter) {
			filter.addEventListener('input', function () {
				var q = filter.value.toLocaleLowerCase('tr-TR').trim();
				var shown = 0;
				finder.querySelectorAll('.ibp-h-tile').forEach(function (tile) {
					var match = !q || tile.dataset.keywords.indexOf(q) !== -1;
					tile.hidden = !match;
					shown += match ? 1 : 0;
				});
				if (empty) {
					empty.hidden = shown > 0;
				}
			});
			filter.addEventListener('keydown', function (event) {
				if (event.key === 'Enter') {
					event.preventDefault();
					var first = finder.querySelector('.ibp-h-tile:not([hidden])');
					if (first) {
						pickCategory(first);
					}
				}
			});
		}
	});

	/* Sekmeler (Yarış Arenası, kategori sıralaması) ------------------------ */
	document.addEventListener('click', function (event) {
		var tab = event.target.closest('[data-ibp-tabs] [data-tab]');
		if (!tab) {
			return;
		}
		var group = tab.closest('[role="tablist"]');
		var root = tab.closest('[data-ibp-tabs]');
		group.querySelectorAll('[data-tab]').forEach(function (t) {
			var on = t === tab;
			t.classList.toggle('is-on', on);
			t.setAttribute('aria-selected', on ? 'true' : 'false');
			var panel = root.querySelector('#' + CSS.escape(t.dataset.tab));
			if (panel) {
				panel.hidden = !on;
			}
		});
	});

	/* Şehir listesi dışına tıklanınca kapansın ----------------------------- */
	document.addEventListener('click', function (event) {
		document.querySelectorAll('.ibp-h-city[open]').forEach(function (details) {
			if (!details.contains(event.target)) {
				details.removeAttribute('open');
			}
		});
	});

	/* Belirme animasyonu ---------------------------------------------------- */
	var items = document.querySelectorAll('.ibp-reveal');
	if (!('IntersectionObserver' in window)) {
		items.forEach(function (el) { el.classList.add('is-visible'); });
		return;
	}
	var observer = new IntersectionObserver(function (entries) {
		entries.forEach(function (entry) {
			if (entry.isIntersecting) {
				entry.target.classList.add('is-visible');
				observer.unobserve(entry.target);
			}
		});
	}, { rootMargin: '0px 0px -8% 0px' });
	items.forEach(function (el) { observer.observe(el); });
})();
