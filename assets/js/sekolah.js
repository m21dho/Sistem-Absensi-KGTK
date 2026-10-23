/*
 * Widget "Kabupaten/Kota + Asal sekolah" untuk form peserta dan form admin.
 * KGTKSekolah.create({ root: urlRestKgtk, kabkota: ['Kota Batam', ...] }) mengembalikan:
 *   { node, getValue(), setValue(v), validate() }
 * Bila daftar kabupaten/kota kosong (data sekolah belum diimpor), widget hanya berupa isian teks sekolah.
 */
(function () {
	'use strict';
	var LAIN = 'Lainnya';
	var uid = 0;

	function el(tag, attrs, kids) {
		var n = document.createElement(tag);
		Object.keys(attrs || {}).forEach(function (k) {
			var v = attrs[k];
			if (v === false || v === null || v === undefined) { return; }
			if (k === 'class') { n.className = v; } else if (k === 'text') { n.textContent = v; } else { n.setAttribute(k, v === true ? '' : v); }
		});
		(kids || []).forEach(function (c) { if (c) { n.appendChild(typeof c === 'string' ? document.createTextNode(c) : c); } });
		return n;
	}

	function fetchSekolah(root, kab) {
		var sep = root.indexOf('?') > -1 ? '&' : '?';
		return fetch(root + 'public/sekolah' + sep + 'kabkota=' + encodeURIComponent(kab), { credentials: 'same-origin' })
			.then(function (r) {
				return r.json().catch(function () { return {}; }).then(function (d) {
					if (!r.ok) { throw new Error(d.message || 'Gagal memuat daftar sekolah.'); }
					return d.sekolah || [];
				});
			}, function () { throw new Error('Tidak dapat terhubung ke server.'); });
	}

	function norm(s) { return String(s || '').toLowerCase(); }

	function formatKabLabel(raw) {
		var s = String(raw || '').trim();
		if (!s || s === LAIN) { return s; }
		var lower = s.toLowerCase();
		if (lower === 'pulau lingga' || lower === 'lingga' || lower === 'kab. pulau lingga' || lower === 'kabupaten pulau lingga' || lower === 'kab. lingga' || lower === 'kabupaten lingga') {
			return 'Kabupaten Lingga';
		}
		if (lower === 'batam' || lower === 'kota batam') {
			return 'Kota Batam';
		}
		if (lower === 'tanjungpinang' || lower === 'kota tanjungpinang' || lower === 'tanjung pinang' || lower === 'kota tanjung pinang') {
			return 'Kota Tanjungpinang';
		}
		if (/^kabupaten\s+/i.test(s)) {
			return s;
		}
		if (/^kab\.\s*/i.test(s)) {
			return 'Kabupaten ' + s.replace(/^kab\.\s*/i, '');
		}
		if (/^kota\s+/i.test(s)) {
			return s;
		}
		return 'Kabupaten ' + s;
	}

	function create(o) {
		o = o || {};
		var id = 'kgtk-sk-' + (++uid);
		var kabs = o.kabkota || [];
		var hasList = kabs.length > 0;
		var cache = {};
		var st = { kab: '', list: [], sel: null, manual: false, active: -1 };

		var node = el('div', { class: 'kgtk-sekolah' });

		/* ---- isian manual (juga satu-satunya isian bila data sekolah belum ada) ---- */
		var manualInput = el('input', { class: 'kgtk-input', type: 'text', id: id + '-manual', maxlength: 200, placeholder: 'Ketik nama sekolah/instansi atau NPSN', autocomplete: 'off' });
		var manualWrap = el('label', { class: 'kgtk-field', for: id + '-manual' }, [el('span', { text: hasList ? 'Nama sekolah/instansi (diketik) *' : 'Asal sekolah/instansi *' }), manualInput]);

		if (!hasList) {
			node.appendChild(manualWrap);
			return {
				node: node,
				getValue: function () { return { kabkota: '', sekolah_id: 0, sekolah: manualInput.value.trim() }; },
				setValue: function (v) { manualInput.value = (v && v.sekolah) || ''; },
				validate: function () { return manualInput.value.trim() ? '' : 'Asal sekolah/instansi wajib diisi.'; }
			};
		}

		/* ---- kabupaten/kota ---- */
		var kabSel = el('select', { class: 'kgtk-input', id: id + '-kab' }, [el('option', { value: '', text: 'Pilih kabupaten/kota' })]
			.concat(kabs.map(function (k) { return el('option', { value: k, text: formatKabLabel(k) }); }))
			.concat([el('option', { value: LAIN, text: 'Lainnya (di luar daftar)' })]));
		node.appendChild(el('label', { class: 'kgtk-field', for: id + '-kab' }, [el('span', { text: 'Kabupaten/Kota *' }), kabSel]));

		/* ---- pencarian sekolah ---- */
		var cari = el('input', { class: 'kgtk-input', type: 'text', id: id + '-cari', placeholder: 'Ketik untuk mencari sekolah/instansi atau NPSN', autocomplete: 'off', role: 'combobox', 'aria-expanded': 'false', 'aria-controls': id + '-list' });
		var list = el('ul', { class: 'kgtk-combo-list', id: id + '-list', role: 'listbox', hidden: true });
		var info = el('small', { class: 'kgtk-combo-info' });
		var toggle = el('button', { type: 'button', class: 'kgtk-link' });
		var comboBox = el('div', { class: 'kgtk-field' }, [
			el('label', { for: id + '-cari' }, [el('span', { text: 'Asal sekolah/instansi *' })]),
			el('div', { class: 'kgtk-combo' }, [cari, list]),
			info
		]);
		node.appendChild(comboBox);
		node.appendChild(manualWrap);
		node.appendChild(toggle);

		function manualMode() { return st.kab === LAIN || st.manual; }

		function sync() {
			var ada = !!st.kab, lain = st.kab === LAIN, man = manualMode();
			comboBox.hidden = !ada || man;
			manualWrap.hidden = !ada || !man;
			toggle.hidden = !ada || lain;
			toggle.textContent = st.manual ? 'Pilih sekolah/instansi dari daftar' : 'Sekolah/instansi saya tidak ada di daftar';
		}

		function tutup() { list.hidden = true; cari.setAttribute('aria-expanded', 'false'); st.active = -1; }

		function pilih(item) {
			st.sel = item;
			cari.value = item.nama;
			tutup();
		}

		function render() {
			var q = norm(cari.value);
			var hasil = st.list.filter(function (s) { return !q || norm(s.nama).indexOf(q) > -1 || norm(s.npsn).indexOf(q) > -1; });
			var tampil = hasil.slice(0, 40);
			list.textContent = '';
			tampil.forEach(function (s, i) {
				var li = el('li', { class: 'kgtk-combo-item' + (i === st.active ? ' is-active' : ''), role: 'option' }, [
					el('span', { text: s.nama }),
					el('small', { text: [s.jenjang, s.npsn ? 'NPSN ' + s.npsn : ''].filter(Boolean).join(' - ') })
				]);
				li.addEventListener('mousedown', function (e) { e.preventDefault(); pilih(s); });
				list.appendChild(li);
			});
			if (!tampil.length) { list.appendChild(el('li', { class: 'kgtk-combo-empty', text: 'Tidak ada sekolah/instansi yang cocok.' })); }
			if (hasil.length > tampil.length) { list.appendChild(el('li', { class: 'kgtk-combo-empty', text: 'Menampilkan 40 dari ' + hasil.length + ' sekolah/instansi. Ketik untuk mempersempit.' })); }
			st.shown = tampil;
			list.hidden = false;
			cari.setAttribute('aria-expanded', 'true');
		}

		function muat(kab) {
			if (cache[kab]) { st.list = cache[kab]; info.textContent = st.list.length + ' sekolah.'; return Promise.resolve(st.list); }
			info.textContent = 'Memuat daftar sekolah...';
			return fetchSekolah(o.root, kab).then(function (l) {
				cache[kab] = l;
				if (st.kab === kab) { st.list = l; info.textContent = l.length + ' sekolah.'; }
				return l;
			}).catch(function (e) { info.textContent = e.message; return []; });
		}

		function gantiKab(kab) {
			st.kab = kab; st.sel = null; st.manual = false; st.list = [];
			cari.value = ''; manualInput.value = ''; tutup();
			sync();
			if (kab && kab !== LAIN) { return muat(kab); }
			return Promise.resolve([]);
		}

		kabSel.addEventListener('change', function () { gantiKab(kabSel.value); });
		cari.addEventListener('input', function () { st.sel = null; st.active = -1; render(); });
		cari.addEventListener('focus', function () { if (st.list.length) { render(); } });
		cari.addEventListener('blur', function () { setTimeout(tutup, 120); });
		cari.addEventListener('keydown', function (e) {
			var n = (st.shown || []).length;
			if (e.key === 'ArrowDown') { e.preventDefault(); if (list.hidden) { render(); } st.active = Math.min(n - 1, st.active + 1); render(); }
			else if (e.key === 'ArrowUp') { e.preventDefault(); st.active = Math.max(0, st.active - 1); render(); }
			else if (e.key === 'Enter' && !list.hidden && n) { e.preventDefault(); pilih(st.shown[st.active >= 0 ? st.active : 0]); }
			else if (e.key === 'Escape') { tutup(); }
		});
		toggle.addEventListener('click', function () {
			st.manual = !st.manual; st.sel = null; cari.value = ''; manualInput.value = '';
			sync();
			(st.manual ? manualInput : cari).focus();
		});
		sync();

		return {
			node: node,
			getValue: function () {
				var man = manualMode();
				return {
					kabkota: st.kab,
					sekolah_id: (!man && st.sel) ? st.sel.id : 0,
					sekolah: man ? manualInput.value.trim() : (st.sel ? st.sel.nama : '')
				};
			},
			validate: function () {
				if (!st.kab) { return 'Pilih kabupaten/kota.'; }
				if (manualMode()) { return manualInput.value.trim() ? '' : 'Ketik nama sekolah/instansi Anda.'; }
				return st.sel ? '' : 'Pilih sekolah/instansi dari daftar, atau pilih "Sekolah/instansi saya tidak ada di daftar".';
			},
			setValue: function (v) {
				v = v || {};
				var kab = v.kabkota || '';
				if (kab && kab !== LAIN && kabs.indexOf(kab) < 0) { kab = ''; }
				kabSel.value = kab;
				gantiKab(kab).then(function () {
					if (!kab) { return; }
					if (kab === LAIN) { manualInput.value = v.sekolah || ''; return; }
					var sid = parseInt(v.sekolah_id, 10) || 0, ketemu = null;
					if (sid) { st.list.some(function (s) { if (s.id === sid) { ketemu = s; return true; } return false; }); }
					if (ketemu) { pilih(ketemu); }
					else if (v.sekolah) { st.manual = true; manualInput.value = v.sekolah; sync(); }
				});
			}
		};
	}

	window.KGTKSekolah = { create: create };
})();
