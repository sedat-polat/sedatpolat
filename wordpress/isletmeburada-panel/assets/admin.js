/* İşletmeBurada Panel — Widget'lar ekranı: arama, grup filtresi, toplu aç/kapat, kaydetme uyarısı. */
(function () {
	var form = document.querySelector('[data-ibp-widgets]');
	if (!form) {
		return;
	}

	var cards = Array.prototype.slice.call(form.querySelectorAll('.ibp-wx-card'));
	var search = form.querySelector('[data-ibp-search]');
	var none = form.querySelector('[data-ibp-none]');
	var save = form.querySelector('[data-ibp-save]');
	var dirtyText = form.querySelector('[data-ibp-dirty-text]');
	var countOn = document.querySelector('[data-ibp-count-on]');
	var group = '';
	var initial = state();

	function state() {
		return cards.map(function (card) {
			return card.querySelector('input[type="checkbox"]').checked ? '1' : '0';
		}).join('');
	}

	function refresh() {
		var changed = 0;
		var on = 0;
		var now = state();
		for (var i = 0; i < now.length; i++) {
			changed += now[i] !== initial[i] ? 1 : 0;
			on += now[i] === '1' ? 1 : 0;
		}
		cards.forEach(function (card) {
			card.classList.toggle('is-off', !card.querySelector('input[type="checkbox"]').checked);
		});
		save.classList.toggle('is-dirty', changed > 0);
		dirtyText.textContent = changed ? changed + ' widget değişti, kaydetmeyi unutma.' : 'Değişiklik yok.';
		if (countOn) {
			countOn.textContent = on;
		}
	}

	function filter() {
		var q = (search.value || '').toLocaleLowerCase('tr-TR').trim();
		var shown = 0;
		cards.forEach(function (card) {
			var match = (!group || card.dataset.group === group) && (!q || card.dataset.search.indexOf(q) !== -1);
			card.hidden = !match;
			shown += match ? 1 : 0;
		});
		none.hidden = shown > 0;
	}

	form.addEventListener('change', refresh);
	search.addEventListener('input', filter);

	form.addEventListener('click', function (event) {
		var chip = event.target.closest('[data-ibp-group]');
		if (chip) {
			group = chip.dataset.ibpGroup;
			form.querySelectorAll('[data-ibp-group]').forEach(function (c) {
				c.classList.toggle('is-on', c === chip);
			});
			filter();
			return;
		}
		var bulk = event.target.closest('[data-ibp-all]');
		if (bulk) {
			var value = bulk.dataset.ibpAll === '1';
			cards.forEach(function (card) {
				if (!card.hidden) {
					card.querySelector('input[type="checkbox"]').checked = value;
				}
			});
			refresh();
		}
	});

	// Sayfada kullanılan bir widget kapatılıyorsa onay iste.
	form.addEventListener('submit', function (event) {
		var risky = cards.map(function (card) {
			return card.querySelector('input[type="checkbox"]');
		}).filter(function (input) {
			return !input.checked && parseInt(input.dataset.used, 10) > 0;
		}).map(function (input) {
			return input.dataset.title + ' (' + input.dataset.used + ' sayfa)';
		});
		if (risky.length && !window.confirm('Şu widget\'lar sayfalarda kullanılıyor; kapatılırsa o sayfalarda görünmez:\n\n' + risky.join('\n') + '\n\nYine de kaydedilsin mi?')) {
			event.preventDefault();
			return;
		}
		initial = state();
	});

	window.addEventListener('beforeunload', function (event) {
		if (state() !== initial) {
			event.preventDefault();
			event.returnValue = '';
		}
	});
})();
