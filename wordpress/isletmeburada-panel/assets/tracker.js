/* İşletmeBurada Panel — işletme sayfası sayacı. */
(function () {
	var cfg = window.ibpTrack;
	if (!cfg || !cfg.post || !navigator.sendBeacon) {
		return;
	}

	function send(type) {
		// Aynı sekmede aynı işletme için her türü bir kez say.
		var key = 'ibp:' + cfg.post + ':' + type;
		try {
			if (sessionStorage.getItem(key)) {
				return;
			}
			sessionStorage.setItem(key, '1');
		} catch (e) {}

		var data = new FormData();
		data.append('action', 'ibp_track');
		data.append('post', cfg.post);
		data.append('type', type);
		navigator.sendBeacon(cfg.url, data);
	}

	function typeOf(link) {
		var href = (link.getAttribute('href') || '').toLowerCase();
		if (href.indexOf('tel:') === 0) {
			return 'phone';
		}
		if (href.indexOf('mailto:') === 0) {
			return 'email';
		}
		if (/google\.[a-z.]+\/maps|maps\.google|goo\.gl\/maps|maps\.app\.goo\.gl|maps\.apple\.com|yandex\.[a-z.]+\/maps|waze\.com/.test(href)) {
			return 'directions';
		}
		if (cfg.website && link.hostname && link.hostname.replace(/^www\./, '') === cfg.website.replace(/^www\./, '')) {
			return 'website';
		}
		return '';
	}

	send('view');

	document.addEventListener('click', function (event) {
		var link = event.target.closest ? event.target.closest('a[href]') : null;
		var type = link ? typeOf(link) : '';
		if (type) {
			send(type);
		}
	}, true);
})();
