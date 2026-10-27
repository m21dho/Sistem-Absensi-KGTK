(function () {
	'use strict';
	var root = document.getElementById('kgtk-app');
	if (!root || !window.KGTK_PUB) { return; }

	// Pastikan meta viewport ada di <head> agar peramban ponsel (Chrome/Safari) langsung pas sesuai resolusi layar HP
	try {
		var vp = document.querySelector('meta[name="viewport"]');
		if (!vp) {
			vp = document.createElement('meta');
			vp.name = 'viewport';
			vp.content = 'width=device-width, initial-scale=1.0, maximum-scale=5.0';
			document.head.appendChild(vp);
		}
	} catch (eVp) { }

	// Sembunyikan judul halaman bawaan theme WordPress yang duplikat
	try {
		var postTitles = document.querySelectorAll('.wp-block-post-title, .entry-title, .page-title, header.entry-header');
		Array.prototype.forEach.call(postTitles, function (pt) {
			if (!root.contains(pt)) {
				pt.style.display = 'none';
			}
		});
	} catch (eTitle) { }

	var C = window.KGTK_PUB || {};
	var API = C.root || '';

	var PERAN = ['Fasilitator', 'Pengajar', 'Narasumber', 'Penceramah', 'Pemateri', 'Panitia', 'Peserta'];
	var JENIS_GTK = ['Guru', 'Tenaga Didik', 'Kepala Sekolah', 'Pengawas Sekolah'];
	var JABATAN = ['Guru', 'Tenaga Didik', 'Kepala Sekolah', 'Pengawas Sekolah'];
	var JENIS_KELAMIN = ['Laki-laki', 'Perempuan'];
	var PENDIDIKAN = ['SMA/SMK Sederajat', 'D3', 'D4 / S1', 'S2', 'S3'];
	var AGAMA = ['Islam', 'Kristen Protestan', 'Katolik', 'Hindu', 'Buddha', 'Khonghucu'];

	function formatNamaGelar(nama, gd, gb) {
		nama = (nama || '').trim();
		gd = (gd || '').trim();
		gb = (gb || '').trim();
		var res = nama;
		if (gd) {
			res = gd + ' ' + res;
		}
		if (gb) {
			res = res + (res ? ', ' : '') + gb;
		}
		return res;
	}

	function emptyForm() {
		return {
			nama: '',
			gelar_depan: '',
			gelar_belakang: '',
			tempat_lahir: '',
			tanggal_lahir: '',
			jenis_kelamin: '',
			alamat: '',
			alamat_instansi: '',
			pangkat_golongan: '',
			agama: '',
			pendidikan: '',
			bank: '',
			rekening: '',
			npwp: '',
			pengalaman_jabatan: '',
			sekolah: '',
			kabkota: '',
			sekolah_id: 0,
			peran: 'Peserta',
			jenis_gtk: '',
			jabatan: '',
			nuptk: '',
			nisn: '',
			nip: '',
			hp: '',
			email: '',
			email_belajar: '',
			simpkb: '',
			email_guru: ''
		};
	}
	var S = {
		view: 'beranda',
		kode: '',
		info: null,
		nik: '',
		cek: null,
		hasil: null,
		form: emptyForm(),
		biodataState: 'input',
		biodataData: null,
		verifTipe: '',
		verifNilai: '',
		verifKode: ''
	};

	/* ---------- util ---------- */
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
	function api(path, method, body) {
		var url = API + path;
		return fetch(url, {
			method: method || 'GET',
			headers: { 'Content-Type': 'application/json' },
			body: body ? JSON.stringify(body) : undefined
		}).then(function (r) {
			return r.json().catch(function () { return {}; }).then(function (d) {
				if (!r.ok) { var e = new Error(d.message || 'Terjadi kesalahan. Coba lagi.'); e.code = (d.data && d.data.kode) || ''; throw e; }
				return d;
			});
		}, function () { throw new Error('Tidak dapat terhubung ke server. Periksa koneksi internet lalu coba lagi.'); });
	}
	function getInfo(kode) {
		var sep = API.indexOf('?') > -1 ? '&' : '?';
		return api('public/pelatihan' + sep + 'kode=' + encodeURIComponent(kode), 'GET');
	}
	function go(view) { S.view = view; render(); window.scrollTo({ top: root.offsetTop - 10, behavior: 'smooth' }); }
	function errBox() { return el('div', { class: 'kgtk-alert', role: 'alert', hidden: true }); }
	function showErr(box, m) { box.textContent = m; box.hidden = false; }
	function busy(btn, on, label) { btn.disabled = on; btn.textContent = on ? 'Memproses...' : label; }

	function field(label, id, attrs, value) {
		var input = el('input', Object.assign({ class: 'kgtk-input', id: id, name: id }, attrs));
		if (value) { input.value = value; }
		var lblNode = (typeof label === 'string') ? el('span', { text: label }) : label;
		return { wrap: el('label', { class: 'kgtk-field', for: id }, [lblNode, input]), input: input };
	}

	function textareaField(label, id, attrs, value) {
		var input = el('textarea', Object.assign({ class: 'kgtk-input', id: id, name: id }, attrs));
		if (value) { input.value = value; }
		return { wrap: el('label', { class: 'kgtk-field', for: id }, [el('span', { text: label }), input]), input: input };
	}

	function selectField(label, id, options, value) {
		var opts = options.map(function (o) {
			var val = typeof o === 'object' ? o.value : o;
			var txt = typeof o === 'object' ? o.text : o;
			return el('option', { value: val, text: txt });
		});
		var sel = el('select', { class: 'kgtk-input', id: id, name: id }, opts);
		if (value) { sel.value = value; }
		return { wrap: el('label', { class: 'kgtk-field', for: id }, [el('span', { text: label }), sel]), input: sel };
	}

	function maskNik(nik) {
		return nik ? nik.slice(0, 4) + '************' : '';
	}

	function goBiodataSaya() {
		S.biodataState = 'input';
		S.biodataData = null;
		S.verifTipe = '';
		S.verifNilai = '';
		go('biodata-saya');
	}

	function esc(str) {
		if (str === null || str === undefined) { return ''; }
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	/**
	 * Menampilkan dialog modal popup bertema resmi KGTK
	 */
	function kgtkAlert(title, message, options) {
		options = options || {};
		var type = options.type || 'warning';
		var confirmText = options.confirmText || 'Tutup';
		var errors = options.errors || [];

		return new Promise(function (resolve) {
			var iconSvg = '';
			if (type === 'danger') {
				iconSvg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';
			} else if (type === 'warning') {
				iconSvg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>';
			} else {
				iconSvg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>';
			}

			var overlay = document.createElement('div');
			overlay.className = 'kgtk-modal-overlay';

			var dialog = document.createElement('div');
			dialog.className = 'kgtk-modal-dialog kgtk-ui';
			dialog.style.maxWidth = '480px';

			var listHtml = '';
			if (errors.length > 0) {
				listHtml = '<ul class="kgtk-modal-err-list">';
				errors.forEach(function (errItem) {
					listHtml += '<li class="kgtk-modal-err-item">' +
						'<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>' +
						'<div><strong>' + esc(errItem.label) + ':</strong> ' + esc(errItem.msg) + '</div>' +
						'</li>';
				});
				listHtml += '</ul>';
			}

			var msgHtml = esc(message).replace(/\n\n/g, '<div class="kgtk-modal-space"></div>').replace(/\n/g, '<br>');

			dialog.innerHTML =
				'<div class="kgtk-modal-top">' +
				'<div class="kgtk-modal-icon is-' + type + '">' + iconSvg + '</div>' +
				'<div class="kgtk-modal-content">' +
				'<h3 class="kgtk-modal-title">' + esc(title) + '</h3>' +
				(message ? '<div class="kgtk-modal-desc">' + msgHtml + '</div>' : '') +
				listHtml +
				'</div>' +
				'</div>' +
				'<div class="kgtk-modal-actions">' +
				'<button type="button" class="kgtk-btn kgtk-btn--navy kgtk-modal-confirm" style="width:100%;">' + esc(confirmText) + '</button>' +
				'</div>';

			overlay.appendChild(dialog);
			document.body.appendChild(overlay);

			requestAnimationFrame(function () {
				overlay.classList.add('is-visible');
			});

			var isClosed = false;
			var closeDialog = function () {
				if (isClosed) { return; }
				isClosed = true;
				overlay.classList.remove('is-visible');
				document.removeEventListener('keydown', onKey);
				setTimeout(function () {
					if (overlay.parentNode) {
						overlay.parentNode.removeChild(overlay);
					}
					resolve();
				}, 200);
			};

			var onKey = function (e) {
				if (e.key === 'Escape' || e.key === 'Enter') {
					e.preventDefault();
					closeDialog();
				}
			};

			var btnConfirm = dialog.querySelector('.kgtk-modal-confirm');
			if (btnConfirm) {
				btnConfirm.addEventListener('click', closeDialog);
				btnConfirm.focus();
			}

			overlay.addEventListener('click', function (e) {
				if (e.target === overlay) {
					closeDialog();
				}
			});

			document.addEventListener('keydown', onKey);
		});
	}

	/** Link WhatsApp Bantuan KGTK */
	function formatWaLink(customMsg) {
		var raw = (C && C.waHelp) ? String(C.waHelp) : '08136798163';
		var num = raw.replace(/\D/g, '');
		if (num.indexOf('0') === 0) {
			num = '62' + num.slice(1);
		} else if (num.indexOf('62') !== 0) {
			num = '62' + num;
		}
		var msg = customMsg || 'Halo Admin KGTK Kepri, saya mengalami kendala saat pendaftaran pelatihan. Mohon bantuannya.';
		return 'https://wa.me/' + num + '?text=' + encodeURIComponent(msg);
	}

	function formatDisplayWa(raw) {
		raw = String(raw || '08136798163').trim();
		var digits = raw.replace(/\D/g, '');
		if (digits.length >= 10 && digits.length <= 13) {
			if (digits.indexOf('62') === 0) {
				return '+62 ' + digits.slice(2, 5) + '-' + digits.slice(5, 9) + '-' + digits.slice(9);
			} else if (digits.indexOf('0') === 0) {
				return digits.slice(0, 4) + '-' + digits.slice(4, 8) + '-' + digits.slice(8);
			}
		}
		return raw;
	}

	/** SVG Icon WhatsApp resmi */
	function getWaIconSvg(size) {
		size = size || 28;
		return '<svg viewBox="0 0 32 32" width="' + size + '" height="' + size + '" fill="currentColor" style="display:block;">' +
			'<path d="M16.01 3C8.84 3 3 8.84 3 16.01c0 2.4.66 4.72 1.91 6.74L3 29l6.45-1.87a12.94 12.94 0 0 0 6.56 1.78h.01c7.17 0 13.01-5.84 13.01-13.01A12.94 12.94 0 0 0 16.01 3zm0 23.77c-2.07 0-4.08-.56-5.83-1.63l-.42-.25-4.32 1.26 1.28-4.22-.27-.44a10.83 10.83 0 0 1-1.65-5.73c0-5.99 4.88-10.87 10.87-10.87 2.9 0 5.63 1.13 7.68 3.19a10.8 10.8 0 0 1 3.19 7.68c0 5.99-4.88 10.87-10.87 10.87zm5.96-8.15c-.33-.16-1.94-.96-2.24-1.07-.3-.11-.52-.16-.74.16-.22.33-.86 1.07-1.05 1.29-.2.22-.39.25-.72.08-.33-.16-1.39-.51-2.65-1.63-.98-.87-1.64-1.95-1.83-2.28-.2-.33-.02-.51.14-.67.15-.15.33-.39.49-.58.17-.2.22-.33.33-.55.11-.22.06-.41-.03-.58-.08-.16-.74-1.78-1.01-2.44-.27-.64-.54-.55-.74-.56l-.63-.01c-.22 0-.58.08-.88.41-.3.33-1.16 1.13-1.16 2.76s1.19 3.2 1.35 3.42c.16.22 2.34 3.57 5.67 5.01.79.34 1.41.55 1.89.7.79.25 1.51.22 2.08.13.64-.1 1.94-.79 2.22-1.56.27-.76.27-1.42.19-1.56-.08-.13-.3-.22-.63-.38z"/>' +
			'</svg>';
	}

	/** Banner bantuan di area footer */
	function createFooterHelp() {
		var rawNum = (C && C.waHelp) ? String(C.waHelp) : '08136798163';
		var displayNum = formatDisplayWa(rawNum);
		var waUrl = formatWaLink();
		var wrap = el('footer', { class: 'kgtk-footer-help' });
		wrap.innerHTML = '<div class="kgtk-footer-help-info">' +
			'<div class="kgtk-footer-help-icon" aria-hidden="true">' + getWaIconSvg(28) + '</div>' +
			'<div class="kgtk-footer-help-text">' +
			'<div class="kgtk-footer-help-title">Mengalami kendala saat pendaftaran pelatihan?</div>' +
			'<div class="kgtk-footer-help-sub">Hubungi Layanan Bantuan KGTK Kepri via WhatsApp di <strong>' + esc(displayNum) + '</strong> untuk bantuan langsung.</div>' +
			'</div>' +
			'</div>' +
			'<a class="kgtk-footer-help-cta" href="' + esc(waUrl) + '" target="_blank" rel="noopener noreferrer" aria-label="Hubungi WhatsApp KGTK">' +
			getWaIconSvg(20) +
			'<span>Chat WhatsApp</span>' +
			'</a>';
		return wrap;
	}

	/** Tombol Floating WhatsApp Bantuan */
	function createFloatingWa() {
		var waUrl = formatWaLink();
		var btn = el('a', {
			class: 'kgtk-wa-float',
			href: waUrl,
			target: '_blank',
			rel: 'noopener noreferrer',
			'aria-label': 'Hubungi WhatsApp Bantuan KGTK'
		});
		btn.innerHTML = '<span class="kgtk-wa-float-icon">' + getWaIconSvg(24) + '</span>' +
			'<span class="kgtk-wa-float-label">Bantuan WhatsApp</span>';
		return btn;
	}

	/** Format Kop resmi Kemendikdasmen */
	function getKopHtml() {
		var logoImg = (C && C.logoUrl)
			? '<img src="' + esc(C.logoUrl) + '" style="max-height:74px;width:auto;display:inline-block;vertical-align:middle;" alt="Kemendikdasmen" />'
			: '';
		var headerSrc = (C && C.headerUrl) ? C.headerUrl : ((C && C.logoUrl) ? C.logoUrl.replace('logo-kemendikdasmen.svg', 'header-kemendikdasmen.png') : '');
		var headerImg = headerSrc
			? '<img src="' + esc(headerSrc) + '" style="max-height:80px;width:auto;display:inline-block;vertical-align:middle;" alt="Kementerian Pendidikan Dasar dan Menengah" />'
			: '';
		return '<div class="kgtk-kop-wrap" style="width:100%;text-align:center;margin:0 auto 16px;">' +
			'<table class="kgtk-kop-tbl" align="center" style="width:100%;max-width:760px;margin:0 auto;border-collapse:collapse;border:0;">' +
			'<tr>' +
			'<td style="width:49%;vertical-align:middle;padding:0 14px 0 0;border:0;text-align:right;">' + logoImg + '</td>' +
			'<td class="kgtk-kop-line" style="width:1px;max-width:1px;min-width:1px;background-color:#0083C4;padding:0;border:0;font-size:1px;line-height:1px;">&nbsp;</td>' +
			'<td style="width:49%;vertical-align:middle;padding:0 0 0 16px;border:0;text-align:left;">' + headerImg + '</td>' +
			'</tr></table>' +
			'</div>';
	}

	/** Buka jendela cetak biodata guru untuk satu pelatihan tertentu. */
	function downloadBiodataPerPelatihan(b, pel) {
		function getKabkotaKegiatan(pelObj) {
			var kab = (pelObj && pelObj.kabkota) || '';
			var lok = (pelObj && pelObj.lokasi) || (typeof pelObj === 'string' ? pelObj : '');
			var target = kab.trim() || lok.trim();
			if (!target) { return 'Bintan'; }
			var low = target.toLowerCase();
			if (!kab.trim() && (low.indexOf('zoom') > -1 || low.indexOf('daring') > -1 || low.indexOf('online') > -1 || low.indexOf('lms') > -1)) {
				return 'Bintan';
			}
			if (low.indexOf('tanjungpinang') > -1 || low.indexOf('tanjung pinang') > -1) { return 'Tanjungpinang'; }
			if (low.indexOf('batam') > -1) { return 'Batam'; }
			if (low.indexOf('bintan') > -1) { return 'Bintan'; }
			if (low.indexOf('karimun') > -1) { return 'Karimun'; }
			if (low.indexOf('natuna') > -1) { return 'Natuna'; }
			if (low.indexOf('lingga') > -1) { return 'Lingga'; }
			if (low.indexOf('anambas') > -1) { return 'Kepulauan Anambas'; }
			var clean = target.replace(/^(kota|kabupaten|kab\.)\s+/i, '');
			return clean || target;
		}

		var peranUpper = (pel.peran || b.peran || 'PESERTA').toUpperCase();
		var lokasiTxt = pel.lokasi ? (pel.lokasi.toLowerCase().indexOf('di ') === 0 ? pel.lokasi : 'di ' + pel.lokasi) : '';
		var nipTxt = b.nip || '-';
		var kabkotaTxt = getKabkotaKegiatan(pel);
		var emailTxt = b.email || b.email_belajar || '-';
		var bankTxt = b.bank || '-';
		var rekeningTxt = b.rekening || '-';
		var jabatanTxt = b.jabatan || b.jenis_gtk || pel.jenis_gtk || '-';
		var sekolahTxt = b.sekolah || '-';
		var hpTxt = b.hp || '-';

		var today = new Date();
		var months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
		var tglCetak = today.getDate() + ' ' + months[today.getMonth()] + ' ' + today.getFullYear();

		var ttlTxt = '-';
		var tglFmt = b.tanggal_lahir || '';
		if (tglFmt && /^\d{4}-\d{2}-\d{2}$/.test(tglFmt)) {
			var pDate = tglFmt.split('-');
			var mIdx = parseInt(pDate[1], 10) - 1;
			if (mIdx >= 0 && mIdx < 12) {
				tglFmt = parseInt(pDate[2], 10) + ' ' + months[mIdx] + ' ' + pDate[0];
			}
		}
		if (b.tempat_lahir && b.tempat_lahir !== '-' && tglFmt && tglFmt !== '-') {
			ttlTxt = b.tempat_lahir + ', ' + tglFmt;
		} else if (b.tempat_lahir && b.tempat_lahir !== '-') {
			ttlTxt = b.tempat_lahir;
		} else if (tglFmt && tglFmt !== '-') {
			ttlTxt = tglFmt;
		}

		var namaLengkap = formatNamaGelar(b.nama, b.gelar_depan, b.gelar_belakang);
		var bioRows = [
			['NAMA', namaLengkap],
			['NIP', nipTxt],
			['TEMPAT/TANGGAL LAHIR', ttlTxt],
			['PANGKAT/GOLONGAN', b.pangkat_golongan || '-'],
			['PENDIDIKAN TERAKHIR', b.pendidikan || '-'],
			['JENIS KELAMIN', b.jenis_kelamin || '-'],
			['AGAMA', b.agama || '-'],
			['JABATAN', jabatanTxt],
			['INSTANSI TEMPAT TUGAS', sekolahTxt],
			['ALAMAT INSTANSI', b.alamat_instansi || '-'],
			['NO. HP/WHATSAPP', hpTxt],
			['EMAIL', emailTxt],
			['NAMA BANK', bankTxt],
			['NOMOR REKENING', rekeningTxt]
		];

		var trRows = bioRows.map(function (r) {
			return '<tr>' +
				'<td class="lbl">' + esc(r[0]) + '</td>' +
				'<td class="sep">:</td>' +
				'<td class="val">' + esc(r[1] || '-') + '</td>' +
				'</tr>';
		}).join('');

		var kop = getKopHtml();

		var html = '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Biodata - ' + esc(namaLengkap) + '</title>' +
			'<style>' +
			'@page{size:A4;margin:0.51cm 1.0cm}' +
			'body{font-family:\'Times New Roman\',Times,Georgia,serif;margin:0;padding:0.51cm 1.0cm;color:#000;line-height:1.35;background:#fff}' +
			'.kgtk-kop-wrap{width:100%;text-align:center;margin:0 auto 16px}' +
			'.kgtk-kop-tbl{margin:0 auto;border-collapse:collapse;border:0;width:100%;max-width:760px}' +
			'.kgtk-kop-tbl td{border:0!important;vertical-align:middle}' +
			'.title-box{text-align:center;margin:8pt 0;font-family:\'Times New Roman\',Times,Georgia,serif}' +
			'.title-main{font-size:16pt;font-weight:bold;text-transform:uppercase;margin-bottom:4px;letter-spacing:0.5px}' +
			'.title-sub{font-size:14pt;line-height:1.35;margin-bottom:2px;font-weight:bold}' +
			'.title-date{font-size:14pt;margin-bottom:2px}' +
			'.title-loc{font-size:14pt;margin-bottom:14pt}' +
			'table.bio-box{width:100%;border-collapse:collapse;border:1.5px solid #000;margin-bottom:18px}' +
			'table.bio-box td{padding:4.5px 8px;font-size:12pt;vertical-align:top;border:0!important}' +
			'td.lbl{width:34%}' +
			'td.sep{width:3%;text-align:center}' +
			'td.val{width:63%}' +
			'table.ttd-tbl{width:100%;border-collapse:collapse;margin-top:15px}' +
			'table.ttd-tbl td{border:0!important;vertical-align:top}' +
			'td.ttd-right{width:250px;text-align:center;font-size:12pt}' +
			'.space{height:60px}' +
			'@media print{body{padding:0!important}}' +
			'</style></head><body>' +
			kop +
			'<div class="title-box">' +
			'<div class="title-main">BIODATA ' + esc(peranUpper) + '</div>' +
			'<div class="title-sub">' + esc(pel.nama) + '</div>' +
			(pel.tanggal ? '<div class="title-date">' + esc(pel.tanggal) + '</div>' : '') +
			(lokasiTxt ? '<div class="title-loc">' + esc(lokasiTxt) + '</div>' : '') +
			'</div>' +
			'<table class="bio-box">' + trRows + '</table>' +
			'<table class="ttd-tbl"><tr><td>&nbsp;</td><td class="ttd-right">' +
			'<div>' + esc(kabkotaTxt) + ', ' + esc(tglCetak) + '</div>' +
			'<div class="space">&nbsp;</div>' +
			'<div><u><strong>' + esc(namaLengkap) + '</strong></u></div>' +
			'</td></tr></table>' +
			'<script>window.onload=function(){window.print();}<\/script>' +
			'</body></html>';

		var w = window.open('', '_blank', 'width=800,height=750');
		if (w) { w.document.write(html); w.document.close(); }
	}

	/* ---------- kerangka ---------- */
	var STEPS = ['Kode pelatihan', 'Identitas (NIK)', 'Biodata'];
	var LABELS = {
		loading: 'Memuat',
		beranda: 'Web Portal KGTK',
		kode: 'Langkah 1 \u00b7 Kode pelatihan',
		nik: 'Langkah 2 \u00b7 Identitas',
		'nik-verif': 'Langkah 2 \u00b7 Verifikasi pengisian otomatis',
		terdaftar: 'Status pendaftaran',
		biodata: 'Langkah 3 \u00b7 Biodata',
		sukses: 'Pendaftaran selesai',
		'biodata-saya': 'Biodata & Riwayat Saya'
	};
	function stepNow() {
		return { beranda: 1, kode: 1, loading: 1, nik: 2, 'nik-verif': 2, terdaftar: 2, biodata: 3, sukses: 4 }[S.view] || 1;
	}
	/** Kerangka halaman mengikuti gaya situs: header logo di atas, menu langkah di samping, banner gradien, label bagian, lalu isi. */
	function frame(content) {
		var isHomeView = (S.view === 'beranda');
		var isBioView = (S.view === 'biodata-saya');
		var now = stepNow();
		var steps = el('ol', { class: 'kgtk-steps' }, STEPS.map(function (s, i) {
			var n = i + 1;
			return el('li', { class: !isBioView && !isHomeView && n < now ? 'is-done' : (!isBioView && !isHomeView && n === now ? 'is-now' : '') }, [el('span', { class: 'n', text: !isBioView && !isHomeView && n < now ? '\u2713' : String(n) }), s]);
		}));
		var side = el('aside', { class: 'kgtk-side kgtk-card' }, [el('div', { class: 'kgtk-side-title', text: 'Langkah pendaftaran' }), steps]);

		// Header atas dengan logo resmi Kemendikdasmen KGTK Kepulauan Riau
		var logoImg = el('img', {
			class: 'kgtk-top-header-logo',
			src: C.logoUrl || '',
			alt: 'Kementerian Pendidikan Dasar dan Menengah - KGTK Kepulauan Riau'
		});
		var brandWrap = el('div', { class: 'kgtk-top-header-brand' }, [
			logoImg,
			el('div', { class: 'kgtk-top-header-tagline' }, [
				el('div', { class: 'kgtk-tagline-title', text: 'KGTK Provinsi Kepulauan Riau' }),
				el('div', { class: 'kgtk-tagline-sub', text: 'Kantor Guru dan Tenaga Kependidikan' })
			])
		]);
		brandWrap.style.cursor = 'pointer';
		brandWrap.title = 'Kembali ke Beranda';
		brandWrap.addEventListener('click', function () {
			S.biodataData = null;
			S.biodataState = 'input';
			go('beranda');
		});

		var badgeText = 'Web Portal KGTK';
		if (isBioView) {
			badgeText = 'Biodata & Riwayat Guru';
		} else if (!isHomeView) {
			badgeText = 'Pendaftaran Pelatihan Guru';
		}

		var portalBadge = el('div', { class: 'kgtk-top-header-badge' }, [
			el('span', { class: 'kgtk-badge-dot' }),
			el('span', { text: badgeText })
		]);

		var topHeader = el('header', { class: 'kgtk-top-header' }, [
			brandWrap,
			portalBadge
		]);

		var footerHelp = createFooterHelp();

		// Jika di halaman beranda / landasan awal, gunakan layout khusus tanpa sidebar langkah pendaftaran
		if (isHomeView) {
			var landingLayout = el('div', { class: 'kgtk-layout kgtk-layout--beranda' }, [content]);
			return [topHeader, landingLayout, footerHelp];
		}

		// Jika di halaman biodata & riwayat saya, gunakan layout simpel tanpa sidebar dan tanpa banner ganda
		if (isBioView) {
			var simpleLayout = el('div', { class: 'kgtk-layout kgtk-layout--simple' }, [content]);
			return [topHeader, simpleLayout, footerHelp];
		}

		var crumbHome = el('span', { class: 'kgtk-crumb', text: 'Beranda', style: 'cursor:pointer;' });
		crumbHome.addEventListener('click', function () { go('beranda'); });

		var crumbs = el('div', { class: 'kgtk-crumbs' }, [
			crumbHome,
			el('span', { class: 'kgtk-crumb-sep', text: '\u203a' }),
			el('span', { class: 'kgtk-crumb is-now', text: 'Pendaftaran Pelatihan' })
		]);

		// Tombol biodata di header hero alur pendaftaran
		var iconSpan = el('span', { class: 'kgtk-bio-btn-icon', 'aria-hidden': 'true' });
		iconSpan.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:block;"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>';
		var textSpan = el('span', { text: 'Lihat Biodata & Riwayat Saya' });
		var pillBtn = el('button', { type: 'button', class: 'kgtk-hero-bio-btn', 'aria-label': 'Lihat Biodata dan Riwayat Saya' }, [iconSpan, textSpan]);
		pillBtn.addEventListener('click', goBiodataSaya);
		var heroWrap = el('div', { class: 'kgtk-hero-bio-wrap' }, [pillBtn]);

		var heroChildren = [
			crumbs,
			el('h1', {}, ['Pendaftaran ', el('span', { text: 'Pelatihan' })]),
			el('p', { text: 'Daftar pelatihan guru dan tenaga kependidikan dengan kode pelatihan dan NIK. Ikuti langkah-langkah berikut.' }),
			heroWrap
		];

		var hero = el('header', { class: 'kgtk-hero' }, heroChildren);
		var main = el('div', { class: 'kgtk-main' }, [hero, el('div', { class: 'kgtk-label', text: LABELS[S.view] || '' }), content]);
		return [topHeader, el('div', { class: 'kgtk-layout' }, [side, main]), footerHelp];
	}
	function render() {
		try {
			var fn = {
				loading: vLoading,
				beranda: vBeranda,
				kode: vKode,
				nik: vNik,
				'nik-verif': vNikVerif,
				biodata: vBiodata,
				terdaftar: vTerdaftar,
				sukses: vSukses,
				'biodata-saya': vBiodataSaya
			}[S.view];
			if (!fn) { fn = vBeranda; }
			var content = fn();
			root.textContent = '';
			frame(content).forEach(function (n) { root.appendChild(n); });
		} catch (err) {
			console.error('KGTK Render Error:', err);
			var errCard = el('div', { class: 'kgtk-card kgtk-center' }, [
				el('p', { class: 'kgtk-alert', text: 'Terjadi kendala saat memuat halaman: ' + (err.message || 'Error') }),
				el('button', { type: 'button', class: 'kgtk-btn kgtk-btn--ghost', text: 'Muat Ulang Halaman' })
			]);
			var rBtn = errCard.querySelector('button');
			if (rBtn) { rBtn.addEventListener('click', function () { window.location.reload(); }); }
			root.textContent = '';
			root.appendChild(errCard);
		}
	}

	/* ---------- komponen ---------- */
	function infoCard(p) {
		var card = el('div', { class: 'kgtk-card kgtk-info' }, [el('h2', { text: p.nama })]);
		var rows = [['Penyelenggara', p.penyelenggara], ['Tanggal', p.tanggal], ['Lokasi', p.lokasi]];
		rows.forEach(function (r) {
			if (r[1]) { card.appendChild(el('div', { class: 'row' }, [el('span', { text: r[0] + ': ' }), r[1]])); }
		});
		if (p.keterangan) { card.appendChild(el('div', { class: 'kgtk-ket', text: p.keterangan })); }
		card.appendChild(el('div', { class: 'kgtk-note', style: 'margin-top:12px;', text: 'Pendaftaran pelatihan dibuka, segera daftarkan diri anda' }));
		return card;
	}
	function detailList(items) {
		return el('dl', { class: 'kgtk-detail' }, items.map(function (i) { return el('div', {}, [el('dt', { text: i[0] }), el('dd', { text: i[1] })]); }));
	}
	function nomorBox(h) {
		if (h.nomor && h.slot > 0) {
			return el('div', { class: 'kgtk-nomor', text: 'Nomor urut: ' + h.nomor + ' dari ' + h.slot + ' slot ' + h.kelas_label });
		}
		if (h.nomor && h.kelas_label && h.kelas_label.indexOf('Menunggu') === -1) {
			return el('div', { class: 'kgtk-nomor', text: 'Nomor urut: ' + h.nomor + ' pada daftar hadir ' + h.peran + ' ' + h.kelas_label });
		}
		return el('div', { class: 'kgtk-nomor', text: 'Status: Berhasil Mendaftar' });
	}

	/* ---------- tampilan ---------- */
	function vLoading() { return el('div', { class: 'kgtk-card kgtk-center' }, [el('p', { class: 'kgtk-muted', text: 'Memuat data...' })]); }

	/**
	 * Halaman Landasan Awal (Landing Portal KGTK)
	 * Menyambut pengguna dengan ucapan selamat datang dan dua tombol aksi utama.
	 */
	function vBeranda() {
		// Hero Section dengan Ucapan Selamat Datang
		var badgeWrap = el('div', { class: 'kgtk-crumbs' }, [
			el('span', { class: 'kgtk-crumb is-now', text: 'PORTAL LAYANAN RESMI' }),
			el('span', { class: 'kgtk-crumb', text: 'KGTK KEPULAUAN RIAU' })
		]);

		var heroTitle = el('h1', {}, ['Selamat Datang di Web Portal ', el('span', { text: 'KGTK' })]);
		var heroSub = el('p', { text: 'Portal Layanan Guru dan Tenaga Kependidikan Provinsi Kepulauan Riau. Silakan pilih salah satu layanan di bawah ini:' });

		var hero = el('header', { class: 'kgtk-hero kgtk-hero--beranda' }, [
			badgeWrap,
			heroTitle,
			heroSub
		]);

		// Tombol 1: Daftar Pelatihan
		var iconDaftar = el('div', { class: 'kgtk-landing-icon kgtk-landing-icon--primary', 'aria-hidden': 'true' });
		iconDaftar.innerHTML = '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">' +
			'<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>' +
			'<rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>' +
			'<path d="M9 14l2 2 4-4"></path>' +
			'</svg>';

		var btnDaftar = el('button', { type: 'button', class: 'kgtk-btn kgtk-btn--block kgtk-btn--large', text: 'Mulai Pendaftaran Pelatihan' }, [
			el('span', { class: 'arr', style: 'margin-left:8px;', text: '→' })
		]);

		var cardDaftar = el('div', { class: 'kgtk-landing-card kgtk-landing-card--primary' }, [
			iconDaftar,
			el('h3', { text: '1. Daftar Pelatihan Baru' }),
			el('p', { text: 'Daftarkan diri Anda pada kegiatan pelatihan KGTK yang baru dengan memasukkan kode pelatihan dari panitia.' }),
			btnDaftar
		]);

		cardDaftar.addEventListener('click', function () {
			go('kode');
		});

		// Tombol 2: Lihat Pelatihan Saya
		var iconBio = el('div', { class: 'kgtk-landing-icon kgtk-landing-icon--secondary', 'aria-hidden': 'true' });
		iconBio.innerHTML = '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">' +
			'<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>' +
			'<polyline points="14 2 14 8 20 8"></polyline>' +
			'<line x1="16" y1="13" x2="8" y2="13"></line>' +
			'<line x1="16" y1="17" x2="8" y2="17"></line>' +
			'<polyline points="10 9 9 9 8 9"></polyline>' +
			'</svg>';

		var btnBio = el('button', { type: 'button', class: 'kgtk-btn kgtk-btn--ghost kgtk-btn--block kgtk-btn--large', text: 'Buka Data & Riwayat Saya' }, [
			el('span', { class: 'arr', style: 'margin-left:8px;', text: '→' })
		]);

		var cardBio = el('div', { class: 'kgtk-landing-card kgtk-landing-card--secondary' }, [
			iconBio,
			el('h3', { text: '2. Lihat Pelatihan & Biodata Saya' }),
			el('p', { text: 'Periksa status pendaftaran Anda, cetak biodata, atau lihat riwayat seluruh pelatihan yang pernah diikuti.' }),
			btnBio
		]);

		cardBio.addEventListener('click', function () {
			goBiodataSaya();
		});

		var grid = el('div', { class: 'kgtk-landing-grid' }, [cardDaftar, cardBio]);

		return el('div', { class: 'kgtk-beranda-wrap' }, [hero, grid]);
	}

	function vKode() {
		var btnBackHome = el('button', { type: 'button', class: 'kgtk-btn-back', text: '← Kembali ke Beranda' });
		btnBackHome.addEventListener('click', function () { go('beranda'); });

		var f = field('Kode Pelatihan (sesuai surat undangan) *', 'kode', {
			type: 'text',
			maxlength: 20,
			autocapitalize: 'characters',
			autocomplete: 'off',
			placeholder: 'Contoh: KGTK-M7BD',
			required: true
		}, S.kode);
		var err = errBox();
		if (S.kodeError) { showErr(err, S.kodeError); S.kodeError = ''; }
		var btn = el('button', { type: 'submit', class: 'kgtk-btn kgtk-btn--block kgtk-btn--large', text: 'Lanjut ke Langkah 2 (Identitas) →' });

		var linkBio = el('button', { type: 'button', class: 'kgtk-link', text: '🔍 Sudah pernah mendaftar? Klik di sini untuk Lihat Pelatihan Saya' });
		linkBio.addEventListener('click', function () { goBiodataSaya(); });

		var form = el('form', { class: 'kgtk-card kgtk-card--senior', novalidate: true }, [
			el('div', { class: 'kgtk-card-header-clean' }, [
				el('div', { class: 'kgtk-card-icon-circle', 'aria-hidden': 'true' }, [el('span', { text: '📝' })]),
				el('div', {}, [
					el('h2', { text: 'Langkah 1: Masukkan Kode Pelatihan' }),
					el('p', { class: 'kgtk-card-desc', text: 'Silakan masukkan kode pelatihan yang tertera pada surat undangan atau informasi dari panitia kegiatan.' })
				])
			]),
			err, f.wrap, btn,
			el('div', { style: 'text-align:center;margin-top:16px;' }, [linkBio])
		]);
		f.input.addEventListener('input', function () { f.input.value = f.input.value.toUpperCase(); });
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			err.hidden = true;
			f.input.classList.remove('is-invalid');
			var kode = f.input.value.trim().toUpperCase();
			if (!/^[A-Z0-9-]{3,20}$/.test(kode)) {
				f.input.classList.add('is-invalid');
				var msg = !kode ? 'Kode pelatihan belum diisi.' : 'Format kode pelatihan tidak valid.';
				showErr(err, msg);
				kgtkAlert('Kode Belum Sesuai', msg + ' Silakan periksa kembali kode pelatihan yang Anda dapatkan dari panitia/undangan.', {
					type: 'warning',
					confirmText: 'Mengerti'
				}).then(function () {
					f.input.focus();
				});
				return;
			}
			busy(btn, true);
			getInfo(kode).then(function (d) { S.kode = kode; S.info = d; go('nik'); })
				.catch(function (x) {
					busy(btn, false, 'Lanjut');
					showErr(err, x.message);
					kgtkAlert('Kode Tidak Ditemukan', x.message || 'Pelatihan tidak ditemukan. Pastikan kode yang dimasukkan sudah benar.', {
						type: 'danger',
						confirmText: 'Coba Lagi'
					});
				});
		});
		return form;
	}

	function vNik() {
		var idType = S.idType || 'nik';
		var rNik = el('input', { type: 'radio', name: 'v_id_type', value: 'nik', id: 'v-id-nik', checked: idType !== 'nip' });
		var rNip = el('input', { type: 'radio', name: 'v_id_type', value: 'nip', id: 'v-id-nip', checked: idType === 'nip' });
		var radios = el('div', { class: 'kgtk-radios', style: 'margin-bottom:14px;' }, [
			el('label', { class: 'kgtk-radio', for: 'v-id-nik' }, [rNik, 'NIK (16 digit)']),
			el('label', { class: 'kgtk-radio', for: 'v-id-nip' }, [rNip, 'NIP (18 digit - Panitia/ASN)'])
		]);

		var f = field('NIK (16 digit)', 'nik', { type: 'text', inputmode: 'numeric', maxlength: 16, autocomplete: 'off', pattern: '[0-9]*', required: true }, S.nik || S.nip);
		var err = errBox();
		var btn = el('button', { type: 'submit', class: 'kgtk-btn kgtk-btn--block', text: 'Lanjut' });
		var back = el('button', { type: 'button', class: 'kgtk-link', text: 'Ganti kode pelatihan' });
		var form = el('form', { class: 'kgtk-card', novalidate: true }, [
			el('h2', { text: 'Nomor Identitas' }),
			el('p', { class: 'kgtk-muted', text: 'Pilih jenis identitas yang ingin digunakan untuk mengenali Anda di kegiatan ini.' }),
			radios,
			err,
			f.wrap,
			btn,
			back
		]);

		function updateInputMode() {
			var isNip = rNip.checked;
			var span = f.wrap.querySelector('span');
			if (span) { span.textContent = isNip ? 'NIP (18 digit) *' : 'NIK (16 digit) *'; }
			f.input.maxLength = isNip ? 18 : 16;
			f.input.placeholder = isNip ? '18 digit angka NIP' : '16 digit angka NIK';
			f.input.value = f.input.value.slice(0, isNip ? 18 : 16);
		}
		rNik.addEventListener('change', updateInputMode);
		rNip.addEventListener('change', updateInputMode);
		updateInputMode();

		f.input.addEventListener('input', function () {
			var max = rNip.checked ? 18 : 16;
			f.input.value = f.input.value.replace(/\D/g, '').slice(0, max);
			S.verifTipe = '';
			S.verifNilai = '';
		});
		back.addEventListener('click', function () {
			S.nik = '';
			S.nip = '';
			S.idType = 'nik';
			S.verifTipe = '';
			S.verifNilai = '';
			go('kode');
		});
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			err.hidden = true;
			f.input.classList.remove('is-invalid');
			var val = f.input.value.trim();
			var isNip = rNip.checked;
			if (isNip) {
				if (!/^\d{18}$/.test(val)) {
					f.input.classList.add('is-invalid');
					var msgNip = !val ? 'NIP belum diisi.' : 'NIP harus terdiri dari 18 digit angka.';
					showErr(err, msgNip);
					kgtkAlert('NIP Belum Sesuai', msgNip + ' Silakan periksa kembali 18 digit Nomor Induk Pegawai Anda.', {
						type: 'warning',
						confirmText: 'Periksa NIP'
					}).then(function () {
						f.input.focus();
					});
					return;
				}
				S.idType = 'nip';
				S.nip = val;
				S.nik = '';
			} else {
				if (!/^\d{16}$/.test(val)) {
					f.input.classList.add('is-invalid');
					var msgNik = !val ? 'NIK belum diisi.' : 'NIK harus terdiri dari 16 digit angka.';
					showErr(err, msgNik);
					kgtkAlert('NIK Belum Sesuai', msgNik + ' Silakan periksa kembali Nomor Induk Kependudukan pada KTP Anda.', {
						type: 'warning',
						confirmText: 'Periksa NIK'
					}).then(function () {
						f.input.focus();
					});
					return;
				}
				S.idType = 'nik';
				S.nik = val;
				S.nip = '';
			}
			busy(btn, true);
			cekNikOrNip(val, S.idType).catch(function (x) {
				busy(btn, false, 'Lanjut');
				showErr(err, x.message);
				kgtkAlert('Pemeriksaan Gagal', x.message || 'Terjadi kesalahan saat memeriksa data.', {
					type: 'danger',
					confirmText: 'Tutup'
				});
			});
		});
		var wrap = el('div', {}, [infoCard(S.info), form]);
		return wrap;
	}

	function vNikVerif() {
		var err = errBox();
		var rHp = el('input', { type: 'radio', name: 'verif_type', value: 'hp', id: 'vf-hp', checked: true });
		var rEmail = el('input', { type: 'radio', name: 'verif_type', value: 'email', id: 'vf-email' });
		var radios = el('div', { class: 'kgtk-radios' }, [
			el('label', { class: 'kgtk-radio', for: 'vf-hp' }, [rHp, 'Nomor HP']),
			el('label', { class: 'kgtk-radio', for: 'vf-email' }, [rEmail, 'Email belajar.id'])
		]);

		var fVal = field('No. HP/WhatsApp *', 'f-vval', { type: 'text', required: true, autocomplete: 'off', placeholder: 'mis. 081234567890 atau 62812...' });
		function updateLabel() {
			var isHp = rHp.checked;
			var span = fVal.wrap.querySelector('span');
			if (span) { span.textContent = isHp ? 'No. HP/WhatsApp *' : 'Email belajar.id *'; }
			fVal.input.placeholder = isHp ? 'mis. 081234567890 atau 62812...' : 'mis. nama@belajar.id';
		}
		rHp.addEventListener('change', updateLabel);
		rEmail.addEventListener('change', updateLabel);

		var btnVerif = el('button', { type: 'submit', class: 'kgtk-btn kgtk-btn--block', text: 'Tarik Biodata Otomatis' });
		var btnSkip = el('button', {
			type: 'button',
			class: 'kgtk-btn kgtk-btn--ghost kgtk-btn--block',
			style: 'border:1.5px solid var(--blue);color:var(--blue);font-weight:800;background:#fff;',
			text: 'Lewati & Isi Data Baru \u2192'
		});
		btnSkip.addEventListener('click', function () {
			S.verifTipe = '';
			S.verifNilai = '';
			S.form = emptyForm();
			go('biodata');
		});

		var petunjuk = el('div', {
			class: 'kgtk-note',
			style: 'margin-bottom:18px;background:#f0fdf4;border:1.5px solid #86efac;color:#166534;border-radius:12px;padding:12px 14px;'
		});
		petunjuk.innerHTML = '<div style="font-weight:800;font-size:13.5px;margin-bottom:6px;color:#15803d;display:flex;align-items:center;gap:6px;">' +
			'<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>' +
			'Petunjuk Pengisian:</div>' +
			'<div style="font-size:13px;line-height:1.55;">' +
			'• <strong>Belum pernah mendaftar pelatihan KGTK?</strong> Anda tidak perlu mengisi verifikasi nomor HP atau email di bawah ini. Silakan langsung klik tombol <strong>&ldquo;Lewati &amp; Isi Data Baru&rdquo;</strong> di bagian bawah untuk mengisi formulir pendaftaran.<br>' +
			'• <strong>Sudah pernah mendaftar sebelumnya?</strong> Masukkan No. HP atau Email belajar.id yang pernah Anda daftarkan agar data biodata Anda otomatis terisi tanpa mengetik ulang.' +
			'</div>';

		var bagianBaru = el('div', {
			style: 'margin-top:22px;padding-top:18px;border-top:1.5px dashed var(--line);text-align:center;'
		}, [
			el('div', { style: 'font-weight:800;color:var(--navy);font-size:15px;margin-bottom:4px;', text: 'Belum pernah mendaftar pelatihan sebelumnya?' }),
			el('p', { class: 'kgtk-muted', style: 'margin:0 0 12px;font-size:13px;line-height:1.45;', text: 'Jika Anda peserta baru atau belum pernah mendaftar di sistem ini, lewati langkah ini dan langsung isi formulir data baru Anda.' }),
			btnSkip
		]);

		var form = el('form', { class: 'kgtk-card', novalidate: true }, [
			el('h2', { text: 'Pengisian Otomatis Biodata' }),
			el('p', { class: 'kgtk-muted', text: 'Fitur opsional untuk mempercepat pengisian formulir jika Anda pernah mendaftar pelatihan sebelumnya.' }),
			petunjuk,
			err,
			el('div', { style: 'font-weight:700;color:var(--navy);font-size:13.5px;margin-bottom:6px;', text: 'Pernah mendaftar? Tarik data otomatis dengan:' }),
			radios,
			fVal.wrap,
			btnVerif,
			bagianBaru
		]);

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			err.hidden = true;
			var tipe = rHp.checked ? 'hp' : 'email';
			var nilai = fVal.input.value.trim();
			if (!nilai) { return showErr(err, tipe === 'hp' ? 'Masukkan nomor HP yang terdaftar.' : 'Masukkan email belajar.id yang terdaftar.'); }
			busy(btnVerif, true);
			api('public/cek-nik', 'POST', { kode: S.kode, nik: S.nik, tipe: tipe, nilai: nilai }).then(function (d) {
				S.info = d.pelatihan; S.cek = d;
				if (d.status === 'terdaftar') {
					S.verifTipe = '';
					S.verifNilai = '';
					S.hasil = d.hasil;
					return go('terdaftar');
				}
				if (d.guru) {
					S.verifTipe = tipe;
					S.verifNilai = nilai;
					S.form = Object.assign(emptyForm(), d.guru);
				} else {
					S.verifTipe = '';
					S.verifNilai = '';
					S.form = emptyForm();
				}
				go('biodata');
			}).catch(function (x) {
				busy(btnVerif, false, 'Tarik Biodata Otomatis');
				var pesan = x.message || 'Terjadi kesalahan.';
				if (pesan.indexOf('Data tidak cocok') > -1) {
					pesan += ' Jika Anda belum pernah mendaftar sebelumnya, silakan langsung klik tombol "Lewati & Isi Data Baru" di bawah.';
				}
				showErr(err, pesan);
			});
		});

		return el('div', {}, [infoCard(S.info), form]);
	}

	/** Cek NIK lalu arahkan ke tampilan yang sesuai. */
	/** Cek NIK / NIP lalu arahkan ke tampilan yang sesuai. */
	function cekNikOrNip(val, idType) {
		S.verifTipe = '';
		S.verifNilai = '';
		idType = idType || 'nik';
		var payload = { kode: S.kode, id_type: idType };
		if (idType === 'nip') {
			payload.nip = val;
		} else {
			payload.nik = val;
		}
		return api('public/cek-nik', 'POST', payload).then(function (d) {
			S.info = d.pelatihan; S.cek = d;
			if (idType === 'nip') {
				S.idType = 'nip';
				S.nip = val;
				S.nik = d.guru && d.guru.nik ? d.guru.nik : '';
			} else {
				S.idType = 'nik';
				S.nik = val;
				S.nip = d.guru && d.guru.nip ? d.guru.nip : '';
			}
			if (d.status === 'terdaftar') { S.hasil = d.hasil; return go('terdaftar'); }
			if (d.guru) {
				S.form = Object.assign(emptyForm(), d.guru);
				go('biodata');
			} else {
				S.form = emptyForm();
				if (idType === 'nip') {
					S.form.peran = 'Panitia';
					S.form.nip = val;
					go('biodata');
				} else {
					go('nik-verif');
				}
			}
		});
	}

	function cekNik(nik) {
		return cekNikOrNip(nik, 'nik');
	}

	function digitsOnly(input, max) { input.addEventListener('input', function () { input.value = input.value.replace(/\D/g, '').slice(0, max); }); }

	function vBiodata() {
		var baru = !S.cek || !S.cek.guru, F = S.form;
		var err = errBox();
		var fn = field('NIK', 'f-nik', { type: 'text', readonly: true }, S.nik);

		var lblNama = el('span', {}, [
			el('span', { text: 'Nama *' }),
			el('small', { style: 'color:#dc2626;font-size:12.5px;font-weight:normal;margin-left:4px;', text: 'Hanya nama tanpa gelar' })
		]);
		var fnama = field(lblNama, 'f-nama', { type: 'text', maxlength: 100, required: true, autocomplete: 'name', placeholder: 'Anggy Putri Oktavia' }, F.nama);

		var fgelarDepan = field('Gelar Depan', 'f-gelar-depan', { type: 'text', maxlength: 30, placeholder: 'mis. Dr. / Drs.' }, F.gelar_depan);
		var fgelarBelakang = field('Gelar Belakang', 'f-gelar-belakang', { type: 'text', maxlength: 50, placeholder: 'mis. S.Pd. / S.I.Kom' }, F.gelar_belakang);
		var rowGelar = el('div', { class: 'kgtk-row2' }, [fgelarDepan.wrap, fgelarBelakang.wrap]);

		var lblNip = el('span', {}, [
			el('span', { text: 'NIP' }),
			el('small', { style: 'color:#dc2626;font-size:12.5px;font-weight:normal;margin-left:4px;', text: ' *Hanya angka, tanpa spasi atau karakter lain' })
		]);
		var fnip = field(lblNip, 'f-nip', { type: 'text', inputmode: 'numeric', maxlength: 18, autocomplete: 'off', placeholder: '199602082025211022' }, F.nip);
		digitsOnly(fnip.input, 18);

		var ftempat = field('Tempat Lahir *', 'f-tempat-lahir', { type: 'text', maxlength: 80, required: true }, F.tempat_lahir);
		var ftgl = field('Tanggal Lahir *', 'f-tanggal-lahir', { type: 'date', required: true }, F.tanggal_lahir);
		var rowTtl = el('div', { class: 'kgtk-row2' }, [ftempat.wrap, ftgl.wrap]);

		var jkOpts = [{ value: '', text: '-- Pilih Jenis Kelamin --' }].concat(
			JENIS_KELAMIN.map(function (k) { return { value: k, text: k }; })
		);
		var fjk = selectField('Jenis Kelamin *', 'f-jenis-kelamin', jkOpts, F.jenis_kelamin);

		var agOpts = [{ value: '', text: '-- Pilih Agama --' }].concat(
			AGAMA.map(function (a) { return { value: a, text: a }; })
		);
		var fagama = selectField('Agama *', 'f-agama', agOpts, F.agama);
		var rowJkAgama = el('div', { class: 'kgtk-row2' }, [fjk.wrap, fagama.wrap]);

		var fpangkat = field('Pangkat / Golongan (opsional)', 'f-pangkat', { type: 'text', maxlength: 80, placeholder: 'mis. Penata Muda (III/a) atau Non ASN' }, F.pangkat_golongan);

		var falamat = textareaField('Alamat Rumah / Tempat Tinggal *', 'f-alamat', { rows: 2, maxlength: 500, required: true, placeholder: 'Alamat tempat tinggal / domisili' }, F.alamat);
		var falamatInstansi = textareaField('Alamat Sekolah / Instansi *', 'f-alamat-instansi', { rows: 2, maxlength: 500, required: true, placeholder: 'Alamat lengkap instansi / tempat bertugas' }, F.alamat_instansi);

		var pendOpts = [{ value: '', text: '-- Pilih Pendidikan Terakhir --' }].concat(
			PENDIDIKAN.map(function (p) { return { value: p, text: p }; })
		);
		var fpendidikan = selectField('Pendidikan Terakhir *', 'f-pendidikan', pendOpts, F.pendidikan);

		var fbank = field('Nama Bank *', 'f-bank', { type: 'text', maxlength: 60, placeholder: 'mis. Bank Riau Kepri Syariah / BRI / Mandiri', required: true }, F.bank);
		var frek = field('Nomor Rekening *', 'f-rekening', { type: 'text', maxlength: 50, inputmode: 'numeric', required: true }, F.rekening);
		digitsOnly(frek.input, 50);
		var rowBank = el('div', { class: 'kgtk-row2' }, [fbank.wrap, frek.wrap]);

		var fnpwp = field('NPWP (opsional)', 'f-npwp', { type: 'text', maxlength: 30, autocomplete: 'off' }, F.npwp);
		var fpengalaman = textareaField('Pengalaman Jabatan *', 'f-pengalaman-jabatan', { rows: 2, maxlength: 255, required: true, placeholder: 'Contoh: Guru Kelas (5 Tahun), Wakasek (2 Tahun)' }, F.pengalaman_jabatan);

		// Kabupaten/kota lalu sekolah (dari data sekolah yang diimpor admin). Tanpa data: isian teks biasa.
		var sek = window.KGTKSekolah.create({ root: API, kabkota: S.info.kabkota || [] });
		sek.setValue({ kabkota: F.kabkota, sekolah_id: F.sekolah_id, sekolah: F.sekolah });

		// Peran: Guru mendaftar pelatihan tanpa dibatasi slot per kelas. Penempatan kelas dipilihkan admin.
		var opts = PERAN.map(function (p) { return el('option', { value: p, text: p }); });
		var sel = el('select', { class: 'kgtk-input', id: 'f-peran' }, opts);
		sel.value = PERAN.indexOf(F.peran) > -1 ? F.peran : 'Peserta';
		var peran = el('label', { class: 'kgtk-field', for: 'f-peran' }, [el('span', { text: 'Peran *' }), sel]);

		// Jabatan: hanya muncul jika peran adalah Peserta (menggantikan label Jenis GTK)
		var gtkOpts = [el('option', { value: '', text: '-- Pilih Jabatan --' })].concat(
			JABATAN.map(function (g) { return el('option', { value: g, text: g }); })
		);
		var selGtk = el('select', { class: 'kgtk-input', id: 'f-jenis-gtk' }, gtkOpts);
		selGtk.value = JABATAN.indexOf(F.jabatan || F.jenis_gtk) > -1 ? (F.jabatan || F.jenis_gtk) : '';
		var wrapGtk = el('label', { class: 'kgtk-field', for: 'f-jenis-gtk', id: 'wrap-jenis-gtk' }, [el('span', { text: 'Jabatan *' }), selGtk]);

		var fnuptk = field('NUPTK (isi jika ada)', 'f-nuptk', { type: 'text', inputmode: 'numeric', maxlength: 16, autocomplete: 'off' }, F.nuptk || F.nisn);
		var fsimpkb = field('SIMPKB (isi jika ada)', 'f-simpkb', { type: 'text', maxlength: 100, autocomplete: 'off' }, F.simpkb || F.email_guru);
		digitsOnly(fnuptk.input, 16);
		var rowNupSimp = el('div', { class: 'kgtk-row2' }, [fnuptk.wrap, fsimpkb.wrap]);

		var fhp = field('No. HP/WhatsApp *', 'f-hp', { type: 'tel', maxlength: 20, required: true, autocomplete: 'tel', inputmode: 'tel' }, F.hp);
		var fmail = field('Email *', 'f-email', { type: 'email', maxlength: 100, required: true, autocomplete: 'email' }, F.email);
		var fbel = field('Email belajar.id *', 'f-belajar', { type: 'email', maxlength: 100, required: true, placeholder: 'nama@belajar.id', autocomplete: 'off' }, F.email_belajar);

		function updatePeranVisibility() {
			var isPeserta = sel.value === 'Peserta';
			var isPanitia = sel.value === 'Panitia';
			if (wrapGtk) {
				wrapGtk.style.display = isPeserta ? '' : 'none';
				if (!isPeserta) {
					selGtk.value = '';
				}
			}
			// Khusus Panitia: sembunyikan nama bank, nomor bank, email belajar.id, nuptk, simpkb, pengalaman jabatan, npwp
			if (rowBank) { rowBank.style.display = isPanitia ? 'none' : ''; }
			if (fnpwp && fnpwp.wrap) { fnpwp.wrap.style.display = isPanitia ? 'none' : ''; }
			if (fpengalaman && fpengalaman.wrap) { fpengalaman.wrap.style.display = isPanitia ? 'none' : ''; }
			if (rowNupSimp) { rowNupSimp.style.display = isPanitia ? 'none' : ''; }
			if (fbel && fbel.wrap) { fbel.wrap.style.display = isPanitia ? 'none' : ''; }

			if (fbank && fbank.input) { fbank.input.required = !isPanitia; }
			if (frek && frek.input) { frek.input.required = !isPanitia; }
			if (fpengalaman && fpengalaman.input) { fpengalaman.input.required = !isPanitia; }
			if (fbel && fbel.input) { fbel.input.required = isPeserta; }

			// Perlakuan NIK / NIP untuk Panitia
			var isPakaiNip = (isPanitia && S.idType === 'nip');
			if (isPakaiNip) {
				fn.wrap.style.display = 'none';
				fnip.wrap.querySelector('span').innerHTML = 'NIP * <small style="color:#dc2626;font-size:12.5px;font-weight:normal;margin-left:4px;">*Identitas utama panitia (18 digit angka)</small>';
				fnip.input.required = true;
			} else if (isPanitia) {
				fn.wrap.style.display = '';
				fn.wrap.querySelector('span').textContent = 'NIK (16 digit) *';
				fnip.wrap.querySelector('span').innerHTML = 'NIP <small style="color:#64748b;font-weight:normal;">(opsional bila ada)</small>';
				fnip.input.required = false;
			} else {
				fn.wrap.style.display = '';
				fn.wrap.querySelector('span').textContent = 'NIK (16 digit) *';
				fnip.wrap.querySelector('span').innerHTML = 'NIP <small style="color:#64748b;font-weight:normal;">(hanya angka, kosongkan jika belum ada)</small>';
				fnip.input.required = false;
			}
		}
		sel.addEventListener('change', updatePeranVisibility);
		updatePeranVisibility();

		var checkSetuju = el('input', { type: 'checkbox', id: 'f-setuju-kirim', value: '1' });
		var labelSetuju = el('label', { class: 'kgtk-checkbox-label', style: 'display:flex;align-items:flex-start;gap:8px;margin:16px 0;font-size:13px;line-height:1.4;cursor:pointer;' }, [
			checkSetuju,
			el('span', { text: 'Saya bersedia menerima pemberitahuan pelatihan melalui email atau WhatsApp.' })
		]);

		var btn = el('button', { type: 'submit', class: 'kgtk-btn kgtk-btn--block', text: 'Daftar' });
		var back = el('button', { type: 'button', class: 'kgtk-link', text: 'Kembali' });
		var form = el('form', { class: 'kgtk-card', novalidate: true }, [
			el('h2', { text: baru ? 'Lengkapi biodata' : 'Periksa biodata' }),
			el('p', { class: 'kgtk-muted', text: baru ? 'Isi data diri Anda untuk mendaftar. Tanda * wajib diisi.' : 'Data tersimpan dari pendaftaran sebelumnya. Perbarui bila ada yang berubah. Tanda * wajib diisi.' }),
			err,
			fn.wrap,
			peran,
			wrapGtk,
			fnama.wrap,
			rowGelar,
			fnip.wrap,
			rowTtl,
			rowJkAgama,
			fpangkat.wrap,
			falamat.wrap,
			falamatInstansi.wrap,
			fpendidikan.wrap,
			sek.node,
			rowBank,
			fnpwp.wrap,
			fpengalaman.wrap,
			rowNupSimp,
			fhp.wrap,
			fmail.wrap,
			fbel.wrap,
			labelSetuju,
			btn,
			back
		]);
		back.addEventListener('click', function () {
			S.verifTipe = '';
			S.verifNilai = '';
			go('nik');
		});

		form.addEventListener('input', function (e) {
			if (e.target && e.target.classList) {
				e.target.classList.remove('is-invalid');
			}
		});
		form.addEventListener('change', function (e) {
			if (e.target && e.target.classList) {
				e.target.classList.remove('is-invalid');
			}
		});

		var EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			err.hidden = true;
			var v = {
				nama: fnama.input.value.trim(),
				gelar_depan: fgelarDepan.input.value.trim(),
				gelar_belakang: fgelarBelakang.input.value.trim(),
				tempat_lahir: ftempat.input.value.trim(),
				tanggal_lahir: ftgl.input.value.trim(),
				jenis_kelamin: fjk.input.value,
				agama: fagama.input.value,
				pangkat_golongan: fpangkat.input.value.trim(),
				alamat: falamat.input.value.trim(),
				alamat_instansi: falamatInstansi.input.value.trim(),
				pendidikan: fpendidikan.input.value,
				bank: fbank.input.value.trim(),
				rekening: frek.input.value.trim(),
				npwp: fnpwp.input.value.trim(),
				pengalaman_jabatan: fpengalaman.input.value.trim(),
				peran: sel.value,
				jenis_gtk: sel.value === 'Peserta' ? selGtk.value : '',
				jabatan: sel.value === 'Peserta' ? selGtk.value : '',
				nuptk: fnuptk.input.value.trim(),
				nisn: fnuptk.input.value.trim(),
				nip: fnip.input.value.trim(),
				hp: fhp.input.value.trim(),
				email: fmail.input.value.trim().toLowerCase(),
				email_belajar: fbel.input.value.trim().toLowerCase(),
				simpkb: fsimpkb.input.value.trim(),
				email_guru: fsimpkb.input.value.trim(),
				setuju_kirim: checkSetuju.checked ? 1 : 0
			};
			var sv = sek.getValue();
			v.kabkota = sv.kabkota;
			v.sekolah_id = sv.sekolah_id;
			v.sekolah = sv.sekolah;
			S.form = v;

			// Reset tanda invalid sebelumnya
			[fnama.input, fgelarDepan.input, fgelarBelakang.input, ftempat.input, ftgl.input, fjk.input, fagama.input, fpangkat.input, falamat.input, falamatInstansi.input, fpendidikan.input, fbank.input, frek.input, fpengalaman.input, sel, selGtk, fhp.input, fmail.input, fbel.input, fnuptk.input, fnip.input, fsimpkb.input].forEach(function (inp) {
				if (inp) { inp.classList.remove('is-invalid'); }
			});
			if (sek.node) {
				Array.prototype.forEach.call(sek.node.querySelectorAll('.kgtk-input'), function (inp) {
					inp.classList.remove('is-invalid');
				});
			}

			var errors = [];

			// 1. Nama lengkap
			if (!v.nama) {
				errors.push({ label: 'Nama Lengkap', msg: 'Nama lengkap belum diisi.', el: fnama.input });
			}

			// 2. Tempat & Tanggal Lahir (TTL)
			if (!v.tempat_lahir) {
				errors.push({ label: 'Tempat Lahir', msg: 'Tempat lahir belum diisi.', el: ftempat.input });
			}
			if (!v.tanggal_lahir) {
				errors.push({ label: 'Tanggal Lahir', msg: 'Tanggal lahir belum diisi.', el: ftgl.input });
			}

			// 3. Jenis Kelamin & Agama
			if (!v.jenis_kelamin) {
				errors.push({ label: 'Jenis Kelamin', msg: 'Pilih jenis kelamin Anda.', el: fjk.input });
			}
			if (!v.agama) {
				errors.push({ label: 'Agama', msg: 'Pilih agama Anda.', el: fagama.input });
			}

			// 4. Alamat Rumah / Tempat Tinggal
			if (!v.alamat) {
				errors.push({ label: 'Alamat Rumah / Tempat Tinggal', msg: 'Alamat rumah / tempat tinggal belum diisi.', el: falamat.input });
			}

			// 5. Alamat Sekolah / Instansi
			if (!v.alamat_instansi) {
				errors.push({ label: 'Alamat Sekolah / Instansi', msg: 'Alamat sekolah / instansi belum diisi.', el: falamatInstansi.input });
			}

			// 6. Pendidikan Terakhir
			if (!v.pendidikan) {
				errors.push({ label: 'Pendidikan Terakhir', msg: 'Pilih pendidikan terakhir Anda.', el: fpendidikan.input });
			}

			// Asal Sekolah/Instansi & Kabupaten/Kota
			var se = sek.validate();
			if (se) {
				var sekTarget = sek.node ? (sek.node.querySelector('.kgtk-input:invalid') || sek.node.querySelector('.kgtk-input')) : null;
				errors.push({ label: 'Asal Sekolah/Instansi', msg: se, el: sekTarget });
			}

			// Peran
			if (!v.peran) {
				errors.push({ label: 'Peran', msg: 'Peran belum dipilih.', el: sel });
			}

			if (v.peran === 'Panitia') {
				// Khusus Panitia: kosongkan bank, rekening, pengalaman_jabatan, email_belajar, nuptk, simpkb, npwp
				v.bank = '-';
				v.rekening = '-';
				v.pengalaman_jabatan = '-';
				v.email_belajar = '';
				v.nuptk = '';
				v.simpkb = '';
				v.npwp = '';
				v.jenis_gtk = 'Panitia Pelaksana';
				v.jabatan = 'Panitia Pelaksana';
			} else {
				// 7. Nama Bank
				if (!v.bank) {
					errors.push({ label: 'Nama Bank', msg: 'Nama bank belum diisi.', el: fbank.input });
				}

				// 8. Nomor Rekening
				if (!v.rekening) {
					errors.push({ label: 'Nomor Rekening', msg: 'Nomor rekening belum diisi.', el: frek.input });
				} else if (!/^[0-9\- ]{5,30}$/.test(v.rekening)) {
					errors.push({ label: 'Nomor Rekening', msg: 'Nomor rekening tidak valid (hanya angka/tanda hubung, min 5 digit).', el: frek.input });
				}

				// 9. Pengalaman Jabatan
				if (!v.pengalaman_jabatan) {
					errors.push({ label: 'Pengalaman Jabatan', msg: 'Pengalaman jabatan belum diisi.', el: fpengalaman.input });
				}

				// 11. Jabatan (khusus peran Peserta)
				if (v.peran === 'Peserta') {
					if (!v.jenis_gtk) {
						errors.push({ label: 'Jabatan', msg: 'Jabatan belum dipilih (Guru, Kepala Sekolah, dll).', el: selGtk });
					}
					// 14. Email belajar.id (wajib untuk Peserta)
					if (!v.email_belajar) {
						errors.push({ label: 'Email belajar.id', msg: 'Email belajar.id belum diisi.', el: fbel.input });
					} else if (!EMAIL.test(v.email_belajar) || !/@([a-z0-9-]+\.)*belajar\.id$/.test(v.email_belajar)) {
						errors.push({ label: 'Email belajar.id', msg: 'Email belajar.id tidak valid (harus berakhiran @belajar.id).', el: fbel.input });
					}
				} else {
					if (v.email_belajar && !EMAIL.test(v.email_belajar)) {
						errors.push({ label: 'Email belajar.id', msg: 'Format email belajar.id tidak valid (kosongkan jika tidak ada).', el: fbel.input });
					}
				}

				// 15. NUPTK (Opsional)
				if (v.nuptk && !/^\d{1,16}$/.test(v.nuptk)) {
					errors.push({ label: 'NUPTK', msg: 'NUPTK harus berupa maksimal 16 digit angka (kosongkan jika tidak ada).', el: fnuptk.input });
				}
			}

			// 16. NIP
			var isPakaiNip = (v.peran === 'Panitia' && S.idType === 'nip');
			if (isPakaiNip) {
				if (!/^\d{18}$/.test(v.nip)) {
					errors.push({ label: 'NIP', msg: 'NIP panitia wajib diisi (18 digit angka).', el: fnip.input });
				}
			} else if (v.nip && !/^\d{18}$/.test(v.nip)) {
				errors.push({ label: 'NIP', msg: 'NIP harus 18 digit angka (kosongkan jika tidak ada).', el: fnip.input });
			}

			if (errors.length > 0) {
				errors.forEach(function (it) {
					if (it.el) { it.el.classList.add('is-invalid'); }
				});

				var firstInvalid = errors[0].el;
				showErr(err, 'Ada ' + errors.length + ' data yang belum terisi atau belum sesuai. Silakan lengkapi bagian yang bertanda merah.');

				kgtkAlert(
					'Data Belum Lengkap',
					'Mohon lengkapi atau perbaiki ' + errors.length + ' bagian berikut sebelum formulir dikirimkan:',
					{
						type: 'warning',
						confirmText: 'Lengkapi Data Sekarang',
						errors: errors
					}
				).then(function () {
					if (firstInvalid) {
						firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
						try { firstInvalid.focus(); } catch (errFocus) { }
					}
				});
				return;
			}

			busy(btn, true);
			var isPakaiNip = (v.peran === 'Panitia' && S.idType === 'nip');
			var postData = Object.assign({
				kode: S.kode,
				nik: S.nik,
				nip: v.nip,
				panitia_id_type: isPakaiNip ? 'nip' : 'nik',
				id_type: isPakaiNip ? 'nip' : 'nik'
			}, v);
			if (S.verifTipe && S.verifNilai) {
				postData.tipe = S.verifTipe;
				postData.nilai = S.verifNilai;
			}
			api('public/daftar', 'POST', postData)
				.then(function (d) {
					S.verifTipe = '';
					S.verifNilai = '';
					S.hasil = d.hasil;
					go('sukses');
				})
				.catch(function (x) {
					// NIK sudah terdaftar: periksa ulang status dan arahkan ke layar yang sesuai.
					if (x.code === 'sudah_terdaftar') { return cekNik(S.nik); }
					busy(btn, false, 'Daftar');
					showErr(err, x.message);
					kgtkAlert('Pendaftaran Tidak Berhasil', x.message || 'Terjadi kesalahan saat memproses pendaftaran. Silakan periksa koneksi dan coba lagi.', {
						type: 'danger',
						confirmText: 'Tutup'
					});
				});
		});
		return form;
	}

	function resetNik() {
		S.nik = '';
		S.cek = null;
		S.hasil = null;
		S.form = emptyForm();
		S.verifTipe = '';
		S.verifNilai = '';
		go('nik');
	}

	function vTerdaftar() {
		var btn = el('button', { type: 'button', class: 'kgtk-btn kgtk-btn--block', text: 'Selesai' });
		btn.addEventListener('click', resetNik);
		var bioBtn = el('button', { type: 'button', class: 'kgtk-btn kgtk-btn--ghost kgtk-btn--block', style: 'margin-top:10px;', text: '\uD83D\uDD0D Lihat Biodata & Riwayat Saya' });
		bioBtn.addEventListener('click', goBiodataSaya);

		var h = S.hasil;
		var items = [['Nama', formatNamaGelar(h.nama, h.gelar_depan, h.gelar_belakang)], ['Sekolah', h.sekolah], ['Peran', h.peran]];
		if (h.jenis_gtk) { items.push(['Jenis GTK', h.jenis_gtk]); }
		items.push(['Pelatihan', h.pelatihan], ['Kelas', h.kelas_label], ['Tanggal Pelatihan', h.tanggal], ['Lokasi', h.lokasi || '-']);

		return el('div', { class: 'kgtk-card kgtk-center' }, [
			el('div', { class: 'kgtk-icon is-info', text: 'i' }), el('h2', { text: 'Kamu sudah terdaftar' }),
			el('p', { class: 'kgtk-muted', text: 'NIK ini sudah terdaftar pada pelatihan ini.' }),
			detailList(items), nomorBox(h), btn,
			el('div', { style: 'margin-top:12px;' }, [bioBtn])
		]);
	}

	function vSukses() {
		var btn = el('button', { type: 'button', class: 'kgtk-btn kgtk-btn--block', text: 'Selesai' });
		btn.addEventListener('click', resetNik);
		var bioBtn = el('button', { type: 'button', class: 'kgtk-btn kgtk-btn--ghost kgtk-btn--block', style: 'margin-top:10px;', text: '\uD83D\uDD0D Lihat Biodata & Riwayat Saya' });
		bioBtn.addEventListener('click', goBiodataSaya);

		var h = S.hasil;
		var items = [['Nama', formatNamaGelar(h.nama, h.gelar_depan, h.gelar_belakang)], ['Instansi', h.sekolah], ['Peran', h.peran]];
		if (h.jabatan || h.jenis_gtk) { items.push(['Jabatan', h.jabatan || h.jenis_gtk]); }
		items.push(['Pelatihan', h.pelatihan], ['Kelas', h.kelas_label], ['Tanggal Pelatihan', h.tanggal], ['Lokasi', h.lokasi || '-']);

		return el('div', { class: 'kgtk-card kgtk-center' }, [
			el('div', { class: 'kgtk-icon', text: '\u2713' }), el('h2', { text: 'Pendaftaran berhasil' }),
			el('p', { class: 'kgtk-muted', text: 'Simpan informasi di bawah ini sebagai bukti pendaftaran.' }),
			detailList(items), nomorBox(h), btn,
			el('div', { style: 'margin-top:12px;' }, [bioBtn])
		]);
	}

	function vBiodataSaya() {
		if (S.biodataState === 'result' && S.biodataData) {
			var b = S.biodataData.biodata;
			var rList = S.biodataData.riwayat || [];

			var ttlTxt = '-';
			var months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
			var tglFmt = b.tanggal_lahir || '';
			if (tglFmt && /^\d{4}-\d{2}-\d{2}$/.test(tglFmt)) {
				var pDate = tglFmt.split('-');
				var mIdx = parseInt(pDate[1], 10) - 1;
				if (mIdx >= 0 && mIdx < 12) {
					tglFmt = parseInt(pDate[2], 10) + ' ' + months[mIdx] + ' ' + pDate[0];
				}
			}
			if (b.tempat_lahir && b.tempat_lahir !== '-' && tglFmt && tglFmt !== '-') {
				ttlTxt = b.tempat_lahir + ', ' + tglFmt;
			} else if (b.tempat_lahir && b.tempat_lahir !== '-') {
				ttlTxt = b.tempat_lahir;
			} else if (tglFmt && tglFmt !== '-') {
				ttlTxt = tglFmt;
			}

			var bioItems = [
				['Nama lengkap', formatNamaGelar(b.nama, b.gelar_depan, b.gelar_belakang)],
				['Tempat, Tanggal Lahir', ttlTxt],
				['NUPTK', b.nuptk || b.nisn || '-'],
				['NIP', b.nip || '-'],
				['Instansi', b.sekolah || '-'],
				['Kabupaten/Kota', b.kabkota || '-'],
				['Jabatan', b.jabatan || b.jenis_gtk || '-'],
				['No. HP/WhatsApp', b.hp || '-'],
				['Nama Bank', b.bank || '-'],
				['Nomor Rekening', b.rekening || '-'],
				['Email', b.email || b.email_belajar || '-'],
				['SIMPKB', b.simpkb || b.email_guru || '-']
			];

			// Baris NIK diberi class kgtk-hide-print agar tersembunyi saat cetak
			var nikRow = el('div', { class: 'kgtk-hide-print' }, [
				el('dt', { text: 'NIK' }),
				el('dd', { text: maskNik(b.nik) })
			]);
			var bioDl = el('dl', { class: 'kgtk-detail' });
			bioDl.appendChild(el('div', {}, [el('dt', { text: bioItems[0][0] }), el('dd', { text: bioItems[0][1] })]));
			bioDl.appendChild(el('div', {}, [el('dt', { text: bioItems[1][0] }), el('dd', { text: bioItems[1][1] })]));
			bioDl.appendChild(nikRow);
			for (var bi = 2; bi < bioItems.length; bi++) {
				bioDl.appendChild(el('div', {}, [el('dt', { text: bioItems[bi][0] }), el('dd', { text: bioItems[bi][1] })]));
			}

			var cardBio = el('div', { class: 'kgtk-card' }, [
				el('h2', { text: 'Biodata Saya' }),
				bioDl
			]);

			var cardRiwayat = el('div', { class: 'kgtk-card' }, [
				el('h2', { text: 'Riwayat Pelatihan' })
			]);

			if (!rList.length) {
				cardRiwayat.appendChild(el('p', { class: 'kgtk-muted', text: 'Belum ada riwayat pelatihan.' }));
			} else {
				rList.forEach(function (it, idx) {
					var downloadBtn = el('button', { type: 'button', class: 'kgtk-btn kgtk-btn--ghost kgtk-riwayat-dl', 'data-idx': String(idx), text: 'Lihat Biodata' });
					downloadBtn.addEventListener('click', (function (item) {
						return function () { downloadBiodataPerPelatihan(S.biodataData.biodata, item); };
					})(it));

					var actions = [downloadBtn];


					var itemBox = el('div', { class: 'kgtk-riwayat-item' }, [
						el('h3', { text: it.nama }),
						it.penyelenggara ? el('div', { class: 'kgtk-riwayat-meta', text: 'Penyelenggara: ' + it.penyelenggara }) : null,
						el('div', { class: 'kgtk-riwayat-meta', text: 'Tanggal: ' + it.tanggal }),
						el('div', { class: 'kgtk-riwayat-meta', text: 'Lokasi: ' + (it.lokasi || '-') }),
						el('div', { class: 'kgtk-riwayat-meta', text: 'Kelas & Peran: ' + it.kelas_label + ' (' + it.peran + ')' }),
						el('div', { class: 'kgtk-riwayat-actions' }, actions)
					]);
					cardRiwayat.appendChild(itemBox);
				});
			}

			var btnBack = el('button', { type: 'button', class: 'kgtk-btn kgtk-btn--ghost', text: '\u2190 Kembali' });
			btnBack.addEventListener('click', function () {
				S.biodataData = null;
				S.biodataState = 'input';
				render();
				window.scrollTo({ top: root.offsetTop - 10, behavior: 'smooth' });
			});

			var btnPrint = el('button', { type: 'button', class: 'kgtk-btn kgtk-btn--ghost', text: 'Cetak' });
			btnPrint.addEventListener('click', function () {
				var oldTitle = document.title;
				document.title = '';
				window.print();
				setTimeout(function () { document.title = oldTitle; }, 500);
			});

			var btnSelesai = el('button', { type: 'button', class: 'kgtk-btn kgtk-btn--navy', text: 'Selesai' });
			btnSelesai.addEventListener('click', function () {
				S.biodataData = null;
				S.biodataState = 'input';
				S.nik = '';
				S.verifTipe = '';
				S.verifNilai = '';
				go('beranda');
			});

			var actions = el('div', { class: 'kgtk-actions-bar' }, [btnBack, btnPrint, btnSelesai]);

			var printKop = el('div', { class: 'kgtk-print-kop' });
			printKop.innerHTML = getKopHtml();

			var topNavResult = el('div', { style: 'margin-bottom:14px;' }, [
				el('button', { type: 'button', class: 'kgtk-btn-back', text: '← Cek NIK Lainnya / Kembali' })
			]);
			topNavResult.querySelector('button').addEventListener('click', function () {
				S.biodataData = null;
				S.biodataState = 'input';
				render();
				window.scrollTo({ top: root.offsetTop - 10, behavior: 'smooth' });
			});

			return el('div', {}, [topNavResult, printKop, cardBio, cardRiwayat, actions]);
		}

		// State: 'input'
		var btnBackHome = el('button', { type: 'button', class: 'kgtk-btn-back', text: '← Kembali ke Beranda' });
		btnBackHome.addEventListener('click', function () {
			S.biodataData = null;
			S.biodataState = 'input';
			go('beranda');
		});

		var fNik = field('Nomor Induk Kependudukan (NIK)', 'f-bs-nik', {
			type: 'text',
			inputmode: 'numeric',
			maxlength: 16,
			autocomplete: 'off',
			pattern: '[0-9]*',
			required: true,
			placeholder: 'Masukkan 16 digit angka NIK'
		});
		digitsOnly(fNik.input, 16);

		var rHp = el('input', { type: 'radio', name: 'bs_tipe', value: 'hp', id: 'bs-hp', checked: true });
		var rEmail = el('input', { type: 'radio', name: 'bs_tipe', value: 'email', id: 'bs-email' });

		var lblHp = el('label', { class: 'kgtk-radio is-checked', for: 'bs-hp' }, [
			rHp,
			el('span', { text: '📱 Nomor HP / WhatsApp' })
		]);
		var lblEmail = el('label', { class: 'kgtk-radio', for: 'bs-email' }, [
			rEmail,
			el('span', { text: '✉️ Email belajar.id' })
		]);
		var radios = el('div', { class: 'kgtk-radios' }, [lblHp, lblEmail]);

		var fVal = field('Nomor HP / WhatsApp yang Terdaftar *', 'f-bs-val', {
			type: 'text',
			required: true,
			autocomplete: 'off',
			placeholder: 'Contoh: 081234567890'
		});

		function updateLabel() {
			var isHp = rHp.checked;
			lblHp.classList.toggle('is-checked', isHp);
			lblEmail.classList.toggle('is-checked', !isHp);
			var span = fVal.wrap.querySelector('span');
			if (span) {
				span.textContent = isHp ? 'Nomor HP / WhatsApp yang Terdaftar *' : 'Email belajar.id yang Terdaftar *';
			}
			fVal.input.placeholder = isHp ? 'Contoh: 081234567890 atau 62812...' : 'Contoh: nama@guru.smp.belajar.id';
		}
		rHp.addEventListener('change', updateLabel);
		rEmail.addEventListener('change', updateLabel);

		var err = errBox();
		var btn = el('button', { type: 'submit', class: 'kgtk-btn kgtk-btn--block kgtk-btn--large', text: 'Tampilkan Data Pelatihan Saya →' });

		var linkDaftar = el('button', { type: 'button', class: 'kgtk-link', text: 'Ingin mendaftar pelatihan baru? Klik di sini untuk Daftar Pelatihan' });
		linkDaftar.addEventListener('click', function () { go('kode'); });

		var headerClean = el('div', { class: 'kgtk-card-header-clean' }, [
			el('div', { class: 'kgtk-card-icon-circle', 'aria-hidden': 'true' }, [el('span', { text: '🔍' })]),
			el('div', {}, [
				el('h2', { text: 'Lihat Biodata & Riwayat Pelatihan' }),
				el('p', { class: 'kgtk-card-desc', text: 'Silakan masukkan 16 digit NIK dan nomor kontak Anda untuk melihat data biodata dan pelatihan yang pernah diikuti.' })
			])
		]);

		var form = el('form', { class: 'kgtk-card kgtk-card--senior', novalidate: true }, [
			headerClean,
			err,
			fNik.wrap,
			el('div', { class: 'kgtk-field' }, [
				el('span', { class: 'kgtk-field-title', text: 'Pilih Jalur Verifikasi *' }),
				radios
			]),
			fVal.wrap,
			btn,
			el('div', { style: 'text-align:center;margin-top:16px;' }, [linkDaftar])
		]);

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			err.hidden = true;
			var nik = fNik.input.value.trim();
			var tipe = rHp.checked ? 'hp' : 'email';
			var nilai = fVal.input.value.trim();

			if (!/^\d{16}$/.test(nik)) { return showErr(err, 'NIK harus 16 digit angka.'); }
			if (!nilai) { return showErr(err, tipe === 'hp' ? 'Masukkan nomor HP yang terdaftar.' : 'Masukkan email belajar.id yang terdaftar.'); }

			busy(btn, true);
			api('public/biodata-saya', 'POST', { nik: nik, tipe: tipe, nilai: nilai }).then(function (d) {
				S.biodataData = d;
				S.biodataState = 'result';
				render();
				window.scrollTo({ top: root.offsetTop - 10, behavior: 'smooth' });
			}).catch(function (x) {
				busy(btn, false, 'Tampilkan Data Pelatihan Saya →');
				showErr(err, x.message);
			});
		});

		return el('div', {}, [btnBackHome, form]);
	}


	/* ---------- mulai ---------- */
	var qKode = '', qMenu = '';
	try {
		var sp = new URLSearchParams(window.location.search);
		qKode = (sp.get('kode') || '').trim().toUpperCase();
		qMenu = (sp.get('menu') || '').trim().toLowerCase();
	} catch (e) { }

	if (typeof window.addEventListener === 'function') {
		window.addEventListener('beforeprint', function () {
			window._kgtkOldTitle = document.title;
			document.title = '';
		});
		window.addEventListener('afterprint', function () {
			if (window._kgtkOldTitle !== undefined) {
				document.title = window._kgtkOldTitle;
				delete window._kgtkOldTitle;
			}
		});
	}

	render();
	if (qMenu === 'biodata') {
		goBiodataSaya();
	} else if (qKode) {
		S.kode = qKode;
		getInfo(qKode).then(function (d) { S.info = d; go('nik'); })
			.catch(function (x) { S.kodeError = x.message; go('kode'); });
	} else {
		S.view = 'beranda';
		render();
	}
})();
