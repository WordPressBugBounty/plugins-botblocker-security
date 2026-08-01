window.bbcsCircuitBreaker = {
	failures: 0,
	threshold: 3,
	cooldownMs: 30000,
	openUntil: 0,
	_countdownId: 0,
	_key: 'bbcs_cb',

	_save: function () {
		try {
			sessionStorage.setItem(this._key, JSON.stringify({f: this.failures, o: this.openUntil}));
		} catch (e) {}
	},

	_restore: function () {
		try {
			var raw = sessionStorage.getItem(this._key);
			if (!raw) return;
			var d = JSON.parse(raw);
			if (typeof d.f === 'number') this.failures = d.f;
			if (typeof d.o === 'number') this.openUntil = d.o;
		} catch (e) {}
		if (this.isOpen()) {
			this.showFallback();
		}
	},

	recordFailure: function () {
		this.failures++;
		if (this.failures >= this.threshold) {
			this.openUntil = Date.now() + this.cooldownMs;
			this.showFallback();
		}
		this._save();
	},

	recordSuccess: function () {
		this.failures = 0;
		this.openUntil = 0;
		this._save();
	},

	isOpen: function () {
		if (Date.now() >= this.openUntil) {
			if (this.openUntil > 0) {
				this.failures = 0;
				this.openUntil = 0;
				this._save();
			}
			return false;
		}
		return true;
	},

	showFallback: function () {
		if (typeof bbcsCheckUI !== 'undefined') {
			bbcsCheckUI.hide();
		}
		var el = document.getElementById('content');
		if (el) {
			var self = this;
			var secs = Math.ceil((this.openUntil - Date.now()) / 1000);
			el.innerHTML =
				'<div style="text-align:center;padding:40px">' +
				'<p>Verification is temporarily unavailable. Please wait</p>' +
				'<p><small id="bbcs-cooldown-countdown">' + secs + 's</small></p>' +
				'</div>';
			if (this._countdownId) {
				clearInterval(this._countdownId);
			}
			this._countdownId = setInterval(function () {
				var open = self.isOpen();
				if (!open) {
					clearInterval(self._countdownId);
					self._countdownId = 0;
					window.location.reload();
					return;
				}
				var ct = document.getElementById('bbcs-cooldown-countdown');
				if (ct) {
					ct.textContent = Math.ceil((self.openUntil - Date.now()) / 1000) + 's';
				}
			}, 1000);
		}
	}
};
window.bbcsCircuitBreaker._restore();
