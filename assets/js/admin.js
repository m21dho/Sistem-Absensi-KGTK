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
			if (!f.nama.value.trim()) { return showErr('Nama pelatihan wajib diisi.'); }
			if (!f.tanggal_mulai.value) { return showErr('Tanggal mulai wajib diisi.'); }
			if (f.tanggal_selesai.value && f.tanggal_selesai.value < f.tanggal_mulai.value) {
				return showErr('Tanggal selesai tidak boleh sebelum tanggal mulai.');
			}
			var kelas = [], seen = {};
			var rows = list.querySelectorAll('.kgtk-kelas-row');
			for (var i = 0; i < rows.length; i++) {
				var n = rows[i].querySelector('[data-knama]').value.trim();
				var v = parseInt(rows[i].querySelector('[data-kslot]').value, 10);
				if (!n) { return showErr('Nama kelas nomor ' + (i + 1) + ' wajib diisi.'); }
				if (seen[n.toLowerCase()]) { return showErr('Nama kelas tidak boleh sama: ' + n + '.'); }
				if (!(v >= 1 && v <= 2000)) { return showErr('Slot kelas ' + n + ' harus antara 1 dan 2000.'); }
				seen[n.toLowerCase()] = true;
				kelas.push({ nama: n, slot: v });
			}
			if (!kelas.length) { return showErr('Tambahkan minimal satu kelas.'); }

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
			btn.disabled = true; btn.textContent = 'Menyimpan...';
			api('admin/pelatihan', 'POST', {
				nama: f.nama.value, penyelenggara: f.penyelenggara.value,
				tanggal_mulai: f.tanggal_mulai.value, tanggal_selesai: f.tanggal_selesai.value,
				lokasi: f.lokasi.value,
				kabkota: f.kabkota ? f.kabkota.value : '',
				tipe_pj: f.tipe_pj ? f.tipe_pj.value : 'Penanggung Jawab',
				penanggung_jawab: f.penanggung_jawab.value, keterangan: f.keterangan.value,
				kode: f.kode.value, kelas: kelas, undangan: undangan, panitia: panitia
			}).then(function (d) { window.location.href = d.url || C.list; })
				.catch(function (err) { btn.disabled = false; btn.textContent = 'Simpan pelatihan'; showErr(err.message); });
		});
	}

	/* Tab kelas & cetak - Event Delegation pada document agar tahan terhadap render timing */
	document.addEventListener('click', function (e) {
		var t = e.target.closest('.kgtk-tab');
		if (!t) { return; }
		var id = t.getAttribute('data-tab');
		if (!id) { return; }
		var allTabs = document.querySelectorAll('.kgtk-tab');
		var allPanes = document.querySelectorAll('.kgtk-pane');
		Array.prototype.forEach.call(allTabs, function (x) {
			x.classList.toggle('is-active', x === t);
		});
		Array.prototype.forEach.call(allPanes, function (p) {
			p.classList.toggle('is-active', p.getAttribute('data-pane') === id);
		});
		if (window.history && window.history.replaceState) {
			window.history.replaceState(null, '', '#' + id);
		}
	});

	function activateTabFromHash() {
		if (window.location.hash) {
			var activeHash = window.location.hash.replace('#', '');
			var activeTabEl = document.querySelector('.kgtk-tab[data-tab="' + activeHash + '"]');
			if (activeTabEl) {
				activeTabEl.click();
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
		var FIELDS = ['nama', 'gelar_depan', 'gelar_belakang', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'alamat', 'alamat_instansi', 'pangkat_golongan', 'agama', 'pendidikan', 'bank', 'rekening', 'npwp', 'pengalaman_jabatan', 'peran', 'jenis_gtk', 'nuptk', 'nip', 'hp', 'email', 'email_belajar', 'simpkb'];
		var EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
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
				wrapJenisGtk.style.display = pakaiSlot ? '' : 'none';
				if (!pakaiSlot && F.jenis_gtk) { F.jenis_gtk.value = ''; }
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

			// HANYA untuk Panitia: sembunyikan nama bank, nomor bank, email belajar.id, nuptk, simpkb, pengalaman jabatan, npwp
			// Untuk yang lainnya (Peserta, Narasumber, Pengajar, Fasilitator, Penceramah, Pemateri): tetap sama / ditampilkan
			if (wrapBankRek) {
				wrapBankRek.style.display = isPanitia ? 'none' : '';
				if (F.bank) { F.bank.required = !isPanitia; }
				if (F.rekening) { F.rekening.required = !isPanitia; }
			}
			if (wrapNpwp) {
				wrapNpwp.style.display = isPanitia ? 'none' : '';
			}
			if (wrapPengalaman) {
				wrapPengalaman.style.display = isPanitia ? 'none' : '';
				if (F.pengalaman_jabatan) { F.pengalaman_jabatan.required = !isPanitia; }
			}
			if (wrapNuptkSimpkb) {
				wrapNuptkSimpkb.style.display = isPanitia ? 'none' : '';
			}
			if (wrapEmailBelajar) {
				wrapEmailBelajar.style.display = isPanitia ? 'none' : '';
				if (inpEmailBelajar) { inpEmailBelajar.required = pakaiSlot; }
				var lblEmailBelajar = document.getElementById('kgtk-lbl-email-belajar');
				if (lblEmailBelajar) {
					lblEmailBelajar.innerHTML = pakaiSlot ? 'Email belajar.id *' : 'Email belajar.id <small style="color:#64748b;font-weight:normal;">(opsional untuk ' + peranVal + ')</small>';
				}
			}
			if (wrapKelas) {
				wrapKelas.style.display = '';
			}

			if (hintPeran) {
				if (pakaiSlot) {
					hintPeran.textContent = 'Peserta akan masuk ke lembar absen kelas dan memotong kuota slot kelas yang dipilih.';
				} else if (isPanitia) {
					hintPeran.textContent = 'Panitia akan otomatis tercatat di lembar hadir Panitia (data rekening, pengalaman, & akun belajar.id ditiadakan).';
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
				if (!d.guru.peran || !F.peran.querySelector('option[value="' + d.guru.peran + '"]')) { F.peran.value = 'Peserta'; }
				applyPeran();
				info.textContent = 'Data tersimpan dari pendaftaran sebelumnya sudah diisikan. Periksa dan edit bila perlu.';
				info.hidden = false;
			}).catch(function () { /* diam: pengisian otomatis hanya bantuan */ });
		});

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
			['nik'].concat(FIELDS).forEach(function (n) { if (F[n]) { v[n] = F[n].value.trim(); } });
			v.email = (v.email || '').toLowerCase();
			v.email_belajar = (v.email_belajar || '').toLowerCase();
			v.simpkb = v.simpkb || '';
			v.nuptk = v.nuptk || '';
			v.nisn = v.nuptk;
			v.email_guru = v.simpkb;

			// Reset indikator invalid sebelumnya
			['nik'].concat(FIELDS).forEach(function (n) { if (F[n]) { F[n].classList.remove('is-invalid'); } });
			if (sek.node) {
				Array.prototype.forEach.call(sek.node.querySelectorAll('.kgtk-input'), function (inp) { inp.classList.remove('is-invalid'); });
			}

			var errors = [];
			if (!/^\d{16}$/.test(v.nik)) {
				errors.push({ label: 'NIK', msg: 'NIK harus 16 digit angka.', el: F.nik });
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
				// HANYA untuk Panitia saja: hapus/kosongkan bank, rekening, email_belajar, nuptk, simpkb, pengalaman jabatan, npwp
				v.bank = '-';
				v.rekening = '-';
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
					errors.push({ label: 'Pengalaman Jabatan', msg: 'Pengalaman jabatan wajib diisi.', el: F.pengalaman_jabatan });
				}
				if (v.peran === 'Peserta') {
					if (!v.jenis_gtk) {
						errors.push({ label: 'Jabatan GTK', msg: 'Pilih Jabatan GTK untuk peran Peserta.', el: F.jenis_gtk });
					}
					if (!EMAIL.test(v.email_belajar) || !/@([a-z0-9-]+\.)*belajar\.id$/.test(v.email_belajar)) {
						errors.push({ label: 'Email belajar.id', msg: 'Email belajar.id wajib diisi dan harus berakhiran belajar.id untuk peran Peserta.', el: F.email_belajar });
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
			if (v.nip && !/^\d{18}$/.test(v.nip)) {
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
		var pane = sub.closest('.kgtk-pane');
		if (pane) { pane.classList.add('kgtk-printing'); }
	}
	document.addEventListener('click', function (e) {
		var b = e.target.closest('[data-cetak]');
		if (!b) { return; }
		clearPrinting();
		markPrinting(document.querySelector('.kgtk-sub[data-sub="' + b.getAttribute('data-cetak') + '"]'));
		window.print();
	});
	// Ctrl+P manual: cetak lembar yang sedang tampil.
	window.addEventListener('beforeprint', function () {
		if (!document.querySelector('.kgtk-sub.kgtk-printing')) {
			markPrinting(document.querySelector('.kgtk-pane.is-active .kgtk-sub.is-active'));
		}
	});
	window.addEventListener('afterprint', clearPrinting);

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

	function filterRows() {
		var q = (searchInput ? searchInput.value : '').toLowerCase().trim();
		var rows = document.querySelectorAll('#kgtk-tbl-peserta tbody tr');
		Array.prototype.forEach.call(rows, function (r) {
			var text = r.getAttribute('data-search') || '';
			var rowKelas = r.getAttribute('data-kelas');
			var matchSearch = !q || text.indexOf(q) > -1;
			var matchFilter = currentFilter === 'all' || rowKelas === currentFilter;
			r.style.display = (matchSearch && matchFilter) ? '' : 'none';
		});
	}

	function setActiveFilter(filterVal) {
		currentFilter = filterVal;
		// Sync filter buttons
		var allBtns = document.querySelectorAll('.kgtk-fbtn');
		Array.prototype.forEach.call(allBtns, function (b) {
			b.classList.toggle('is-active', b.getAttribute('data-filter') === filterVal);
		});
		// Sync stat cards
		var allScards = document.querySelectorAll('.kgtk-scard');
		Array.prototype.forEach.call(allScards, function (s) {
			s.classList.toggle('is-active', s.getAttribute('data-filter') === filterVal);
		});
		filterRows();
	}

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

	// Klik tombol filter
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.kgtk-fbtn');
		if (btn) {
			if (btn.closest('#kgtk-undangan-container')) { return; }
			setActiveFilter(btn.getAttribute('data-filter'));
			return;
		}
		// Klik kartu statistik
		var scard = e.target.closest('.kgtk-scard');
		if (scard) {
			if (scard.closest('#kgtk-undangan-container')) { return; }
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
			message: 'Hapus pendaftaran ' + nama + ' dari pelatihan ini?\n\nData peserta akan dihapus dari daftar hadir.',
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

					fetch(C.root + 'admin/pelatihan/' + pelatihanId + '/undangan/upload', {
						method: 'POST',
						credentials: 'same-origin',
						headers: { 'X-WP-Nonce': C.nonce },
						body: fd
					}).then(function (r) {
						return r.json().then(function (d) {
							if (!r.ok) { throw new Error(d.message || 'Gagal mengunggah berkas.'); }
							return d;
						});
					}).then(function (data) {
						impSessionKey = data.session_key;
						var headers = data.headers || [];
						var detected = data.detected || {};
						var preview = data.preview || [];

						var buildSelectOptions = function (selectedIdx) {
							var optHtml = '<option value="-1">-- Lewati --</option>';
							headers.forEach(function (h, i) {
								var sel = (i === selectedIdx) ? ' selected' : '';
								optHtml += '<option value="' + i + '"' + sel + '>' + esc(h || ('Kolom ' + (i + 1))) + '</option>';
							});
							return optHtml;
						};

						mapNama.innerHTML = buildSelectOptions(detected.nama !== false ? detected.nama : -1);
						mapNik.innerHTML = buildSelectOptions(detected.nik !== false ? detected.nik : -1);
						mapHp.innerHTML = buildSelectOptions(detected.hp !== false ? detected.hp : -1);
						mapEmail.innerHTML = buildSelectOptions(detected.email_belajar !== false ? detected.email_belajar : -1);

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

					api('admin/pelatihan/' + pelatihanId + '/undangan/import', 'POST', {
						session_key: impSessionKey,
						mapping: {
							nama: vNama,
							nik: vNik,
							hp: vHp,
							email_belajar: vEmail
						}
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
					message: 'Hapus ' + uNamaD + ' dari daftar peserta yang ditetapkan?',
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
				var belajarLink = b.email_belajar && b.email_belajar !== '-' ? '<a href="mailto:' + escHtml(b.email_belajar) + '" style="color:var(--blue);text-decoration:none;">' + escHtml(b.email_belajar) + '</a>' : '-';

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

					var html = '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Biodata - ' + escHtml(namaLengkap) + '</title>' +
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
						'<div class="title-main">BIODATA ' + escHtml(peranUpper) + '</div>' +
						'<div class="title-sub">' + escHtml(b.pelatihan) + '</div>' +
						(b.pelatihan_tgl ? '<div class="title-date">' + escHtml(b.pelatihan_tgl) + '</div>' : '') +
						(lokasiTxt ? '<div class="title-loc">' + escHtml(lokasiTxt) + '</div>' : '') +
						'</div>' +
						'<table class="bio-box">' + trRows + '</table>' +
						'<table class="ttd-tbl"><tr><td>&nbsp;</td><td class="ttd-right">' +
						'<div>' + escHtml(kabkotaTxt) + ', ' + escHtml(tglCetak) + '</div>' +
						'<div class="space">&nbsp;</div>' +
						'<div><u><strong>' + escHtml(namaLengkap) + '</strong></u></div>' +
						'</td></tr></table>' +
						'<script>window.onload=function(){window.print();}<\/script>' +
						'</body></html>';

					var w = window.open('', '_blank', 'width=800,height=750');
					if (w) { w.document.write(html); w.document.close(); }
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

		// 4. Update teks label tombol Cetak di setiap subtab
		var printButtons = document.querySelectorAll('[data-cetak]');
		Array.prototype.forEach.call(printButtons, function (btn) {
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
					var fMap = {
						'kgtk-pan-field-nama': data.nama || '',
						'kgtk-pan-field-jabatan': data.jabatan || 'Panitia Pelaksana',
						'kgtk-pan-field-nik': (data.nik && data.nik.indexOf('99') !== 0) ? data.nik : '',
						'kgtk-pan-field-nip': data.nip || '',
						'kgtk-pan-field-nuptk': data.nuptk || '',
						'kgtk-pan-field-instansi': data.instansi || '',
						'kgtk-pan-field-kabkota': data.kabkota || '',
						'kgtk-pan-field-hp': data.hp || '',
						'kgtk-pan-field-email': data.email || '',
						'kgtk-pan-field-npwp': data.npwp || ''
					};
					for (var fieldId in fMap) {
						var el = document.getElementById(fieldId);
						if (el) { el.value = fMap[fieldId]; }
					}
				} else {
					var jEl = document.getElementById('kgtk-pan-field-jabatan');
					if (jEl) { jEl.value = 'Panitia Pelaksana'; }
				}
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
		['kgtk-pan-field-nik', 'kgtk-pan-field-nuptk'].forEach(function (id) {
			var el = document.getElementById(id);
			if (el) {
				el.addEventListener('input', function (e) {
					e.target.value = e.target.value.replace(/\D/g, '').slice(0, 16);
				});
			}
		});
		var nipEl = document.getElementById('kgtk-pan-field-nip');
		if (nipEl) {
			nipEl.addEventListener('input', function (e) {
				e.target.value = e.target.value.replace(/\D/g, '').slice(0, 18);
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
				var jabatan = (f.jabatan.value || '').trim();
				var nik = (f.nik.value || '').trim();
				var nip = (f.nip.value || '').trim();
				var nuptk = (f.nuptk ? f.nuptk.value : '').trim();
				var instansi = (f.instansi ? f.instansi.value : '').trim();
				var kabkota = (f.kabkota ? f.kabkota.value : '').trim();
				var hp = (f.hp ? f.hp.value : '').trim();
				var email = (f.email ? f.email.value : '').trim();
				var npwp = (f.npwp ? f.npwp.value : '').trim();

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
				if (nik && !/^\d{16}$/.test(nik)) {
					if (errBox) { errBox.textContent = 'NIK harus berupa 16 digit angka (atau kosongkan bila belum ada).'; errBox.hidden = false; }
					f.nik.focus();
					return;
				}
				if (nip && !/^\d{18}$/.test(nip)) {
					if (errBox) { errBox.textContent = 'NIP harus berupa 18 digit angka (atau kosongkan bila bukan PNS).'; errBox.hidden = false; }
					f.nip.focus();
					return;
				}

				var saveBtn = document.getElementById('kgtk-btn-form-panitia-save');
				if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = 'Menyimpan...'; }

				var payload = {
					nama: nama,
					jabatan: jabatan,
					nik: nik,
					nip: nip,
					nuptk: nuptk,
					instansi: instansi,
					kabkota: kabkota,
					hp: hp,
					email: email,
					npwp: npwp
				};

				var method = id > 0 ? 'PUT' : 'POST';
				var path = id > 0 ? 'admin/panitia/' + id : 'admin/panitia';

				api(path, method, payload).then(function () {
					closeModal();
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
		if (f.nama) { f.nama.value = data.nama || ''; }
		if (f.penyelenggara) { f.penyelenggara.value = data.penyelenggara || ''; }
		if (f.tanggal_mulai) { f.tanggal_mulai.value = data.tanggal_mulai || ''; }
		if (f.tanggal_selesai) { f.tanggal_selesai.value = data.tanggal_selesai || data.tanggal_mulai || ''; }
		if (f.lokasi) { f.lokasi.value = data.lokasi || ''; }
		if (f.kabkota) { f.kabkota.value = data.kabkota || ''; }
		if (f.tipe_pj) { f.tipe_pj.value = (data.tipe_pj === 'Ketua') ? 'Ketua' : 'Penanggung Jawab'; }
		if (f.penanggung_jawab) { f.penanggung_jawab.value = data.penanggung_jawab || ''; }
		if (f.kode) { f.kode.value = data.kode || ''; }
		if (f.keterangan) { f.keterangan.value = data.keterangan || ''; }

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

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			if (errBox) {
				errBox.hidden = true;
				errBox.textContent = '';
			}

			var f = form.elements;
			var id = parseInt(f.id.value || '0', 10);
			var nama = (f.nama.value || '').trim();
			var penyelenggara = (f.penyelenggara.value || '').trim();
			var mulai = (f.tanggal_mulai.value || '').trim();
			var selesai = (f.tanggal_selesai.value || '').trim() || mulai;
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

			var simpanBtn = document.getElementById('kgtk-btn-edit-pelatihan-simpan');
			if (simpanBtn) {
				simpanBtn.disabled = true;
				simpanBtn.textContent = 'Menyimpan...';
			}

			api('admin/pelatihan/' + id, 'PUT', {
				nama: nama,
				penyelenggara: penyelenggara,
				tanggal_mulai: mulai,
				tanggal_selesai: selesai,
				lokasi: lokasi,
				kabkota: kabkota,
				tipe_pj: tipePj,
				penanggung_jawab: penanggungJawab,
				kode: kode,
				keterangan: keterangan
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
	   PANE PANITIA PELATIHAN & LEMBAR HADIR PANITIA
	   ========================================================================== */
	function initPanitiaPelatihan() {
		var container = document.getElementById('kgtk-panitia-container');
		if (!container) {
			return;
		}

		var pelId = container.getAttribute('data-pelatihan') || '0';

		// Live search tabel panitia
		var panSearch = document.getElementById('kgtk-panitia-pel-search');
		var panTable = document.getElementById('kgtk-tbl-panitia-pelatihan');
		if (panSearch && panTable) {
			panSearch.addEventListener('input', function (e) {
				var q = (e.target.value || '').trim().toLowerCase();
				var rows = panTable.querySelectorAll('tbody tr[data-search]');
				var visIdx = 1;
				Array.prototype.forEach.call(rows, function (row) {
					var s = row.getAttribute('data-search') || '';
					var match = (!q || s.indexOf(q) > -1);
					row.style.display = match ? '' : 'none';
					if (match) {
						var numCell = row.querySelector('.kgtk-row-num');
						if (numCell) { numCell.textContent = visIdx + '.'; }
						visIdx++;
					}
				});
			});
		}

		// Hapus panitia dari pelatihan
		container.addEventListener('click', function (e) {
			var delBtn = e.target.closest('[data-del-panitia-pelatihan]');
			if (!delBtn) { return; }
			var pesertaId = delBtn.getAttribute('data-del-panitia-pelatihan');
			var nama = delBtn.getAttribute('data-nama') || 'panitia ini';

			modalConfirm({
				title: 'Hapus Panitia dari Pelatihan',
				message: 'Hapus ' + nama + ' dari susunan panitia kegiatan pelatihan ini?\n\n(Data master profil panitia tetap tersimpan di database dan dapat digunakan kembali).',
				confirmText: 'Ya, Hapus',
				cancelText: 'Batal',
				type: 'danger'
			}).then(function (ok) {
				if (!ok) { return; }
				delBtn.disabled = true;
				api('admin/pelatihan/' + pelId + '/panitia/' + pesertaId, 'DELETE').then(function () {
					window.location.hash = 'panitia';
					window.location.reload();
				}).catch(function (err) {
					delBtn.disabled = false;
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
						nip: nipM
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
					exportPdfBtn.href = u.toString();
				} catch (e) {
					var h = baseUrl;
					h = h.replace(/([?&])barang=[^&]*/, '');
					h = h.replace(/([?&])kelas_id=[^&]*/, '');
					if (val) { h += (h.indexOf('?') > -1 ? '&' : '?') + 'barang=' + encodeURIComponent(val); }
					if (kelasSelect && kelasSelect.value && kelasSelect.value !== 'all') {
						h += (h.indexOf('?') > -1 ? '&' : '?') + 'kelas_id=' + encodeURIComponent(kelasSelect.value);
					}
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

		if (kelasSelect) {
			kelasSelect.addEventListener('change', function () {
				var selVal = kelasSelect.value || 'all';
				var groups = pane.querySelectorAll('.kgtk-tt-group');
				Array.prototype.forEach.call(groups, function (grp) {
					grp.style.display = (grp.getAttribute('data-tt-group') === selVal) ? '' : 'none';
				});
				updateTandaTerima();
			});
		}

		updateTandaTerima();

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
						btnSaveAtk.textContent = '💾 Simpan Perubahan ATK';
						if (saveMsg) {
							saveMsg.textContent = '✓ Daftar ATK berhasil disimpan!';
							saveMsg.style.color = '#16a34a';
							setTimeout(function () {
								saveMsg.textContent = '';
							}, 3500);
						}
					})
					.catch(function (err) {
						btnSaveAtk.disabled = false;
						btnSaveAtk.textContent = '💾 Simpan Perubahan ATK';
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
