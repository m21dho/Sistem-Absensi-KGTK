(function () {
	'use strict';
	console.log('[KGTK] admin.js loaded successfully.');
	var C = window.KGTK_ADMIN || {};

	function api(path, method, body) {
		var url = C.root + path;
		if (C.root.indexOf('?') > -1 && path.indexOf('?') > -1) { // permalink polos: gabungkan query dengan &
			var q = path.indexOf('?');
			url = C.root + path.slice(0, q) + '&' + path.slice(q + 1);
		}
		return fetch(url, {
			method: method,
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': C.nonce },
			body: body ? JSON.stringify(body) : undefined
		}).then(function (r) {
			return r.json().catch(function () { return {}; }).then(function (d) {
				if (!r.ok) { throw new Error(d.message || 'Terjadi kesalahan. Coba lagi.'); }
				return d;
			});
		});
	}

	/* Salin link */
	function copyText(text, btn) {
		var done = function () {
			var old = btn.textContent;
			btn.textContent = 'Tersalin!';
			setTimeout(function () { btn.textContent = old; }, 1500);
		};
		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(text).then(done, function () { fallback(); });
		} else { fallback(); }
		function fallback() {
			var t = document.createElement('textarea');
			t.value = text; t.style.position = 'fixed'; t.style.opacity = '0';
			document.body.appendChild(t); t.select();
			try { document.execCommand('copy'); done(); } catch (e) { window.prompt('Salin link ini:', text); }
			document.body.removeChild(t);
		}
	}
	document.addEventListener('click', function (e) {
		var b = e.target.closest('[data-copy]');
		if (b) { copyText(b.getAttribute('data-copy'), b); }
	});

	/* Jaminan keamanan: sembunyikan seluruh modal overlay yang tidak aktif agar tidak memblokir klik mouse */
	Array.prototype.forEach.call(document.querySelectorAll('.kgtk-modal-overlay'), function (ov) {
		if (!ov.classList.contains('is-visible')) {
			ov.style.display = 'none';
		}
	});

	function esc(str) {
		if (str === null || str === undefined) { return ''; }
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}
	var escHtml = esc;

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

	/**
	 * Dialog Modal KGTK yang selaras dengan tema sistem web.
	 */
	function showModal(opts) {
		return new Promise(function (resolve) {
			opts = opts || {};
			var type = opts.type || 'primary'; // 'primary' | 'danger' | 'warning'
			var isAlert = !!opts.isAlert;
			var title = opts.title || (isAlert ? 'Pemberitahuan' : 'Konfirmasi');
			var message = opts.message || '';
			var confirmText = opts.confirmText || (isAlert ? 'Mengerti' : (type === 'danger' ? 'Ya, Hapus' : 'Ya, Lanjutkan'));
			var cancelText = opts.cancelText || 'Batal';

			var iconSvg = '';
			if (type === 'danger') {
				iconSvg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M10 11v6M14 11v6"/></svg>';
			} else if (type === 'warning') {
				iconSvg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0zM12 9v4M12 17h.01"/></svg>';
			} else {
				iconSvg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>';
			}

			var msgHtml = esc(message).replace(/\n\n/g, '<div class="kgtk-modal-space"></div>').replace(/\n/g, '<br>');

			var overlay = document.createElement('div');
			overlay.className = 'kgtk-modal-overlay';

			var dialog = document.createElement('div');
			dialog.className = 'kgtk-modal-dialog kgtk-ui';

			var confirmBtnClass = 'kgtk-btn';
			if (type === 'danger') {
				confirmBtnClass += ' kgtk-btn--danger-solid';
			} else if (type === 'primary') {
				confirmBtnClass += ' kgtk-btn--navy';
			}

			var listHtml = '';
			if (opts.errors && opts.errors.length > 0) {
				listHtml = '<ul class="kgtk-modal-err-list">';
				opts.errors.forEach(function (errItem) {
					listHtml += '<li class="kgtk-modal-err-item">' +
						'<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>' +
						'<div><strong>' + esc(errItem.label) + ':</strong> ' + esc(errItem.msg) + '</div>' +
					'</li>';
				});
				listHtml += '</ul>';
			}

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
					(!isAlert ? '<button type="button" class="kgtk-btn kgtk-btn--ghost kgtk-modal-cancel">' + esc(cancelText) + '</button>' : '') +
					'<button type="button" class="' + confirmBtnClass + ' kgtk-modal-confirm">' + esc(confirmText) + '</button>' +
				'</div>';

			overlay.appendChild(dialog);
			document.body.appendChild(overlay);

			requestAnimationFrame(function () {
				overlay.classList.add('is-visible');
			});

			var isClosed = false;
			var closeDialog = function (result) {
				if (isClosed) { return; }
				isClosed = true;
				overlay.classList.remove('is-visible');
				document.removeEventListener('keydown', onKey);
				setTimeout(function () {
					if (overlay.parentNode) {
						overlay.parentNode.removeChild(overlay);
					}
					resolve(result);
				}, 200);
			};

			var onKey = function (e) {
				if (e.key === 'Escape') {
					e.preventDefault();
					closeDialog(false);
				} else if (e.key === 'Enter') {
					e.preventDefault();
					closeDialog(true);
				}
			};
			document.addEventListener('keydown', onKey);

			var btnConfirm = dialog.querySelector('.kgtk-modal-confirm');
			if (btnConfirm) {
				btnConfirm.addEventListener('click', function () { closeDialog(true); });
				btnConfirm.focus();
			}

			var btnCancel = dialog.querySelector('.kgtk-modal-cancel');
			if (btnCancel) {
				btnCancel.addEventListener('click', function () { closeDialog(false); });
			}

			overlay.addEventListener('click', function (e) {
				if (e.target === overlay) {
					closeDialog(false);
				}
			});
		});
	}

	function modalConfirm(opts) {
		if (typeof opts === 'string') {
			opts = { message: opts };
		}
		opts.isAlert = false;
		return showModal(opts);
	}

	function modalAlert(opts) {
		if (typeof opts === 'string') {
			opts = { message: opts };
		}
		opts.isAlert = true;
		return showModal(opts);
	}

	function tOk(msg) {
		if (!msg) { return; }
		var existing = document.getElementById('kgtk-toast-ok');
		if (existing && existing.parentNode) {
			existing.parentNode.removeChild(existing);
		}
		var toast = document.createElement('div');
		toast.id = 'kgtk-toast-ok';
		toast.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:999999;background:#0f172a;color:#fff;padding:12px 18px;border-radius:10px;font-size:13.5px;font-weight:600;box-shadow:0 10px 25px rgba(0,0,0,0.18);display:flex;align-items:center;gap:10px;border-left:4px solid #10b981;transition:opacity 0.25s ease,transform 0.25s ease;';
		toast.innerHTML = '<span style="color:#10b981;font-size:16px;line-height:1;">&#10004;</span><span>' + esc(msg) + '</span>';
		document.body.appendChild(toast);
		setTimeout(function () {
			toast.style.opacity = '0';
			toast.style.transform = 'translateY(8px)';
			setTimeout(function () {
				if (toast.parentNode) {
					toast.parentNode.removeChild(toast);
				}
			}, 260);
		}, 2800);
	}

	/* Hapus pelatihan */
	document.addEventListener('click', function (e) {
		var b = e.target.closest('[data-hapus]');
		if (!b) { return; }
		var nama = b.getAttribute('data-nama');
		modalConfirm({
			title: 'Hapus Pelatihan',
			message: 'Hapus pelatihan "' + nama + '"?\n\nSemua kelas dan data peserta pelatihan ini ikut terhapus dan tidak bisa dikembalikan.',
			confirmText: 'Ya, Hapus Pelatihan',
			cancelText: 'Batal',
			type: 'danger'
		}).then(function (ok) {
			if (!ok) { return; }
			b.disabled = true;
			api('admin/pelatihan/' + b.getAttribute('data-hapus'), 'DELETE').then(function () {
				var card = b.closest('.kgtk-pcard');
				if (card) { card.remove(); }
				if (!document.querySelector('.kgtk-pcard')) { window.location.reload(); }
			}).catch(function (err) {
				b.disabled = false;
				modalAlert({ title: 'Gagal Menghapus', message: err.message, type: 'danger' });
			});
		});
	});

	/* Form buat pelatihan */
	var form = document.getElementById('kgtk-form-buat');
	if (form) {
		var box = document.getElementById('kgtk-form-error');
		var showErr = function (m) { box.textContent = m; box.hidden = false; box.scrollIntoView({ behavior: 'smooth', block: 'center' }); };

		/* Switcher Tipe Kegiatan: Pelatihan vs Absen Acara */
		var tipeRadios = form.querySelectorAll('input[name="jenis"]');
		var cardPelatihan = document.getElementById('kgtk-tipe-pelatihan-card');
		var cardAcara = document.getElementById('kgtk-tipe-acara-card');
		var wrapKelas = document.getElementById('kgtk-wrap-kelas');
		var lblNama = document.getElementById('kgtk-label-nama');
		var legKelas = document.getElementById('kgtk-legend-kelas');
		var descKelas = document.getElementById('kgtk-desc-kelas');
		var legPanitia = document.getElementById('kgtk-legend-panitia');
		var lblKode = document.getElementById('kgtk-label-kode');
		var btnSubmitBuat = document.getElementById('kgtk-btn-submit-buat');

		var updateTipeUI = function () {
			var checked = form.querySelector('input[name="jenis"]:checked');
			var val = checked ? checked.value : 'pelatihan';
			if (val === 'acara') {
				if (cardPelatihan) {
					cardPelatihan.style.borderColor = '#cbd5e1';
					cardPelatihan.style.background = '#fff';
				}
				if (cardAcara) {
					cardAcara.style.borderColor = '#0d9488';
					cardAcara.style.background = '#f0fdfa';
				}
				if (wrapKelas) { wrapKelas.style.display = 'block'; }
				if (lblNama) { lblNama.innerHTML = 'Nama acara / kegiatan <b>*</b>'; }
				if (legKelas) { legKelas.textContent = 'Kelas / Kategori Peserta Acara dan Kuota'; }
				if (descKelas) { descKelas.innerHTML = 'Ketik nama kategori/ruang kelas acara (mis. <b>Tamu Undangan</b>, <b>Tamu Pemenang</b>, atau Kelas/Ruang A) beserta jumlah kuota slotnya.'; }
				if (legPanitia) { legPanitia.textContent = 'Panitia / Petugas Acara'; }
				if (lblKode) { lblKode.textContent = 'Kode Acara'; }
				if (btnSubmitBuat) { btnSubmitBuat.textContent = 'Simpan Acara'; }
			} else {
				if (cardPelatihan) {
					cardPelatihan.style.borderColor = '#2563eb';
					cardPelatihan.style.background = '#eff6ff';
				}
				if (cardAcara) {
					cardAcara.style.borderColor = '#cbd5e1';
					cardAcara.style.background = '#fff';
				}
				if (wrapKelas) { wrapKelas.style.display = 'block'; }
				if (lblNama) { lblNama.innerHTML = 'Nama pelatihan <b>*</b>'; }
				if (legKelas) { legKelas.textContent = 'Kelas dan slot'; }
				if (descKelas) { descKelas.innerHTML = 'Ketik nama kelas (mis. Kelas PAUD, Kelas SD, Kelas SMP, Kelas SMA) dan jumlah slotnya. Jika jadwal tanggal atau batas pendaftaran tiap kelas berbeda-beda, isi kolom <b>Mulai</b>, <b>Selesai</b>, dan <b>Batas Daftar</b> pada masing-masing kelas (kosongkan jika mengikuti jadwal &amp; batas pendaftaran umum di atas).'; }
				if (legPanitia) { legPanitia.textContent = 'Panitia'; }
				if (lblKode) { lblKode.textContent = 'Kode Pelatihan'; }
				if (btnSubmitBuat) { btnSubmitBuat.textContent = 'Simpan Pelatihan'; }
			}
		};

		Array.prototype.forEach.call(tipeRadios, function (r) {
			r.addEventListener('change', updateTipeUI);
		});
		if (cardPelatihan) {
			cardPelatihan.addEventListener('click', function () {
				var rad = cardPelatihan.querySelector('input[type="radio"]');
				if (rad) { rad.checked = true; updateTipeUI(); }
			});
		}
		if (cardAcara) {
			cardAcara.addEventListener('click', function () {
				var rad = cardAcara.querySelector('input[type="radio"]');
				if (rad) { rad.checked = true; updateTipeUI(); }
			});
		}
		updateTipeUI();

		var list = document.getElementById('kgtk-kelas-list');
		var tpl = document.getElementById('kgtk-kelas-tpl');
		var refresh = function () {
			var rows = list.querySelectorAll('.kgtk-kelas-row');
			Array.prototype.forEach.call(rows, function (r, i) {
				var noEl = r.querySelector('.kgtk-kelas-no');
				if (noEl) { noEl.textContent = String(i + 1); }
				var upBtn = r.querySelector('[data-up], [data-move="up"]');
				var downBtn = r.querySelector('[data-down], [data-move="down"]');
				var delBtn = r.querySelector('[data-del]');
				if (upBtn) { upBtn.disabled = (i === 0); }
				if (downBtn) { downBtn.disabled = (i === rows.length - 1); }
				if (delBtn) { delBtn.disabled = (rows.length === 1); }
			});
		};
		document.getElementById('kgtk-kelas-add').addEventListener('click', function () {
			if (list.querySelectorAll('.kgtk-kelas-row').length >= 20) { return showErr('Maksimal 20 kelas per pelatihan.'); }
			list.appendChild(tpl.content.cloneNode(true));
			refresh();
			var rows = list.querySelectorAll('.kgtk-kelas-row');
			var lastNama = rows[rows.length - 1].querySelector('[data-knama]');
			if (lastNama) { lastNama.focus(); }
		});
		list.addEventListener('click', function (e) {
			var b = e.target.closest('button');
			if (!b || b.disabled) { return; }
			var row = b.closest('.kgtk-kelas-row');
			if (b.hasAttribute('data-del')) { row.remove(); }
			else if ((b.hasAttribute('data-up') || b.getAttribute('data-move') === 'up') && row.previousElementSibling) { list.insertBefore(row, row.previousElementSibling); }
			else if ((b.hasAttribute('data-down') || b.getAttribute('data-move') === 'down') && row.nextElementSibling) { list.insertBefore(row.nextElementSibling, row); }
			refresh();
		});
		refresh();
		form.elements.kode.addEventListener('input', function (e) {
			e.target.value = e.target.value.toUpperCase().replace(/[^A-Z0-9-]/g, '');
		});

		var undList = document.getElementById('kgtk-undangan-list');
		var undTpl = document.getElementById('kgtk-undangan-tpl');
		var undAddBtn = document.getElementById('kgtk-undangan-add');

		var refreshUndanganRows = function () {
			if (!undList) { return; }
			var uRows = undList.querySelectorAll('.kgtk-und-row');
			Array.prototype.forEach.call(uRows, function (r, i) {
				var noEl = r.querySelector('.kgtk-und-no');
				if (noEl) { noEl.textContent = String(i + 1); }
			});
		};

		if (undAddBtn && undList && undTpl) {
			undAddBtn.addEventListener('click', function () {
				if (undList.querySelectorAll('.kgtk-und-row').length >= 1000) {
					return showErr('Maksimal 1000 baris peserta ditetapkan saat membuat pelatihan.');
				}
				undList.appendChild(undTpl.content.cloneNode(true));
				refreshUndanganRows();
				var uRows = undList.querySelectorAll('.kgtk-und-row');
				var lastRow = uRows[uRows.length - 1];
				var inpNik = lastRow.querySelector('[data-unik]');
				if (inpNik) {
					inpNik.addEventListener('input', function (e) {
						e.target.value = e.target.value.replace(/[^0-9]/g, '').slice(0, 16);
					});
				}
				var inpNama = lastRow.querySelector('[data-unama]');
				if (inpNama) { inpNama.focus(); }
			});

			undList.addEventListener('click', function (e) {
				var b = e.target.closest('[data-und-del]');
				if (!b) { return; }
				var row = b.closest('.kgtk-und-row');
				if (row) { row.remove(); }
				refreshUndanganRows();
			});
		}

		/* Panitia Builder di Form Buat Pelatihan */
		var panWrap = document.getElementById('kgtk-panitia-wrap');
		var panTable = document.getElementById('kgtk-panitia-table');
		var panTbody = document.getElementById('kgtk-panitia-tbody');
		var panEmpty = document.getElementById('kgtk-panitia-empty');
		var panAddBtn = document.getElementById('kgtk-btn-tambah-panitia-manual');
		var panPickBtn = document.getElementById('kgtk-btn-pilih-panitia');
		var panModal = document.getElementById('kgtk-modal-pilih-panitia');
		var panModalClose = document.getElementById('kgtk-btn-modal-panitia-close');
		var panModalBatal = document.getElementById('kgtk-btn-modal-panitia-batal');
		var panModalSubmit = document.getElementById('kgtk-btn-modal-panitia-submit');
		var panModalSearch = document.getElementById('kgtk-modal-panitia-search');
		var panModalCount = document.getElementById('kgtk-modal-panitia-count');

		var refreshPanitiaRows = function () {
			if (!panTbody) { panTbody = document.getElementById('kgtk-panitia-tbody'); }
			if (!panTbody) { return; }
			var pRows = panTbody.querySelectorAll('.kgtk-panitia-row');
			if (!panWrap) { panWrap = document.getElementById('kgtk-panitia-wrap'); }
			if (!panTable) { panTable = document.getElementById('kgtk-panitia-table'); }
			if (!panEmpty) { panEmpty = document.getElementById('kgtk-panitia-empty'); }

			if (panWrap) {
				panWrap.style.display = pRows.length > 0 ? 'block' : 'none';
			}
			if (panTable) {
				panTable.style.display = pRows.length > 0 ? 'table' : 'none';
			}
			if (panEmpty) {
				panEmpty.style.display = pRows.length > 0 ? 'none' : 'flex';
			}
			Array.prototype.forEach.call(pRows, function (r, idx) {
				var noEl = r.querySelector('.kgtk-pan-num');
				if (noEl) { noEl.textContent = String(idx + 1); }
			});
		};

		var appendPanitiaRow = function (data) {
			if (!panTbody) {
				panTbody = document.getElementById('kgtk-panitia-tbody');
				if (!panTbody) { return null; }
			}
			var tr = document.createElement('tr');
			tr.className = 'kgtk-panitia-row';

			var nama = (data && data.nama) ? esc(data.nama) : '';
			var jabatan = (data && data.jabatan) ? esc(data.jabatan) : 'Panitia Pelaksana';
			var nik = (data && data.nik && String(data.nik).indexOf('99') !== 0) ? esc(data.nik) : '';
			var hp = (data && data.hp) ? esc(data.hp) : '';
			var instansi = (data && data.instansi) ? esc(data.instansi) : '';

			tr.innerHTML =
				'<td class="c-no" style="text-align:center;"><span class="kgtk-pan-num">1</span></td>' +
				'<td><input type="text" class="kgtk-input kgtk-pan-nama" name="panitia_nama[]" placeholder="Nama lengkap panitia *" required maxlength="100" value="' + nama + '"></td>' +
				'<td><input type="text" class="kgtk-input kgtk-pan-jabatan" name="panitia_jabatan[]" list="kgtk-jabatan-panitia-options" placeholder="Ketua / Sekretaris / Anggota" maxlength="100" value="' + jabatan + '"></td>' +
				'<td><input type="text" class="kgtk-input kgtk-pan-instansi" name="panitia_instansi[]" placeholder="Asal instansi / unit kerja" maxlength="200" value="' + instansi + '"></td>' +
				'<td><input type="text" class="kgtk-input kgtk-pan-hp" name="panitia_hp[]" placeholder="08xxxxxxxxxx" maxlength="20" value="' + hp + '"></td>' +
				'<td><input type="text" class="kgtk-input kgtk-pan-nik" name="panitia_nik[]" placeholder="16 digit angka" inputmode="numeric" maxlength="16" value="' + nik + '"></td>' +
				'<td style="text-align:center;"><button type="button" class="kgtk-iconbtn kgtk-iconbtn--del" data-del-panitia-row title="Hapus baris">&times;</button></td>';

			if (data) {
				tr.setAttribute('data-full', JSON.stringify(data));
			}

			var inpNik = tr.querySelector('.kgtk-pan-nik');
			if (inpNik) {
				inpNik.addEventListener('input', function (e) {
					e.target.value = e.target.value.replace(/[^0-9]/g, '').slice(0, 16);
				});
			}

			panTbody.appendChild(tr);
			refreshPanitiaRows();
			return tr;
		};

		// Fungsi global yang dapat dipanggil langsung dari mana saja
		window.kgtkTambahPanitiaRow = function () {
			if (!panTbody) { panTbody = document.getElementById('kgtk-panitia-tbody'); }
			if (panTbody && panTbody.querySelectorAll('.kgtk-panitia-row').length >= 100) {
				return showErr('Maksimal 100 panitia per pelatihan.');
			}
			var tr = appendPanitiaRow();
			if (tr) {
				var lastNama = tr.querySelector('.kgtk-pan-nama');
				if (lastNama) { lastNama.focus(); }
			}
			return tr;
		};

		if (panAddBtn) {
			panAddBtn.addEventListener('click', function (e) {
				e.preventDefault();
				window.kgtkTambahPanitiaRow();
			});
		}

		if (panTbody) {
			panTbody.addEventListener('click', function (e) {
				var b = e.target.closest('[data-del-panitia-row]');
				if (!b) { return; }
				var row = b.closest('.kgtk-panitia-row');
				if (row) { row.remove(); }
				refreshPanitiaRows();
			});
		}

		window.kgtkBukaModalPanitia = function () {
			if (!panModal) { panModal = document.getElementById('kgtk-modal-pilih-panitia'); }
			if (!panModal) { return; }
			panModal.classList.add('is-visible');
			if (panModalSearch) {
				panModalSearch.value = '';
				panModalSearch.dispatchEvent(new Event('input'));
				panModalSearch.focus();
			}
			if (panModalCount) {
				var checked = panModal.querySelectorAll('.kgtk-panitia-chk:checked').length;
				panModalCount.textContent = checked + ' panitia dipilih';
			}
		};

		if (panPickBtn) {
			panPickBtn.addEventListener('click', function (e) {
				e.preventDefault();
				window.kgtkBukaModalPanitia();
			});
		}

		if (panModal) {
			var closeModal = function () {
				panModal.classList.remove('is-visible');
			};
			if (panModalClose) { panModalClose.addEventListener('click', closeModal); }
			if (panModalBatal) { panModalBatal.addEventListener('click', closeModal); }
			panModal.addEventListener('click', function (e) {
				if (e.target === panModal) { closeModal(); }
			});

			if (panModalSearch) {
				panModalSearch.addEventListener('input', function (e) {
					var q = (e.target.value || '').trim().toLowerCase();
					var items = panModal.querySelectorAll('.kgtk-panitia-pick-item');
					var visibleCount = 0;
					Array.prototype.forEach.call(items, function (it) {
						var s = it.getAttribute('data-search') || '';
						var match = (!q || s.indexOf(q) > -1);
						it.style.display = match ? 'flex' : 'none';
						if (match) { visibleCount++; }
					});
					var emptyMsg = document.getElementById('kgtk-modal-panitia-empty-msg');
					if (emptyMsg) {
						emptyMsg.style.display = (visibleCount === 0 && items.length > 0) ? 'block' : 'none';
						if (visibleCount === 0 && items.length > 0) {
							emptyMsg.textContent = 'Tidak ditemukan panitia yang cocok dengan kata pencarian tersebut.';
						}
					}
				});
			}

			panModal.addEventListener('change', function (e) {
				if (e.target.classList.contains('kgtk-panitia-chk')) {
					if (panModalCount) {
						var checked = panModal.querySelectorAll('.kgtk-panitia-chk:checked').length;
						panModalCount.textContent = checked + ' panitia dipilih';
					}
				}
			});

			if (panModalSubmit) {
				panModalSubmit.addEventListener('click', function () {
					var checkedBoxes = panModal.querySelectorAll('.kgtk-panitia-chk:checked');
					if (!checkedBoxes.length) {
						modalAlert({ title: 'Pilih Panitia', message: 'Silakan centang minimal satu panitia yang ingin dimasukkan.' });
						return;
					}
					Array.prototype.forEach.call(checkedBoxes, function (chk) {
						var item = chk.closest('.kgtk-panitia-pick-item');
						if (!item) { return; }
						var rawJson = item.getAttribute('data-panitia');
						var pData = {};
						try { pData = JSON.parse(rawJson); } catch (ex) {}
						if (pData.nama) {
							appendPanitiaRow(pData);
						}
						chk.checked = false;
					});
					closeModal();
					if (panModalCount) {
						panModalCount.textContent = '0 panitia dipilih';
					}
				});
			}
		}

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			box.hidden = true;
			var f = form.elements;
			var checkedTipe = form.querySelector('input[name="jenis"]:checked');
			var jenisVal = checkedTipe ? checkedTipe.value : 'pelatihan';

			if (!f.nama.value.trim()) {
				return showErr(jenisVal === 'acara' ? 'Nama acara wajib diisi.' : 'Nama pelatihan wajib diisi.');
			}
			if (!f.tanggal_mulai.value) { return showErr('Tanggal mulai wajib diisi.'); }
			if (f.tanggal_selesai.value && f.tanggal_selesai.value < f.tanggal_mulai.value) {
				return showErr('Tanggal selesai tidak boleh sebelum tanggal mulai.');
			}
			var kelas = [], seen = {};
			var rows = list ? list.querySelectorAll('.kgtk-kelas-row') : [];
			for (var i = 0; i < rows.length; i++) {
				var n = rows[i].querySelector('[data-knama]').value.trim();
				var v = parseInt(rows[i].querySelector('[data-kslot]').value, 10);
				var kmEl = rows[i].querySelector('[data-ktgl-mulai]');
				var ksEl = rows[i].querySelector('[data-ktgl-selesai]');
				var kdEl = rows[i].querySelector('[data-kdeadline]');
				var km = kmEl ? (kmEl.value || '').trim() : '';
				var ks = ksEl ? (ksEl.value || '').trim() : '';
				var kd = kdEl ? (kdEl.value || '').trim() : '';
				if (!n) { return showErr('Nama ' + (jenisVal === 'acara' ? 'kategori/kelas' : 'kelas') + ' nomor ' + (i + 1) + ' wajib diisi.'); }
				if (seen[n.toLowerCase()]) { return showErr('Nama ' + (jenisVal === 'acara' ? 'kategori/kelas' : 'kelas') + ' tidak boleh sama: ' + n + '.'); }
				if (!(v >= 1 && v <= 2000)) { return showErr('Slot ' + (jenisVal === 'acara' ? 'kategori/kelas ' : 'kelas ') + n + ' harus antara 1 dan 2000.'); }
				if (km && ks && ks < km) {
					return showErr('Tanggal selesai ' + (jenisVal === 'acara' ? 'kategori/kelas ' : 'kelas ') + n + ' tidak boleh sebelum tanggal mulai.');
				}
				seen[n.toLowerCase()] = true;
				kelas.push({
					nama: n,
					slot: v,
					tanggal_mulai: km || (ks ? ks : ''),
					tanggal_selesai: ks || (km ? km : ''),
					deadline_pendaftaran: kd
				});
			}
			if (!kelas.length) {
				return showErr('Tambahkan minimal satu ' + (jenisVal === 'acara' ? 'kategori/kelas acara (mis. Tamu Undangan, Tamu Pemenang).' : 'kelas.'));
			}

			var undangan = [];
			if (undList) {
				var uRows = undList.querySelectorAll('.kgtk-und-row');
				for (var ui = 0; ui < uRows.length; ui++) {
					var unama = (uRows[ui].querySelector('[data-unama]').value || '').trim();
					var unik = (uRows[ui].querySelector('[data-unik]').value || '').trim();
					var uhp = (uRows[ui].querySelector('[data-uhp]').value || '').trim();
					var uemail = (uRows[ui].querySelector('[data-uemail]').value || '').trim();

					if (!unama && !unik && !uhp && !uemail) {
						continue; // baris kosong diabaikan
					}

					if (unik && !/^\d{16}$/.test(unik)) {
						return showErr('Baris ' + (ui + 1) + ' peserta ditetapkan: NIK harus 16 digit angka.');
					}
					if (uemail) {
						if (uemail.indexOf('@') === -1 || !uemail.toLowerCase().endsWith('belajar.id')) {
							return showErr('Baris ' + (ui + 1) + ' peserta ditetapkan: Email harus berakhiran belajar.id.');
						}
					}

					undangan.push({
						nama: unama,
						nik: unik,
						hp: uhp,
						email_belajar: uemail
					});
				}
			}

			var panitia = [];
			if (panTbody) {
				var pRows = panTbody.querySelectorAll('.kgtk-panitia-row');
				for (var pi = 0; pi < pRows.length; pi++) {
					var pr = pRows[pi];
					var pnama = (pr.querySelector('.kgtk-pan-nama').value || '').trim();
					var pjabatan = (pr.querySelector('.kgtk-pan-jabatan').value || '').trim();
					var pnik = (pr.querySelector('.kgtk-pan-nik').value || '').trim();
					var php = (pr.querySelector('.kgtk-pan-hp').value || '').trim();
					var pinstansi = (pr.querySelector('.kgtk-pan-instansi').value || '').trim();

					if (!pnama) {
						return showErr('Baris panitia nomor ' + (pi + 1) + ': Nama panitia wajib diisi.');
					}
					if (pnik && !/^\d{16}$/.test(pnik)) {
						return showErr('Baris panitia nomor ' + (pi + 1) + ': NIK harus 16 digit angka (atau kosongkan bila belum ada).');
					}

					var fullObj = {};
					try {
						if (pr.getAttribute('data-full')) {
							fullObj = JSON.parse(pr.getAttribute('data-full')) || {};
						}
					} catch (exObj) {}

					panitia.push(Object.assign({}, fullObj, {
						nama: pnama,
						jabatan: pjabatan || 'Panitia Pelaksana',
						nik: pnik,
						hp: php,
						instansi: pinstansi
					}));
				}
			}

			var btn = form.querySelector('button[type=submit]');
			btn.disabled = true; btn.textContent = (jenisVal === 'acara' ? 'Menyimpan acara...' : 'Menyimpan...');
			api('admin/pelatihan', 'POST', {
				jenis: jenisVal,
				nama: f.nama.value, penyelenggara: f.penyelenggara.value,
				tanggal_mulai: f.tanggal_mulai.value, tanggal_selesai: f.tanggal_selesai.value,
				deadline_pendaftaran: f.deadline_pendaftaran ? f.deadline_pendaftaran.value : '',
				lokasi: f.lokasi.value,
				kabkota: f.kabkota ? f.kabkota.value : '',
				tipe_pj: f.tipe_pj ? f.tipe_pj.value : 'Penanggung Jawab',
				penanggung_jawab: f.penanggung_jawab.value, keterangan: f.keterangan.value,
				kode: f.kode.value, kelas: kelas, undangan: undangan, panitia: panitia
			}).then(function (d) { window.location.href = d.url || C.list; })
				.catch(function (err) { btn.disabled = false; btn.textContent = (jenisVal === 'acara' ? 'Simpan acara' : 'Simpan pelatihan'); showErr(err.message); });
		});
	}

	/* Tab kelas & cetak - Event Delegation pada document agar tahan terhadap render timing */
	function switchAdminPane(paneId, activeTabEl) {
		var isClassPane = /^\d+$/.test(String(paneId)) && parseInt(paneId, 10) > 0;
		if (isClassPane && window._kgtkKelasDirty) {
			try { sessionStorage.setItem('kgtk_open_pane', String(paneId)); } catch (ignore) {}
			window.location.hash = String(paneId);
			window.location.reload();
			return;
		}
		var allTabs = document.querySelectorAll('.kgtk-tab');
		var allPanes = document.querySelectorAll('.kgtk-pane');
		Array.prototype.forEach.call(allTabs, function (x) {
			x.classList.toggle('is-active', x === activeTabEl);
		});
		Array.prototype.forEach.call(allPanes, function (p) {
			p.classList.toggle('is-active', p.getAttribute('data-pane') === paneId);
		});
		if (window.history && window.history.replaceState) {
			window.history.replaceState(null, '', '#' + paneId);
		}
	}

	document.addEventListener('click', function (e) {
		var openPaneBtn = e.target.closest('[data-open-pane]');
		if (openPaneBtn) {
			e.preventDefault();
			e.stopPropagation();
			var targetPane = openPaneBtn.getAttribute('data-open-pane');
			if (targetPane) {
				switchAdminPane(targetPane, null);
			}
			return;
		}
		var t = e.target.closest('.kgtk-tab');
		if (!t) { return; }
		var id = t.getAttribute('data-tab');
		if (!id) { return; }
		if (t.classList.contains('is-active')) {
			switchAdminPane('kelola-peserta', null);
			return;
		}
		switchAdminPane(id, t);
	});

	function activateTabFromHash() {
		try {
			var pendingPane = sessionStorage.getItem('kgtk_open_pane');
			if (pendingPane) {
				sessionStorage.removeItem('kgtk_open_pane');
				if (document.querySelector('.kgtk-pane[data-pane="' + pendingPane + '"]')) {
					switchAdminPane(pendingPane, null);
					return;
				}
			}
		} catch (ignore) {}
		if (window.location.hash) {
			var activeHash = window.location.hash.replace('#', '');
			var activeTabEl = document.querySelector('.kgtk-tab[data-tab="' + activeHash + '"]');
			if (activeTabEl) {
				switchAdminPane(activeHash, activeTabEl);
			} else if (document.querySelector('.kgtk-pane[data-pane="' + activeHash + '"]')) {
				switchAdminPane(activeHash, null);
			}
		}
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', activateTabFromHash);
	} else {
		activateTabFromHash();
	}

	/* Tambah peserta oleh admin */
	var ft = document.getElementById('kgtk-form-tambah');
	if (ft) {
		var okBox = document.getElementById('kgtk-tambah-ok');
		var erBox = document.getElementById('kgtk-tambah-error');
		var info = document.getElementById('kgtk-tambah-info');
		var selKelas = document.getElementById('kgtk-tambah-kelas');
		var F = ft.elements;
		var wrapJenisGtk = document.getElementById('kgtk-admin-wrap-jenis-gtk');
		var sek = window.KGTKSekolah.create({ root: C.pubroot, kabkota: C.kabkota || [] });
		document.getElementById('kgtk-sekolah-slot').appendChild(sek.node);
		var FIELDS = ['nama', 'gelar_depan', 'gelar_belakang', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'alamat', 'alamat_instansi', 'pangkat_golongan', 'agama', 'pendidikan', 'bank', 'rekening', 'npwp', 'pengalaman_jabatan', 'nuptk', 'nip', 'hp', 'email', 'email_belajar', 'simpkb'];
		var EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		var isAcaraAdmin = (ft.getAttribute('data-jenis') === 'acara');
		// Slot dan Jabatan disesuaikan secara dinamis berdasarkan Peran Kegiatan.
		var applyPeran = function () {
			var peranVal = F.peran ? F.peran.value : 'Peserta';
			var pakaiSlot = peranVal === 'Peserta';
			var wrapJenisGtk = document.getElementById('kgtk-admin-wrap-jenis-gtk');
			var wrapTugasTim = document.getElementById('kgtk-admin-wrap-tugas-tim');
			var inpTugas = document.getElementById('kgtk-tambah-tugas-tim');
			var wrapBankRek = document.getElementById('kgtk-wrap-bank-rek');
			var wrapNpwp = document.getElementById('kgtk-wrap-npwp');
			var wrapPengalaman = document.getElementById('kgtk-wrap-pengalaman');
			var wrapNuptkSimpkb = document.getElementById('kgtk-wrap-nuptk-simpkb');
			var wrapEmailBelajar = document.getElementById('kgtk-admin-wrap-email-belajar');
			var inpEmailBelajar = document.getElementById('kgtk-input-email-belajar');
			var wrapKelas = document.getElementById('kgtk-admin-wrap-kelas');
			var hintPeran = document.getElementById('kgtk-peran-hint');
			var hintKelas = document.getElementById('kgtk-kelas-hint');
			var btnSubmit = ft.querySelector('button[type=submit]');

			if (wrapJenisGtk) {
				wrapJenisGtk.style.display = (pakaiSlot && !isAcaraAdmin) ? '' : 'none';
				if ((!pakaiSlot || isAcaraAdmin) && F.jenis_gtk) { F.jenis_gtk.value = ''; }
			}
			if (wrapTugasTim) {
				wrapTugasTim.style.display = pakaiSlot ? 'none' : '';
				if (!pakaiSlot && inpTugas) {
					if (!inpTugas.value || inpTugas.value === 'Peserta' || inpTugas.value === 'Panitia' || inpTugas.value === 'Narasumber' || inpTugas.value === 'Pengajar' || inpTugas.value === 'Fasilitator' || inpTugas.value === 'Penceramah' || inpTugas.value === 'Pemateri') {
						inpTugas.value = peranVal === 'Panitia' ? 'Panitia Pelaksana' : peranVal;
					}
				}
			}

			var isPanitia = (peranVal === 'Panitia');

			// Untuk Panitia: nama bank dan rekening bank tetap bisa diisi (opsional)
			if (wrapBankRek) {
				wrapBankRek.style.display = '';
				if (F.bank) { F.bank.required = !isPanitia; }
				if (F.rekening) { F.rekening.required = !isPanitia; }
			}
			if (wrapNpwp) {
				wrapNpwp.style.display = isPanitia ? 'none' : '';
			}
			if (wrapPengalaman) {
				wrapPengalaman.style.display = isPanitia ? 'none' : '';
				if (F.pengalaman_jabatan) { F.pengalaman_jabatan.required = false; }
			}
			if (wrapNuptkSimpkb) {
				wrapNuptkSimpkb.style.display = isPanitia ? 'none' : '';
			}
			if (wrapEmailBelajar) {
				wrapEmailBelajar.style.display = isPanitia ? 'none' : '';
				if (inpEmailBelajar) { inpEmailBelajar.required = false; }
				var lblEmailBelajar = document.getElementById('kgtk-lbl-email-belajar');
				if (lblEmailBelajar) {
					lblEmailBelajar.innerHTML = pakaiSlot ? 'Email belajar.id <small style="color:#64748b;font-weight:normal;">(opsional jika belum memiliki akun)</small>' : 'Email belajar.id <small style="color:#64748b;font-weight:normal;">(opsional untuk ' + peranVal + ')</small>';
				}
				var chkTanpaBelajar = document.getElementById('kgtk-chk-tanpa-belajar-id');
				if (chkTanpaBelajar && inpEmailBelajar && !chkTanpaBelajar._bound) {
					chkTanpaBelajar._bound = true;
					chkTanpaBelajar.addEventListener('change', function () {
						if (chkTanpaBelajar.checked) {
							inpEmailBelajar.value = '';
							inpEmailBelajar.disabled = true;
							inpEmailBelajar.style.opacity = '0.6';
						} else {
							inpEmailBelajar.disabled = false;
							inpEmailBelajar.style.opacity = '1';
							inpEmailBelajar.focus();
						}
					});
				}
			}
			if (wrapKelas) {
				wrapKelas.style.display = isPanitia ? 'none' : '';
				if (isPanitia && selKelas) {
					selKelas.value = '0';
				}
			}

			var btnSelesai = document.getElementById('kgtk-btn-selesai-tambah');
			var btnHeadBack = document.getElementById('kgtk-head-back-btn');
			if (btnSelesai && btnSelesai.href) {
				var baseBack = btnSelesai.href.split('#')[0];
				var targetHash = isPanitia ? '#panitia' : (pakaiSlot ? '#kelola-peserta' : '#kelola-peserta');
				btnSelesai.href = baseBack + targetHash;
				if (btnHeadBack && btnHeadBack.href) {
					btnHeadBack.href = baseBack + targetHash;
				}
			}

			// Pilihan jenis identitas Panitia (NIK vs NIP)
			var wrapTipeId = document.getElementById('kgtk-wrap-tipe-identitas-panitia');
			var wrapNik = document.getElementById('kgtk-wrap-nik-field');
			var wrapNipPan = document.getElementById('kgtk-wrap-nip-panitia-field');
			var wrapNipUmum = document.getElementById('kgtk-wrap-nip-umum');
			var rNik = document.getElementById('kgtk-radio-id-nik');
			var rNip = document.getElementById('kgtk-radio-id-nip');
			var inpNipPan = document.getElementById('kgtk-inp-nip-panitia');

			var updatePanitiaIdView = function () {
				if (!isPanitia) {
					if (wrapTipeId) { wrapTipeId.style.display = 'none'; }
					if (wrapNik) { wrapNik.style.display = ''; }
					if (wrapNipPan) { wrapNipPan.style.display = 'none'; }
					if (wrapNipUmum) { wrapNipUmum.style.display = ''; }
					if (F.nik) { F.nik.required = true; }
				} else {
					if (wrapTipeId) { wrapTipeId.style.display = ''; }
					var pakaiNip = rNip && rNip.checked;
					if (pakaiNip) {
						if (wrapNik) { wrapNik.style.display = 'none'; }
						if (wrapNipPan) { wrapNipPan.style.display = ''; }
						if (wrapNipUmum) { wrapNipUmum.style.display = 'none'; }
						if (F.nik) { F.nik.required = false; }
						if (inpNipPan) { inpNipPan.focus(); }
					} else {
						if (wrapNik) { wrapNik.style.display = ''; }
						if (wrapNipPan) { wrapNipPan.style.display = 'none'; }
						if (wrapNipUmum) { wrapNipUmum.style.display = ''; }
						if (F.nik) { F.nik.required = true; }
					}
				}
			};

			if (rNik && !rNik._bound) {
				rNik._bound = true;
				rNik.addEventListener('change', updatePanitiaIdView);
			}
			if (rNip && !rNip._bound) {
				rNip._bound = true;
				rNip.addEventListener('change', updatePanitiaIdView);
			}
			updatePanitiaIdView();

			if (hintPeran) {
				if (pakaiSlot) {
					hintPeran.textContent = 'Peserta akan masuk ke lembar absen kelas dan memotong kuota slot kelas yang dipilih.';
				} else if (isPanitia) {
					hintPeran.textContent = 'Panitia akan otomatis tercatat di lembar hadir Panitia (bisa memilih menggunakan NIK atau NIP).';
				} else {
					hintPeran.textContent = peranVal + ' akan tercatat di lembar khusus tim kegiatan dan tidak memotong kuota slot kelas.';
				}
			}
			if (btnSubmit) {
				btnSubmit.textContent = 'Simpan ' + peranVal;
			}

			Array.prototype.forEach.call(selKelas.options, function (o) {
				if (o.value !== '0') { o.disabled = pakaiSlot && o.getAttribute('data-penuh') === '1'; }
			});
			if (selKelas.selectedOptions[0] && selKelas.selectedOptions[0].disabled) { selKelas.value = '0'; }
		};
		F.peran.addEventListener('change', applyPeran);
		applyPeran();
		var tErr = function (m) { okBox.hidden = true; erBox.textContent = m; erBox.hidden = false; erBox.scrollIntoView({ behavior: 'smooth', block: 'center' }); };

		['nik', 'nuptk', 'nip'].forEach(function (n) {
			if (!F[n]) { return; }
			var max = (n === 'nik' || n === 'nuptk') ? 16 : 18;
			F[n].addEventListener('input', function () { F[n].value = F[n].value.replace(/\D/g, '').slice(0, max); });
		});

		// NIK 16 digit: isi otomatis dari biodata tersimpan (bila ada).
		F.nik.addEventListener('input', function () {
			info.hidden = true;
			if (F.nik.value.length !== 16) { return; }
			var nik = F.nik.value;
			api('admin/guru?nik=' + encodeURIComponent(nik), 'GET').then(function (d) {
				if (F.nik.value !== nik || !d.guru) { return; }
				FIELDS.forEach(function (n) {
					if (!F[n]) { return; }
					if (d.guru[n] !== undefined && d.guru[n] !== null) {
						F[n].value = d.guru[n];
					} else if (n === 'nuptk' && d.guru.nisn) {
						F[n].value = d.guru.nisn;
					} else if (n === 'simpkb' && d.guru.email_guru) {
						F[n].value = d.guru.email_guru;
					}
				});
				sek.setValue(d.guru);
				if (F.peran && F.peran.value === 'Peserta' && d.guru.jenis_gtk && F.jenis_gtk) {
					F.jenis_gtk.value = d.guru.jenis_gtk;
				} else if (F.peran && F.peran.value === 'Panitia') {
					var inpTugas = document.getElementById('kgtk-tambah-tugas-tim');
					if (inpTugas && (!inpTugas.value || inpTugas.value === 'Panitia Pelaksana') && d.guru.jabatan) {
						inpTugas.value = d.guru.jabatan;
					}
				}
				applyPeran();
				info.textContent = 'Data tersimpan dari pendaftaran sebelumnya sudah diisikan. Periksa dan edit bila perlu.';
				info.hidden = false;
			}).catch(function () { /* diam: pengisian otomatis hanya bantuan */ });
		});

		// NIP 18 digit: sinkronisasi dan isi otomatis dari biodata tersimpan
		var inpNipPan = document.getElementById('kgtk-inp-nip-panitia');
		if (inpNipPan) {
			inpNipPan.addEventListener('input', function () {
				inpNipPan.value = inpNipPan.value.replace(/\D/g, '').slice(0, 18);
				if (F.nip) { F.nip.value = inpNipPan.value; }
				if (inpNipPan.value.length === 18) {
					api('admin/guru?nip=' + encodeURIComponent(inpNipPan.value), 'GET').then(function (d) {
						if (!d.guru) { return; }
						FIELDS.forEach(function (n) {
							if (!F[n]) { return; }
							if (d.guru[n] !== undefined && d.guru[n] !== null) {
								F[n].value = d.guru[n];
							}
						});
						sek.setValue(d.guru);
						var inpTugasNip = document.getElementById('kgtk-tambah-tugas-tim');
						if (inpTugasNip && (!inpTugasNip.value || inpTugasNip.value === 'Panitia Pelaksana') && d.guru.jabatan) {
							inpTugasNip.value = d.guru.jabatan;
						}
						applyPeran();
						info.textContent = 'Data panitia tersimpan dari NIP sudah diisikan otomatis. Periksa dan edit bila perlu.';
						info.hidden = false;
					}).catch(function () {});
				}
			});
		}
		if (F.nip) {
			F.nip.addEventListener('input', function () {
				if (inpNipPan && inpNipPan.value !== F.nip.value) {
					inpNipPan.value = F.nip.value;
				}
			});
		}

		ft.addEventListener('input', function (e) {
			if (e.target && e.target.classList) {
				e.target.classList.remove('is-invalid');
			}
		});
		ft.addEventListener('change', function (e) {
			if (e.target && e.target.classList) {
				e.target.classList.remove('is-invalid');
			}
		});

		ft.addEventListener('submit', function (e) {
			e.preventDefault();
			erBox.hidden = true; okBox.hidden = true;
			var v = {};
			['nik', 'peran', 'jenis_gtk'].concat(FIELDS).forEach(function (n) { if (F[n]) { v[n] = F[n].value.trim(); } });
			v.email = (v.email || '').toLowerCase();
			v.email_belajar = (v.email_belajar || '').toLowerCase();
			v.simpkb = v.simpkb || '';
			v.nuptk = v.nuptk || '';
			v.nisn = v.nuptk;
			v.email_guru = v.simpkb;

			// Reset indikator invalid sebelumnya
			['nik', 'peran', 'jenis_gtk'].concat(FIELDS).forEach(function (n) { if (F[n]) { F[n].classList.remove('is-invalid'); } });
			if (sek.node) {
				Array.prototype.forEach.call(sek.node.querySelectorAll('.kgtk-input'), function (inp) { inp.classList.remove('is-invalid'); });
			}

			var errors = [];
			var isPanitia = (v.peran === 'Panitia');
			var radioNip = document.getElementById('kgtk-radio-id-nip');
			var pakaiNip = isPanitia && radioNip && radioNip.checked;
			v.panitia_id_type = pakaiNip ? 'nip' : 'nik';
			var inpNipPan = document.getElementById('kgtk-inp-nip-panitia');
			if (pakaiNip && inpNipPan) {
				v.nip = inpNipPan.value.trim();
				if (F.nip) { F.nip.value = v.nip; }
			}

			if (pakaiNip) {
				if (!/^\d{18}$/.test(v.nip)) {
					errors.push({ label: 'NIP', msg: 'NIP panitia harus 18 digit angka.', el: inpNipPan || F.nip });
				}
			} else {
				if (!/^\d{16}$/.test(v.nik)) {
					errors.push({ label: 'NIK', msg: 'NIK harus 16 digit angka.', el: F.nik });
				}
			}
			if (!v.nama) {
				errors.push({ label: 'Nama Lengkap', msg: 'Nama lengkap wajib diisi.', el: F.nama });
			}
			if (!v.tempat_lahir) {
				errors.push({ label: 'Tempat Lahir', msg: 'Tempat lahir wajib diisi.', el: F.tempat_lahir });
			}
			if (!v.tanggal_lahir) {
				errors.push({ label: 'Tanggal Lahir', msg: 'Tanggal lahir wajib diisi.', el: F.tanggal_lahir });
			}
			if (!v.jenis_kelamin) {
				errors.push({ label: 'Jenis Kelamin', msg: 'Pilih jenis kelamin.', el: F.jenis_kelamin });
			}
			if (!v.alamat) {
				errors.push({ label: 'Alamat Rumah / Tempat Tinggal', msg: 'Alamat rumah/tempat tinggal wajib diisi.', el: F.alamat });
			}
			if (!v.alamat_instansi) {
				errors.push({ label: 'Alamat Sekolah / Instansi', msg: 'Alamat sekolah/instansi wajib diisi.', el: F.alamat_instansi });
			}
			if (!v.pendidikan) {
				errors.push({ label: 'Pendidikan Terakhir', msg: 'Pilih pendidikan terakhir.', el: F.pendidikan });
			}
			var se = sek.validate();
			if (se) {
				var sekTarget = sek.node ? (sek.node.querySelector('.kgtk-input:invalid') || sek.node.querySelector('.kgtk-input')) : null;
				errors.push({ label: 'Asal Sekolah/Instansi', msg: se, el: sekTarget });
			}
			var sv = sek.getValue();
			v.kabkota = sv.kabkota; v.sekolah_id = sv.sekolah_id; v.sekolah = sv.sekolah;

			if (!v.peran) {
				errors.push({ label: 'Peran Kegiatan', msg: 'Pilih peran dalam kegiatan ini.', el: F.peran });
			}
			if (v.peran === 'Panitia') {
				// Panitia: nama bank & no rekening boleh diisi (opsional, default '-')
				v.bank = (F.bank && F.bank.value.trim()) ? F.bank.value.trim() : '-';
				v.rekening = (F.rekening && F.rekening.value.trim()) ? F.rekening.value.trim() : '-';
				v.pengalaman_jabatan = '-';
				v.email_belajar = '';
				v.nuptk = '';
				v.simpkb = '';
				v.npwp = '';
				var inpTugas = document.getElementById('kgtk-tambah-tugas-tim');
				var valTugas = inpTugas ? inpTugas.value.trim() : '';
				if (!valTugas) {
					errors.push({ label: 'Tugas / Jabatan', msg: 'Tugas atau jabatan dalam kegiatan wajib diisi untuk peran Panitia.', el: inpTugas });
				}
				v.jenis_gtk = valTugas || 'Panitia Pelaksana';
			} else {
				// Untuk yang lainnya (Peserta, Narasumber, Pengajar, Fasilitator, Penceramah, Pemateri): tetap sama
				if (!v.bank) {
					errors.push({ label: 'Nama Bank', msg: 'Nama bank wajib diisi.', el: F.bank });
				}
				if (!v.rekening) {
					errors.push({ label: 'Nomor Rekening', msg: 'Nomor rekening wajib diisi.', el: F.rekening });
				}
				if (!v.pengalaman_jabatan) {
					// Pengalaman jabatan opsional untuk admin
					v.pengalaman_jabatan = (v.pengalaman_jabatan || '').trim() || '-';
				}
				if (v.peran === 'Peserta') {
					if (isAcaraAdmin) {
						v.jenis_gtk = v.jenis_gtk || '-';
						if (v.email_belajar && !EMAIL.test(v.email_belajar)) {
							errors.push({ label: 'Email belajar.id', msg: 'Format email belajar.id tidak valid (kosongkan jika tidak ada).', el: F.email_belajar });
						}
					} else {
						if (!v.jenis_gtk) {
							errors.push({ label: 'Jabatan GTK', msg: 'Pilih Jabatan GTK untuk peran Peserta.', el: F.jenis_gtk });
						}
						var chkTanpaBelajar = document.getElementById('kgtk-chk-tanpa-belajar-id');
						var tanpaBelajar = chkTanpaBelajar && chkTanpaBelajar.checked;
						if (tanpaBelajar) {
							v.email_belajar = '';
						} else if (v.email_belajar) {
							if (!EMAIL.test(v.email_belajar) || !/@([a-z0-9-]+\.)*belajar\.id$/.test(v.email_belajar)) {
								errors.push({ label: 'Email belajar.id', msg: 'Email belajar.id harus berakhiran @...belajar.id (atau kosongkan / centang belum memiliki akun belajar.id).', el: F.email_belajar });
							}
						}
					}
				} else {
					// Non-peserta non-panitia (Narasumber, Pengajar, Fasilitator, dll.)
					var inpTugasLain = document.getElementById('kgtk-tambah-tugas-tim');
					var valTugasLain = inpTugasLain ? inpTugasLain.value.trim() : '';
					if (!valTugasLain) {
						errors.push({ label: 'Tugas / Jabatan', msg: 'Tugas atau jabatan dalam kegiatan wajib diisi untuk peran ' + v.peran + '.', el: inpTugasLain });
					}
					v.jenis_gtk = valTugasLain || v.peran;
					if (v.email_belajar && !EMAIL.test(v.email_belajar)) {
						errors.push({ label: 'Email belajar.id', msg: 'Format email belajar.id tidak valid (kosongkan jika tidak ada).', el: F.email_belajar });
					}
				}
				if (v.nuptk && !/^\d{1,16}$/.test(v.nuptk)) {
					errors.push({ label: 'NUPTK', msg: 'NUPTK harus berupa maksimal 16 digit angka (kosongkan jika tidak ada).', el: F.nuptk });
				}
			}
			if (!pakaiNip && v.nip && !/^\d{18}$/.test(v.nip)) {
				errors.push({ label: 'NIP', msg: 'NIP harus 18 digit angka (kosongkan jika tidak ada).', el: F.nip });
			}
			if (!/^\+?[0-9][0-9 \-]{6,18}[0-9]$/.test(v.hp)) {
				errors.push({ label: 'No. HP/WhatsApp', msg: 'Nomor HP/WhatsApp tidak valid.', el: F.hp });
			}
			if (!EMAIL.test(v.email)) {
				errors.push({ label: 'Email', msg: 'Email wajib diisi dengan format yang benar.', el: F.email });
			}

			if (errors.length > 0) {
				errors.forEach(function (it) {
					if (it.el) { it.el.classList.add('is-invalid'); }
				});
				var firstInvalid = errors[0].el;
				tErr('Ada ' + errors.length + ' data yang belum terisi atau belum sesuai.');
				modalAlert({
					title: 'Data Belum Lengkap',
					message: 'Mohon lengkapi atau perbaiki bagian berikut sebelum menyimpan data:',
					type: 'warning',
					confirmText: 'Lengkapi Data',
					errors: errors
				}).then(function () {
					if (firstInvalid) {
						firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
						try { firstInvalid.focus(); } catch (errFocus) {}
					}
				});
				return;
			}
			v.kelas_id = parseInt(selKelas.value, 10) || 0;

			var btn = ft.querySelector('button[type=submit]');
			btn.disabled = true; btn.textContent = 'Menyimpan...';
			api('admin/pelatihan/' + ft.getAttribute('data-pelatihan') + '/peserta', 'POST', v).then(function (d) {
				var h = d.hasil;
				if (h.peran === 'Peserta') {
					okBox.textContent = h.nama + ' berhasil didaftarkan ke ' + (h.kelas_label || 'kelas') + ', nomor urut ' + h.nomor + ' dari ' + h.slot + ' slot.';
				} else {
					var tugasInfo = h.jenis_gtk ? ' (' + h.jenis_gtk + ')' : '';
					var kelasInfo = (h.kelas_label && h.kelas_label !== 'Umum') ? ' di ' + h.kelas_label : '';
					okBox.textContent = h.nama + ' berhasil ditambahkan sebagai ' + h.peran + tugasInfo + kelasInfo + ' pada kegiatan pelatihan ini.';
				}
				okBox.hidden = false;
				// Segarkan pilihan kelas (sisa slot), lalu kosongkan form untuk peserta berikutnya.
				selKelas.textContent = '';
				var o = document.createElement('option'); o.value = '0'; o.textContent = 'Otomatis (sesuai urutan kelas)'; selKelas.appendChild(o);
				(d.kelas || []).forEach(function (k) {
					var op = document.createElement('option');
					op.value = String(k.id); op.setAttribute('data-penuh', k.sisa < 1 ? '1' : '0');
					op.textContent = k.label + ' - ' + (k.sisa < 1 ? 'slot peserta penuh' : k.sisa + ' slot peserta tersisa');
					selKelas.appendChild(op);
				});
				var prevPeran = F.peran ? F.peran.value : 'Peserta';
				ft.reset();
				if (F.peran) { F.peran.value = prevPeran; }
				sek.setValue({});
				applyPeran();
				info.hidden = true;
				F.nik.focus();
				okBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
			}).catch(function (err) { tErr(err.message); })
				.then(function () {
					btn.disabled = false;
					var curPeran = (F.peran && F.peran.value) ? F.peran.value : 'Peserta';
					btn.textContent = 'Simpan ' + curPeran;
				});
		});
	}

	/* Sub-tab peran di dalam kelas */
	document.addEventListener('click', function (e) {
		var t = e.target.closest('.kgtk-subtab');
		if (!t) { return; }
		var pane = t.closest('.kgtk-pane'), id = t.getAttribute('data-sub');
		Array.prototype.forEach.call(pane.querySelectorAll('.kgtk-subtab'), function (x) { x.classList.toggle('is-active', x === t); });
		Array.prototype.forEach.call(pane.querySelectorAll('.kgtk-sub'), function (x) { x.classList.toggle('is-active', x.getAttribute('data-sub') === id); });
	});

	function clearPrinting() {
		Array.prototype.forEach.call(document.querySelectorAll('.kgtk-printing'), function (p) { p.classList.remove('kgtk-printing'); });
	}
	function markPrinting(sub) {
		if (!sub) { return; }
		sub.classList.add('kgtk-printing');
		var childSubs = sub.querySelectorAll('.kgtk-sub');
		Array.prototype.forEach.call(childSubs, function (cs) { cs.classList.add('kgtk-printing'); });
		var pane = sub.closest('.kgtk-pane');
		if (pane) { pane.classList.add('kgtk-printing'); }
	}
	document.addEventListener('click', function (e) {
		var b = e.target.closest('[data-cetak]');
		if (!b) { return; }
		clearPrinting();
		var targetId = b.getAttribute('data-cetak');
		var subs = document.querySelectorAll('.kgtk-sub[data-sub="' + targetId + '"]');
		if (subs.length) {
			Array.prototype.forEach.call(subs, function (s) { markPrinting(s); });
		} else {
			markPrinting(document.querySelector('.kgtk-sub[data-sub="' + targetId + '"]'));
		}
		window.print();
	});
	var origDocTitle = '';
	// Ctrl+P manual: cetak lembar yang sedang tampil & kosongkan title agar tidak muncul di header cetak browser.
	window.addEventListener('beforeprint', function () {
		origDocTitle = document.title;
		document.title = ' ';
		if (!document.querySelector('.kgtk-sub.kgtk-printing')) {
			var activeSub = document.querySelector('.kgtk-pane.is-active .kgtk-sub.is-active');
			if (!activeSub) {
				activeSub = document.querySelector('.kgtk-pane.is-active .kgtk-sub[data-sub="panitia-lembar-absen"]') || document.querySelector('.kgtk-pane.is-active .kgtk-sub');
			}
			markPrinting(activeSub);
		}
	});
	window.addEventListener('afterprint', function () {
		if (origDocTitle) {
			document.title = origDocTitle;
		}
		clearPrinting();
	});

	/* ====================== Penempatan Kelas Peserta ====================== */

	/* Helper pembaruan badge & statistik saat kelas diubah */
	function updateTabAndFilterStats(stats, unassigned) {
		// Update banner unassigned bila ada
		var banner = document.querySelector('.kgtk-unassigned-banner');
		if (banner) {
			if (unassigned > 0) {
				banner.style.display = '';
				var st = banner.querySelector('strong');
				if (st) { st.textContent = 'Ada ' + unassigned + ' guru yang baru mendaftar dan belum ditentukan kelasnya!'; }
			} else {
				banner.style.display = 'none';
			}
		}

		// Update badge pada header & tab kelola-peserta
		var headBadge = document.querySelector('.kgtk-kelola-head-badge .kgtk-badge');
		if (headBadge) {
			if (unassigned > 0) {
				headBadge.className = 'kgtk-badge is-alert';
				headBadge.textContent = unassigned + ' Belum Masuk Kelas';
			} else {
				headBadge.className = 'kgtk-badge is-open';
				headBadge.textContent = 'Semua Terbagi Kelas';
			}
		}

		var tabBadge = document.querySelector('[data-tab="kelola-peserta"] .kgtk-tab-badge');
		if (tabBadge) {
			if (unassigned > 0) {
				tabBadge.textContent = unassigned + ' belum ada kelas';
				tabBadge.classList.add('is-alert');
			} else {
				var totalRows = document.querySelectorAll('#kgtk-tbl-peserta tbody tr').length;
				tabBadge.textContent = totalRows + ' guru';
				tabBadge.classList.remove('is-alert');
			}
		}

		// Update kartu statistik "Belum Ada Kelas"
		var scardUnassigned = document.querySelector('.kgtk-scard[data-filter="0"]');
		if (scardUnassigned) {
			var num0 = scardUnassigned.querySelector('.kgtk-scard-num');
			if (num0) { num0.textContent = String(unassigned); }
			scardUnassigned.classList.toggle('is-warn', unassigned > 0);
			var pill0 = scardUnassigned.querySelector('.kgtk-scard-pill');
			if (unassigned > 0 && !pill0) {
				var sp = document.createElement('span');
				sp.className = 'kgtk-scard-pill';
				sp.textContent = 'Perlu Penempatan';
				scardUnassigned.appendChild(sp);
			} else if (unassigned === 0 && pill0) {
				pill0.remove();
			}
		}

		// Update tombol filter "Belum Ada Kelas"
		var fUnassigned = document.querySelector('.kgtk-fbtn[data-filter="0"] .kgtk-fcount');
		if (fUnassigned) {
			fUnassigned.textContent = String(unassigned);
			var btn0 = fUnassigned.closest('.kgtk-fbtn');
			if (btn0) { btn0.classList.toggle('is-warn', unassigned > 0); }
		}

		// Update filter, kartu, dan tab tiap kelas bila stats tersedia
		if (Array.isArray(stats)) {
			stats.forEach(function (k) {
				// Tab kelas
				var tab = document.querySelector('.kgtk-tab[data-tab="' + k.id + '"] small');
				if (tab) { tab.textContent = k.terisi + '/' + k.slot; }

				// Tombol filter
				var fbtnCount = document.querySelector('.kgtk-fbtn[data-filter="' + k.id + '"] .kgtk-fcount');
				if (fbtnCount) { fbtnCount.textContent = String(k.terisi); }

				// Kartu statistik kelas
				var scard = document.querySelector('.kgtk-scard[data-filter="' + k.id + '"]');
				if (scard) {
					var snum = scard.querySelector('.kgtk-scard-num');
					if (snum) { snum.innerHTML = k.terisi + '<small>/' + k.slot + '</small>'; }
					var sbar = scard.querySelector('.kgtk-scard-bar span');
					if (sbar) {
						var pct = Math.min(100, Math.round(k.terisi / Math.max(1, k.slot) * 100));
						sbar.style.width = pct + '%';
					}
				}
			});
		}
	}



	/* Live search & filter peserta */
	var searchInput = document.getElementById('kgtk-peserta-search');
	var searchClear = document.getElementById('kgtk-search-clear');
	var currentFilter = 'all';
	var currentRoleFilter = 'all';

	// Elemen Header Kelas Aktif
	var akhIcon = document.getElementById('kgtk-akh-icon');
	var akhTitle = document.getElementById('kgtk-akh-title');
	var akhBadgeSlot = document.getElementById('kgtk-akh-badge-slot');
	var akhBadgePanitia = document.getElementById('kgtk-akh-badge-panitia');
	var akhDesc = document.getElementById('kgtk-akh-desc');
	var akhBtnAbsen = document.getElementById('kgtk-akh-btn-absen');
	var akhBtnDeadline = document.getElementById('kgtk-akh-btn-deadline');

	// Hitung dan filter baris tabel peserta & panitia
	function filterRows() {
		var q = (searchInput ? searchInput.value : '').toLowerCase().trim();
		var rows = document.querySelectorAll('#kgtk-tbl-peserta tbody tr');

		var cntAll = 0;
		var cntPeserta = 0;
		var cntPanitia = 0;
		var cntLain = 0;
		var visIdx = 1;

		Array.prototype.forEach.call(rows, function (r) {
			var text = r.getAttribute('data-search') || '';
			var rowKelas = r.getAttribute('data-kelas') || '0';
			var rowPeran = (r.getAttribute('data-peran') || 'peserta').toLowerCase();

			var matchSearch = !q || text.indexOf(q) > -1;
			var matchKelas = currentFilter === 'all' || rowKelas === String(currentFilter);

			// Hitung kuota anggota yang cocok dengan kelas aktif & query pencarian
			if (matchKelas && matchSearch) {
				cntAll++;
				if (rowPeran === 'peserta') {
					cntPeserta++;
				} else if (rowPeran === 'panitia') {
					cntPanitia++;
				} else {
					cntLain++;
				}
			}

			// Filter peran spesifik
			var matchRole = true;
			if (currentRoleFilter === 'peserta') {
				matchRole = (rowPeran === 'peserta');
			} else if (currentRoleFilter === 'panitia') {
				matchRole = (rowPeran === 'panitia');
			} else if (currentRoleFilter === 'lain') {
				matchRole = (rowPeran !== 'peserta' && rowPeran !== 'panitia');
			}

			var isVisible = matchSearch && matchKelas && matchRole;
			r.style.display = isVisible ? '' : 'none';
			if (isVisible) {
				var numEl = r.querySelector('.kgtk-row-num');
				if (numEl) { numEl.textContent = visIdx + '.'; }
				visIdx++;
			}
		});

		// Update pill counters
		var elAll = document.getElementById('kgtk-role-cnt-all');
		if (elAll) { elAll.textContent = String(cntAll); }
		var elPes = document.getElementById('kgtk-role-cnt-peserta');
		if (elPes) { elPes.textContent = String(cntPeserta); }
		var elPan = document.getElementById('kgtk-role-cnt-panitia');
		if (elPan) { elPan.textContent = String(cntPanitia); }
		var lblPan = document.getElementById('kgtk-role-lbl-panitia');
		if (lblPan) {
			lblPan.textContent = (currentFilter === 'all' || currentFilter === '0') ? 'Panitia' : 'Panitia Kelas';
		}
		var elLain = document.getElementById('kgtk-role-cnt-lain');
		if (elLain) { elLain.textContent = String(cntLain); }

		// Sinkronkan badge panitia pada banner
		if (akhBadgePanitia) {
			if (cntPanitia > 0) {
				akhBadgePanitia.textContent = cntPanitia + ' panitia kelas';
				akhBadgePanitia.style.display = 'inline-block';
			} else {
				akhBadgePanitia.style.display = 'none';
			}
		}

		updateSelectedCounter();
	}

	function setActiveFilter(filterVal) {
		currentFilter = filterVal;

		// Update banner kelas aktif
		if (filterVal === 'all') {
			if (akhIcon) { akhIcon.textContent = '👥'; }
			if (akhTitle) { akhTitle.textContent = 'Semua Pendaftar & Panitia'; }
			if (akhBtnAbsen) { akhBtnAbsen.style.display = 'none'; }
			if (akhBtnDeadline) { akhBtnDeadline.style.display = 'none'; }
		} else if (filterVal === '0') {
			if (akhIcon) { akhIcon.textContent = '⚠️'; }
			if (akhTitle) { akhTitle.textContent = 'Belum Ada Kelas / Perlu Penempatan'; }
			if (akhBtnAbsen) { akhBtnAbsen.style.display = 'none'; }
			if (akhBtnDeadline) { akhBtnDeadline.style.display = 'none'; }
		} else {
			var cardEl = document.querySelector('.kgtk-scard[data-filter="' + filterVal + '"]');
			var namaKelas = cardEl ? cardEl.getAttribute('data-nama') : ('Kelas ' + filterVal);
			var slotInfo = cardEl ? cardEl.getAttribute('data-slot') : '';

			if (akhIcon) { akhIcon.textContent = '🏫'; }
			if (akhTitle) { akhTitle.textContent = namaKelas; }
			if (akhBadgeSlot) { akhBadgeSlot.textContent = slotInfo ? ('Slot: ' + slotInfo) : namaKelas; }
			if (akhBtnAbsen) {
				akhBtnAbsen.setAttribute('data-open-pane', String(filterVal));
				akhBtnAbsen.innerHTML = '&#128196; Buka Lembar Absen ' + esc(namaKelas);
				akhBtnAbsen.style.display = 'inline-flex';
			}
			if (akhBtnDeadline) {
				akhBtnDeadline.setAttribute('data-atur-deadline-kelas', String(filterVal));
				akhBtnDeadline.style.display = 'inline-flex';
			}
		}

		// Sync filter buttons
		var allBtns = document.querySelectorAll('.kgtk-fbtn[data-filter]');
		Array.prototype.forEach.call(allBtns, function (b) {
			b.classList.toggle('is-active', b.getAttribute('data-filter') === filterVal);
		});
		// Sync stat cards
		var allScards = document.querySelectorAll('.kgtk-scard[data-filter]');
		Array.prototype.forEach.call(allScards, function (s) {
			s.classList.toggle('is-active', s.getAttribute('data-filter') === filterVal);
		});

		filterRows();
	}

	// Filter per peran (Semua / Peserta / Panitia / Lainnya)
	document.addEventListener('click', function (e) {
		var roleBtn = e.target.closest('[data-role-filter]');
		if (!roleBtn) { return; }
		e.preventDefault();
		e.stopPropagation();
		currentRoleFilter = roleBtn.getAttribute('data-role-filter') || 'all';
		var allRoleBtns = document.querySelectorAll('[data-role-filter]');
		Array.prototype.forEach.call(allRoleBtns, function (rb) {
			rb.classList.toggle('is-active', rb === roleBtn);
		});
		filterRows();
	});

	// Tombol Buka Lembar Absen dari Banner Kelas Aktif
	if (akhBtnAbsen) {
		akhBtnAbsen.addEventListener('click', function () {
			var targetPane = this.getAttribute('data-open-pane');
			if (targetPane) {
				switchAdminPane(targetPane, null);
			}
		});
	}

	// Tombol Kembali dari Lembar Absen Kelas ke Daftar Peserta
	document.addEventListener('click', function (e) {
		var backBtn = e.target.closest('.kgtk-btn-back-to-peserta');
		if (!backBtn) { return; }
		var targetKId = backBtn.getAttribute('data-back-kelas');
		switchAdminPane('kelola-peserta', null);
		if (targetKId) {
			setActiveFilter(targetKId);
		}
	});

	if (searchInput) {
		searchInput.addEventListener('input', function () {
			if (searchClear) {
				searchClear.style.display = searchInput.value ? 'flex' : 'none';
			}
			filterRows();
		});
	}
	if (searchClear) {
		searchClear.addEventListener('click', function () {
			if (searchInput) {
				searchInput.value = '';
				searchClear.style.display = 'none';
				searchInput.focus();
				filterRows();
			}
		});
	}

	// Klik tombol filter kelas
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.kgtk-fbtn[data-filter]');
		if (btn) {
			if (btn.closest('#kgtk-undangan-container')) { return; }
			var fVal = btn.getAttribute('data-filter');
			if (fVal !== null && fVal !== undefined) {
				setActiveFilter(fVal);
			}
			return;
		}
		// Klik kartu statistik
		var scard = e.target.closest('.kgtk-scard');
		if (scard) {
			if (scard.closest('#kgtk-undangan-container')) { return; }
			if (e.target.closest('[data-atur-deadline-kelas], [data-open-pane]')) { return; }
			var activePane = document.querySelector('.kgtk-pane.is-active');
			var activePaneName = activePane ? activePane.getAttribute('data-pane') : '';
			if (activePaneName !== 'panitia') {
				switchAdminPane('kelola-peserta', null);
			}
			setActiveFilter(scard.getAttribute('data-filter'));
			return;
		}
	});

	/* Salin NIK 1-klik */
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-copy-nik]');
		if (!btn) { return; }
		var nik = btn.getAttribute('data-copy-nik');
		if (!nik) { return; }
		var done = function () {
			var oldTitle = btn.getAttribute('title') || 'Salin NIK';
			var oldHtml = btn.innerHTML;
			btn.innerHTML = '✓';
			btn.classList.add('is-copied');
			btn.setAttribute('title', 'Tersalin!');
			setTimeout(function () {
				btn.innerHTML = oldHtml;
				btn.classList.remove('is-copied');
				btn.setAttribute('title', oldTitle);
			}, 1500);
		};
		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(nik).then(done, function () { fallbackCopy(nik, done); });
		} else {
			fallbackCopy(nik, done);
		}
		function fallbackCopy(text, cb) {
			var ta = document.createElement('textarea');
			ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
			document.body.appendChild(ta); ta.select();
			try { document.execCommand('copy'); cb(); } catch (err) {}
			document.body.removeChild(ta);
		}
	});

	/* Update hitungan centang & bulk action UI */
	function updateSelectedCounter() {
		var checks = document.querySelectorAll('.kgtk-ps-check');
		var checkedCount = 0;
		Array.prototype.forEach.call(checks, function (c) {
			var tr = c.closest('tr');
			if (tr) {
				tr.classList.toggle('is-selected', c.checked);
			}
			if (c.checked) { checkedCount++; }
		});

		var badge = document.getElementById('kgtk-selected-badge');
		var applyBtn = document.getElementById('kgtk-bulk-apply');
		var bulkToolbar = document.querySelector('.kgtk-bulk-toolbar');
		if (bulkToolbar) {
			bulkToolbar.classList.toggle('has-selected', checkedCount > 0);
		}
		if (badge) {
			if (checkedCount > 0) {
				badge.textContent = checkedCount + ' guru dipilih';
				badge.style.display = 'inline-block';
			} else {
				badge.style.display = 'none';
			}
		}
		if (applyBtn) {
			applyBtn.disabled = (checkedCount === 0);
		}
		var bulkBioBtn = document.getElementById('kgtk-btn-bulk-biodata');
		if (bulkBioBtn) {
			bulkBioBtn.disabled = (checkedCount === 0);
			var bioCount = document.getElementById('kgtk-bio-checked-count');
			if (bioCount) {
				bioCount.textContent = checkedCount;
			}
		}

		// Sync kedua checkbox header & toolbar (Pilih Semua)
		var checkAll = document.getElementById('kgtk-check-all');
		var thCheckAll = document.getElementById('kgtk-th-check-all');
		var visibleChecks = Array.prototype.filter.call(checks, function (c) {
			return c.closest('tr') && c.closest('tr').style.display !== 'none';
		});
		var allChecked = visibleChecks.length > 0 && visibleChecks.every(function (c) { return c.checked; });
		if (checkAll) { checkAll.checked = allChecked; }
		if (thCheckAll) { thCheckAll.checked = allChecked; }
	}

	// Listener centang per baris
	document.addEventListener('change', function (e) {
		if (e.target.classList.contains('kgtk-ps-check')) {
			updateSelectedCounter();
		}
	});

	// Klik baris peserta untuk mencentang nama secara langsung dan praktis
	document.addEventListener('click', function (e) {
		var tr = e.target.closest('#kgtk-tbl-peserta tbody tr');
		if (!tr) { return; }
		// Abaikan jika klik berasal dari link WhatsApp, tombol salin/hapus, atau checkbox itu sendiri
		if (e.target.closest('a, button, input[type=checkbox]')) { return; }
		var chk = tr.querySelector('.kgtk-ps-check');
		if (chk) {
			chk.checked = !chk.checked;
			updateSelectedCounter();
		}
	});

	/* Fungsi centang semua baris yang sedang tampil */
	function toggleAllVisibleChecks(state) {
		var checks = document.querySelectorAll('.kgtk-ps-check');
		Array.prototype.forEach.call(checks, function (c) {
			var tr = c.closest('tr');
			if (tr && tr.style.display !== 'none') {
				c.checked = state;
			}
		});
		updateSelectedCounter();
	}

	var checkAll = document.getElementById('kgtk-check-all');
	if (checkAll) {
		checkAll.addEventListener('change', function () {
			toggleAllVisibleChecks(checkAll.checked);
		});
	}

	var thCheckAll = document.getElementById('kgtk-th-check-all');
	if (thCheckAll) {
		thCheckAll.addEventListener('change', function () {
			toggleAllVisibleChecks(thCheckAll.checked);
		});
	}

	/* Terapkan aksi masal (bulk assign kelas) */
	var bulkApply = document.getElementById('kgtk-bulk-apply');
	var bulkTarget = document.getElementById('kgtk-bulk-target');
	if (bulkApply && bulkTarget) {
		bulkApply.addEventListener('click', function () {
			var val = bulkTarget.value;
			if (val === '') {
				return modalAlert({ title: 'Pilih Kelas Tujuan', message: 'Silakan pilih kelas tujuan terlebih dahulu.', type: 'warning' });
			}
			var targetKelasId = parseInt(val, 10);
			var selectedIds = [];
			var checks = document.querySelectorAll('.kgtk-ps-check:checked');
			Array.prototype.forEach.call(checks, function (c) {
				selectedIds.push(parseInt(c.value, 10));
			});
			if (!selectedIds.length) {
				return modalAlert({ title: 'Pilih Guru', message: 'Centang minimal satu nama guru yang ingin dipindahkan kelasnya.', type: 'warning' });
			}

			var targetLabel = bulkTarget.options[bulkTarget.selectedIndex].textContent;
			modalConfirm({
				title: 'Pindahkan Kelas Guru',
				message: 'Pindahkan ' + selectedIds.length + ' guru yang dipilih ke: ' + targetLabel + '?',
				confirmText: 'Ya, Pindahkan',
				cancelText: 'Batal',
				type: 'primary'
			}).then(function (ok) {
				if (!ok) { return; }

				bulkApply.disabled = true;
				var oldText = bulkApply.textContent;
				bulkApply.textContent = 'Menyimpan...';

				var tbl = document.getElementById('kgtk-tbl-peserta');
				var pelatihanId = tbl ? tbl.getAttribute('data-pelatihan') : '0';

				api('admin/pelatihan/' + pelatihanId + '/bulk-kelas', 'POST', {
					peserta_ids: selectedIds,
					kelas_id: targetKelasId
				}).then(function () {
					window.location.reload();
				}).catch(function (err) {
					bulkApply.disabled = false;
					bulkApply.textContent = oldText;
					modalAlert({ title: 'Gagal Memindahkan', message: 'Gagal memproses pemindahan kelas: ' + err.message, type: 'danger' });
				});
			});
		});
	}

	/* Cetak / Unduh Biodata Terpilih (Bulk Biodata) */
	var bulkBio = document.getElementById('kgtk-btn-bulk-biodata');
	if (bulkBio) {
		bulkBio.addEventListener('click', function () {
			var selectedIds = [];
			var checks = document.querySelectorAll('.kgtk-ps-check:checked');
			Array.prototype.forEach.call(checks, function (c) {
				selectedIds.push(parseInt(c.value, 10));
			});
			if (!selectedIds.length) {
				return modalAlert({ title: 'Pilih Peserta', message: 'Centang minimal satu guru yang ingin dicetak atau diunduh biodatanya.', type: 'warning' });
			}
			var baseUrl = bulkBio.getAttribute('data-bio-url');
			if (!baseUrl) { return; }
			var sep = baseUrl.indexOf('?') !== -1 ? '&' : '?';
			var url = baseUrl + sep + 'ids=' + encodeURIComponent(selectedIds.join(','));
			window.open(url, '_blank');
		});
	}

	/* Hapus peserta tunggal */
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-del-peserta]');
		if (!btn) { return; }
		var id = btn.getAttribute('data-del-peserta');
		var nama = btn.getAttribute('data-nama');
		modalConfirm({
			title: 'Hapus Peserta',
			message: 'Hapus pendaftaran ' + nama + ' dari pelatihan ini?\n\nData peserta akan dihapus dari daftar hadir dan dari Riwayat Pelatihan guru.',
			confirmText: 'Ya, Hapus',
			cancelText: 'Batal',
			type: 'danger'
		}).then(function (ok) {
			if (!ok) { return; }
			btn.disabled = true;
			api('admin/peserta/' + id, 'DELETE').then(function (res) {
				var tr = btn.closest('tr');
				if (tr) { tr.remove(); }
				// Segarkan nomor urut baris
				var numCells = document.querySelectorAll('#kgtk-tbl-peserta tbody td:nth-child(2) .kgtk-row-num');
				Array.prototype.forEach.call(numCells, function (span, idx) {
					span.textContent = String(idx + 1) + '.';
				});
				updateSelectedCounter();
				updateTabAndFilterStats(res.stats, res.unassigned);
			}).catch(function (err) {
				btn.disabled = false;
				modalAlert({ title: 'Gagal Menghapus', message: 'Gagal menghapus: ' + err.message, type: 'danger' });
			});
		});
	});

	/* ==========================================================================
	   PESERTA DITETAPKAN (KEMENTERIAN)
	   ========================================================================== */
	var undContainer = document.getElementById('kgtk-undangan-container');
	if (undContainer) {
		var pelatihanId = undContainer.getAttribute('data-pelatihan') || '0';
		var undSearch = document.getElementById('kgtk-undangan-search');
		var undSearchClear = document.getElementById('kgtk-undangan-search-clear');
		var currentUndFilter = 'all';

		var filterUndanganRows = function () {
			var q = (undSearch ? undSearch.value : '').toLowerCase().trim();
			var rows = document.querySelectorAll('#kgtk-tbl-undangan tbody tr:not(.kgtk-row-empty)');
			var visNo = 1;
			Array.prototype.forEach.call(rows, function (r) {
				var text = (r.getAttribute('data-search') || '').toLowerCase();
				var st = r.getAttribute('data-status') || '';
				var matchSearch = !q || text.indexOf(q) > -1;
				var matchFilter = currentUndFilter === 'all' || st === currentUndFilter;
				if (matchSearch && matchFilter) {
					r.style.display = '';
					var numCell = r.querySelector('.kgtk-row-num');
					if (numCell) { numCell.textContent = String(visNo++) + '.'; }
				} else {
					r.style.display = 'none';
				}
			});
		};

		var setUndFilter = function (fVal) {
			currentUndFilter = fVal;
			var fbtns = undContainer.querySelectorAll('.kgtk-fbtn');
			Array.prototype.forEach.call(fbtns, function (b) {
				b.classList.toggle('is-active', b.getAttribute('data-filter') === fVal);
			});
			var scards = undContainer.querySelectorAll('.kgtk-scard');
			Array.prototype.forEach.call(scards, function (s) {
				s.classList.toggle('is-active', s.getAttribute('data-filter') === fVal);
			});
			filterUndanganRows();
		};

		if (undSearch) {
			undSearch.addEventListener('input', function () {
				if (undSearchClear) {
					undSearchClear.style.display = undSearch.value ? 'flex' : 'none';
				}
				filterUndanganRows();
			});
		}
		if (undSearchClear) {
			undSearchClear.addEventListener('click', function () {
				if (undSearch) {
					undSearch.value = '';
					undSearchClear.style.display = 'none';
					undSearch.focus();
					filterUndanganRows();
				}
			});
		}

		undContainer.addEventListener('click', function (e) {
			var b = e.target.closest('.kgtk-fbtn');
			if (b) { setUndFilter(b.getAttribute('data-filter')); return; }
			var s = e.target.closest('.kgtk-scard');
			if (s) { setUndFilter(s.getAttribute('data-filter')); return; }
		});

		/* Salin daftar belum mendaftar */
		var btnSalinBelum = document.getElementById('kgtk-btn-salin-belum');
		if (btnSalinBelum) {
			btnSalinBelum.addEventListener('click', function () {
				var rows = document.querySelectorAll('#kgtk-tbl-undangan tbody tr[data-status="belum"]');
				if (!rows.length) {
					return modalAlert({ title: 'Semua Sudah Mendaftar', message: 'Tidak ada peserta ditetapkan yang berstatus belum mendaftar.', type: 'primary' });
				}
				var lines = ['DAFTAR PESERTA DITETAPKAN (BELUM MENDAFTAR):'];
				Array.prototype.forEach.call(rows, function (r, idx) {
					var nama = r.getAttribute('data-nama') || '(tanpa nama)';
					var nik = r.getAttribute('data-nik') || '-';
					var hp = r.getAttribute('data-hp') || '-';
					var email = r.getAttribute('data-email') || '-';
					var line = (idx + 1) + '. ' + nama;
					var meta = [];
					if (nik && nik !== '-') { meta.push('NIK: ' + nik); }
					if (hp && hp !== '-') { meta.push('HP: ' + hp); }
					if (email && email !== '-') { meta.push('Email: ' + email); }
					if (meta.length) { line += ' (' + meta.join(', ') + ')'; }
					lines.push(line);
				});
				copyText(lines.join('\n'), btnSalinBelum);
			});
		}

		/* Modal Tambah / Ubah Undangan */
		var modalUnd = document.getElementById('kgtk-modal-undangan');
		var btnTambahUnd = document.getElementById('kgtk-btn-tambah-undangan');
		var btnUndCancel = document.getElementById('kgtk-btn-undangan-cancel');
		var btnUndSave = document.getElementById('kgtk-btn-undangan-save');
		var undForm = document.getElementById('kgtk-form-undangan-modal');
		var undTitle = document.getElementById('kgtk-undangan-form-title');
		var undErr = document.getElementById('kgtk-undangan-modal-error');

		var openUndModal = function (item) {
			if (!modalUnd || !undForm) { return; }
			if (undErr) { undErr.hidden = true; undErr.textContent = ''; }
			if (item) {
				undTitle.textContent = 'Edit Peserta Ditetapkan';
				undForm.elements.undangan_id.value = item.id;
				undForm.elements.nama.value = item.nama || '';
				undForm.elements.nik.value = item.nik || '';
				undForm.elements.hp.value = item.hp || '';
				undForm.elements.email_belajar.value = item.email_belajar || '';
				undForm.elements.catatan.value = item.catatan || '';
			} else {
				undTitle.textContent = 'Tambah Peserta Ditetapkan';
				undForm.reset();
				undForm.elements.undangan_id.value = '0';
			}
			modalUnd.style.display = 'flex';
			modalUnd.classList.add('is-visible');
			undForm.elements.nama.focus();
		};

		var closeUndModal = function () {
			if (modalUnd) {
				modalUnd.classList.remove('is-visible');
				modalUnd.style.display = 'none';
			}
		};

		if (btnTambahUnd) {
			btnTambahUnd.addEventListener('click', function () { openUndModal(null); });
		}
		if (btnUndCancel) {
			btnUndCancel.addEventListener('click', closeUndModal);
		}
		if (modalUnd) {
			modalUnd.addEventListener('click', function (e) {
				if (e.target === modalUnd) { closeUndModal(); }
			});
		}

		if (btnUndSave && undForm) {
			btnUndSave.addEventListener('click', function () {
				if (undErr) { undErr.hidden = true; }
				var f = undForm.elements;
				var undId = parseInt(f.undangan_id.value, 10);
				var nama = f.nama.value.trim();
				var nik = f.nik.value.trim();
				var hp = f.hp.value.trim();
				var email = f.email_belajar.value.trim();
				var catatan = f.catatan.value.trim();

				if (!nama && !nik && !hp && !email) {
					undErr.textContent = 'Isi minimal salah satu dari: Nama, NIK, No. HP, atau Email belajar.id.';
					undErr.hidden = false;
					return;
				}
				if (nik && !/^\d{16}$/.test(nik)) {
					undErr.textContent = 'NIK harus 16 digit angka.';
					undErr.hidden = false;
					return;
				}
				if (email && (email.indexOf('@') === -1 || !email.toLowerCase().endsWith('belajar.id'))) {
					undErr.textContent = 'Email harus format email yang benar dan berakhiran belajar.id.';
					undErr.hidden = false;
					return;
				}

				btnUndSave.disabled = true;
				var oldSaveText = btnUndSave.textContent;
				btnUndSave.textContent = 'Menyimpan...';

				var endpoint = undId > 0 ? ('admin/undangan/' + undId) : ('admin/pelatihan/' + pelatihanId + '/undangan');
				var method = undId > 0 ? 'PUT' : 'POST';

				api(endpoint, method, {
					nama: nama,
					nik: nik,
					hp: hp,
					email_belajar: email,
					catatan: catatan
				}).then(function () {
					closeUndModal();
					window.location.hash = 'peserta-ditetapkan';
					window.location.reload();
				}).catch(function (err) {
					btnUndSave.disabled = false;
					btnUndSave.textContent = oldSaveText;
					undErr.textContent = err.message;
					undErr.hidden = false;
				});
			});
		}

		/* Modal Hubungkan ke Peserta Terdaftar */
		var modalHub = document.getElementById('kgtk-modal-hubungkan');
		var hubInfo = document.getElementById('kgtk-hubungkan-info');
		var hubErr = document.getElementById('kgtk-hubungkan-modal-error');
		var selUnlinked = document.getElementById('kgtk-select-unlinked-peserta');
		var btnHubCancel = document.getElementById('kgtk-btn-hubungkan-cancel');
		var btnHubSave = document.getElementById('kgtk-btn-hubungkan-save');
		var currentTargetUndId = 0;

		var openHubModal = function (undId, tr) {
			if (!modalHub || !selUnlinked) { return; }
			currentTargetUndId = undId;
			if (hubErr) { hubErr.hidden = true; hubErr.textContent = ''; }
			var uNama = tr ? (tr.getAttribute('data-nama') || '(tanpa nama)') : '';
			var uNik = tr ? (tr.getAttribute('data-nik') || '-') : '-';
			if (hubInfo) {
				hubInfo.innerHTML = 'Peserta Ditetapkan: <strong>' + esc(uNama) + '</strong> (NIK: <code>' + esc(uNik) + '</code>)';
			}
			selUnlinked.innerHTML = '<option value="">-- Memuat daftar peserta terdaftar... --</option>';
			selUnlinked.disabled = true;
			btnHubSave.disabled = true;
			modalHub.style.display = 'flex';
			modalHub.classList.add('is-visible');

			api('admin/pelatihan/' + pelatihanId + '/peserta-unlinked', 'GET').then(function (list) {
				selUnlinked.disabled = false;
				btnHubSave.disabled = false;
				if (!list || !list.length) {
					selUnlinked.innerHTML = '<option value="">-- Tidak ada peserta terdaftar yang belum terhubung --</option>';
					return;
				}
				var opts = '<option value="">-- Pilih salah satu peserta terdaftar --</option>';
				list.forEach(function (p) {
					var pKet = p.nama + ' (NIK: ' + p.nik + (p.hp ? ', HP: ' + p.hp : '') + (p.kelas_label ? ', Kelas: ' + p.kelas_label : '') + ')';
					opts += '<option value="' + p.id + '">' + esc(pKet) + '</option>';
				});
				selUnlinked.innerHTML = opts;
			}).catch(function (err) {
				selUnlinked.innerHTML = '<option value="">-- Gagal memuat daftar peserta --</option>';
				if (hubErr) {
					hubErr.textContent = err.message;
					hubErr.hidden = false;
				}
			});
		};

		var closeHubModal = function () {
			if (modalHub) {
				modalHub.classList.remove('is-visible');
				modalHub.style.display = 'none';
			}
		};
		if (btnHubCancel) { btnHubCancel.addEventListener('click', closeHubModal); }
		if (modalHub) {
			modalHub.addEventListener('click', function (e) {
				if (e.target === modalHub) { closeHubModal(); }
			});
		}

		if (btnHubSave) {
			btnHubSave.addEventListener('click', function () {
				var pesId = selUnlinked.value;
				if (!pesId) {
					hubErr.textContent = 'Pilih salah satu peserta terdaftar terlebih dahulu.';
					hubErr.hidden = false;
					return;
				}
				hubErr.hidden = true;
				btnHubSave.disabled = true;
				var oldHText = btnHubSave.textContent;
				btnHubSave.textContent = 'Menghubungkan...';

				api('admin/undangan/' + currentTargetUndId + '/hubungkan', 'POST', {
					peserta_id: parseInt(pesId, 10)
				}).then(function () {
					closeHubModal();
					window.location.hash = 'peserta-ditetapkan';
					window.location.reload();
				}).catch(function (err) {
					btnHubSave.disabled = false;
					btnHubSave.textContent = oldHText;
					hubErr.textContent = err.message;
					hubErr.hidden = false;
				});
			});
		}

		/* Sinkronkan ulang pencocokan */
		var btnSinkron = document.getElementById('kgtk-btn-sinkron-undangan');
		if (btnSinkron) {
			btnSinkron.addEventListener('click', function () {
				modalConfirm({
					title: 'Sinkronkan Ulang',
					message: 'Jalankan ulang pencocokan otomatis untuk seluruh peserta ditetapkan yang belum cocok?',
					confirmText: 'Ya, Sinkronkan',
					cancelText: 'Batal',
					type: 'primary'
				}).then(function (ok) {
					if (!ok) { return; }
					btnSinkron.disabled = true;
					var oldSText = btnSinkron.textContent;
					btnSinkron.textContent = 'Menyinkronkan...';
					api('admin/pelatihan/' + pelatihanId + '/undangan/sinkron', 'POST').then(function (res) {
						modalAlert({
							title: 'Sinkronisasi Selesai',
							message: 'Berhasil mencocokkan ' + res.matched + ' peserta baru.',
							type: 'primary'
						}).then(function () {
							window.location.hash = 'peserta-ditetapkan';
							window.location.reload();
						});
					}).catch(function (err) {
						btnSinkron.disabled = false;
						btnSinkron.textContent = oldSText;
						modalAlert({ title: 'Gagal Menyinkronkan', message: err.message, type: 'danger' });
					});
				});
			});
		}

		/* Modal Impor Excel/CSV */
		var modalImp = document.getElementById('kgtk-modal-impor');
		var btnImpOpen = document.getElementById('kgtk-btn-impor-undangan');
		var btnImpCancel = document.getElementById('kgtk-btn-impor-cancel');
		var btnImpNext = document.getElementById('kgtk-btn-impor-next');
		var fileInput = document.getElementById('kgtk-impor-file-input');
		var impErr = document.getElementById('kgtk-impor-modal-error');
		var step1 = document.getElementById('kgtk-impor-step-1');
		var step2 = document.getElementById('kgtk-impor-step-2');
		var step3 = document.getElementById('kgtk-impor-step-3');
		var mapNama = document.getElementById('kgtk-map-nama');
		var mapNik = document.getElementById('kgtk-map-nik');
		var mapHp = document.getElementById('kgtk-map-hp');
		var mapEmail = document.getElementById('kgtk-map-email');
		var impPreview = document.getElementById('kgtk-impor-preview');
		var impResMsg = document.getElementById('kgtk-impor-result-msg');
		var impReasonsWrap = document.getElementById('kgtk-impor-reasons-wrap');

		var impStep = 1;
		var impSessionKey = '';

		var resetImpModal = function () {
			impStep = 1;
			impSessionKey = '';
			if (fileInput) { fileInput.value = ''; }
			if (impErr) { impErr.hidden = true; impErr.textContent = ''; }
			if (step1) { step1.style.display = ''; }
			if (step2) { step2.style.display = 'none'; }
			if (step3) { step3.style.display = 'none'; }
			if (btnImpNext) {
				btnImpNext.textContent = 'Lanjutkan';
				btnImpNext.disabled = false;
			}
			if (btnImpCancel) { btnImpCancel.style.display = ''; }
		};

		var closeImpModal = function () {
			if (modalImp) {
				modalImp.classList.remove('is-visible');
				modalImp.style.display = 'none';
			}
			resetImpModal();
		};

		if (btnImpOpen) {
			btnImpOpen.addEventListener('click', function () {
				resetImpModal();
				if (modalImp) {
					modalImp.style.display = 'flex';
					modalImp.classList.add('is-visible');
				}
			});
		}
		if (btnImpCancel) { btnImpCancel.addEventListener('click', closeImpModal); }
		if (modalImp) {
			modalImp.addEventListener('click', function (e) {
				if (e.target === modalImp) { closeImpModal(); }
			});
		}

		if (btnImpNext) {
			btnImpNext.addEventListener('click', function () {
				if (impErr) { impErr.hidden = true; }
				if (impStep === 1) {
					if (!fileInput || !fileInput.files || !fileInput.files[0]) {
						impErr.textContent = 'Pilih berkas Excel (.xlsx, .xls) atau CSV terlebih dahulu.';
						impErr.hidden = false;
						return;
					}
					var file = fileInput.files[0];
					var ext = file.name.split('.').pop().toLowerCase();
					if (['xlsx', 'xls', 'csv'].indexOf(ext) === -1) {
						impErr.textContent = 'Format berkas tidak didukung. Gunakan .xlsx, .xls, atau .csv.';
						impErr.hidden = false;
						return;
					}
					if (file.size > 10 * 1024 * 1024) {
						impErr.textContent = 'Ukuran berkas melebihi batas 10 MB.';
						impErr.hidden = false;
						return;
					}

					btnImpNext.disabled = true;
					btnImpNext.textContent = 'Mengunggah...';

					var fd = new FormData();
					fd.append('file', file);
					fd.append('berkas', file);

					fetch(C.root + 'admin/pelatihan/' + pelatihanId + '/undangan/upload', {
						method: 'POST',
						credentials: 'same-origin',
						headers: { 'X-WP-Nonce': C.nonce },
						body: fd
					}).then(function (r) {
						return r.text().then(function (text) {
							var d = {};
							try {
								d = JSON.parse(text);
							} catch (e) {
								throw new Error('Respon server tidak valid atau terjadi error sistem.');
							}
							if (!r.ok) { throw new Error(d.message || 'Gagal mengunggah berkas.'); }
							return d;
						});
					}).then(function (data) {
						impSessionKey = data.session_key || data.token;
						var headers = data.headers || data.header || [];
						var detected = data.detected || data.guessed || {};
						var preview = data.preview || data.sample || [];

						var buildSelectOptions = function (selectedIdx) {
							var optHtml = '<option value="-1">-- Lewati --</option>';
							headers.forEach(function (h, i) {
								var sel = (i === selectedIdx) ? ' selected' : '';
								optHtml += '<option value="' + i + '"' + sel + '>' + esc(h || ('Kolom ' + (i + 1))) + '</option>';
							});
							return optHtml;
						};

						mapNama.innerHTML = buildSelectOptions(detected.nama !== false && detected.nama !== undefined ? detected.nama : -1);
						mapNik.innerHTML = buildSelectOptions(detected.nik !== false && detected.nik !== undefined ? detected.nik : -1);
						mapHp.innerHTML = buildSelectOptions(detected.hp !== false && detected.hp !== undefined ? detected.hp : -1);
						mapEmail.innerHTML = buildSelectOptions(detected.email_belajar !== false && detected.email_belajar !== undefined ? detected.email_belajar : -1);

						if (impPreview) {
							var prevHtml = '<table class="kgtk-table" style="font-size:12px;margin:0;"><thead><tr>';
							headers.forEach(function (h) {
								prevHtml += '<th>' + esc(h || '-') + '</th>';
							});
							prevHtml += '</tr></thead><tbody>';
							preview.forEach(function (pRow) {
								prevHtml += '<tr>';
								for (var c = 0; c < headers.length; c++) {
									prevHtml += '<td>' + esc(pRow[c] !== undefined ? pRow[c] : '') + '</td>';
								}
								prevHtml += '</tr>';
							});
							prevHtml += '</tbody></table>';
							impPreview.innerHTML = prevHtml;
						}

						step1.style.display = 'none';
						step2.style.display = '';
						impStep = 2;
						btnImpNext.disabled = false;
						btnImpNext.textContent = 'Mulai Impor';
					}).catch(function (err) {
						btnImpNext.disabled = false;
						btnImpNext.textContent = 'Lanjutkan';
						impErr.textContent = err.message;
						impErr.hidden = false;
					});

				} else if (impStep === 2) {
					var vNama = parseInt(mapNama.value, 10);
					var vNik = parseInt(mapNik.value, 10);
					var vHp = parseInt(mapHp.value, 10);
					var vEmail = parseInt(mapEmail.value, 10);

					if (vNama < 0 && vNik < 0 && vHp < 0 && vEmail < 0) {
						impErr.textContent = 'Minimal satu kolom (Nama, NIK, No. HP, atau Email belajar.id) wajib dipetakan.';
						impErr.hidden = false;
						return;
					}

					btnImpNext.disabled = true;
					btnImpNext.textContent = 'Mengimpor data...';

					var mapPayload = {
						nama: vNama,
						nik: vNik,
						hp: vHp,
						email_belajar: vEmail
					};

					api('admin/pelatihan/' + pelatihanId + '/undangan/import', 'POST', {
						session_key: impSessionKey,
						token: impSessionKey,
						mapping: mapPayload,
						map: mapPayload
					}).then(function (res) {
						step2.style.display = 'none';
						step3.style.display = '';
						impStep = 3;

						if (impResMsg) {
							impResMsg.innerHTML = '<strong>Impor Selesai!</strong><br>'
								+ 'Berhasil mengimpor <strong>' + res.imported + '</strong> peserta ditetapkan '
								+ 'dari total ' + res.total_rows + ' baris data.';
						}

						if (impReasonsWrap) {
							if (res.skipped > 0 && res.reasons && res.reasons.length) {
								var rHtml = '<strong>' + res.skipped + ' baris dilewati:</strong><ul style="margin:4px 0 0 16px;padding:0;list-style:disc;">';
								res.reasons.slice(0, 50).forEach(function (reason) {
									rHtml += '<li>' + esc(reason) + '</li>';
								});
								if (res.reasons.length > 50) {
									rHtml += '<li>...dan ' + (res.reasons.length - 50) + ' baris lainnya.</li>';
								}
								rHtml += '</ul>';
								impReasonsWrap.innerHTML = rHtml;
								impReasonsWrap.style.display = '';
							} else {
								impReasonsWrap.style.display = 'none';
							}
						}

						btnImpNext.disabled = false;
						btnImpNext.textContent = 'Selesai';
						if (btnImpCancel) { btnImpCancel.style.display = 'none'; }
					}).catch(function (err) {
						btnImpNext.disabled = false;
						btnImpNext.textContent = 'Mulai Impor';
						impErr.textContent = err.message;
						impErr.hidden = false;
					});

				} else if (impStep === 3) {
					closeImpModal();
					window.location.hash = 'peserta-ditetapkan';
					window.location.reload();
				}
			});
		}

		/* Row Action Event Delegations */
		document.addEventListener('click', function (e) {
			var editBtn = e.target.closest('[data-edit-undangan]');
			if (editBtn) {
				var trE = editBtn.closest('tr');
				if (trE) {
					openUndModal({
						id: trE.getAttribute('data-id'),
						nama: trE.getAttribute('data-nama'),
						nik: trE.getAttribute('data-nik'),
						hp: trE.getAttribute('data-hp'),
						email_belajar: trE.getAttribute('data-email'),
						catatan: trE.getAttribute('data-catatan')
					});
				}
				return;
			}

			var delBtn = e.target.closest('[data-del-undangan]');
			if (delBtn) {
				var uIdD = delBtn.getAttribute('data-del-undangan');
				var trD = delBtn.closest('tr');
				var uNamaD = (trD ? trD.getAttribute('data-nama') : '') || 'calon peserta ini';
				modalConfirm({
					title: 'Hapus Peserta Ditetapkan',
					message: 'Hapus ' + uNamaD + ' dari daftar peserta yang ditetapkan?\n\nJika guru ini sudah mendaftar, pendaftarannya di pelatihan ini juga ikut dihapus dan tidak lagi muncul di Riwayat Pelatihan guru.',
					confirmText: 'Ya, Hapus',
					cancelText: 'Batal',
					type: 'danger'
				}).then(function (ok) {
					if (!ok) { return; }
					delBtn.disabled = true;
					api('admin/undangan/' + uIdD, 'DELETE').then(function () {
						window.location.hash = 'peserta-ditetapkan';
						window.location.reload();
					}).catch(function (err) {
						delBtn.disabled = false;
						modalAlert({ title: 'Gagal Menghapus', message: err.message, type: 'danger' });
					});
				});
				return;
			}

			var konfBtn = e.target.closest('[data-konfirmasi]');
			if (konfBtn) {
				var uIdK = konfBtn.getAttribute('data-konfirmasi');
				modalConfirm({
					title: 'Konfirmasi Peserta',
					message: 'Konfirmasi bahwa peserta ini sesuai dengan data penetapan?',
					confirmText: 'Ya, Konfirmasi',
					cancelText: 'Batal',
					type: 'primary'
				}).then(function (ok) {
					if (!ok) { return; }
					konfBtn.disabled = true;
					api('admin/undangan/' + uIdK + '/konfirmasi', 'POST').then(function () {
						window.location.hash = 'peserta-ditetapkan';
						window.location.reload();
					}).catch(function (err) {
						konfBtn.disabled = false;
						modalAlert({ title: 'Gagal Mengonfirmasi', message: err.message, type: 'danger' });
					});
				});
				return;
			}

			var lepasBtn = e.target.closest('[data-lepas]');
			if (lepasBtn) {
				var uIdL = lepasBtn.getAttribute('data-lepas');
				modalConfirm({
					title: 'Lepas Hubungan',
					message: 'Lepas hubungan antara peserta ditetapkan ini dengan peserta yang mendaftar?\nStatus akan kembali menjadi "Belum Mendaftar".',
					confirmText: 'Ya, Lepas Hubungan',
					cancelText: 'Batal',
					type: 'warning'
				}).then(function (ok) {
					if (!ok) { return; }
					lepasBtn.disabled = true;
					api('admin/undangan/' + uIdL + '/lepas', 'POST').then(function () {
						window.location.hash = 'peserta-ditetapkan';
						window.location.reload();
					}).catch(function (err) {
						lepasBtn.disabled = false;
						modalAlert({ title: 'Gagal Melepas Hubungan', message: err.message, type: 'danger' });
					});
				});
				return;
			}

			var hubBtn = e.target.closest('[data-hubungkan]');
			if (hubBtn) {
				var uIdH = hubBtn.getAttribute('data-hubungkan');
				var trH = hubBtn.closest('tr');
				openHubModal(uIdH, trH);
				return;
			}
		});
	}

	/* Modal Biodata Peserta */
	var modalBio = document.getElementById('kgtk-modal-biodata');
	if (modalBio) {
			var bioNama = document.getElementById('kgtk-bio-nama');
			var bioSub = document.getElementById('kgtk-bio-sub');
			var bioBody = document.getElementById('kgtk-bio-body');
			var btnBioPdf = document.getElementById('kgtk-btn-bio-pdf');
			var btnBioCetak = document.getElementById('kgtk-btn-bio-cetak');
			var btnBioClose = document.getElementById('kgtk-btn-bio-close');
			var btnBioBatal = document.getElementById('kgtk-btn-bio-batal');
			var activeBioData = null;

			var closeBioModal = function () {
				modalBio.classList.remove('is-visible');
				modalBio.style.display = 'none';
				activeBioData = null;
			};

			if (btnBioClose) { btnBioClose.addEventListener('click', closeBioModal); }
			if (btnBioBatal) { btnBioBatal.addEventListener('click', closeBioModal); }
			modalBio.addEventListener('click', function (e) {
				if (e.target === modalBio) { closeBioModal(); }
			});

			var escHtml = function (s) {
				return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
			};

			document.addEventListener('click', function (e) {
				var trigger = e.target.closest('[data-view-bio]');
				if (!trigger) { return; }
				e.preventDefault();
				var tr = trigger.closest('tr');
				if (!tr) { return; }
				var raw = tr.getAttribute('data-biodata');
				if (!raw) { return; }
				try {
					activeBioData = JSON.parse(raw);
				} catch (err) {
					return;
				}

				var b = activeBioData;
				var namaLengkap = formatNamaGelar(b.nama, b.gelar_depan, b.gelar_belakang);
				if (bioNama) { bioNama.textContent = namaLengkap; }
				if (bioSub) { bioSub.textContent = (b.sekolah || '') + (b.kabkota && b.kabkota !== '-' ? ' · ' + b.kabkota : ''); }
				if (btnBioPdf) { btnBioPdf.href = b.pdf_url || '#'; }

				var waNum = (b.hp || '').replace(/^0/, '62').replace(/\D/g, '');
				var waLink = waNum ? '<a href="https://wa.me/' + escHtml(waNum) + '" target="_blank" rel="noopener noreferrer" class="kgtk-wa-link" title="Kirim pesan WhatsApp"><span class="kgtk-wa-icon">&#128172;</span> ' + escHtml(b.hp) + '</a>' : escHtml(b.hp || '-');
				var emailLink = b.email && b.email !== '-' ? '<a href="mailto:' + escHtml(b.email) + '" style="color:var(--blue);text-decoration:none;">' + escHtml(b.email) + '</a>' : '-';
				var belajarLink = b.email_belajar && b.email_belajar !== '-' ? '<a href="mailto:' + escHtml(b.email_belajar) + '" style="color:var(--blue);text-decoration:none;">' + escHtml(b.email_belajar) + '</a>' : '<span class="kgtk-badge" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;font-size:11px;padding:2px 8px;border-radius:4px;">Belum ada Belajar.id</span>';

				var rows = [
					['Nama Lengkap', '<strong>' + escHtml(namaLengkap) + '</strong>'],
					['NIK', '<div class="kgtk-nik-cell"><code class="kgtk-nik-code">' + escHtml(b.nik) + '</code><button type="button" class="kgtk-copy-btn" data-copy-nik="' + escHtml(b.nik) + '" title="Salin NIK">&#128203;</button></div>'],
					['Tempat, Tanggal Lahir', escHtml((b.tempat_lahir && b.tempat_lahir !== '-' ? b.tempat_lahir + ', ' : '') + (b.tanggal_lahir || '-'))],
					['Jenis Kelamin', escHtml(b.jenis_kelamin || '-')],
					['NUPTK', escHtml(b.nuptk || '-')],
					['NIP', escHtml(b.nip || '-')],
					['Instansi', escHtml(b.sekolah || '-')],
					['Kabupaten/Kota', escHtml(b.kabkota || '-')],
					['Alamat Rumah / Tempat Tinggal', escHtml(b.alamat || '-')],
					['Alamat Sekolah / Instansi', escHtml(b.alamat_instansi || '-')],
					['Pangkat / Golongan', escHtml(b.pangkat_golongan || '-')],
					['Agama', escHtml(b.agama || '-')],
					['Pendidikan Terakhir', escHtml(b.pendidikan || '-')],
					['Jabatan', escHtml(b.jabatan || b.jenis_gtk || '-')],
					['Nama Bank', escHtml(b.bank || '-')],
					['Nomor Rekening', escHtml(b.rekening || '-')],
					['NPWP', escHtml(b.npwp || '-')],
					['Pengalaman Jabatan', escHtml(b.pengalaman_jabatan || '-')],
					['Peran', '<span class="kgtk-badge ' + (b.peran === 'Peserta' ? 'is-peserta' : 'is-lain') + '">' + escHtml(b.peran || 'Peserta') + '</span>'],
					['Kelas Saat Ini', '<span class="kgtk-badge ' + (b.kelas_id === 0 ? 'is-unassigned-tag' : 'is-assigned-tag') + '">' + escHtml(b.kelas_label || '-') + '</span>'],
					['No. HP / WhatsApp', waLink],
					['Email', emailLink],
					['Email Belajar.id', belajarLink],
					['SIMPKB', escHtml(b.simpkb || '-')],
					['Pelatihan', escHtml(b.pelatihan || '-')],
					['Waktu Mendaftar', '<span style="color:var(--muted);">' + escHtml(b.created_at || '-') + '</span>']
				];

				var h = '<table class="kgtk-table kgtk-table--bio" style="width:100%;border-collapse:collapse;margin:4px 0;"><tbody>';
				rows.forEach(function (r) {
					h += '<tr>';
					h += '<td style="width:34%;font-weight:600;color:var(--muted);padding:8px 10px;border-bottom:1px solid #f1f5f9;font-size:12.5px;vertical-align:middle;">' + r[0] + '</td>';
					h += '<td style="width:3%;text-align:center;color:var(--muted);padding:8px 0;border-bottom:1px solid #f1f5f9;font-size:12.5px;vertical-align:middle;">:</td>';
					h += '<td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;font-size:13px;vertical-align:middle;">' + r[1] + '</td>';
					h += '</tr>';
				});
				h += '</tbody></table>';

				if (bioBody) { bioBody.innerHTML = h; }
				modalBio.style.display = 'flex';
				modalBio.classList.add('is-visible');
			});

			if (btnBioCetak) {
				btnBioCetak.addEventListener('click', function () {
					if (!activeBioData) { return; }
					var b = activeBioData;

					function getKabkotaKegiatan(bio) {
						var kab = (bio && bio.pelatihan_kabkota) || '';
						var lok = (bio && bio.pelatihan_lok) || (typeof bio === 'string' ? bio : '');
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

					var peranUpper = (b.peran || 'PESERTA').toUpperCase();
					var lokasiTxt = b.pelatihan_lok ? (b.pelatihan_lok.toLowerCase().indexOf('di ') === 0 ? b.pelatihan_lok : 'di ' + b.pelatihan_lok) : '';
					var nipTxt = b.nip || '-';
					var kabkotaTxt = getKabkotaKegiatan(b);
					var namaLengkap = formatNamaGelar(b.nama, b.gelar_depan, b.gelar_belakang);

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

					var bioRows = [
						['NAMA', namaLengkap],
						['NIP', nipTxt],
						['TEMPAT/TANGGAL LAHIR', ttlTxt],
						['PANGKAT/GOLONGAN', b.pangkat_golongan || '-'],
						['PENDIDIKAN TERAKHIR', b.pendidikan || '-'],
						['JENIS KELAMIN', b.jenis_kelamin || '-'],
						['AGAMA', b.agama || '-'],
						['JABATAN', b.jabatan || b.jenis_gtk || '-'],
						['INSTANSI TEMPAT TUGAS', b.sekolah || '-'],
						['ALAMAT INSTANSI', b.alamat_instansi || '-'],
						['NO. HP/WHATSAPP', b.hp || '-'],
						['EMAIL', b.email || b.email_belajar || '-'],
						['NAMA BANK', b.bank || '-'],
						['NOMOR REKENING', b.rekening || '-']
					];

					var trRows = bioRows.map(function (r) {
						return '<tr><td class="lbl">' + escHtml(r[0]) + '</td><td class="sep">:</td><td class="val">' + escHtml(r[1] || '-') + '</td></tr>';
					}).join('');

					var kop = C.kop_html || '';

					var html = '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>&nbsp;</title>' +
						'<style>' +
						'@page{size:A4 portrait;margin:0!important}' +
						'body{font-family:\'Times New Roman\',Times,Georgia,serif;margin:0!important;padding:1.5cm 2.2cm 1.5cm 2.8cm!important;box-sizing:border-box!important;color:#000;line-height:1.35;background:#fff}' +
						'.kgtk-kop-wrap{width:100%;text-align:center;margin:0 auto 14px}' +
						'.kgtk-kop-tbl{margin:0 auto;border-collapse:collapse;border:0;width:100%;max-width:760px}' +
						'.kgtk-kop-tbl td{border:0!important;vertical-align:middle}' +
						'.title-box{text-align:center;margin:6pt 0 10pt;font-family:\'Times New Roman\',Times,Georgia,serif}' +
						'.title-main{font-size:15pt;font-weight:bold;text-transform:uppercase;margin-bottom:4px;letter-spacing:0.5px}' +
						'.title-sub{font-size:13pt;line-height:1.3;margin-bottom:2px;font-weight:bold}' +
						'.title-date{font-size:13pt;margin-bottom:2px}' +
						'.title-loc{font-size:13pt;margin-bottom:10pt}' +
						'table.bio-box{width:100%;border-collapse:collapse;border:1.5px solid #000;margin-bottom:14px}' +
						'table.bio-box td{padding:3.5px 10px;font-size:11.5pt;vertical-align:top;border:0!important}' +
						'td.lbl{width:34%}' +
						'td.sep{width:3%;text-align:center}' +
						'td.val{width:63%}' +
						'table.ttd-tbl{width:100%;border-collapse:collapse;margin-top:10px}' +
						'table.ttd-tbl td{border:0!important;vertical-align:top}' +
						'td.ttd-right{width:250px;text-align:center;font-size:12pt}' +
						'.space{height:50px}' +
						'@media print{body{padding:1.5cm 2.2cm 1.5cm 2.8cm!important}}' +
						'</style></head><body>' +
						kop +
						'<div class="title-box">' +
						'<div class="title-main">BIODATA ' + escHtml(peranUpper) + '</div>' +
						'<div class="title-sub">' + escHtml(b.pelatihan || '') + '</div>' +
						(b.pelatihan_tgl ? '<div class="title-date">' + escHtml(b.pelatihan_tgl) + '</div>' : '') +
						(lokasiTxt ? '<div class="title-loc">' + escHtml(lokasiTxt) + '</div>' : '') +
						'</div>' +
						'<table class="bio-box">' + trRows + '</table>' +
						'<table class="ttd-tbl"><tr><td>&nbsp;</td><td class="ttd-right">' +
						'<div>' + escHtml(kabkotaTxt) + ', ' + escHtml(tglCetak) + '</div>' +
						'<div class="space">&nbsp;</div>' +
						'<div><strong>' + escHtml(namaLengkap) + '</strong></div>' +
						'</td></tr></table>' +
						'<script>' +
						'window.addEventListener("load", function(){ setTimeout(function(){ window.focus(); window.print(); }, 250); });' +
						'<\/script>' +
						'</body></html>';

					var printViaIframe = function (docHtml) {
						var frame = document.getElementById('kgtk-print-biodata-frame');
						if (!frame) {
							frame = document.createElement('iframe');
							frame.id = 'kgtk-print-biodata-frame';
							frame.style.position = 'fixed';
							frame.style.right = '0';
							frame.style.bottom = '0';
							frame.style.width = '0';
							frame.style.height = '0';
							frame.style.border = '0';
							document.body.appendChild(frame);
						}
						var fDoc = frame.contentWindow || frame.contentDocument;
						if (fDoc.document) { fDoc = fDoc.document; }
						fDoc.open();
						fDoc.write(docHtml);
						fDoc.close();
						setTimeout(function () {
							try {
								frame.contentWindow.focus();
								frame.contentWindow.print();
							} catch (e) {
								window.print();
							}
						}, 350);
					};

					try {
						var w = window.open('', '_blank', 'width=850,height=800');
						if (w && !w.closed) {
							w.document.open();
							w.document.write(html);
							w.document.close();
							w.focus();
							setTimeout(function () {
								try {
									w.print();
								} catch (errP) {
									printViaIframe(html);
								}
							}, 400);
						} else {
							printViaIframe(html);
						}
					} catch (errOpen) {
						printViaIframe(html);
					}
				});
			}
		}

	/* Tombol Impor Data di Header Pelatihan */
	var btnImpHeader = document.getElementById('kgtk-btn-impor-header');
	if (btnImpHeader) {
		btnImpHeader.addEventListener('click', function () {
			var btnImp = document.getElementById('kgtk-btn-impor-undangan');
			if (btnImp) {
				btnImp.click();
			} else {
				var mImp = document.getElementById('kgtk-modal-impor');
				if (mImp) {
					mImp.style.display = 'flex';
					mImp.classList.add('is-visible');
				}
			}
		});
	}

	/* Modal Edit Data Peserta Pelatihan */
	var modalEditPs = document.getElementById('kgtk-modal-edit-peserta');
	if (modalEditPs) {
		var formEditPs = document.getElementById('kgtk-form-edit-peserta');
		var btnEditPsClose = document.getElementById('kgtk-btn-edit-peserta-close');
		var btnEditPsCancel = document.getElementById('kgtk-btn-edit-peserta-cancel');
		var btnEditPsSave = document.getElementById('kgtk-btn-edit-peserta-save');
		var errEditPs = document.getElementById('kgtk-edit-peserta-error');

		var closeEditPsModal = function () {
			modalEditPs.classList.remove('is-visible');
			modalEditPs.style.display = 'none';
			if (errEditPs) {
				errEditPs.hidden = true;
				errEditPs.textContent = '';
			}
		};

		if (btnEditPsClose) { btnEditPsClose.addEventListener('click', closeEditPsModal); }
		if (btnEditPsCancel) { btnEditPsCancel.addEventListener('click', closeEditPsModal); }
		modalEditPs.addEventListener('click', function (e) {
			if (e.target === modalEditPs) { closeEditPsModal(); }
		});

		var cleanVal = function (v) {
			if (v === null || v === undefined || v === '-') { return ''; }
			return String(v).trim();
		};

		var populateEditForm = function (d) {
			if (!formEditPs || !d) { return; }
			var f = formEditPs.elements;
			if (f.peserta_id) { f.peserta_id.value = d.id || d.peserta_id || '0'; }
			if (f.peran) { f.peran.value = cleanVal(d.peran) || 'Peserta'; }
			if (f.kelas_id) { f.kelas_id.value = d.kelas_id !== undefined ? String(d.kelas_id) : '0'; }
			if (f.nama) { f.nama.value = cleanVal(d.nama); }
			if (f.gelar_depan) { f.gelar_depan.value = cleanVal(d.gelar_depan); }
			if (f.gelar_belakang) { f.gelar_belakang.value = cleanVal(d.gelar_belakang); }
			if (f.nik) { f.nik.value = cleanVal(d.nik); }
			if (f.nip) { f.nip.value = cleanVal(d.nip); }
			if (f.tempat_lahir) { f.tempat_lahir.value = cleanVal(d.tempat_lahir); }
			if (f.tanggal_lahir) { f.tanggal_lahir.value = cleanVal(d.tanggal_lahir); }
			if (f.jenis_kelamin) { f.jenis_kelamin.value = cleanVal(d.jenis_kelamin); }
			if (f.agama) { f.agama.value = cleanVal(d.agama); }
			if (f.sekolah) { f.sekolah.value = cleanVal(d.sekolah); }
			if (f.kabkota) { f.kabkota.value = cleanVal(d.kabkota); }
			if (f.jenis_gtk) { f.jenis_gtk.value = cleanVal(d.jenis_gtk) || cleanVal(d.jabatan) || 'Guru'; }
			if (f.pangkat_golongan) { f.pangkat_golongan.value = cleanVal(d.pangkat_golongan); }
			if (f.pendidikan) { f.pendidikan.value = cleanVal(d.pendidikan); }
			if (f.nuptk) { f.nuptk.value = cleanVal(d.nuptk) || cleanVal(d.nisn); }
			if (f.hp) { f.hp.value = cleanVal(d.hp); }
			if (f.email) { f.email.value = cleanVal(d.email); }
			if (f.email_belajar) { f.email_belajar.value = cleanVal(d.email_belajar); }
			if (f.simpkb) { f.simpkb.value = cleanVal(d.simpkb) || cleanVal(d.email_guru); }
			if (f.bank) { f.bank.value = cleanVal(d.bank); }
			if (f.rekening) { f.rekening.value = cleanVal(d.rekening); }
			if (f.npwp) { f.npwp.value = cleanVal(d.npwp); }
			if (f.alamat) { f.alamat.value = cleanVal(d.alamat); }
			if (f.alamat_instansi) { f.alamat_instansi.value = cleanVal(d.alamat_instansi); }
			if (f.pengalaman_jabatan) { f.pengalaman_jabatan.value = cleanVal(d.pengalaman_jabatan); }
		};

		var openEditPsModal = function (pesertaId, initialData) {
			if (errEditPs) {
				errEditPs.hidden = true;
				errEditPs.textContent = '';
			}
			populateEditForm(initialData || { id: pesertaId });
			modalEditPs.style.display = 'flex';
			modalEditPs.classList.add('is-visible');

			// Ambil data terbaru dari server
			api('admin/peserta/' + pesertaId, 'GET').then(function (res) {
				if (res && res.peserta) {
					populateEditForm(res.peserta);
				}
			}).catch(function () {
				// Gunakan data initialData jika offline
			});
		};

		// Klik tombol edit di tabel peserta
		document.addEventListener('click', function (e) {
			var trigger = e.target.closest('[data-edit-peserta]');
			if (!trigger) { return; }
			e.preventDefault();
			var psId = trigger.getAttribute('data-edit-peserta');
			var tr = trigger.closest('tr');
			var bio = null;
			if (tr) {
				try {
					bio = JSON.parse(tr.getAttribute('data-biodata'));
				} catch (err) {}
			}
			openEditPsModal(psId, bio);
		});

		// Klik tombol edit di dalam modal biodata
		var btnBioEdit = document.getElementById('kgtk-btn-bio-edit');
		if (btnBioEdit) {
			btnBioEdit.addEventListener('click', function () {
				if (modalBio) {
					modalBio.classList.remove('is-visible');
					modalBio.style.display = 'none';
				}
				if (activeBioData && activeBioData.id) {
					openEditPsModal(activeBioData.id, activeBioData);
				}
			});
		}

		// Simpan perubahan peserta
		if (btnEditPsSave && formEditPs) {
			btnEditPsSave.addEventListener('click', function () {
				if (errEditPs) { errEditPs.hidden = true; }
				var f = formEditPs.elements;
				var psId = parseInt(f.peserta_id.value, 10);
				if (!psId) { return; }

				var nama = (f.nama.value || '').trim();
				var nik = (f.nik.value || '').trim();
				var hp = (f.hp.value || '').trim();
				var sekolah = (f.sekolah.value || '').trim();
				var kabkota = (f.kabkota.value || '').trim();
				var alamat = (f.alamat.value || '').trim();
				var peran = f.peran ? f.peran.value : 'Peserta';

				if (!nama) {
					errEditPs.textContent = 'Nama lengkap wajib diisi.';
					errEditPs.hidden = false;
					f.nama.focus();
					return;
				}
				if (peran !== 'Panitia' && !/^\d{16}$/.test(nik)) {
					errEditPs.textContent = 'NIK harus 16 digit angka.';
					errEditPs.hidden = false;
					f.nik.focus();
					return;
				}
				if (!hp) {
					errEditPs.textContent = 'Nomor HP/WhatsApp wajib diisi.';
					errEditPs.hidden = false;
					f.hp.focus();
					return;
				}
				if (!sekolah) {
					errEditPs.textContent = 'Asal sekolah / instansi wajib diisi.';
					errEditPs.hidden = false;
					f.sekolah.focus();
					return;
				}
				if (!kabkota) {
					errEditPs.textContent = 'Kabupaten / Kota wajib diisi.';
					errEditPs.hidden = false;
					f.kabkota.focus();
					return;
				}
				if (!alamat) {
					errEditPs.textContent = 'Alamat rumah wajib diisi.';
					errEditPs.hidden = false;
					f.alamat.focus();
					return;
				}

				btnEditPsSave.disabled = true;
				var oldText = btnEditPsSave.textContent;
				btnEditPsSave.textContent = 'Menyimpan...';

				var payload = {
					peran: peran,
					kelas_id: f.kelas_id ? parseInt(f.kelas_id.value, 10) : 0,
					nama: nama,
					gelar_depan: (f.gelar_depan.value || '').trim(),
					gelar_belakang: (f.gelar_belakang.value || '').trim(),
					nik: nik,
					nip: (f.nip.value || '').trim(),
					tempat_lahir: (f.tempat_lahir.value || '').trim(),
					tanggal_lahir: (f.tanggal_lahir.value || '').trim(),
					jenis_kelamin: f.jenis_kelamin ? f.jenis_kelamin.value : '',
					agama: f.agama ? f.agama.value : '',
					sekolah: sekolah,
					kabkota: kabkota,
					jenis_gtk: (f.jenis_gtk.value || '').trim(),
					pangkat_golongan: (f.pangkat_golongan.value || '').trim(),
					pendidikan: (f.pendidikan.value || '').trim(),
					nuptk: (f.nuptk.value || '').trim(),
					hp: hp,
					email: (f.email.value || '').trim(),
					email_belajar: (f.email_belajar.value || '').trim(),
					simpkb: (f.simpkb.value || '').trim(),
					bank: (f.bank.value || '').trim(),
					rekening: (f.rekening.value || '').trim(),
					npwp: (f.npwp.value || '').trim(),
					pengalaman_jabatan: (f.pengalaman_jabatan ? f.pengalaman_jabatan.value : '').trim() || '-',
					alamat: alamat,
					alamat_instansi: (f.alamat_instansi.value || '').trim()
				};

				api('admin/peserta/' + psId, 'PUT', payload).then(function () {
					closeEditPsModal();
					window.location.reload();
				}).catch(function (err) {
					btnEditPsSave.disabled = false;
					btnEditPsSave.textContent = oldText;
					errEditPs.textContent = err.message || 'Gagal menyimpan perubahan.';
					errEditPs.hidden = false;
				});
			});
		}
	}

	/* Modal Cepat Ubah Peran Guru / Peserta */
	var modalUbahPeran = document.getElementById('kgtk-modal-ubah-peran');
	if (modalUbahPeran) {
		var formUbahPeran = document.getElementById('kgtk-form-ubah-peran');
		var btnUbahPeranClose = document.getElementById('kgtk-btn-ubah-peran-close');
		var btnUbahPeranCancel = document.getElementById('kgtk-btn-ubah-peran-cancel');
		var btnUbahPeranSave = document.getElementById('kgtk-btn-ubah-peran-save');
		var errUbahPeran = document.getElementById('kgtk-ubah-peran-error');
		var txtUbahPeranNama = document.getElementById('kgtk-ubah-peran-nama');
		var txtUbahPeranSaatIni = document.getElementById('kgtk-ubah-peran-saat-ini');
		var selUbahPeran = document.getElementById('kgtk-ubah-peran-select');
		var inpUbahPeranId = document.getElementById('kgtk-ubah-peran-ps-id');

		var closeUbahPeranModal = function () {
			modalUbahPeran.classList.remove('is-visible');
			modalUbahPeran.style.display = 'none';
			if (errUbahPeran) {
				errUbahPeran.hidden = true;
				errUbahPeran.textContent = '';
			}
		};

		if (btnUbahPeranClose) { btnUbahPeranClose.addEventListener('click', closeUbahPeranModal); }
		if (btnUbahPeranCancel) { btnUbahPeranCancel.addEventListener('click', closeUbahPeranModal); }
		modalUbahPeran.addEventListener('click', function (e) {
			if (e.target === modalUbahPeran) { closeUbahPeranModal(); }
		});

		var openUbahPeranModal = function (psId, nama, peranSaatIni) {
			if (errUbahPeran) {
				errUbahPeran.hidden = true;
				errUbahPeran.textContent = '';
			}
			if (inpUbahPeranId) { inpUbahPeranId.value = psId; }
			if (txtUbahPeranNama) { txtUbahPeranNama.textContent = nama || '-'; }
			if (txtUbahPeranSaatIni) { txtUbahPeranSaatIni.textContent = peranSaatIni || 'Peserta'; }
			if (selUbahPeran) {
				// Jika saat ini peran bukan Peserta, defaultkan pilihan baru ke 'Peserta'
				selUbahPeran.value = (peranSaatIni === 'Peserta') ? 'Pengajar' : 'Peserta';
			}
			modalUbahPeran.style.display = 'flex';
			modalUbahPeran.classList.add('is-visible');
		};

		// Klik tombol / badge ubah peran di baris tabel
		document.addEventListener('click', function (e) {
			var trigger = e.target.closest('.kgtk-btn-ubah-peran');
			if (!trigger) { return; }
			e.preventDefault();
			var psId = trigger.getAttribute('data-id');
			var nama = trigger.getAttribute('data-nama');
			var peran = trigger.getAttribute('data-peran');
			openUbahPeranModal(psId, nama, peran);
		});

		// Submit form ubah peran
		if (formUbahPeran) {
			formUbahPeran.addEventListener('submit', function (e) {
				e.preventDefault();
				if (errUbahPeran) { errUbahPeran.hidden = true; }
				var psId = parseInt(inpUbahPeranId.value, 10);
				var newPeran = selUbahPeran ? selUbahPeran.value : '';
				if (!psId || !newPeran) { return; }

				btnUbahPeranSave.disabled = true;
				var oldText = btnUbahPeranSave.textContent;
				btnUbahPeranSave.textContent = 'Menyimpan...';

				api('admin/peserta/' + psId + '/peran', 'POST', { peran: newPeran }).then(function () {
					closeUbahPeranModal();
					window.location.reload();
				}).catch(function (err) {
					btnUbahPeranSave.disabled = false;
					btnUbahPeranSave.textContent = oldText;
					errUbahPeran.textContent = err.message || 'Gagal mengubah peran peserta.';
					errUbahPeran.hidden = false;
				});
			});
		}
	}

	/* Modal Pulihkan / Cari Guru di Master Data */
	var modalPulihkan = document.getElementById('kgtk-modal-pulihkan-guru');
	var btnOpenPulihkan = document.getElementById('kgtk-btn-open-pulihkan');
	if (modalPulihkan && btnOpenPulihkan) {
		var btnClosePulihkan = document.getElementById('kgtk-btn-pulihkan-close');
		var btnBatalPulihkan = document.getElementById('kgtk-btn-pulihkan-batal');
		var inpSearchPulihkan = document.getElementById('kgtk-pulihkan-search');
		var tbodyPulihkan = document.getElementById('kgtk-pulihkan-tbody');
		var tblPulihkan = document.getElementById('kgtk-tbl-pulihkan');
		var loadingPulihkan = document.getElementById('kgtk-pulihkan-loading');
		var emptyPulihkan = document.getElementById('kgtk-pulihkan-empty');
		var countPulihkan = document.getElementById('kgtk-pulihkan-count');
		var statusPulihkan = document.getElementById('kgtk-pulihkan-status');

		var tblPeserta = document.getElementById('kgtk-tbl-peserta');
		var pelatihanId = tblPeserta ? tblPeserta.getAttribute('data-pelatihan') : '0';

		var rawGuruList = [];

		var closePulihkanModal = function () {
			modalPulihkan.classList.remove('is-visible');
			modalPulihkan.style.display = 'none';
			if (statusPulihkan) { statusPulihkan.hidden = true; statusPulihkan.textContent = ''; }
		};

		if (btnClosePulihkan) { btnClosePulihkan.addEventListener('click', closePulihkanModal); }
		if (btnBatalPulihkan) { btnBatalPulihkan.addEventListener('click', closePulihkanModal); }
		modalPulihkan.addEventListener('click', function (e) {
			if (e.target === modalPulihkan) { closePulihkanModal(); }
		});

		var renderPulihkanList = function (filter) {
			if (!tbodyPulihkan) { return; }
			var q = (filter || '').trim().toLowerCase();
			var filtered = rawGuruList.filter(function (g) {
				if (!q) { return true; }
				var txt = ((g.nama_tampil || '') + ' ' + (g.nik || '') + ' ' + (g.sekolah || '') + ' ' + (g.kabkota || '') + ' ' + (g.hp || '')).toLowerCase();
				return txt.indexOf(q) !== -1;
			});

			if (countPulihkan) { countPulihkan.textContent = filtered.length; }

			if (filtered.length === 0) {
				tblPulihkan.style.display = 'none';
				emptyPulihkan.style.display = 'block';
				emptyPulihkan.textContent = q ? 'Tidak ada guru yang cocok dengan pencarian "' + filter + '".' : 'Tidak ada guru di Master Data yang belum terdaftar di pelatihan ini.';
				tbodyPulihkan.innerHTML = '';
				return;
			}

			emptyPulihkan.style.display = 'none';
			tblPulihkan.style.display = 'table';

			var rows = filtered.map(function (g, idx) {
				var sekolahTxt = esc(g.sekolah || '-');
				if (g.kabkota) { sekolahTxt += '<div style="font-size:11px;color:#64748b;">📍 ' + esc(g.kabkota) + '</div>'; }
				return '<tr>' +
					'<td style="text-align:center;color:#64748b;">' + (idx + 1) + '.</td>' +
					'<td><strong>' + esc(g.nama_tampil || g.nama) + '</strong>' + (g.hp ? '<div style="font-size:11px;color:#16a34a;">💬 ' + esc(g.hp) + '</div>' : '') + '</td>' +
					'<td><code style="font-size:11.5px;background:#f1f5f9;padding:2px 4px;border-radius:4px;">' + esc(g.nik) + '</code></td>' +
					'<td>' + sekolahTxt + '</td>' +
					'<td><span class="kgtk-badge" style="background:#e0f2fe;color:#0369a1;font-size:11px;padding:1px 6px;">' + esc(g.peran || 'Peserta') + '</span></td>' +
					'<td style="text-align:center;">' +
						'<button type="button" class="kgtk-btn kgtk-btn--navy kgtk-btn-pulihkan-act" data-nik="' + esc(g.nik) + '" data-nama="' + esc(g.nama_tampil || g.nama) + '" style="font-size:11.5px;padding:4px 10px;white-space:nowrap;">+ Daftarkan Kembali</button>' +
					'</td>' +
				'</tr>';
			}).join('');

			tbodyPulihkan.innerHTML = rows;
		};

		var loadGuruMaster = function () {
			loadingPulihkan.style.display = 'block';
			tblPulihkan.style.display = 'none';
			emptyPulihkan.style.display = 'none';
			if (statusPulihkan) { statusPulihkan.hidden = true; }

			api('admin/pelatihan/' + pelatihanId + '/guru-master', 'GET').then(function (res) {
				loadingPulihkan.style.display = 'none';
				rawGuruList = (res && Array.isArray(res.guru)) ? res.guru : [];
				renderPulihkanList(inpSearchPulihkan ? inpSearchPulihkan.value : '');
			}).catch(function (err) {
				loadingPulihkan.style.display = 'none';
				if (statusPulihkan) {
					statusPulihkan.textContent = err.message || 'Gagal memuat data guru dari Master Data.';
					statusPulihkan.hidden = false;
				}
			});
		};

		btnOpenPulihkan.addEventListener('click', function (e) {
			e.preventDefault();
			modalPulihkan.style.display = 'flex';
			modalPulihkan.classList.add('is-visible');
			if (inpSearchPulihkan) { inpSearchPulihkan.value = ''; }
			loadGuruMaster();
		});

		if (inpSearchPulihkan) {
			inpSearchPulihkan.addEventListener('input', function () {
				renderPulihkanList(this.value);
			});
		}

		// Klik aksi pulihkan
		if (tbodyPulihkan) {
			tbodyPulihkan.addEventListener('click', function (e) {
				var btn = e.target.closest('.kgtk-btn-pulihkan-act');
				if (!btn) { return; }
				var nik = btn.getAttribute('data-nik');
				var nama = btn.getAttribute('data-nama');
				if (!nik) { return; }

				btn.disabled = true;
				var oldTxt = btn.textContent;
				btn.textContent = 'Mendaftarkan...';

				api('admin/pelatihan/' + pelatihanId + '/pulihkan-guru', 'POST', { nik: nik }).then(function (res) {
					if (statusPulihkan) {
						statusPulihkan.className = 'kgtk-ok';
						statusPulihkan.textContent = res.pesan || ('Berhasil mendaftarkan kembali ' + nama + '!');
						statusPulihkan.hidden = false;
					}
					setTimeout(function () {
						window.location.reload();
					}, 900);
				}).catch(function (err) {
					btn.disabled = false;
					btn.textContent = oldTxt;
					if (statusPulihkan) {
						statusPulihkan.className = 'kgtk-alert';
						statusPulihkan.textContent = err.message || 'Gagal mendaftarkan kembali guru ini.';
						statusPulihkan.hidden = false;
					}
				});
			});
		}
	}

	/* Modal Export PDF */
	var btnExportPdf = document.getElementById('kgtk-btn-export-pdf');
	var modalExportPdf = document.getElementById('kgtk-modal-export-pdf');
	if (btnExportPdf && modalExportPdf) {
		var btnCloseExport = document.getElementById('kgtk-btn-export-pdf-close');
		var btnBatalExport = document.getElementById('kgtk-btn-export-pdf-batal');

		var closeExportModal = function () {
			modalExportPdf.classList.remove('is-visible');
			modalExportPdf.style.display = 'none';
		};

		btnExportPdf.addEventListener('click', function (e) {
			e.preventDefault();
			modalExportPdf.style.display = 'flex';
			modalExportPdf.classList.add('is-visible');
		});

		if (btnCloseExport) { btnCloseExport.addEventListener('click', closeExportModal); }
		if (btnBatalExport) { btnBatalExport.addEventListener('click', closeExportModal); }

		modalExportPdf.addEventListener('click', function (e) {
			if (e.target === modalExportPdf) {
				closeExportModal();
			}
		});

		var exportLinks = modalExportPdf.querySelectorAll('.kgtk-export-item');
		Array.prototype.forEach.call(exportLinks, function (link) {
			link.addEventListener('click', function () {
				setTimeout(closeExportModal, 500);
			});
		});
	}

	/* Modal Export Excel */
	var btnExportExcel = document.getElementById('kgtk-btn-export-excel');
	var modalExportExcel = document.getElementById('kgtk-modal-export-excel');
	if (btnExportExcel && modalExportExcel) {
		var btnCloseExportExcel = document.getElementById('kgtk-btn-export-excel-close');
		var btnBatalExportExcel = document.getElementById('kgtk-btn-export-excel-batal');

		var closeExportExcelModal = function () {
			modalExportExcel.classList.remove('is-visible');
			modalExportExcel.style.display = 'none';
		};

		btnExportExcel.addEventListener('click', function (e) {
			e.preventDefault();
			modalExportExcel.style.display = 'flex';
			modalExportExcel.classList.add('is-visible');
		});

		if (btnCloseExportExcel) { btnCloseExportExcel.addEventListener('click', closeExportExcelModal); }
		if (btnBatalExportExcel) { btnBatalExportExcel.addEventListener('click', closeExportExcelModal); }

		modalExportExcel.addEventListener('click', function (e) {
			if (e.target === modalExportExcel) {
				closeExportExcelModal();
			}
		});

		var exportExcelLinks = modalExportExcel.querySelectorAll('.kgtk-export-item');
		Array.prototype.forEach.call(exportExcelLinks, function (link) {
			link.addEventListener('click', function () {
				setTimeout(closeExportExcelModal, 500);
			});
		});
	}

	/* Sinkronisasi Pilihan Hari / Tanggal Absen */
	var dateSelects = document.querySelectorAll('.kgtk-select-tanggal-absen');
	var exportDateSelect = document.getElementById('kgtk-export-pdf-tanggal');

	var safeEsc = function (s) {
		return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
	};

	function updateExportLinksDate(val) {
		var modalExport = document.getElementById('kgtk-modal-export-pdf');
		if (!modalExport) { return; }
		var links = modalExport.querySelectorAll('.kgtk-export-item');
		Array.prototype.forEach.call(links, function (link) {
			var href = link.getAttribute('href');
			if (!href || link.hasAttribute('data-skip-date') || href.indexOf('kgtk_semua_biodata_pdf') !== -1) { return; }
			var re = /([?&])tanggal=[^&]*(&|$)/;
			var cleanHref = href.replace(re, function (m, p1, p2) {
				return p2 === '&' ? p1 : '';
			}).replace(/(&|\?)$/, '');

			if (val) {
				var sep = cleanHref.indexOf('?') !== -1 ? '&' : '?';
				link.setAttribute('href', cleanHref + sep + 'tanggal=' + encodeURIComponent(val));
			} else {
				link.setAttribute('href', cleanHref);
			}
		});
	}

	function updateAttendanceDate(val, label) {
		if (!val) { val = 'all_days'; }

		// 1. Sinkronisasi semua select dropdown tanggal di halaman
		Array.prototype.forEach.call(dateSelects, function (sel) {
			if (sel.value !== val) {
				sel.value = val;
			}
		});

		// 2. Sinkronisasi select dropdown di modal export PDF
		if (exportDateSelect && exportDateSelect.value !== val) {
			exportDateSelect.value = val;
		}
		updateExportLinksDate(val);

		// 3. Tampilkan lembar yang sesuai pada setiap tab (.kgtk-sheet-day)
		var sheetParents = document.querySelectorAll('.kgtk-sheet');
		Array.prototype.forEach.call(sheetParents, function (sheet) {
			var dayElements = sheet.querySelectorAll('.kgtk-sheet-day');
			if (dayElements.length === 0) { return; }
			var visibleList = [];
			Array.prototype.forEach.call(dayElements, function (ds) {
				var dayVal = ds.getAttribute('data-day');
				var show = false;
				if (val === 'all_days' || val === '' || val === 'all' || val === 'semua') {
					show = (dayVal !== 'range');
				} else if (val === 'range') {
					show = (dayVal === 'range');
				} else {
					show = (dayVal === val);
				}
				ds.classList.toggle('is-hidden', !show);
				ds.classList.remove('is-last-print-day');
				if (show) {
					visibleList.push(ds);
				}
			});
			if (visibleList.length > 0) {
				visibleList[visibleList.length - 1].classList.add('is-last-print-day');
			}
		});

		// 4. Update teks label tombol Cetak di setiap subtab (hanya untuk toolbar lembar absen yang punya select tanggal)
		var printButtons = document.querySelectorAll('.kgtk-toolbar [data-cetak]');
		Array.prototype.forEach.call(printButtons, function (btn) {
			if (btn.closest('[data-sub="tanda-terima-peserta"]') || btn.id === 'kgtk-btn-cetak-tt') {
				return;
			}
			var base = btn.getAttribute('data-base-label');
			if (!base) {
				base = btn.textContent.replace(/^[🖨️\s\uD83D\uDDA4\uFE0F]+/, '').replace(/\s*\([^)]*\)$/, '').trim();
				btn.setAttribute('data-base-label', base);
			}
			var suffix = '';
			if (val === 'all_days' || val === '' || val === 'all' || val === 'semua') {
				suffix = ' (Semua Hari)';
			} else if (val === 'range') {
				suffix = ' (Rentang)';
			} else if (label) {
				var m = label.match(/^(Hari\s+ke-\d+)/i);
				suffix = m ? ' (' + m[1] + ')' : ' (' + label + ')';
			} else {
				if (dateSelects.length > 0) {
					var firstSel = dateSelects[0];
					var opt = firstSel.querySelector('option[value="' + val + '"]');
					if (opt && opt.textContent) {
						var m2 = opt.textContent.match(/^(Hari\s+ke-\d+)/i);
						suffix = m2 ? ' (' + m2[1] + ')' : '';
					}
				}
			}
			btn.innerHTML = '&#128424;&#65039; ' + safeEsc(base + suffix);
		});

		// 5. Update teks tanggal bila ada elemen .kgtk-doc-tanggal tunggal (fallback tanpa CSS :has agar kompatibel semua browser)
		var allDocDates = document.querySelectorAll('.kgtk-sheet .kgtk-doc-tanggal');
		var docDates = Array.prototype.filter.call(allDocDates, function (el) {
			return !el.closest('.kgtk-sheet-day');
		});
		if (docDates.length === 0 && document.querySelectorAll('.kgtk-sheet-day').length === 0) {
			docDates = document.querySelectorAll('.kgtk-doc-tanggal');
		}
		Array.prototype.forEach.call(docDates, function (el) {
			if (label) {
				el.textContent = label;
			} else {
				var def = el.getAttribute('data-tgl-default');
				if (def) {
					el.textContent = def;
				}
			}
		});
	}

	if (dateSelects.length > 0) {
		Array.prototype.forEach.call(dateSelects, function (sel) {
			sel.addEventListener('change', function () {
				var opt = this.options[this.selectedIndex];
				var label = opt ? opt.getAttribute('data-label') : '';
				updateAttendanceDate(this.value, label);
			});
		});
		// Inisialisasi awal agar tombol cetak dan hari terakhir siap cetak
		var initialSel = dateSelects[0];
		var initialOpt = initialSel.options[initialSel.selectedIndex];
		var initialLabel = initialOpt ? initialOpt.getAttribute('data-label') : '';
		updateAttendanceDate(initialSel.value, initialLabel);
	}

	if (exportDateSelect) {
		exportDateSelect.addEventListener('change', function () {
			var opt = this.options[this.selectedIndex];
			var label = opt ? opt.getAttribute('data-label') : '';
			updateAttendanceDate(this.value, label);
		});
	}

	/* ==========================================================================
	   Modul Manajemen Data Master Panitia (Pelatihan KGTK > Data Panitia)
	   ========================================================================== */
	function initMasterPanitia() {
		var tbl = document.getElementById('kgtk-tbl-panitia-master');
		var formModal = document.getElementById('kgtk-modal-form-panitia');
		var formPanitia = document.getElementById('kgtk-form-panitia-master');
		var btnTambah = document.getElementById('kgtk-btn-tambah-panitia-master');
		var btnEmptyTambah = document.getElementById('kgtk-btn-empty-tambah-panitia');
		var searchInput = document.getElementById('kgtk-search-panitia-master');
		var errBox = document.getElementById('kgtk-panitia-form-error');

		if (!formModal && !tbl) {
			return; // Bukan halaman Data Panitia
		}

		var rNik = document.getElementById('kgtk-pan-radio-id-nik');
		var rNip = document.getElementById('kgtk-pan-radio-id-nip');
		var wrapNik = document.getElementById('kgtk-pan-wrap-nik');
		var wrapNipTop = document.getElementById('kgtk-pan-wrap-nip-top');
		var wrapNipBottom = document.getElementById('kgtk-pan-wrap-nip-bottom');
		var inpNik = document.getElementById('kgtk-pan-field-nik');
		var inpNipTop = document.getElementById('kgtk-pan-field-nip-top');
		var inpNipBottom = document.getElementById('kgtk-pan-field-nip');

		var updateMasterPanitiaIdView = function () {
			var pakaiNip = rNip && rNip.checked;
			if (pakaiNip) {
				if (wrapNik) { wrapNik.style.display = 'none'; }
				if (wrapNipTop) { wrapNipTop.style.display = ''; }
				if (wrapNipBottom) { wrapNipBottom.style.display = 'none'; }
				if (inpNipTop) { inpNipTop.focus(); }
			} else {
				if (wrapNik) { wrapNik.style.display = ''; }
				if (wrapNipTop) { wrapNipTop.style.display = 'none'; }
				if (wrapNipBottom) { wrapNipBottom.style.display = ''; }
			}
		};

		if (rNik) { rNik.addEventListener('change', updateMasterPanitiaIdView); }
		if (rNip) { rNip.addEventListener('change', updateMasterPanitiaIdView); }

		var openModal = function (title, data) {
			if (!formModal) { return; }
			var titleEl = document.getElementById('kgtk-modal-panitia-title');
			if (titleEl) { titleEl.textContent = title || 'Tambah Data Panitia'; }
			if (errBox) { errBox.hidden = true; errBox.textContent = ''; }

			if (formPanitia) {
				formPanitia.reset();
				var fId = document.getElementById('kgtk-pan-field-id');
				if (fId) { fId.value = (data && data.id) ? data.id : 0; }
				if (data) {
					var isNipOnly = (!data.nik || data.nik.indexOf('99') === 0) && !!data.nip;
					if (isNipOnly) {
						if (rNip) { rNip.checked = true; }
					} else {
						if (rNik) { rNik.checked = true; }
					}

					var fMap = {
						'kgtk-pan-field-nama': data.nama || '',
						'kgtk-pan-field-gelar-depan': data.gelar_depan || '',
						'kgtk-pan-field-gelar-belakang': data.gelar_belakang || '',
						'kgtk-pan-field-jabatan': data.jabatan || 'Panitia Pelaksana',
						'kgtk-pan-field-nik': (data.nik && data.nik.indexOf('99') !== 0) ? data.nik : '',
						'kgtk-pan-field-nip': data.nip || '',
						'kgtk-pan-field-nip-top': data.nip || '',
						'kgtk-pan-field-instansi': data.instansi || '',
						'kgtk-pan-field-kabkota': data.kabkota || '',
						'kgtk-pan-field-hp': data.hp || '',
						'kgtk-pan-field-email': data.email || '',
						'kgtk-pan-field-bank': data.bank || '',
						'kgtk-pan-field-rekening': data.rekening || ''
					};
					for (var fieldId in fMap) {
						var el = document.getElementById(fieldId);
						if (el) { el.value = fMap[fieldId]; }
					}
				} else {
					if (rNik) { rNik.checked = true; }
					var jEl = document.getElementById('kgtk-pan-field-jabatan');
					if (jEl) { jEl.value = 'Panitia Pelaksana'; }
				}
				updateMasterPanitiaIdView();
			}

			if (formModal) {
				formModal.style.display = 'flex';
				formModal.classList.add('is-visible');
			}
			var firstInp = document.getElementById('kgtk-pan-field-nama');
			if (firstInp) { firstInp.focus(); }
		};

		var closeModal = function () {
			if (formModal) {
				formModal.classList.remove('is-visible');
				formModal.style.display = 'none';
			}
		};

		if (btnTambah) {
			btnTambah.addEventListener('click', function () {
				openModal('Tambah Data Panitia', null);
			});
		}
		if (btnEmptyTambah) {
			btnEmptyTambah.addEventListener('click', function () {
				openModal('Tambah Data Panitia', null);
			});
		}

		var btnClose = document.getElementById('kgtk-btn-form-panitia-close');
		var btnBatal = document.getElementById('kgtk-btn-form-panitia-batal');
		if (btnClose) { btnClose.addEventListener('click', closeModal); }
		if (btnBatal) { btnBatal.addEventListener('click', closeModal); }
		if (formModal) {
			formModal.addEventListener('click', function (e) {
				if (e.target === formModal) { closeModal(); }
			});
		}

		// Input formatting
		['kgtk-pan-field-nik'].forEach(function (id) {
			var el = document.getElementById(id);
			if (el) {
				el.addEventListener('input', function (e) {
					e.target.value = e.target.value.replace(/\D/g, '').slice(0, 16);
				});
			}
		});
		if (inpNipTop) {
			inpNipTop.addEventListener('input', function (e) {
				e.target.value = e.target.value.replace(/\D/g, '').slice(0, 18);
				if (inpNipBottom) { inpNipBottom.value = e.target.value; }
			});
		}
		if (inpNipBottom) {
			inpNipBottom.addEventListener('input', function (e) {
				e.target.value = e.target.value.replace(/\D/g, '').slice(0, 18);
				if (inpNipTop && inpNipTop.value !== e.target.value) { inpNipTop.value = e.target.value; }
			});
		}
		var rekMasterEl = document.getElementById('kgtk-pan-field-rekening');
		if (rekMasterEl) {
			rekMasterEl.addEventListener('input', function (e) {
				e.target.value = e.target.value.replace(/[^\d\- ]/g, '').slice(0, 30);
			});
		}

		// Edit & Delete button clicks pada tabel
		if (tbl) {
			tbl.addEventListener('click', function (e) {
				var editBtn = e.target.closest('[data-edit-panitia]');
				if (editBtn) {
					var tr = editBtn.closest('tr[data-json]');
					if (!tr) { return; }
					var raw = tr.getAttribute('data-json');
					var data = {};
					try { data = JSON.parse(raw); } catch (ex) {}
					openModal('Edit Data Panitia', data);
					return;
				}

				var delBtn = e.target.closest('[data-del-panitia]');
				if (delBtn) {
					var id = delBtn.getAttribute('data-del-panitia');
					var nama = delBtn.getAttribute('data-nama') || 'panitia ini';
					modalConfirm({
						title: 'Hapus Data Panitia',
						message: 'Hapus panitia "' + nama + '" dari daftar tersimpan?\n\nRiwayat penugasan panitia di kegiatan pelatihan sebelumnya tetap aman, namun data profilnya tidak akan muncul lagi di daftar pilihan cepat.',
						confirmText: 'Ya, Hapus',
						cancelText: 'Batal',
						type: 'danger'
					}).then(function (ok) {
						if (!ok) { return; }
						delBtn.disabled = true;
						api('admin/panitia/' + id, 'DELETE').then(function () {
							var row = delBtn.closest('tr');
							if (row) {
								row.style.transition = 'all 0.3s ease';
								row.style.opacity = '0';
								setTimeout(function () {
									row.remove();
									var rows = tbl.querySelectorAll('tbody tr');
									Array.prototype.forEach.call(rows, function (r, idx) {
										var num = r.querySelector('.kgtk-row-num');
										if (num) { num.textContent = (idx + 1) + '.'; }
									});
									if (!rows.length) {
										window.location.reload();
									}
								}, 300);
							}
						}).catch(function (err) {
							delBtn.disabled = false;
							modalAlert({ title: 'Gagal Menghapus', message: err.message, type: 'danger' });
						});
					});
				}
			});
		}

		// Live Search
		if (searchInput && tbl) {
			searchInput.addEventListener('input', function (e) {
				var q = (e.target.value || '').trim().toLowerCase();
				var rows = tbl.querySelectorAll('tbody tr[data-search]');
				var visibleIdx = 1;
				Array.prototype.forEach.call(rows, function (row) {
					var s = row.getAttribute('data-search') || '';
					var match = (!q || s.indexOf(q) > -1);
					row.style.display = match ? '' : 'none';
					if (match) {
						var numEl = row.querySelector('.kgtk-row-num');
						if (numEl) { numEl.textContent = visibleIdx + '.'; }
						visibleIdx++;
					}
				});
			});
		}

		// Simpan form panitia (POST / PUT)
		if (formPanitia) {
			formPanitia.addEventListener('submit', function (e) {
				e.preventDefault();
				if (errBox) { errBox.hidden = true; errBox.textContent = ''; }

				var f = formPanitia.elements;
				var id = parseInt(f.id.value || '0', 10);
				var nama = (f.nama.value || '').trim();
				var gelarDepan = (f.gelar_depan ? f.gelar_depan.value : '').trim();
				var gelarBelakang = (f.gelar_belakang ? f.gelar_belakang.value : '').trim();
				var jabatan = (f.jabatan.value || '').trim();
				var nik = (f.nik ? f.nik.value : '').trim();
				var nip = (f.nip ? f.nip.value : '').trim();
				var instansi = (f.instansi ? f.instansi.value : '').trim();
				var kabkota = (f.kabkota ? f.kabkota.value : '').trim();
				var hp = (f.hp ? f.hp.value : '').trim();
				var email = (f.email ? f.email.value : '').trim();
				var bank = (f.bank ? f.bank.value : '').trim();
				var rekening = (f.rekening ? f.rekening.value : '').trim();

				var pakaiNip = rNip && rNip.checked;
				if (pakaiNip && inpNipTop && inpNipTop.value) {
					nip = inpNipTop.value.trim();
				}

				if (pakaiNip) {
					if (!/^\d{18}$/.test(nip)) {
						if (errBox) { errBox.textContent = 'NIP panitia harus 18 digit angka.'; errBox.hidden = false; }
						if (inpNipTop) { inpNipTop.focus(); }
						return;
					}
				} else {
					if (!/^\d{16}$/.test(nik)) {
						if (errBox) { errBox.textContent = 'NIK panitia harus 16 digit angka.'; errBox.hidden = false; }
						if (f.nik) { f.nik.focus(); }
						return;
					}
					if (nip && !/^\d{18}$/.test(nip)) {
						if (errBox) { errBox.textContent = 'NIP harus berupa 18 digit angka (atau kosongkan bila bukan PNS).'; errBox.hidden = false; }
						if (f.nip) { f.nip.focus(); }
						return;
					}
				}

				if (!nama) {
					if (errBox) { errBox.textContent = 'Nama lengkap panitia wajib diisi.'; errBox.hidden = false; }
					f.nama.focus();
					return;
				}
				if (!jabatan) {
					if (errBox) { errBox.textContent = 'Tugas / jabatan panitia wajib diisi.'; errBox.hidden = false; }
					f.jabatan.focus();
					return;
				}
				if (!hp) {
					if (errBox) { errBox.textContent = 'Nomor HP / WhatsApp wajib diisi.'; errBox.hidden = false; }
					f.hp.focus();
					return;
				}

				var saveBtn = document.getElementById('kgtk-btn-form-panitia-save');
				if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = 'Menyimpan...'; }

				var payload = {
					nama: nama,
					gelar_depan: gelarDepan,
					gelar_belakang: gelarBelakang,
					jabatan: jabatan,
					nik: pakaiNip ? '' : nik,
					nip: nip,
					instansi: instansi,
					kabkota: kabkota,
					hp: hp,
					email: email,
					bank: bank,
					rekening: rekening,
					id_type: pakaiNip ? 'nip' : 'nik'
				};

				var method = id > 0 ? 'PUT' : 'POST';
				var path = id > 0 ? 'admin/panitia/' + id : 'admin/panitia';

				api(path, method, payload).then(function (res) {
					closeModal();
					if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Simpan Data Panitia'; }
					var item = (res && res.item) ? res.item : payload;
					item.id = item.id || id;
					if (id > 0 && tbl) {
						var tr = tbl.querySelector('tbody tr[data-id="' + id + '"]');
						if (tr) {
							var namaEl = tr.querySelector('.kgtk-guru-nama');
							if (namaEl) { namaEl.textContent = item.nama; }
							var nikCell = tr.querySelector('.kgtk-nik-cell');
							if (nikCell) {
								if (item.nik && item.nik.indexOf('99') !== 0) {
									nikCell.innerHTML = '<code class="kgtk-nik-code" style="font-size:11.5px;">' + escHtml(item.nik) + '</code>';
								} else {
									nikCell.innerHTML = '';
								}
							}
							var badgeEl = tr.querySelector('.kgtk-badge');
							if (badgeEl) { badgeEl.textContent = '💼 ' + (item.jabatan || 'Panitia Pelaksana'); }
							var instEl = tr.querySelector('.kgtk-sekolah-nama');
							if (instEl) { instEl.textContent = item.instansi || '-'; }
							var waNum = (item.hp || '').replace(/\D/g, '').replace(/^0/, '62');
							var kontakCell = tr.children[5];
							if (kontakCell) {
								var kHtml = '<div style="font-size:12.5px;display:flex;flex-direction:column;gap:3px;">';
								if (item.hp) {
									kHtml += '<a href="https://wa.me/' + escHtml(waNum) + '" target="_blank" rel="noopener noreferrer" class="kgtk-wa-link" title="Chat WhatsApp"><span class="kgtk-wa-icon">&#128172;</span> ' + escHtml(item.hp) + '</a>';
								}
								if (item.email) {
									kHtml += '<a href="mailto:' + escHtml(item.email) + '" style="color:#2563eb;text-decoration:none;font-size:11.5px;">&#9993; ' + escHtml(item.email) + '</a>';
								}
								if (item.bank || item.rekening) {
									kHtml += '<div class="kgtk-muted" style="font-size:11px;color:#0b2d56;background:#f8fafc;padding:2px 6px;border-radius:4px;border:1px solid #e2e8f0;margin-top:2px;">&#127974; <strong>' + escHtml(item.bank || 'Bank') + ':</strong> ' + escHtml(item.rekening || '-') + '</div>';
								}
								kHtml += '</div>';
								kontakCell.innerHTML = kHtml;
							}
							var sText = (item.nama + ' ' + (item.nik || '') + ' ' + (item.nip || '') + ' ' + (item.jabatan || '') + ' ' + (item.instansi || '') + ' ' + (item.hp || '')).toLowerCase();
							tr.setAttribute('data-search', sText);
							tr.setAttribute('data-json', JSON.stringify(item));
							tOk('Data master panitia ' + item.nama + ' berhasil diperbarui.');
							return;
						}
					}
					window.location.reload();
				}).catch(function (err) {
					if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Simpan Data Panitia'; }
					if (errBox) {
						errBox.textContent = err.message || 'Gagal menyimpan data panitia.';
						errBox.hidden = false;
					}
				});
			});
		}
	}
	try {
		initMasterPanitia();
	} catch (errPanitia) {
		console.error('KGTK Init MasterPanitia Error:', errPanitia);
	}

	/* ==========================================================================
	   MODAL EDIT DATA PELATIHAN
	   ========================================================================== */
	function syncEditPelatihanJenisUI(jenisVal) {
		var isAcara = (jenisVal === 'acara');
		var rPel = document.getElementById('kgtk-edit-pel-jenis-pelatihan');
		var rAca = document.getElementById('kgtk-edit-pel-jenis-acara');
		var cPel = document.getElementById('kgtk-edit-tipe-pelatihan-card');
		var cAca = document.getElementById('kgtk-edit-tipe-acara-card');
		if (rPel) { rPel.checked = !isAcara; }
		if (rAca) { rAca.checked = isAcara; }
		if (cPel) {
			cPel.classList.toggle('is-active', !isAcara);
			cPel.style.borderColor = !isAcara ? '#2563eb' : '#cbd5e1';
			cPel.style.background = !isAcara ? '#eff6ff' : '#fff';
		}
		if (cAca) {
			cAca.classList.toggle('is-active', isAcara);
			cAca.style.borderColor = isAcara ? '#0d9488' : '#cbd5e1';
			cAca.style.background = isAcara ? '#f0fdfa' : '#fff';
		}
		var mTitle = document.getElementById('kgtk-edit-pel-modal-title');
		if (mTitle) {
			mTitle.textContent = isAcara ? 'Edit Data Acara' : 'Edit Data Pelatihan';
		}
		var lblNama = document.getElementById('kgtk-edit-pel-label-nama');
		if (lblNama) {
			lblNama.innerHTML = isAcara ? 'Nama acara / kegiatan <b>*</b>' : 'Nama pelatihan <b>*</b>';
		}
		var inNama = document.getElementById('kgtk-edit-pel-nama');
		if (inNama) {
			inNama.placeholder = isAcara ? 'Nama acara / kegiatan' : 'Nama pelatihan';
		}
	}

	function openEditPelatihanModal(data) {
		var modal = document.getElementById('kgtk-modal-edit-pelatihan');
		var form = document.getElementById('kgtk-form-edit-pelatihan');
		var errBox = document.getElementById('kgtk-edit-pel-error');
		if (!modal || !form) {
			return;
		}
		if (!data) {
			return;
		}
		if (errBox) {
			errBox.hidden = true;
			errBox.textContent = '';
		}

		var f = form.elements;
		if (f.id) { f.id.value = data.id || '0'; }
		var jenisVal = (data.jenis === 'acara') ? 'acara' : 'pelatihan';
		syncEditPelatihanJenisUI(jenisVal);
		if (f.nama) { f.nama.value = data.nama || ''; }
		if (f.penyelenggara) { f.penyelenggara.value = data.penyelenggara || ''; }
		if (f.tanggal_mulai) { f.tanggal_mulai.value = data.tanggal_mulai || ''; }
		if (f.tanggal_selesai) { f.tanggal_selesai.value = data.tanggal_selesai || data.tanggal_mulai || ''; }
		if (f.deadline_pendaftaran) {
			if (data.deadline_pendaftaran) {
				f.deadline_pendaftaran.value = data.deadline_pendaftaran.replace(' ', 'T').slice(0, 16);
			} else {
				f.deadline_pendaftaran.value = '';
			}
		}
		if (f.lokasi) { f.lokasi.value = data.lokasi || ''; }
		if (f.kabkota) { f.kabkota.value = data.kabkota || ''; }
		if (f.tipe_pj) { f.tipe_pj.value = (data.tipe_pj === 'Ketua') ? 'Ketua' : 'Penanggung Jawab'; }
		if (f.penanggung_jawab) { f.penanggung_jawab.value = data.penanggung_jawab || ''; }
		if (f.kode) { f.kode.value = data.kode || ''; }
		if (f.keterangan) { f.keterangan.value = data.keterangan || ''; }

		var dlWrap = document.getElementById('kgtk-edit-pel-kelas-deadlines-wrap');
		var dlList = document.getElementById('kgtk-edit-pel-kelas-deadlines-list');
		if (dlWrap && dlList) {
			dlList.innerHTML = '';
			var refreshEditKelasReorder = function (list) {
				if (!list) { return; }
				var rows = list.querySelectorAll('.kgtk-edit-existing-kelas-row');
				for (var ri = 0; ri < rows.length; ri++) {
					var r = rows[ri];
					var numBadge = r.querySelector('.kgtk-ek-num');
					if (numBadge) { numBadge.textContent = (ri + 1) + '.'; }
					var upBtn = r.querySelector('.kgtk-btn-ek-up');
					var downBtn = r.querySelector('.kgtk-btn-ek-down');
					if (upBtn) {
						upBtn.disabled = (ri === 0);
						upBtn.style.opacity = (ri === 0) ? '0.3' : '1';
						upBtn.style.cursor = (ri === 0) ? 'not-allowed' : 'pointer';
					}
					if (downBtn) {
						downBtn.disabled = (ri === rows.length - 1);
						downBtn.style.opacity = (ri === rows.length - 1) ? '0.3' : '1';
						downBtn.style.cursor = (ri === rows.length - 1) ? 'not-allowed' : 'pointer';
					}
				}
			};

			var rawK = Array.isArray(data.kelas) ? data.kelas : [];
			if (rawK.length > 0) {
				rawK.forEach(function (k, kIdx) {
					var kId = k && k.id ? parseInt(k.id, 10) : 0;
					var kNama = k && k.nama ? String(k.nama) : '';
					if (!kId || !kNama) { return; }
					var km = k.tanggal_mulai ? String(k.tanggal_mulai) : '';
					var ks = k.tanggal_selesai ? String(k.tanggal_selesai) : '';
					var kd = k.deadline_pendaftaran ? String(k.deadline_pendaftaran).replace(' ', 'T').slice(0, 16) : '';
					var itemRow = document.createElement('div');
					itemRow.className = 'kgtk-edit-existing-kelas-row';
					itemRow.setAttribute('data-edit-kelas-id', String(kId));
					itemRow.style.cssText = 'display:flex;flex-wrap:wrap;align-items:center;gap:8px;padding:8px 10px;background:#fff;border:1px solid #cbd5e1;border-radius:8px;';
					itemRow.innerHTML =
						'<div style="display:inline-flex;flex-direction:column;gap:2px;margin-right:2px;">' +
							'<button type="button" class="kgtk-btn-ek-up" title="Pindah urutan ke atas" style="border:1px solid #cbd5e1;background:#f8fafc;color:#0b2d56;padding:0 5px;line-height:12px;height:15px;font-size:9px;border-radius:3px;">&#9650;</button>' +
							'<button type="button" class="kgtk-btn-ek-down" title="Pindah urutan ke bawah" style="border:1px solid #cbd5e1;background:#f8fafc;color:#0b2d56;padding:0 5px;line-height:12px;height:15px;font-size:9px;border-radius:3px;">&#9660;</button>' +
						'</div>' +
						'<span class="kgtk-ek-num" style="font-size:12px;font-weight:700;color:#64748b;min-width:18px;text-align:center;">' + (kIdx + 1) + '.</span>' +
						'<label class="kgtk-slot" style="font-size:12px;font-weight:600;color:#0b2d56;">Nama Kelas * ' +
							'<input type="text" class="kgtk-input" data-ek-nama value="' + esc(kNama) + '" maxlength="30" required placeholder="Nama kelas" style="width:145px;min-height:32px;padding:4px 8px;font-weight:700;color:#0b2d56;">' +
						'</label>' +
						'<label class="kgtk-slot" style="font-size:12px;">Mulai <input type="date" class="kgtk-input" data-ek-mulai style="width:130px;padding:4px 6px;min-height:32px;"></label>' +
						'<label class="kgtk-slot" style="font-size:12px;">Selesai <input type="date" class="kgtk-input" data-ek-selesai style="width:130px;padding:4px 6px;min-height:32px;"></label>' +
						'<label class="kgtk-slot" style="font-size:12px;">Batas Daftar <input type="datetime-local" class="kgtk-input" data-ek-deadline style="width:165px;padding:4px 6px;min-height:32px;"></label>';
					var mIn = itemRow.querySelector('[data-ek-mulai]');
					var sIn = itemRow.querySelector('[data-ek-selesai]');
					var dIn = itemRow.querySelector('[data-ek-deadline]');
					if (mIn) { mIn.value = km; }
					if (sIn) { sIn.value = ks; }
					if (dIn) { dIn.value = kd; }

					var upBtn = itemRow.querySelector('.kgtk-btn-ek-up');
					var downBtn = itemRow.querySelector('.kgtk-btn-ek-down');
					if (upBtn) {
						upBtn.addEventListener('click', function () {
							var prev = itemRow.previousElementSibling;
							if (prev) {
								dlList.insertBefore(itemRow, prev);
								refreshEditKelasReorder(dlList);
							}
						});
					}
					if (downBtn) {
						downBtn.addEventListener('click', function () {
							var next = itemRow.nextElementSibling;
							if (next) {
								dlList.insertBefore(next, itemRow);
								refreshEditKelasReorder(dlList);
							}
						});
					}

					dlList.appendChild(itemRow);
				});
				refreshEditKelasReorder(dlList);
				dlWrap.style.display = 'block';
			} else {
				dlWrap.style.display = 'none';
			}
		}

		modal.style.display = 'flex';
		modal.classList.add('is-visible');
		if (f.nama) { f.nama.focus(); }
	}

	function closeEditPelatihanModal() {
		var modal = document.getElementById('kgtk-modal-edit-pelatihan');
		if (modal) {
			modal.classList.remove('is-visible');
			modal.style.display = 'none';
		}
	}

	// Delegated listener untuk membuka modal Edit Pelatihan (tahan timing render)
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-edit-pelatihan]');
		if (btn) {
			var raw = btn.getAttribute('data-pelatihan');
			var data = {};
			try { data = JSON.parse(raw); } catch (ex) {}
			openEditPelatihanModal(data);
			return;
		}
		var detailBtn = e.target.closest('#kgtk-btn-edit-pelatihan-detail');
		if (detailBtn) {
			var rawD = detailBtn.getAttribute('data-pelatihan');
			var dataD = {};
			try { dataD = JSON.parse(rawD); } catch (ex) {}
			openEditPelatihanModal(dataD);
			return;
		}
		// Tutup modal edit pelatihan
		var modal = document.getElementById('kgtk-modal-edit-pelatihan');
		if (modal && modal.classList.contains('is-visible')) {
			if (e.target === modal || e.target.closest('#kgtk-btn-edit-pelatihan-close') || e.target.closest('#kgtk-btn-edit-pelatihan-batal')) {
				closeEditPelatihanModal();
			}
		}
	});

	function initEditPelatihan() {
		var modal = document.getElementById('kgtk-modal-edit-pelatihan');
		var form = document.getElementById('kgtk-form-edit-pelatihan');
		var errBox = document.getElementById('kgtk-edit-pel-error');
		if (!modal || !form) {
			return;
		}

		var kodeInput = document.getElementById('kgtk-edit-pel-kode');
		if (kodeInput) {
			kodeInput.addEventListener('input', function (e) {
				e.target.value = e.target.value.toUpperCase().replace(/[^A-Z0-9-]/g, '');
			});
		}

		var jenisRadios = form.querySelectorAll('input[name="jenis"]');
		for (var ji = 0; ji < jenisRadios.length; ji++) {
			jenisRadios[ji].addEventListener('change', function () {
				var checkedJenis = form.querySelector('input[name="jenis"]:checked');
				syncEditPelatihanJenisUI(checkedJenis ? checkedJenis.value : 'pelatihan');
			});
		}

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			if (errBox) {
				errBox.hidden = true;
				errBox.textContent = '';
			}

			var f = form.elements;
			var id = parseInt(f.id.value || '0', 10);
			var jenisChecked = form.querySelector('input[name="jenis"]:checked');
			var jenis = (jenisChecked && jenisChecked.value === 'acara') ? 'acara' : 'pelatihan';
			var nama = (f.nama.value || '').trim();
			var penyelenggara = (f.penyelenggara.value || '').trim();
			var mulai = (f.tanggal_mulai.value || '').trim();
			var selesai = (f.tanggal_selesai.value || '').trim() || mulai;
			var deadline = (f.deadline_pendaftaran ? f.deadline_pendaftaran.value : '').trim();
			var lokasi = (f.lokasi.value || '').trim();
			var kabkota = (f.kabkota ? f.kabkota.value : '').trim();
			var tipePj = (f.tipe_pj.value || 'Penanggung Jawab').trim();
			var penanggungJawab = (f.penanggung_jawab.value || '').trim();
			var kode = (f.kode.value || '').trim().toUpperCase();
			var keterangan = (f.keterangan.value || '').trim();

			if (!nama) {
				if (errBox) { errBox.textContent = 'Nama pelatihan wajib diisi.'; errBox.hidden = false; }
				f.nama.focus();
				return;
			}
			if (!mulai) {
				if (errBox) { errBox.textContent = 'Tanggal mulai wajib diisi.'; errBox.hidden = false; }
				f.tanggal_mulai.focus();
				return;
			}
			if (selesai < mulai) {
				if (errBox) { errBox.textContent = 'Tanggal selesai tidak boleh sebelum tanggal mulai.'; errBox.hidden = false; }
				f.tanggal_selesai.focus();
				return;
			}
			if (!kode || !/^[A-Za-z0-9-]{3,20}$/.test(kode)) {
				if (errBox) { errBox.textContent = 'Kode pelatihan harus 3-20 karakter: huruf, angka, tanda hubung.'; errBox.hidden = false; }
				f.kode.focus();
				return;
			}

			var existingKelasEdit = [];
			var dlList = document.getElementById('kgtk-edit-pel-kelas-deadlines-list');
			if (dlList) {
				var ekRows = dlList.querySelectorAll('[data-edit-kelas-id]');
				var seenNames = {};
				for (var ei = 0; ei < ekRows.length; ei++) {
					var ekRow = ekRows[ei];
					var ekId = parseInt(ekRow.getAttribute('data-edit-kelas-id') || '0', 10);
					var eknIn = ekRow.querySelector('[data-ek-nama]');
					var ekn = (eknIn ? eknIn.value : '').trim();
					var ekm = (ekRow.querySelector('[data-ek-mulai]').value || '').trim();
					var eks = (ekRow.querySelector('[data-ek-selesai]').value || '').trim();
					var ekd = (ekRow.querySelector('[data-ek-deadline]').value || '').trim();

					if (!ekn) {
						if (errBox) { errBox.textContent = 'Nama setiap kelas tidak boleh kosong.'; errBox.hidden = false; }
						if (eknIn) { eknIn.focus(); }
						return;
					}
					var eknKey = ekn.toLowerCase();
					if (seenNames[eknKey]) {
						if (errBox) { errBox.textContent = 'Nama kelas "' + ekn + '" duplikat. Nama kelas dalam satu pelatihan harus berbeda.'; errBox.hidden = false; }
						if (eknIn) { eknIn.focus(); }
						return;
					}
					seenNames[eknKey] = true;

					if (ekm && eks && eks < ekm) {
						if (errBox) { errBox.textContent = 'Tanggal selesai kelas tidak boleh sebelum tanggal mulai.'; errBox.hidden = false; }
						return;
					}
					existingKelasEdit.push({
						id: ekId,
						nama: ekn,
						urutan: ei + 1,
						tanggal_mulai: ekm || (eks ? eks : ''),
						tanggal_selesai: eks || (ekm ? ekm : ''),
						deadline_pendaftaran: ekd
					});
				}
			}

			var simpanBtn = document.getElementById('kgtk-btn-edit-pelatihan-simpan');
			if (simpanBtn) {
				simpanBtn.disabled = true;
				simpanBtn.textContent = 'Menyimpan...';
			}

			api('admin/pelatihan/' + id, 'PUT', {
				nama: nama,
				jenis: jenis,
				penyelenggara: penyelenggara,
				tanggal_mulai: mulai,
				tanggal_selesai: selesai,
				deadline_pendaftaran: deadline,
				lokasi: lokasi,
				kabkota: kabkota,
				tipe_pj: tipePj,
				penanggung_jawab: penanggungJawab,
				kode: kode,
				keterangan: keterangan,
				existing_kelas: existingKelasEdit
			}).then(function () {
				closeEditPelatihanModal();
				window.location.reload();
			}).catch(function (err) {
				if (simpanBtn) {
					simpanBtn.disabled = false;
					simpanBtn.innerHTML = '&#128190; Simpan Perubahan';
				}
				if (errBox) {
					errBox.textContent = err.message || 'Gagal memperbarui data pelatihan.';
					errBox.hidden = false;
				}
			});
		});
	}
	try {
		initEditPelatihan();
	} catch (errEditPel) {
		console.error('KGTK Init EditPelatihan Error:', errEditPel);
	}

	/* ==========================================================================
	   MODAL TAMBAH KELAS BARU PELATIHAN
	   ========================================================================== */
	var currentExistingKelasNames = {};

	function createKelolaKelasRow(item) {
		var row = document.createElement('div');
		row.className = 'kgtk-kelas-row';
		var nama = item && item.nama ? String(item.nama) : '';
		var slot = item && item.slot ? parseInt(item.slot, 10) : 30;
		var tglMulai = item && item.tanggal_mulai ? String(item.tanggal_mulai) : '';
		var tglSelesai = item && item.tanggal_selesai ? String(item.tanggal_selesai) : '';
		var deadline = item && item.deadline_pendaftaran ? String(item.deadline_pendaftaran) : '';
		row.innerHTML =
			'<span class="kgtk-kelas-no" aria-hidden="true"></span>' +
			'<input type="text" class="kgtk-input" data-knama maxlength="30" placeholder="Nama kelas baru (mis. Kelas PAUD / SD)" aria-label="Nama kelas baru" required>' +
			'<label class="kgtk-slot">Slot <input type="number" class="kgtk-input" data-kslot min="1" max="2000" value="30" required></label>' +
			'<label class="kgtk-slot kgtk-kelas-tgl" title="Tanggal Mulai Kelas (opsional)">Mulai <input type="date" class="kgtk-input" data-ktgl-mulai aria-label="Tanggal mulai kelas"></label>' +
			'<label class="kgtk-slot kgtk-kelas-tgl" title="Tanggal Selesai Kelas (opsional)">Selesai <input type="date" class="kgtk-input" data-ktgl-selesai aria-label="Tanggal selesai kelas"></label>' +
			'<label class="kgtk-slot kgtk-kelas-tgl" title="Batas Waktu Pendaftaran Kelas (opsional)">Batas Daftar <input type="datetime-local" class="kgtk-input" data-kdeadline style="width:182px;" aria-label="Batas pendaftaran kelas"></label>' +
			'<div class="kgtk-row-actions">' +
				'<button type="button" class="kgtk-iconbtn" data-up data-move="up" title="Geser ke atas" aria-label="Geser ke atas">&#8593;</button>' +
				'<button type="button" class="kgtk-iconbtn" data-down data-move="down" title="Geser ke bawah" aria-label="Geser ke bawah">&#8595;</button>' +
				'<button type="button" class="kgtk-iconbtn kgtk-iconbtn--del" data-del title="Hapus baris" aria-label="Hapus baris">&times;</button>' +
			'</div>';
		var namaInput = row.querySelector('[data-knama]');
		var slotInput = row.querySelector('[data-kslot]');
		var mulaiInput = row.querySelector('[data-ktgl-mulai]');
		var selesaiInput = row.querySelector('[data-ktgl-selesai]');
		var dlInput = row.querySelector('[data-kdeadline]');
		if (namaInput) { namaInput.value = nama; }
		if (slotInput) { slotInput.value = String(slot > 0 ? slot : 30); }
		if (mulaiInput && tglMulai) { mulaiInput.value = tglMulai; }
		if (selesaiInput && tglSelesai) { selesaiInput.value = tglSelesai; }
		if (dlInput && deadline) { dlInput.value = deadline; }
		return row;
	}

	function refreshKelolaKelasRows() {
		var list = document.getElementById('kgtk-kelola-kelas-list');
		if (!list) { return; }
		var rows = list.querySelectorAll('.kgtk-kelas-row');
		Array.prototype.forEach.call(rows, function (r, i) {
			var noEl = r.querySelector('.kgtk-kelas-no');
			if (noEl) { noEl.textContent = String(i + 1); }
			var upBtn = r.querySelector('[data-up], [data-move="up"]');
			var downBtn = r.querySelector('[data-down], [data-move="down"]');
			var delBtn = r.querySelector('[data-del]');
			if (upBtn) { upBtn.disabled = (i === 0); }
			if (downBtn) { downBtn.disabled = (i === rows.length - 1); }
			if (delBtn) { delBtn.disabled = (rows.length <= 1); }
		});
	}

	function openKelolaKelasModal(pelatihanId, kelasListArr, focusKelasId) {
		var modal = document.getElementById('kgtk-modal-kelola-kelas');
		var form = document.getElementById('kgtk-form-kelola-kelas');
		var errBox = document.getElementById('kgtk-kelola-kelas-error');
		var list = document.getElementById('kgtk-kelola-kelas-list');
		var chipsBox = document.getElementById('kgtk-existing-kelas-chips');
		var existingBox = document.getElementById('kgtk-existing-kelas-box');
		if (!modal || !form || !list) {
			return;
		}
		if (errBox) {
			errBox.hidden = true;
			errBox.textContent = '';
		}
		var idInput = document.getElementById('kgtk-kelola-kelas-pelatihan-id');
		if (idInput) {
			idInput.value = String(pelatihanId || 0);
		}

		currentExistingKelasNames = {};
		var rawKelas = Array.isArray(kelasListArr) ? kelasListArr : [];
		var focusTargetInput = null;
		if (chipsBox) {
			chipsBox.innerHTML = '';
			rawKelas.forEach(function (k) {
				var kId = k && k.id ? parseInt(k.id, 10) : 0;
				var kNama = k && k.nama ? String(k.nama).trim() : '';
				if (kNama) {
					currentExistingKelasNames[kNama.toLowerCase()] = true;
					var terisi = k.terisi ? parseInt(k.terisi, 10) : 0;
					var slot = k.slot ? parseInt(k.slot, 10) : 30;
					var km = k.tanggal_mulai ? String(k.tanggal_mulai) : '';
					var ks = k.tanggal_selesai ? String(k.tanggal_selesai) : '';
					var kd = k.deadline_pendaftaran ? String(k.deadline_pendaftaran).replace(' ', 'T').slice(0, 16) : '';

					var exRow = document.createElement('div');
					exRow.setAttribute('data-existing-kelas-id', String(kId));
					exRow.setAttribute('data-existing-kelas-nama', kNama);
					exRow.style.cssText = 'display:flex;flex-wrap:wrap;align-items:center;gap:8px;padding:8px 10px;background:#fff;border:1px solid #cbd5e1;border-radius:8px;';
					exRow.innerHTML =
						'<div style="min-width:140px;flex:1 1 140px;">' +
							'<strong style="font-size:13px;color:#0b2d56;display:block;">' + esc(kNama) + '</strong>' +
							'<span style="font-size:11.5px;color:#64748b;">' + terisi + '/' + slot + ' slot terisi</span>' +
						'</div>' +
						'<label class="kgtk-slot" style="font-size:12px;">Mulai <input type="date" class="kgtk-input" data-ex-mulai style="width:136px;padding:4px 6px;min-height:32px;"></label>' +
						'<label class="kgtk-slot" style="font-size:12px;">Selesai <input type="date" class="kgtk-input" data-ex-selesai style="width:136px;padding:4px 6px;min-height:32px;"></label>' +
						'<label class="kgtk-slot" style="font-size:12px;">Batas Daftar <input type="datetime-local" class="kgtk-input" data-ex-deadline style="width:180px;padding:4px 6px;min-height:32px;"></label>';
					var mIn = exRow.querySelector('[data-ex-mulai]');
					var sIn = exRow.querySelector('[data-ex-selesai]');
					var dIn = exRow.querySelector('[data-ex-deadline]');
					if (mIn) { mIn.value = km; }
					if (sIn) { sIn.value = ks; }
					if (dIn) {
						dIn.value = kd;
						if (focusKelasId && parseInt(focusKelasId, 10) === kId) {
							focusTargetInput = dIn;
							exRow.style.borderColor = '#0284c7';
							exRow.style.boxShadow = '0 0 0 2px rgba(2,132,199,0.18)';
						}
					}
					chipsBox.appendChild(exRow);
				}
			});
		}
		if (existingBox) {
			existingBox.style.display = rawKelas.length > 0 ? 'block' : 'none';
		}

		// Selalu mulai dengan 1 baris kosong untuk Kelas Baru agar tidak menimpa kelas lama
		list.innerHTML = '';
		var firstNewRow = createKelolaKelasRow({ nama: '', slot: 30 });
		list.appendChild(firstNewRow);
		refreshKelolaKelasRows();

		modal.style.display = 'flex';
		modal.classList.add('is-visible');
		if (focusTargetInput) {
			focusTargetInput.focus();
		} else {
			var firstInput = firstNewRow.querySelector('[data-knama]');
			if (firstInput) {
				firstInput.focus();
			}
		}
	}

	function closeKelolaKelasModal() {
		var modal = document.getElementById('kgtk-modal-kelola-kelas');
		if (modal) {
			modal.classList.remove('is-visible');
			modal.style.display = 'none';
		}
	}

	document.addEventListener('click', function (e) {
		var dlBtn = e.target.closest('[data-atur-deadline-kelas]');
		if (dlBtn) {
			e.stopPropagation();
			var kIdFocus = parseInt(dlBtn.getAttribute('data-atur-deadline-kelas') || '0', 10);
			var mainBtn = document.getElementById('kgtk-btn-kelola-kelas');
			if (mainBtn) {
				var pelId = parseInt(mainBtn.getAttribute('data-pelatihan-id') || '0', 10);
				var rawK = mainBtn.getAttribute('data-kelas') || '[]';
				var kelasArr = [];
				try { kelasArr = JSON.parse(rawK); } catch (ex) {}
				openKelolaKelasModal(pelId, kelasArr, kIdFocus);
			}
			return;
		}
		var btn = e.target.closest('#kgtk-btn-kelola-kelas');
		if (btn) {
			var pelId2 = parseInt(btn.getAttribute('data-pelatihan-id') || '0', 10);
			var rawK2 = btn.getAttribute('data-kelas') || '[]';
			var kelasArr2 = [];
			try { kelasArr2 = JSON.parse(rawK2); } catch (ex) {}
			openKelolaKelasModal(pelId2, kelasArr2, 0);
			return;
		}
		var modal = document.getElementById('kgtk-modal-kelola-kelas');
		if (modal && modal.classList.contains('is-visible')) {
			if (e.target === modal || e.target.closest('#kgtk-btn-kelola-kelas-close') || e.target.closest('#kgtk-btn-kelola-kelas-batal')) {
				closeKelolaKelasModal();
			}
		}
	});

	function initKelolaKelas() {
		var modal = document.getElementById('kgtk-modal-kelola-kelas');
		var form = document.getElementById('kgtk-form-kelola-kelas');
		var errBox = document.getElementById('kgtk-kelola-kelas-error');
		var list = document.getElementById('kgtk-kelola-kelas-list');
		var addBtn = document.getElementById('kgtk-kelola-kelas-add');
		if (!modal || !form || !list) {
			return;
		}

		if (addBtn) {
			addBtn.addEventListener('click', function () {
				var rows = list.querySelectorAll('.kgtk-kelas-row');
				var existingCount = Object.keys(currentExistingKelasNames).length;
				if (existingCount + rows.length >= 20) {
					if (errBox) {
						errBox.textContent = 'Maksimal total 20 kelas per pelatihan.';
						errBox.hidden = false;
					}
					return;
				}
				var newRow = createKelolaKelasRow({ nama: '', slot: 30 });
				list.appendChild(newRow);
				refreshKelolaKelasRows();
				var namaInput = newRow.querySelector('[data-knama]');
				if (namaInput) {
					namaInput.focus();
				}
			});
		}

		list.addEventListener('click', function (e) {
			var b = e.target.closest('button');
			if (!b || b.disabled) { return; }
			var row = b.closest('.kgtk-kelas-row');
			if (!row) { return; }
			if (b.hasAttribute('data-del')) {
				var rows = list.querySelectorAll('.kgtk-kelas-row');
				if (rows.length <= 1) { return; }
				row.remove();
			} else if ((b.hasAttribute('data-up') || b.getAttribute('data-move') === 'up') && row.previousElementSibling) {
				list.insertBefore(row, row.previousElementSibling);
			} else if ((b.hasAttribute('data-down') || b.getAttribute('data-move') === 'down') && row.nextElementSibling) {
				list.insertBefore(row.nextElementSibling, row);
			}
			refreshKelolaKelasRows();
		});

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			if (errBox) {
				errBox.hidden = true;
				errBox.textContent = '';
			}

			var idInput = document.getElementById('kgtk-kelola-kelas-pelatihan-id');
			var pelatihanId = idInput ? parseInt(idInput.value || '0', 10) : 0;
			var chipsBox = document.getElementById('kgtk-existing-kelas-chips');
			var existingKelasUpdates = [];
			var valid = true;

			if (chipsBox) {
				var exRows = chipsBox.querySelectorAll('[data-existing-kelas-id]');
				Array.prototype.forEach.call(exRows, function (exRow) {
					if (!valid) { return; }
					var exId = parseInt(exRow.getAttribute('data-existing-kelas-id') || '0', 10);
					var exNama = exRow.getAttribute('data-existing-kelas-nama') || '';
					var exmEl = exRow.querySelector('[data-ex-mulai]');
					var exsEl = exRow.querySelector('[data-ex-selesai]');
					var exdEl = exRow.querySelector('[data-ex-deadline]');
					var exm = exmEl ? (exmEl.value || '').trim() : '';
					var exs = exsEl ? (exsEl.value || '').trim() : '';
					var exd = exdEl ? (exdEl.value || '').trim() : '';
					if (exm && exs && exs < exm) {
						if (errBox) { errBox.textContent = 'Tanggal selesai kelas "' + exNama + '" tidak boleh sebelum tanggal mulai.'; errBox.hidden = false; }
						if (exsEl) { exsEl.focus(); }
						valid = false;
						return;
					}
					existingKelasUpdates.push({
						id: exId,
						tanggal_mulai: exm || (exs ? exs : ''),
						tanggal_selesai: exs || (exm ? exm : ''),
						deadline_pendaftaran: exd
					});
				});
			}

			var rows = list.querySelectorAll('.kgtk-kelas-row');
			var kelas = [];
			var seenKelas = {};

			Array.prototype.forEach.call(rows, function (row) {
				if (!valid) { return; }
				var namaEl = row.querySelector('[data-knama]');
				var slotEl = row.querySelector('[data-kslot]');
				var kNama = namaEl ? (namaEl.value || '').trim() : '';
				var kSlot = slotEl ? parseInt(slotEl.value, 10) : 0;
				if (!kNama && existingKelasUpdates.length > 0) {
					return; // Abaikan baris kelas baru yang dikosongkan bila sedang menyimpan deadline kelas lama
				}
				if (!kNama || kNama.length > 30) {
					if (errBox) { errBox.textContent = 'Nama kelas baru wajib diisi (maksimal 30 karakter).'; errBox.hidden = false; }
					if (namaEl) { namaEl.focus(); }
					valid = false;
					return;
				}
				var kKey = kNama.toLowerCase();
				if (currentExistingKelasNames[kKey]) {
					if (errBox) { errBox.textContent = 'Kelas "' + kNama + '" sudah ada pada pelatihan ini.'; errBox.hidden = false; }
					if (namaEl) { namaEl.focus(); }
					valid = false;
					return;
				}
				if (seenKelas[kKey]) {
					if (errBox) { errBox.textContent = 'Nama kelas baru tidak boleh kembar: "' + kNama + '".'; errBox.hidden = false; }
					if (namaEl) { namaEl.focus(); }
					valid = false;
					return;
				}
				seenKelas[kKey] = true;
				if (!kSlot || kSlot < 1 || kSlot > 2000) {
					if (errBox) { errBox.textContent = 'Slot peserta per kelas harus antara 1 dan 2000.'; errBox.hidden = false; }
					if (slotEl) { slotEl.focus(); }
					valid = false;
					return;
				}
				var kmEl = row.querySelector('[data-ktgl-mulai]');
				var ksEl = row.querySelector('[data-ktgl-selesai]');
				var kdEl = row.querySelector('[data-kdeadline]');
				var km = kmEl ? (kmEl.value || '').trim() : '';
				var ks = ksEl ? (ksEl.value || '').trim() : '';
				var kd = kdEl ? (kdEl.value || '').trim() : '';
				if (km && ks && ks < km) {
					if (errBox) { errBox.textContent = 'Tanggal selesai kelas "' + kNama + '" tidak boleh sebelum tanggal mulai.'; errBox.hidden = false; }
					if (ksEl) { ksEl.focus(); }
					valid = false;
					return;
				}
				kelas.push({
					nama: kNama,
					slot: kSlot,
					tanggal_mulai: km || (ks ? ks : ''),
					tanggal_selesai: ks || (km ? km : ''),
					deadline_pendaftaran: kd
				});
			});

			if (!valid) { return; }
			if (!kelas.length && !existingKelasUpdates.length) {
				if (errBox) { errBox.textContent = 'Tambahkan minimal satu kelas baru.'; errBox.hidden = false; }
				return;
			}

			var simpanBtn = document.getElementById('kgtk-btn-kelola-kelas-simpan');
			if (simpanBtn) {
				simpanBtn.disabled = true;
				simpanBtn.textContent = 'Menyimpan...';
			}

			api('admin/pelatihan/' + pelatihanId + '/kelas', 'PUT', {
				existing_kelas: existingKelasUpdates,
				kelas: kelas
			}).then(function () {
				closeKelolaKelasModal();
				window.location.reload();
			}).catch(function (err) {
				if (simpanBtn) {
					simpanBtn.disabled = false;
					simpanBtn.innerHTML = '&#128190; Tambah Kelas';
				}
				if (errBox) {
					errBox.textContent = err.message || 'Gagal menambahkan kelas baru.';
					errBox.hidden = false;
				}
			});
		});
	}
	try {
		initKelolaKelas();
	} catch (errKelolaKelas) {
		console.error('KGTK Init KelolaKelas Error:', errKelolaKelas);
	}

	/* ==========================================================================
	   PANE PANITIA PELATIHAN & LEMBAR HADIR PANITIA
	   ========================================================================== */
	function initPanitiaPelatihan() {
		var container = document.getElementById('kgtk-panitia-container');
		if (!container) {
			return;
		}

		var pelId = container.getAttribute('data-pelatihan') || '0';
		var activePanFilter = 'all';

		// Live search & filter kelas tabel panitia
		var panSearch = document.getElementById('kgtk-panitia-pel-search');
		var panTable = document.getElementById('kgtk-tbl-panitia-pelatihan');
		var panStatCards = container.querySelectorAll('[data-pan-filter]');
		var panCheckAll = document.getElementById('kgtk-pan-check-all');
		var panThCheckAll = document.getElementById('kgtk-pan-th-check-all');
		var panSelectedBadge = document.getElementById('kgtk-pan-selected-badge');
		var panBulkTarget = document.getElementById('kgtk-pan-bulk-target');
		var panBulkApply = document.getElementById('kgtk-pan-bulk-apply');

		function updatePanBulkState() {
			if (!panTable) { return; }
			var checks = panTable.querySelectorAll('tbody .kgtk-pan-check');
			var checkedCount = 0;
			var visibleCount = 0;
			var visibleCheckedCount = 0;
			Array.prototype.forEach.call(checks, function (cb) {
				var tr = cb.closest('tr');
				var isVis = tr && tr.style.display !== 'none';
				if (isVis) {
					visibleCount++;
					if (cb.checked) { visibleCheckedCount++; }
				}
				if (cb.checked) {
					checkedCount++;
					if (tr) { tr.classList.add('is-row-checked'); }
				} else if (tr) {
					tr.classList.remove('is-row-checked');
				}
			});
			var allVisChecked = visibleCount > 0 && visibleCheckedCount === visibleCount;
			if (panCheckAll) { panCheckAll.checked = allVisChecked; }
			if (panThCheckAll) { panThCheckAll.checked = allVisChecked; }
			if (panSelectedBadge) {
				panSelectedBadge.style.display = checkedCount > 0 ? 'inline-block' : 'none';
				panSelectedBadge.textContent = checkedCount + ' dipilih';
			}
			if (panBulkApply) {
				var hasTarget = panBulkTarget && panBulkTarget.value !== '';
				panBulkApply.disabled = !(checkedCount > 0 && hasTarget);
			}
		}

		function recalcPanitiaStats() {
			var rows = panTable ? panTable.querySelectorAll('tbody tr[data-id]') : document.querySelectorAll('#kgtk-tbl-peserta tbody tr[data-is-panitia="1"]');
			var total = rows.length;
			var unassigned = 0;
			var byKelas = {};
			Array.prototype.forEach.call(rows, function (tr) {
				var kid = parseInt(tr.getAttribute('data-kelas') || '0', 10) || 0;
				if (kid > 0) {
					byKelas[kid] = (byKelas[kid] || 0) + 1;
				} else {
					unassigned++;
				}
			});
			var statTotal = document.getElementById('kgtk-pan-stat-total');
			if (statTotal) { statTotal.textContent = String(total); }
			var statZero = document.getElementById('kgtk-pan-stat-0');
			if (statZero) { statZero.textContent = String(unassigned); }
			var cardZero = container ? container.querySelector('[data-pan-filter="0"]') : null;
			if (cardZero) {
				cardZero.classList.toggle('is-warn', unassigned > 0);
			}
			var kelasStatEls = container ? container.querySelectorAll('[data-pan-stat-kelas]') : [];
			Array.prototype.forEach.call(kelasStatEls, function (el) {
				var kId = parseInt(el.getAttribute('data-pan-stat-kelas') || '0', 10) || 0;
				var cnt = byKelas[kId] || 0;
				el.innerHTML = cnt + ' <small style="font-size:12px;font-weight:600;color:#64748b;">panitia</small>';
			});

			// Sinkronkan juga kartu statistik utama di atas halaman (Total Belum Ada Kelas & badge panitia per kartu kelas)
			var allMainRows = document.querySelectorAll('#kgtk-tbl-peserta tbody tr[data-id]');
			if (allMainRows.length) {
				var totalUnassignedAll = 0;
				var panByKelasMain = {};
				Array.prototype.forEach.call(allMainRows, function (tr) {
					var rk = parseInt(tr.getAttribute('data-kelas') || '0', 10) || 0;
					var isPan = tr.getAttribute('data-is-panitia') === '1' || (tr.getAttribute('data-peran') || '').toLowerCase() === 'panitia';
					if (rk === 0) {
						totalUnassignedAll++;
					} else if (isPan) {
						panByKelasMain[rk] = (panByKelasMain[rk] || 0) + 1;
					}
				});
				var topZeroCard = document.querySelector('.kgtk-scard[data-filter="0"]');
				if (topZeroCard) {
					var num0 = topZeroCard.querySelector('.kgtk-scard-num');
					if (num0) { num0.textContent = String(totalUnassignedAll); }
					topZeroCard.classList.toggle('is-warn', totalUnassignedAll > 0);
					var pill0 = topZeroCard.querySelector('.kgtk-scard-pill');
					if (totalUnassignedAll > 0 && !pill0) {
						var sp = document.createElement('span');
						sp.className = 'kgtk-scard-pill';
						sp.textContent = 'Perlu Penempatan';
						topZeroCard.appendChild(sp);
					} else if (totalUnassignedAll === 0 && pill0) {
						pill0.remove();
					}
				}
				var headBadge = document.querySelector('.kgtk-kelola-head-badge .kgtk-badge');
				if (headBadge) {
					if (totalUnassignedAll > 0) {
						headBadge.className = 'kgtk-badge is-alert';
						headBadge.textContent = totalUnassignedAll + ' Belum Masuk Kelas';
					} else {
						headBadge.className = 'kgtk-badge is-open';
						headBadge.textContent = 'Semua Terbagi Kelas';
					}
				}
				var classCards = document.querySelectorAll('.kgtk-scard[data-filter]');
				Array.prototype.forEach.call(classCards, function (cc) {
					var fk = parseInt(cc.getAttribute('data-filter') || '0', 10) || 0;
					if (fk <= 0) { return; }
					var pCnt = panByKelasMain[fk] || 0;
					cc.setAttribute('data-panitia', String(pCnt));
				});
			}
		}

		function applyPanitiaFilter() {
			if (!panTable) { return; }
			var q = panSearch ? (panSearch.value || '').trim().toLowerCase() : '';
			var rows = panTable.querySelectorAll('tbody tr[data-search]');
			var visIdx = 1;
			if (panFilterSelect && panFilterSelect.value !== activePanFilter) {
				panFilterSelect.value = activePanFilter;
			}
			Array.prototype.forEach.call(rows, function (row) {
				var s = row.getAttribute('data-search') || '';
				var rowKelas = row.getAttribute('data-kelas') || '0';
				var matchQ = (!q || s.indexOf(q) > -1);
				var matchK = (activePanFilter === 'all' || String(rowKelas) === String(activePanFilter));
				var match = matchQ && matchK;
				row.style.display = match ? '' : 'none';
				if (match) {
					var numCell = row.querySelector('.kgtk-row-num');
					if (numCell) { numCell.textContent = visIdx + '.'; }
					visIdx++;
				}
			});
			updatePanBulkState();
		}

		var panFilterSelect = document.getElementById('kgtk-panitia-pel-filter-kelas');
		if (panFilterSelect) {
			panFilterSelect.addEventListener('change', function () {
				activePanFilter = panFilterSelect.value || 'all';
				applyPanitiaFilter();
			});
		}

		if (panSearch && panTable) {
			panSearch.addEventListener('input', applyPanitiaFilter);
		}

		// Ikut filter dari kartu kelas di bagian atas halaman saat tab Panitia sedang aktif
		document.addEventListener('click', function (e) {
			var scard = e.target.closest('.kgtk-scard[data-filter]');
			if (!scard || scard.closest('#kgtk-undangan-container')) { return; }
			if (e.target.closest('[data-atur-deadline-kelas], [data-open-pane]')) { return; }
			activePanFilter = scard.getAttribute('data-filter') || 'all';
			if (panFilterSelect) { panFilterSelect.value = activePanFilter; }
			applyPanitiaFilter();
		});

		Array.prototype.forEach.call(panStatCards, function (card) {
			card.addEventListener('click', function () {
				activePanFilter = card.getAttribute('data-pan-filter') || 'all';
				if (panFilterSelect) { panFilterSelect.value = activePanFilter; }
				Array.prototype.forEach.call(panStatCards, function (c) {
					c.classList.toggle('is-active', c === card);
				});
				applyPanitiaFilter();
			});
		});

		// Penempatan kelas panitia individual (1-klik dropdown di tabel panitia maupun tabel peserta)
		document.addEventListener('change', function (e) {
			if (e.target.classList.contains('kgtk-pan-check')) {
				updatePanBulkState();
				return;
			}
			var sel = e.target.closest('.kgtk-pan-kelas-select');
			if (!sel) { return; }
			var panId = parseInt(sel.getAttribute('data-pan-id') || '0', 10);
			if (!panId) { return; }
			var kelasId = parseInt(sel.value || '0', 10) || 0;
			var tr = sel.closest('tr');
			var prevKelas = tr ? parseInt(tr.getAttribute('data-kelas') || '0', 10) : 0;

			sel.disabled = true;
			api('admin/peserta/' + panId + '/kelas', 'POST', { kelas_id: kelasId }).then(function () {
				sel.disabled = false;
				var allMatchingSelects = document.querySelectorAll('.kgtk-pan-kelas-select[data-pan-id="' + panId + '"]');
				Array.prototype.forEach.call(allMatchingSelects, function (s) {
					s.value = String(kelasId);
					s.classList.toggle('is-assigned', kelasId > 0);
					s.classList.toggle('is-unassigned', kelasId === 0);
					s.style.color = kelasId > 0 ? '#1e40af' : '#92400e';
					s.style.background = kelasId > 0 ? '#eff6ff' : '#fffbeb';
					s.style.borderColor = kelasId > 0 ? '#bfdbfe' : '#fde68a';
					var row = s.closest('tr');
					if (row) {
						row.setAttribute('data-kelas', String(kelasId));
						row.classList.toggle('is-row-unassigned', kelasId === 0);
						try {
							var dJson = JSON.parse(row.getAttribute('data-json') || '{}');
							dJson.kelas_id = kelasId;
							row.setAttribute('data-json', JSON.stringify(dJson));
						} catch (ignore) {}
						try {
							var dBio = JSON.parse(row.getAttribute('data-biodata') || '{}');
							dBio.kelas_id = kelasId;
							row.setAttribute('data-biodata', JSON.stringify(dBio));
						} catch (ignore) {}
					}
				});

				window._kgtkKelasDirty = true;
				recalcPanitiaStats();
				applyPanitiaFilter();
				if (typeof filterRows === 'function') {
					filterRows();
				}
				tOk('Penempatan kelas panitia berhasil disimpan.');
			}).catch(function (err) {
				sel.disabled = false;
				sel.value = String(prevKelas);
				modalAlert({ title: 'Gagal Menempatkan Kelas Panitia', message: err.message || 'Gagal menyimpan kelas panitia.', type: 'danger' });
			});
		});

		// Pilih semua panitia & penempatan masal (bulk)
		function toggleAllVisiblePanitia(checked) {
			if (!panTable) { return; }
			var rows = panTable.querySelectorAll('tbody tr[data-id]');
			Array.prototype.forEach.call(rows, function (tr) {
				if (tr.style.display === 'none') { return; }
				var cb = tr.querySelector('.kgtk-pan-check');
				if (cb) { cb.checked = checked; }
			});
			updatePanBulkState();
		}
		if (panCheckAll) {
			panCheckAll.addEventListener('change', function () {
				toggleAllVisiblePanitia(panCheckAll.checked);
			});
		}
		if (panThCheckAll) {
			panThCheckAll.addEventListener('change', function () {
				toggleAllVisiblePanitia(panThCheckAll.checked);
			});
		}
		if (panBulkTarget) {
			panBulkTarget.addEventListener('change', updatePanBulkState);
		}
		if (panBulkApply && panTable) {
			panBulkApply.addEventListener('click', function () {
				if (!panBulkTarget || panBulkTarget.value === '') { return; }
				var targetKelas = parseInt(panBulkTarget.value, 10) || 0;
				var checkedBoxes = panTable.querySelectorAll('tbody .kgtk-pan-check:checked');
				var ids = [];
				Array.prototype.forEach.call(checkedBoxes, function (cb) {
					var id = parseInt(cb.value, 10);
					if (id > 0) { ids.push(id); }
				});
				if (!ids.length) { return; }

				panBulkApply.disabled = true;
				var origTxt = panBulkApply.textContent;
				panBulkApply.textContent = 'Menyimpan...';

				var currentPelId = pelId ||
					(container && container.getAttribute('data-pelatihan')) ||
					(document.getElementById('kgtk-tbl-peserta') && document.getElementById('kgtk-tbl-peserta').getAttribute('data-pelatihan')) ||
					(new URLSearchParams(window.location.search).get('id')) ||
					'0';

				var endpoint = (parseInt(currentPelId, 10) > 0)
					? ('admin/pelatihan/' + currentPelId + '/bulk-kelas')
					: 'admin/peserta/bulk-kelas';

				api(endpoint, 'POST', {
					peserta_ids: ids,
					kelas_id: targetKelas,
					pelatihan_id: parseInt(currentPelId, 10) || 0
				}).then(function () {
					panBulkApply.textContent = origTxt;
					Array.prototype.forEach.call(checkedBoxes, function (cb) {
						cb.checked = false;
						var tr = cb.closest('tr');
						if (!tr) { return; }
						tr.setAttribute('data-kelas', String(targetKelas));
						var sel = tr.querySelector('.kgtk-pan-kelas-select');
						if (sel) {
							sel.value = String(targetKelas);
							sel.classList.toggle('is-assigned', targetKelas > 0);
							sel.classList.toggle('is-unassigned', targetKelas === 0);
							sel.style.color = targetKelas > 0 ? '#1e40af' : '#92400e';
							sel.style.background = targetKelas > 0 ? '#eff6ff' : '#fffbeb';
							sel.style.borderColor = targetKelas > 0 ? '#bfdbfe' : '#fde68a';
						}
						try {
							var dJson = JSON.parse(tr.getAttribute('data-json') || '{}');
							dJson.kelas_id = targetKelas;
							tr.setAttribute('data-json', JSON.stringify(dJson));
						} catch (ignore) {}
					});

					// Sinkronkan juga baris panitia di tabel utama (#kgtk-tbl-peserta) bila ada
					ids.forEach(function (id) {
						var psRow = document.querySelector('#kgtk-tbl-peserta tbody tr[data-id="' + id + '"][data-is-panitia="1"]');
						if (psRow) {
							psRow.setAttribute('data-kelas', String(targetKelas));
							psRow.classList.toggle('is-row-unassigned', targetKelas === 0);
							var psSel = psRow.querySelector('.kgtk-pan-kelas-select');
							if (psSel) {
								psSel.value = String(targetKelas);
								psSel.classList.toggle('is-assigned', targetKelas > 0);
								psSel.classList.toggle('is-unassigned', targetKelas === 0);
								psSel.style.color = targetKelas > 0 ? '#1e40af' : '#92400e';
								psSel.style.background = targetKelas > 0 ? '#eff6ff' : '#fffbeb';
								psSel.style.borderColor = targetKelas > 0 ? '#bfdbfe' : '#fde68a';
							}
						}
					});

					window._kgtkKelasDirty = true;
					if (panBulkTarget) { panBulkTarget.value = ''; }
					updatePanBulkState();
					recalcPanitiaStats();
					applyPanitiaFilter();
					if (typeof filterRows === 'function') {
						filterRows();
					}
					tOk('Berhasil menempatkan ' + ids.length + ' panitia ke kelas.');
				}).catch(function (err) {
					panBulkApply.textContent = origTxt;
					updatePanBulkState();
					modalAlert({ title: 'Gagal Penempatan Masal', message: err.message || 'Gagal menyimpan penempatan kelas panitia.', type: 'danger' });
				});
			});
		}

		// Hapus panitia dari pelatihan (berlaku di tabel utama peserta maupun tab panitia)
		document.addEventListener('click', function (e) {
			var delBtn = e.target.closest('[data-del-panitia-pelatihan]');
			if (!delBtn) { return; }
			var pesertaId = delBtn.getAttribute('data-del-panitia-pelatihan');
			var nama = delBtn.getAttribute('data-nama') || 'panitia ini';
			var fromMainTable = !!delBtn.closest('#kgtk-tbl-peserta');

			modalConfirm({
				title: 'Hapus Panitia',
				message: 'Hapus ' + nama + ' dari susunan panitia kegiatan ini?\n\n(Catatan: Untuk menghapus permanen dari database master panitia, gunakan tombol "Master" di tab Panitia).',
				confirmText: 'Ya, Hapus Panitia',
				cancelText: 'Batal',
				type: 'danger'
			}).then(function (ok) {
				if (!ok) { return; }
				delBtn.disabled = true;

				var currentPelId = pelId ||
					(container && container.getAttribute('data-pelatihan')) ||
					(document.getElementById('kgtk-tbl-peserta') && document.getElementById('kgtk-tbl-peserta').getAttribute('data-pelatihan')) ||
					(new URLSearchParams(window.location.search).get('id')) ||
					'0';

				api('admin/pelatihan/' + currentPelId + '/panitia/' + pesertaId, 'DELETE').then(function () {
					// Hapus baris dari tabel peserta utama bila ada
					var mainRow = document.querySelector('#kgtk-tbl-peserta tbody tr[data-id="' + pesertaId + '"][data-is-panitia="1"]');
					if (mainRow && mainRow.parentNode) {
						mainRow.parentNode.removeChild(mainRow);
					}
					// Hapus baris dari tabel panitia bila ada
					var panRow = panTable ? panTable.querySelector('tbody tr[data-id="' + pesertaId + '"]') : null;
					if (panRow && panRow.parentNode) {
						panRow.parentNode.removeChild(panRow);
					}

					// Perbarui badge jumlah panitia di tab & subtab
					var remainingPanRows = panTable ? panTable.querySelectorAll('tbody tr[data-id]').length : document.querySelectorAll('#kgtk-tbl-peserta tbody tr[data-is-panitia="1"]').length;
					var subBadge = document.getElementById('kgtk-badge-sub-panitia');
					if (subBadge) { subBadge.textContent = String(remainingPanRows); }
					var tabPanSmall = document.querySelector('.kgtk-tab[data-tab="panitia"] small');
					if (tabPanSmall) { tabPanSmall.textContent = String(remainingPanRows); }

					window._kgtkKelasDirty = true;
					recalcPanitiaStats();
					applyPanitiaFilter();
					if (typeof filterRows === 'function') {
						filterRows();
					}
					tOk('Panitia "' + nama + '" berhasil dihapus.');

					if (!fromMainTable && remainingPanRows === 0) {
						window.location.hash = 'panitia';
						window.location.reload();
					}
				}).catch(function (err) {
					delBtn.disabled = false;
					modalAlert({ title: 'Gagal Menghapus', message: err.message, type: 'danger' });
				});
			});
		});

		// Hapus panitia dari Master Data (bisa diklik dari modal atau tabel utama)
		document.addEventListener('click', function (e) {
			var delMasterBtn = e.target.closest('[data-del-master-panitia]');
			if (!delMasterBtn) { return; }
			var mId = delMasterBtn.getAttribute('data-del-master-panitia');
			var nama = delMasterBtn.getAttribute('data-nama') || 'panitia ini';

			modalConfirm({
				title: 'Hapus dari Master Data Panitia',
				message: 'Hapus data panitia "' + nama + '" dari Master Data Panitia?\n\nProfil panitia ini akan dihapus dari daftar tersimpan di database. Penugasan di kegiatan pelatihan tetap tercatat.',
				confirmText: 'Ya, Hapus dari Master Data',
				cancelText: 'Batal',
				type: 'danger'
			}).then(function (ok) {
				if (!ok) { return; }
				delMasterBtn.disabled = true;
				api('admin/panitia/' + mId, 'DELETE').then(function () {
					// Hapus baris dari tabel modal jika ada
					var rowModal = document.querySelector('tr[data-master-row="' + mId + '"]');
					if (rowModal) {
						rowModal.style.transition = 'all 0.25s ease';
						rowModal.style.opacity = '0';
						setTimeout(function () { rowModal.remove(); }, 250);
					}
					// Jika diklik dari tabel utama pelatihan, hilangkan tombol Master-nya
					if (delMasterBtn.closest('#kgtk-tbl-panitia-pelatihan')) {
						delMasterBtn.remove();
					}
					modalAlert({ title: 'Berhasil', message: 'Data panitia "' + nama + '" berhasil dihapus dari Master Data.', type: 'primary' });
				}).catch(function (err) {
					delMasterBtn.disabled = false;
					modalAlert({ title: 'Gagal Menghapus', message: err.message, type: 'danger' });
				});
			});
		});

		// Modal Tambah Panitia ke Pelatihan
		var modal = document.getElementById('kgtk-modal-tambah-panitia-pelatihan');
		var btnOpen = document.getElementById('kgtk-btn-tambah-panitia-pelatihan');
		var btnClose = document.getElementById('kgtk-btn-modal-pan-pel-close');
		var btnBatal = document.getElementById('kgtk-btn-modal-pan-pel-batal');
		var btnSubmit = document.getElementById('kgtk-btn-modal-pan-pel-submit');
		var errBox = document.getElementById('kgtk-modal-pan-pel-error');

		var tabPick = document.getElementById('kgtk-tab-pan-pick');
		var tabManual = document.getElementById('kgtk-tab-pan-manual');
		var boxPick = document.getElementById('kgtk-box-pan-pick');
		var boxManual = document.getElementById('kgtk-box-pan-manual');
		var currentModalTab = 'pick';

		var openModal = function () {
			if (!modal) { return; }
			if (errBox) { errBox.hidden = true; errBox.textContent = ''; }
			modal.style.display = 'flex';
			modal.classList.add('is-visible');
		};

		var closeModal = function () {
			if (!modal) { return; }
			modal.classList.remove('is-visible');
			modal.style.display = 'none';
		};

		if (btnOpen) { btnOpen.addEventListener('click', openModal); }
		if (btnClose) { btnClose.addEventListener('click', closeModal); }
		if (btnBatal) { btnBatal.addEventListener('click', closeModal); }
		if (modal) {
			modal.addEventListener('click', function (e) {
				if (e.target === modal) { closeModal(); }
			});
		}

		if (tabPick && tabManual) {
			tabPick.addEventListener('click', function () {
				currentModalTab = 'pick';
				tabPick.classList.add('is-active');
				tabManual.classList.remove('is-active');
				if (boxPick) { boxPick.style.display = ''; }
				if (boxManual) { boxManual.style.display = 'none'; }
			});
			tabManual.addEventListener('click', function () {
				currentModalTab = 'manual';
				tabManual.classList.add('is-active');
				tabPick.classList.remove('is-active');
				if (boxPick) { boxPick.style.display = 'none'; }
				if (boxManual) { boxManual.style.display = ''; }
			});
		}

		// Search di dalam modal pilih panitia
		var modalSearch = document.getElementById('kgtk-modal-pan-pel-search');
		var modalTable = document.getElementById('kgtk-modal-pan-pel-table');
		if (modalSearch && modalTable) {
			modalSearch.addEventListener('input', function (e) {
				var q = (e.target.value || '').trim().toLowerCase();
				var rows = modalTable.querySelectorAll('tbody tr[data-search]');
				Array.prototype.forEach.call(rows, function (row) {
					var s = row.getAttribute('data-search') || '';
					row.style.display = (!q || s.indexOf(q) > -1) ? '' : 'none';
				});
			});
		}

		// Check all di modal
		var checkAll = document.getElementById('kgtk-pan-pel-checkall');
		if (checkAll && modalTable) {
			checkAll.addEventListener('change', function () {
				var checks = modalTable.querySelectorAll('.kgtk-pan-pel-item-check');
				Array.prototype.forEach.call(checks, function (c) {
					var tr = c.closest('tr');
					if (tr && tr.style.display !== 'none') {
						c.checked = checkAll.checked;
					}
				});
			});
		}

		// Submit modal
		if (btnSubmit) {
			btnSubmit.addEventListener('click', function () {
				if (errBox) { errBox.hidden = true; errBox.textContent = ''; }

				var payloadItems = [];

				if (currentModalTab === 'pick') {
					var checkedItems = document.querySelectorAll('.kgtk-pan-pel-item-check:checked');
					if (!checkedItems.length) {
						if (errBox) {
							errBox.textContent = 'Centang minimal satu panitia yang ingin ditambahkan.';
							errBox.hidden = false;
						}
						return;
					}
					Array.prototype.forEach.call(checkedItems, function (chk) {
						var raw = chk.getAttribute('data-json');
						try {
							var parsed = JSON.parse(raw);
							payloadItems.push(parsed);
						} catch (ex) {}
					});
				} else {
					var namaM = (document.getElementById('kgtk-pan-m-nama') ? document.getElementById('kgtk-pan-m-nama').value : '').trim();
					var jabatanM = (document.getElementById('kgtk-pan-m-jabatan') ? document.getElementById('kgtk-pan-m-jabatan').value : '').trim();
					var hpM = (document.getElementById('kgtk-pan-m-hp') ? document.getElementById('kgtk-pan-m-hp').value : '').trim();
					var instansiM = (document.getElementById('kgtk-pan-m-instansi') ? document.getElementById('kgtk-pan-m-instansi').value : '').trim();
					var kabkotaM = (document.getElementById('kgtk-pan-m-kabkota') ? document.getElementById('kgtk-pan-m-kabkota').value : '').trim();
					var nikM = (document.getElementById('kgtk-pan-m-nik') ? document.getElementById('kgtk-pan-m-nik').value : '').trim();
					var nipM = (document.getElementById('kgtk-pan-m-nip') ? document.getElementById('kgtk-pan-m-nip').value : '').trim();

					var bankM = (document.getElementById('kgtk-pan-m-bank') ? document.getElementById('kgtk-pan-m-bank').value : '').trim();
					var rekeningM = (document.getElementById('kgtk-pan-m-rekening') ? document.getElementById('kgtk-pan-m-rekening').value : '').trim();

					if (!namaM) {
						if (errBox) { errBox.textContent = 'Nama lengkap panitia wajib diisi.'; errBox.hidden = false; }
						return;
					}
					if (!hpM) {
						if (errBox) { errBox.textContent = 'Nomor HP / WhatsApp wajib diisi.'; errBox.hidden = false; }
						return;
					}

					payloadItems.push({
						nama: namaM,
						jabatan: jabatanM || 'Panitia Pelaksana',
						hp: hpM,
						instansi: instansiM,
						kabkota: kabkotaM,
						nik: nikM,
						nip: nipM,
						bank: bankM,
						rekening: rekeningM
					});
				}

				btnSubmit.disabled = true;
				btnSubmit.textContent = 'Menyimpan...';

				api('admin/pelatihan/' + pelId + '/panitia', 'POST', {
					panitia: payloadItems
				}).then(function () {
					closeModal();
					window.location.hash = 'panitia';
					window.location.reload();
				}).catch(function (err) {
					btnSubmit.disabled = false;
					btnSubmit.textContent = '✓ Tambahkan Panitia';
					if (errBox) {
						errBox.textContent = err.message || 'Gagal menambahkan panitia.';
						errBox.hidden = false;
					}
				});
			});
		}

		// Modal Edit Panitia Pelatihan
		var editModal = document.getElementById('kgtk-modal-edit-panitia-pelatihan');
		var formEditPan = document.getElementById('kgtk-form-edit-panitia-pelatihan');
		var btnEditClose = document.getElementById('kgtk-btn-edit-pan-pel-close');
		var btnEditBatal = document.getElementById('kgtk-btn-edit-pan-pel-batal');
		var btnEditSave = document.getElementById('kgtk-btn-edit-pan-pel-save');
		var editErrBox = document.getElementById('kgtk-edit-pan-pel-error');

		var rEditNik = document.getElementById('kgtk-edit-pan-radio-nik');
		var rEditNip = document.getElementById('kgtk-edit-pan-radio-nip');
		var wrapEditNik = document.getElementById('kgtk-edit-pan-wrap-nik');
		var wrapEditNipTop = document.getElementById('kgtk-edit-pan-wrap-nip-top');
		var wrapEditNipBottom = document.getElementById('kgtk-edit-pan-wrap-nip-bottom');
		var inpEditNik = document.getElementById('kgtk-edit-pan-nik');
		var inpEditNipTop = document.getElementById('kgtk-edit-pan-nip-top');
		var inpEditNipBottom = document.getElementById('kgtk-edit-pan-nip');

		var updateEditPanIdView = function () {
			var pakaiNip = rEditNip && rEditNip.checked;
			if (pakaiNip) {
				if (wrapEditNik) { wrapEditNik.style.display = 'none'; }
				if (wrapEditNipTop) { wrapEditNipTop.style.display = ''; }
				if (wrapEditNipBottom) { wrapEditNipBottom.style.display = 'none'; }
				if (inpEditNipTop) { inpEditNipTop.focus(); }
			} else {
				if (wrapEditNik) { wrapEditNik.style.display = ''; }
				if (wrapEditNipTop) { wrapEditNipTop.style.display = 'none'; }
				if (wrapEditNipBottom) { wrapEditNipBottom.style.display = ''; }
			}
		};

		if (rEditNik) { rEditNik.addEventListener('change', updateEditPanIdView); }
		if (rEditNip) { rEditNip.addEventListener('change', updateEditPanIdView); }

		if (inpEditNik) {
			inpEditNik.addEventListener('input', function (e) {
				e.target.value = e.target.value.replace(/\D/g, '').slice(0, 16);
			});
		}
		if (inpEditNipTop) {
			inpEditNipTop.addEventListener('input', function (e) {
				e.target.value = e.target.value.replace(/\D/g, '').slice(0, 18);
				if (inpEditNipBottom) { inpEditNipBottom.value = e.target.value; }
			});
		}
		if (inpEditNipBottom) {
			inpEditNipBottom.addEventListener('input', function (e) {
				e.target.value = e.target.value.replace(/\D/g, '').slice(0, 18);
				if (inpEditNipTop && inpEditNipTop.value !== e.target.value) { inpEditNipTop.value = e.target.value; }
			});
		}

		var openEditPanModal = function (data) {
			if (!editModal || !data) { return; }
			if (editErrBox) { editErrBox.hidden = true; editErrBox.textContent = ''; }
			if (formEditPan) {
				formEditPan.reset();
				var fId = document.getElementById('kgtk-edit-pan-pel-id');
				if (fId) { fId.value = data.id || 0; }

				var isNipOnly = (!data.nik || data.nik.indexOf('99') === 0) && !!data.nip;
				if (isNipOnly) {
					if (rEditNip) { rEditNip.checked = true; }
				} else {
					if (rEditNik) { rEditNik.checked = true; }
				}

				var fMap = {
					'kgtk-edit-pan-nama': data.nama || '',
					'kgtk-edit-pan-gelar-depan': data.gelar_depan || '',
					'kgtk-edit-pan-gelar-belakang': data.gelar_belakang || '',
					'kgtk-edit-pan-jabatan': data.jabatan || 'Panitia Pelaksana',
					'kgtk-edit-pan-nik': (data.nik && data.nik.indexOf('99') !== 0) ? data.nik : '',
					'kgtk-edit-pan-nip': data.nip || '',
					'kgtk-edit-pan-nip-top': data.nip || '',
					'kgtk-edit-pan-instansi': data.instansi || '',
					'kgtk-edit-pan-kabkota': data.kabkota || '',
					'kgtk-edit-pan-hp': data.hp || '',
					'kgtk-edit-pan-email': data.email || '',
					'kgtk-edit-pan-bank': data.bank || '',
					'kgtk-edit-pan-rekening': data.rekening || ''
				};
				for (var fieldId in fMap) {
					var el = document.getElementById(fieldId);
					if (el) { el.value = fMap[fieldId]; }
				}
				updateEditPanIdView();
			}
			editModal.style.display = 'flex';
			editModal.classList.add('is-visible');
		};

		var closeEditPanModal = function () {
			if (!editModal) { return; }
			editModal.classList.remove('is-visible');
			editModal.style.display = 'none';
		};

		if (btnEditClose) { btnEditClose.addEventListener('click', closeEditPanModal); }
		if (btnEditBatal) { btnEditBatal.addEventListener('click', closeEditPanModal); }
		if (editModal) {
			editModal.addEventListener('click', function (e) {
				if (e.target === editModal) { closeEditPanModal(); }
			});
		}

		// Tombol edit pada setiap baris panitia pelatihan
		document.addEventListener('click', function (e) {
			var editBtn = e.target.closest('[data-edit-panitia-pelatihan]');
			if (!editBtn) { return; }
			var tr = editBtn.closest('tr[data-json]');
			if (!tr) { return; }
			var raw = tr.getAttribute('data-json');
			var d = {};
			try { d = JSON.parse(raw); } catch (ex) {}
			openEditPanModal(d);
		});

		// Submit form edit panitia pelatihan
		if (formEditPan) {
			formEditPan.addEventListener('submit', function (e) {
				e.preventDefault();
				if (editErrBox) { editErrBox.hidden = true; editErrBox.textContent = ''; }

				var f = formEditPan.elements;
				var pesertaId = parseInt(document.getElementById('kgtk-edit-pan-pel-id').value || '0', 10);
				var nama = (f.nama.value || '').trim();
				var gelarDepan = (f.gelar_depan ? f.gelar_depan.value : '').trim();
				var gelarBelakang = (f.gelar_belakang ? f.gelar_belakang.value : '').trim();
				var jabatan = (f.jabatan.value || '').trim();
				var nik = (f.nik ? f.nik.value : '').trim();
				var nip = (f.nip ? f.nip.value : '').trim();
				var instansi = (f.instansi ? f.instansi.value : '').trim();
				var kabkota = (f.kabkota ? f.kabkota.value : '').trim();
				var hp = (f.hp ? f.hp.value : '').trim();
				var email = (f.email ? f.email.value : '').trim();
				var bank = (f.bank ? f.bank.value : '').trim();
				var rekening = (f.rekening ? f.rekening.value : '').trim();

				var pakaiNip = rEditNip && rEditNip.checked;
				if (pakaiNip && inpEditNipTop && inpEditNipTop.value) {
					nip = inpEditNipTop.value.trim();
				}

				if (pakaiNip) {
					if (!/^\d{18}$/.test(nip)) {
						if (editErrBox) { editErrBox.textContent = 'NIP panitia harus 18 digit angka.'; editErrBox.hidden = false; }
						if (inpEditNipTop) { inpEditNipTop.focus(); }
						return;
					}
				} else {
					if (nik && !/^\d{16}$/.test(nik)) {
						if (editErrBox) { editErrBox.textContent = 'NIK panitia harus 16 digit angka.'; editErrBox.hidden = false; }
						if (f.nik) { f.nik.focus(); }
						return;
					}
					if (nip && !/^\d{18}$/.test(nip)) {
						if (editErrBox) { editErrBox.textContent = 'NIP harus 18 digit angka (atau kosongkan).'; editErrBox.hidden = false; }
						if (f.nip) { f.nip.focus(); }
						return;
					}
				}

				if (!nama) {
					if (editErrBox) { editErrBox.textContent = 'Nama lengkap panitia wajib diisi.'; editErrBox.hidden = false; }
					f.nama.focus();
					return;
				}
				if (!jabatan) {
					if (editErrBox) { editErrBox.textContent = 'Tugas / jabatan panitia wajib diisi.'; editErrBox.hidden = false; }
					f.jabatan.focus();
					return;
				}
				if (!hp) {
					if (editErrBox) { editErrBox.textContent = 'Nomor HP / WhatsApp wajib diisi.'; editErrBox.hidden = false; }
					f.hp.focus();
					return;
				}

				if (btnEditSave) { btnEditSave.disabled = true; btnEditSave.textContent = 'Menyimpan...'; }

				var payload = {
					nama: nama,
					gelar_depan: gelarDepan,
					gelar_belakang: gelarBelakang,
					jabatan: jabatan,
					nik: pakaiNip ? '' : nik,
					nip: nip,
					instansi: instansi,
					kabkota: kabkota,
					hp: hp,
					email: email,
					bank: bank,
					rekening: rekening
				};

				api('admin/pelatihan/' + pelId + '/panitia/' + pesertaId, 'PUT', payload).then(function (res) {
					closeEditPanModal();
					if (btnEditSave) { btnEditSave.disabled = false; btnEditSave.textContent = 'Simpan Perubahan'; }

					var item = (res && res.item) ? res.item : payload;
					item.id = item.id || pesertaId;

					// Format nama dengan gelar
					var namaTampil = item.nama;
					var prefix = (item.gelar_depan || '').trim();
					var suffix = (item.gelar_belakang || '').trim();
					if (prefix && namaTampil.indexOf(prefix) !== 0) { namaTampil = prefix + ' ' + namaTampil; }
					if (suffix && namaTampil.indexOf(suffix) === -1) { namaTampil = namaTampil + ', ' + suffix; }

					var tr = container.querySelector('tbody tr[data-id="' + pesertaId + '"]');
					if (tr) {
						var namaEl = tr.querySelector('.kgtk-guru-nama');
						if (namaEl) { namaEl.textContent = namaTampil; }
						var badgeEl = tr.querySelector('.kgtk-badge');
						if (badgeEl) { badgeEl.textContent = '💼 ' + (item.jabatan || 'Panitia Pelaksana'); }
						var sekEl = tr.querySelector('.kgtk-sekolah-nama');
						if (sekEl) { sekEl.textContent = item.instansi || item.sekolah || '-'; }
						var kabEl = tr.querySelector('.kgtk-kab-tag');
						if (item.kabkota && item.kabkota !== 'Lainnya' && item.kabkota !== '-') {
							if (kabEl) {
								kabEl.textContent = '📍 ' + item.kabkota;
							} else if (sekEl && sekEl.parentNode) {
								var nKab = document.createElement('div');
								nKab.className = 'kgtk-kab-tag';
								nKab.textContent = '📍 ' + item.kabkota;
								sekEl.parentNode.appendChild(nKab);
							}
						} else if (kabEl) {
							kabEl.remove();
						}

						var nikCell = tr.querySelector('.kgtk-nik-cell');
						if (nikCell) {
							if (item.nik && item.nik.indexOf('99') !== 0) {
								nikCell.innerHTML = '<code class="kgtk-nik-code" style="font-size:11.5px;">' + escHtml(item.nik) + '</code>';
							} else {
								nikCell.innerHTML = '<span class="kgtk-muted">-</span>';
							}
							if (item.nip && item.nip !== '-') {
								nikCell.innerHTML += '<div class="kgtk-muted" style="font-size:11px;margin-top:2px;">NIP: ' + escHtml(item.nip) + '</div>';
							}
						}

						var waNum = (item.hp || '').replace(/\D/g, '').replace(/^0/, '62');
						var kontakDiv = tr.querySelector('.kgtk-guru-kontak');
						if (item.hp && item.hp !== '-') {
							if (!kontakDiv) {
								kontakDiv = document.createElement('div');
								kontakDiv.className = 'kgtk-guru-kontak';
								var gCell = tr.querySelector('.kgtk-guru-cell');
								if (gCell) { gCell.appendChild(kontakDiv); }
							}
							if (kontakDiv) {
								kontakDiv.innerHTML = '<a href="https://wa.me/' + escHtml(waNum) + '" target="_blank" rel="noopener noreferrer" class="kgtk-wa-link" title="Kirim pesan WhatsApp"><span class="kgtk-wa-icon">&#128172;</span> ' + escHtml(item.hp) + '</a>';
							}
						}

						var sText = (namaTampil + ' ' + (item.nik || '') + ' ' + (item.nip || '') + ' ' + (item.jabatan || '') + ' ' + (item.sekolah || item.instansi || '') + ' ' + (item.hp || '')).toLowerCase();
						tr.setAttribute('data-search', sText);

						var mergedJson = {
							id: pesertaId,
							nama: item.nama,
							gelar_depan: item.gelar_depan || '',
							gelar_belakang: item.gelar_belakang || '',
							jabatan: item.jabatan || 'Panitia Pelaksana',
							nik: item.nik || '',
							nip: item.nip || '',
							nuptk: item.nuptk || '',
							instansi: item.sekolah || item.instansi || '',
							kabkota: item.kabkota || '',
							hp: item.hp || '',
							email: item.email || '',
							bank: item.bank || '',
							rekening: item.rekening || '',
							npwp: item.npwp || ''
						};
						tr.setAttribute('data-json', JSON.stringify(mergedJson));
						tOk('Biodata panitia ' + namaTampil + ' berhasil diperbarui.');
					} else {
						window.location.hash = 'panitia';
						window.location.reload();
					}
				}).catch(function (err) {
					if (btnEditSave) { btnEditSave.disabled = false; btnEditSave.textContent = 'Simpan Perubahan'; }
					if (editErrBox) {
						editErrBox.textContent = err.message || 'Gagal memperbarui biodata panitia.';
						editErrBox.hidden = false;
					}
				});
			});
		}
	}
	try {
		initPanitiaPelatihan();
	} catch (errPanPel) {
		console.error('KGTK Init PanitiaPelatihan Error:', errPanPel);
	}

	/* ==========================================================================
	   PANE TANDA TERIMA BARANG / ALAT PELATIHAN
	   ========================================================================== */
	function initTandaTerima() {
		var pane = document.querySelector('.kgtk-pane[data-pane="tanda-terima"]');
		if (!pane) {
			return;
		}

		var pelId = pane.getAttribute('data-pelatihan') || '0';
		var itemInput = document.getElementById('kgtk-tt-item-input');
		var kelasSelect = document.getElementById('kgtk-tt-kelas-select');
		var exportPdfBtn = document.getElementById('kgtk-btn-export-pdf-tt');
		var previewLabel = pane.querySelector('.kgtk-tt-preview-label');

		var storageKey = 'kgtk_tt_item_' + pelId;
		if (window.localStorage && itemInput) {
			try {
				var saved = window.localStorage.getItem(storageKey);
				if (saved !== null && saved !== undefined && saved !== '') {
					itemInput.value = saved;
				}
			} catch (eStorageLoad) {}
		}

		function updateTandaTerima() {
			var val = (itemInput ? itemInput.value : '').trim();
			var upper = val ? val.toUpperCase() : '';
			var display = upper ? ' ' + upper : '';

			var allDisplays = pane.querySelectorAll('.kgtk-tt-item-display');
			Array.prototype.forEach.call(allDisplays, function (el) {
				el.textContent = display;
			});

			if (previewLabel) {
				previewLabel.textContent = upper || '(KOSONG)';
			}

			if (exportPdfBtn) {
				var baseUrl = exportPdfBtn.getAttribute('data-base-url') || exportPdfBtn.href;
				exportPdfBtn.setAttribute('data-base-url', baseUrl);

				var extraVal = extraInput ? parseInt(extraInput.value, 10) : 10;
				if (isNaN(extraVal) || extraVal < 0) { extraVal = 0; }

				try {
					var u = new URL(baseUrl, window.location.origin);
					if (val) {
						u.searchParams.set('barang', val);
					} else {
						u.searchParams.delete('barang');
					}
					var selKelas = kelasSelect ? (kelasSelect.value || 'all') : 'all';
					if (selKelas && selKelas !== 'all') {
						u.searchParams.set('kelas_id', selKelas);
					} else {
						u.searchParams.delete('kelas_id');
					}
					u.searchParams.set('extra_slots', String(extraVal));
					exportPdfBtn.href = u.toString();
				} catch (e) {
					var h = baseUrl;
					h = h.replace(/([?&])barang=[^&]*/, '');
					h = h.replace(/([?&])kelas_id=[^&]*/, '');
					h = h.replace(/([?&])extra_slots=[^&]*/, '');
					if (val) { h += (h.indexOf('?') > -1 ? '&' : '?') + 'barang=' + encodeURIComponent(val); }
					if (kelasSelect && kelasSelect.value && kelasSelect.value !== 'all') {
						h += (h.indexOf('?') > -1 ? '&' : '?') + 'kelas_id=' + encodeURIComponent(kelasSelect.value);
					}
					h += (h.indexOf('?') > -1 ? '&' : '?') + 'extra_slots=' + encodeURIComponent(extraVal);
					exportPdfBtn.href = h;
				}
			}

			if (window.localStorage) {
				try {
					window.localStorage.setItem(storageKey, val);
				} catch (eStorageSave) {}
			}
		}

		if (itemInput) {
			itemInput.addEventListener('input', updateTandaTerima);
			itemInput.addEventListener('change', updateTandaTerima);
		}

		var extraInput = document.getElementById('kgtk-tt-extra-slots');

		function rebuildExtraSlots() {
			var extraCount = extraInput ? parseInt(extraInput.value, 10) : 0;
			if (isNaN(extraCount) || extraCount < 0) {
				extraCount = 0;
			}
			if (extraCount % 2 !== 0) {
				extraCount++;
			}
			var groups = pane.querySelectorAll('.kgtk-tt-group');
			Array.prototype.forEach.call(groups, function (grp) {
				var baseCount = parseInt(grp.getAttribute('data-base-count') || '0', 10);
				var tbody = grp.querySelector('.kgtk-tt-tbody');
				if (!tbody) { return; }

				// Hapus baris extra yang ada
				var currentExtras = tbody.querySelectorAll('.kgtk-tt-extra-row');
				Array.prototype.forEach.call(currentExtras, function (r) {
					r.remove();
				});

				// Hitung jumlah baris dasar (peserta riil + pasangan genap jika ganjil)
				var baseRows = Math.ceil(baseCount / 2) * 2;
				var targetTotal = baseRows + extraCount;

				var curRows = tbody.querySelectorAll('tr').length;
				for (var i = curRows; i < targetTotal; i += 2) {
					var no1 = i + 1;
					var no2 = i + 2;
					var hasPair = (i + 1 < targetTotal);

					var tr1 = document.createElement('tr');
					tr1.className = 'kgtk-tt-extra-row';
					tr1.setAttribute('data-row-idx', String(i));
					tr1.innerHTML =
						'<td class="c-no">' + no1 + '</td>' +
						'<td class="c-nama">&nbsp;</td>' +
						'<td class="c-inst">&nbsp;</td>' +
						(hasPair
							? '<td class="c-ttd-col c-ttd-left" rowspan="2">' + no1 + '.</td><td class="c-ttd-col c-ttd-right" rowspan="2">' + no2 + '.</td>'
							: '<td class="c-ttd-col c-ttd-left">' + no1 + '.</td><td class="c-ttd-col c-ttd-right">&nbsp;</td>');
					tbody.appendChild(tr1);

					if (hasPair) {
						var tr2 = document.createElement('tr');
						tr2.className = 'kgtk-tt-extra-row';
						tr2.setAttribute('data-row-idx', String(i + 1));
						tr2.innerHTML =
							'<td class="c-no">' + no2 + '</td>' +
							'<td class="c-nama">&nbsp;</td>' +
							'<td class="c-inst">&nbsp;</td>';
						tbody.appendChild(tr2);
					}
				}
			});
			updateTandaTerima();
		}

		function syncKelasTandaTerima() {
			var selVal = kelasSelect ? (kelasSelect.value || 'all') : 'all';
			var selText = '';
			if (kelasSelect && kelasSelect.selectedIndex >= 0) {
				selText = kelasSelect.options[kelasSelect.selectedIndex].text.replace(/\s*\(\d+\s*orang\)$/i, '').trim();
			}

			var groups = pane.querySelectorAll('.kgtk-tt-group');
			Array.prototype.forEach.call(groups, function (grp) {
				var matches = (grp.getAttribute('data-tt-group') === selVal);
				if (matches) {
					grp.classList.add('is-active');
					grp.classList.remove('is-hidden');
					grp.style.display = '';
				} else {
					grp.classList.remove('is-active');
					grp.classList.add('is-hidden');
					grp.style.display = 'none';
				}
			});

			var cetakBtn = document.getElementById('kgtk-btn-cetak-tt');
			if (cetakBtn) {
				var printLabel = (selVal === 'all') ? 'Cetak Lembar (Semua Kelas)' : ('Cetak Lembar (' + selText + ')');
				cetakBtn.setAttribute('data-base-label', printLabel);
				cetakBtn.innerHTML = '&#128424;&#65039; ' + safeEsc(printLabel);
			}

			var exportBtn = document.getElementById('kgtk-btn-export-pdf-tt');
			if (exportBtn) {
				var pdfLabel = (selVal === 'all') ? 'Export PDF (Semua Kelas)' : ('Export PDF (' + selText + ')');
				exportBtn.innerHTML = '&#128196; ' + safeEsc(pdfLabel);
			}

			updateTandaTerima();
		}

		if (kelasSelect) {
			kelasSelect.addEventListener('change', syncKelasTandaTerima);
		}

		if (extraInput) {
			extraInput.addEventListener('input', rebuildExtraSlots);
			extraInput.addEventListener('change', rebuildExtraSlots);
		}

		var cetakTtBtn = document.getElementById('kgtk-btn-cetak-tt');
		if (cetakTtBtn) {
			cetakTtBtn.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				clearPrinting();
				var sub = pane.querySelector('.kgtk-sub[data-sub="tanda-terima-peserta"]');
				if (sub) {
					markPrinting(sub);
				}
				window.print();
			});
		}

		syncKelasTandaTerima();
		rebuildExtraSlots();

		/* ---------------- ATK Pengajar / Fasilitator / Pemateri ---------------- */
		var penerimaSelect = document.getElementById('kgtk-atk-penerima-select');
		var customWrap = document.getElementById('kgtk-atk-custom-wrap');
		var customNama = document.getElementById('kgtk-atk-custom-nama');
		var customInstansi = document.getElementById('kgtk-atk-custom-instansi');
		var customNip = document.getElementById('kgtk-atk-custom-nip');
		var penyerahInput = document.getElementById('kgtk-atk-penyerah-input');
		var tanggalInput = document.getElementById('kgtk-atk-tanggal-input');
		var exportPdfAtkBtn = document.getElementById('kgtk-btn-export-pdf-atk');

		var editorTbody = document.getElementById('kgtk-atk-editor-tbody');
		var previewTbody = document.getElementById('kgtk-atk-preview-tbody');
		var btnAddAtk = document.getElementById('kgtk-btn-add-atk');
		var btnResetAtk = document.getElementById('kgtk-btn-reset-atk');
		var btnSaveAtk = document.getElementById('kgtk-btn-save-atk');
		var saveMsg = document.getElementById('kgtk-atk-save-msg');

		var previewPenerimaNama = document.getElementById('kgtk-preview-penerima-nama');
		var previewPenerimaPeran = document.getElementById('kgtk-preview-penerima-peran');
		var previewPenerimaInstansi = document.getElementById('kgtk-preview-penerima-instansi');
		var previewInstansiRow = document.getElementById('kgtk-preview-instansi-row');
		var previewPenyerahNama = document.getElementById('kgtk-preview-penyerah-nama');
		var previewTanggal = document.getElementById('kgtk-preview-tanggal');
		var previewTtdPeran = document.getElementById('kgtk-preview-ttd-peran');
		var previewTtdPenerima = document.getElementById('kgtk-preview-ttd-penerima');
		var previewTtdNipWrap = document.getElementById('kgtk-preview-ttd-nip-wrap');
		var previewTtdNip = document.getElementById('kgtk-preview-ttd-nip');

		function updateAtkMeta() {
			var selVal = penerimaSelect ? penerimaSelect.value : '0';
			var nama = '';
			var nip = '';
			var instansi = '';
			var peran = 'Pengajar / Fasilitator';

			if (selVal === 'custom') {
				if (customWrap) { customWrap.style.display = 'block'; }
				nama = (customNama ? customNama.value : '').trim();
				instansi = (customInstansi ? customInstansi.value : '').trim();
				nip = (customNip ? customNip.value : '').trim();
			} else {
				if (customWrap) { customWrap.style.display = 'none'; }
				if (selVal !== '0' && penerimaSelect) {
					var selIdx = penerimaSelect.selectedIndex;
					var opt = (selIdx >= 0 && penerimaSelect.options) ? penerimaSelect.options[selIdx] : null;
					if (opt) {
						nama = opt.getAttribute('data-nama') || '';
						nip = opt.getAttribute('data-nip') || '';
						instansi = opt.getAttribute('data-instansi') || '';
						peran = opt.getAttribute('data-peran') || 'Pengajar';
					}
				}
			}

			var penyerah = (penyerahInput ? penyerahInput.value : '').trim();
			var tanggal = (tanggalInput ? tanggalInput.value : '').trim();

			// Update Pratinjau Teks
			if (previewPenerimaNama) {
				previewPenerimaNama.textContent = nama ? nama : '...........................................................................................';
			}
			if (previewPenerimaPeran) {
				previewPenerimaPeran.textContent = nama && peran ? ' (' + peran + ')' : '';
			}
			if (previewInstansiRow) {
				if (instansi) {
					previewInstansiRow.style.display = '';
					if (previewPenerimaInstansi) { previewPenerimaInstansi.textContent = instansi; }
				} else {
					previewInstansiRow.style.display = 'none';
				}
			}
			if (previewPenyerahNama) {
				previewPenyerahNama.textContent = penyerah || 'Panitia Pelaksana';
			}
			if (previewTanggal) {
				previewTanggal.textContent = tanggal;
			}
			if (previewTtdPeran) {
				previewTtdPeran.textContent = peran || 'Pengajar / Fasilitator';
			}
			if (previewTtdPenerima) {
				previewTtdPenerima.textContent = nama ? nama : '( ............................................................ )';
			}
			if (previewTtdNipWrap) {
				if (nip) {
					previewTtdNipWrap.style.display = '';
					if (previewTtdNip) { previewTtdNip.textContent = nip; }
				} else {
					previewTtdNipWrap.style.display = 'none';
				}
			}

			// Update URL Export PDF
			if (exportPdfAtkBtn) {
				var baseAtkUrl = exportPdfAtkBtn.getAttribute('data-base-url') || exportPdfAtkBtn.href;
				exportPdfAtkBtn.setAttribute('data-base-url', baseAtkUrl);
				try {
					var u = new URL(baseAtkUrl, window.location.origin);
					if (selVal !== '0' && selVal !== 'custom') {
						u.searchParams.set('penerima_id', selVal);
						u.searchParams.delete('penerima_nama');
					} else if (selVal === 'custom' && nama) {
						u.searchParams.delete('penerima_id');
						u.searchParams.set('penerima_nama', nama);
					} else {
						u.searchParams.delete('penerima_id');
						u.searchParams.delete('penerima_nama');
					}
					if (penyerah) {
						u.searchParams.set('penyerah_nama', penyerah);
					} else {
						u.searchParams.delete('penyerah_nama');
					}
					if (tanggal) {
						u.searchParams.set('tanggal', tanggal);
					} else {
						u.searchParams.delete('tanggal');
					}
					exportPdfAtkBtn.href = u.toString();
				} catch (ePdf) {
					var hAtk = baseAtkUrl;
					hAtk = hAtk.replace(/([?&])penerima_id=[^&]*/, '');
					hAtk = hAtk.replace(/([?&])penerima_nama=[^&]*/, '');
					hAtk = hAtk.replace(/([?&])penyerah_nama=[^&]*/, '');
					hAtk = hAtk.replace(/([?&])tanggal=[^&]*/, '');
					if (selVal !== '0' && selVal !== 'custom') {
						hAtk += (hAtk.indexOf('?') > -1 ? '&' : '?') + 'penerima_id=' + encodeURIComponent(selVal);
					} else if (selVal === 'custom' && nama) {
						hAtk += (hAtk.indexOf('?') > -1 ? '&' : '?') + 'penerima_nama=' + encodeURIComponent(nama);
					}
					if (penyerah) {
						hAtk += (hAtk.indexOf('?') > -1 ? '&' : '?') + 'penyerah_nama=' + encodeURIComponent(penyerah);
					}
					if (tanggal) {
						hAtk += (hAtk.indexOf('?') > -1 ? '&' : '?') + 'tanggal=' + encodeURIComponent(tanggal);
					}
					exportPdfAtkBtn.href = hAtk;
				}
			}
		}

		function syncAtkTablePreview() {
			if (!editorTbody || !previewTbody) { return; }
			var rows = editorTbody.querySelectorAll('.kgtk-atk-row');
			previewTbody.innerHTML = '';

			var validCount = 0;
			Array.prototype.forEach.call(rows, function (r, idx) {
				var noCol = r.querySelector('.kgtk-atk-row-no');
				if (noCol) { noCol.textContent = String(idx + 1); }

				var namaInp = r.querySelector('.kgtk-atk-nama');
				var jmlInp = r.querySelector('.kgtk-atk-jumlah');
				var kuanInp = r.querySelector('.kgtk-atk-kuantitas');
				var ketInp = r.querySelector('.kgtk-atk-keterangan');

				var nama = namaInp ? namaInp.value.trim() : '';
				var jml = jmlInp ? jmlInp.value.trim() : '1';
				var kuan = kuanInp ? kuanInp.value.trim() : 'Buah';
				var ket = ketInp ? ketInp.value.trim() : '';

				if (nama) {
					validCount++;
					var tr = document.createElement('tr');
					tr.innerHTML = '<td class="c-no" style="text-align:center;">' + validCount + '</td>' +
						'<td class="c-nama">' + esc(nama) + '</td>' +
						'<td style="text-align:center;font-weight:bold;">' + esc(jml || '1') + '</td>' +
						'<td style="text-align:center;">' + esc(kuan || 'Buah') + '</td>' +
						'<td>' + (ket ? esc(ket) : '-') + '</td>';
					previewTbody.appendChild(tr);
				}
			});

			if (validCount === 0) {
				var emptyTr = document.createElement('tr');
				emptyTr.innerHTML = '<td colspan="5" style="text-align:center;color:#64748b;padding:12px;">(Belum ada item ATK ditambahkan)</td>';
				previewTbody.appendChild(emptyTr);
			}
		}

		function createAtkRow(nama, jumlah, kuantitas, keterangan) {
			var tr = document.createElement('tr');
			tr.className = 'kgtk-atk-row';
			tr.innerHTML = '<td class="kgtk-atk-row-no" style="text-align:center;font-weight:700;color:#475569;">1</td>' +
				'<td><input type="text" class="kgtk-input kgtk-atk-nama" value="' + esc(nama || '') + '" placeholder="Nama ATK (misal: Spidol Whiteboard)" style="font-size:13px;padding:6px 8px;"></td>' +
				'<td><input type="text" class="kgtk-input kgtk-atk-jumlah" value="' + esc(jumlah || '1') + '" placeholder="1" style="text-align:center;font-size:13px;padding:6px 8px;"></td>' +
				'<td><input type="text" list="kgtk-atk-satuan-list" class="kgtk-input kgtk-atk-kuantitas" value="' + esc(kuantitas || 'Buah') + '" placeholder="Buah / Rim / Set" style="text-align:center;font-size:13px;padding:6px 8px;"></td>' +
				'<td><input type="text" class="kgtk-input kgtk-atk-keterangan" value="' + esc(keterangan || '') + '" placeholder="Merk/warna/tipe (opsional)" style="font-size:13px;padding:6px 8px;"></td>' +
				'<td style="text-align:center;"><button type="button" class="kgtk-btn kgtk-btn--danger kgtk-btn-del-atk" title="Hapus baris ini" style="padding:4px 8px;font-size:12px;line-height:1;">&#128465;&#65039;</button></td>';
			return tr;
		}

		if (editorTbody) {
			editorTbody.addEventListener('input', syncAtkTablePreview);
			editorTbody.addEventListener('change', syncAtkTablePreview);

			editorTbody.addEventListener('click', function (e) {
				var delBtn = e.target.closest('.kgtk-btn-del-atk');
				if (delBtn) {
					var row = delBtn.closest('.kgtk-atk-row');
					if (row) {
						row.remove();
						syncAtkTablePreview();
					}
				}
			});
		}

		if (btnAddAtk && editorTbody) {
			btnAddAtk.addEventListener('click', function () {
				var newRow = createAtkRow('', '1', 'Buah', '');
				editorTbody.appendChild(newRow);
				syncAtkTablePreview();
				var newInp = newRow.querySelector('.kgtk-atk-nama');
				if (newInp) { newInp.focus(); }
			});
		}

		if (btnResetAtk && editorTbody) {
			btnResetAtk.addEventListener('click', function () {
				if (!window.confirm('Kembalikan daftar ATK ke daftar default bawaan? Perubahan yang belum disimpan akan digantikan.')) {
					return;
				}
				var defaults = [
					{ nama: 'Spidol Whiteboard (Hitam/Biru/Merah)', jumlah: '3', kuantitas: 'Buah', keterangan: 'Snowman Boardmarker' },
					{ nama: 'Penghapus Whiteboard', jumlah: '1', kuantitas: 'Buah', keterangan: 'Standar' },
					{ nama: 'Kertas HVS A4 80gr', jumlah: '1', kuantitas: 'Rim', keterangan: 'Sinar Dunia / PaperOne' },
					{ nama: 'Blocknote & Pulpen Gel', jumlah: '1', kuantitas: 'Set', keterangan: 'Untuk catatan pengajar' },
					{ nama: 'Stopmap / Folder Dokumen', jumlah: '2', kuantitas: 'Buah', keterangan: 'Folio / A4' },
					{ nama: 'Sticky Notes / Post-it', jumlah: '1', kuantitas: 'Pad', keterangan: 'Ukuran 3x3 inch' }
				];
				editorTbody.innerHTML = '';
				defaults.forEach(function (d) {
					editorTbody.appendChild(createAtkRow(d.nama, d.jumlah, d.kuantitas, d.keterangan));
				});
				syncAtkTablePreview();
				if (saveMsg) {
					saveMsg.textContent = 'Daftar di-reset. Klik "Simpan Perubahan ATK" untuk menyimpan.';
					saveMsg.style.color = '#0083C4';
				}
			});
		}

		if (btnSaveAtk && editorTbody) {
			btnSaveAtk.addEventListener('click', function () {
				var rows = editorTbody.querySelectorAll('.kgtk-atk-row');
				var items = [];
				Array.prototype.forEach.call(rows, function (r) {
					var nama = (r.querySelector('.kgtk-atk-nama') ? r.querySelector('.kgtk-atk-nama').value : '').trim();
					var jml = (r.querySelector('.kgtk-atk-jumlah') ? r.querySelector('.kgtk-atk-jumlah').value : '').trim();
					var kuan = (r.querySelector('.kgtk-atk-kuantitas') ? r.querySelector('.kgtk-atk-kuantitas').value : '').trim();
					var ket = (r.querySelector('.kgtk-atk-keterangan') ? r.querySelector('.kgtk-atk-keterangan').value : '').trim();
					if (nama) {
						items.push({
							nama: nama,
							jumlah: jml || '1',
							kuantitas: kuan || 'Buah',
							keterangan: ket
						});
					}
				});

				if (items.length === 0) {
					alert('Harap isi minimal 1 item ATK sebelum menyimpan.');
					return;
				}

				btnSaveAtk.disabled = true;
				btnSaveAtk.textContent = 'Menyimpan...';
				if (saveMsg) {
					saveMsg.textContent = '';
				}

				api('admin/pelatihan/' + pelId + '/atk', 'POST', { items: items })
					.then(function (res) {
						btnSaveAtk.disabled = false;
						btnSaveAtk.textContent = 'Simpan Perubahan ATK';
						if (saveMsg) {
							saveMsg.textContent = 'Daftar ATK berhasil disimpan!';
							saveMsg.style.color = '#16a34a';
							setTimeout(function () {
								saveMsg.textContent = '';
							}, 3500);
						}
					})
					.catch(function (err) {
						btnSaveAtk.disabled = false;
						btnSaveAtk.textContent = 'Simpan Perubahan ATK';
						if (saveMsg) {
							saveMsg.textContent = 'Gagal menyimpan: ' + (err.message || 'Error');
							saveMsg.style.color = '#dc2626';
						}
						alert('Gagal menyimpan ATK: ' + (err.message || 'Terjadi kesalahan'));
					});
			});
		}

		if (penerimaSelect) {
			penerimaSelect.addEventListener('change', updateAtkMeta);
		}
		if (customNama) {
			customNama.addEventListener('input', updateAtkMeta);
		}
		if (customInstansi) {
			customInstansi.addEventListener('input', updateAtkMeta);
		}
		if (customNip) {
			customNip.addEventListener('input', updateAtkMeta);
		}
		if (penyerahInput) {
			penyerahInput.addEventListener('input', updateAtkMeta);
		}
		if (tanggalInput) {
			tanggalInput.addEventListener('input', updateAtkMeta);
		}

		updateAtkMeta();
		syncAtkTablePreview();
	}
	try {
		initTandaTerima();
	} catch (errTT) {
		console.error('KGTK Init TandaTerima Error:', errTT);
	}

	/* Inisialisasi Checklist & Tanda Terima SPT & SPPD */
	function initBerkasSptSppd() {
		var pane = document.querySelector('.kgtk-pane[data-pane="berkas-spt-sppd"]');
		if (!pane) { return; }
		var kelasSelect = document.getElementById('kgtk-spt-sppd-kelas-select');
		if (kelasSelect) {
			kelasSelect.addEventListener('change', function () {
				var selVal = kelasSelect.value || 'all';
				var groups = pane.querySelectorAll('.kgtk-spt-group');
				Array.prototype.forEach.call(groups, function (grp) {
					grp.style.display = (grp.getAttribute('data-spt-group') === selVal) ? '' : 'none';
				});
			});
		}
	}
	try {
		initBerkasSptSppd();
	} catch (errSPT) {
		console.error('KGTK Init Berkas SPT/SPPD Error:', errSPT);
	}

	/* Tutup modal jika tombol Escape ditekan */
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') {
			var openOverlays = document.querySelectorAll('.kgtk-modal-overlay.is-visible');
			Array.prototype.forEach.call(openOverlays, function (ov) {
				ov.classList.remove('is-visible');
				ov.style.display = 'none';
			});
		}
	});
	console.log('[KGTK] admin.js initialized completely.');
})();
