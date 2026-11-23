<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * View: Pengaturan Plugin KGTK Pelatihan
 *
 * @var string $lebar   Pilihan lebar halaman pendaftaran ('penuh' atau 'tema').
 * @var string $wa_help Nomor WhatsApp bantuan admin KGTK.
 */
?>
<div class="wrap kgtk-wrap kgtk-ui">
	<?php KGTK_Admin::head( 'Pengaturan', 'Pengaturan sistem KGTK.' ); ?>

	<?php if ( ! empty( $saved_notice ) ) : ?>
		<div class="kgtk-ok" role="status" style="margin-bottom:14px;">Pengaturan berhasil disimpan.</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( KGTK_Admin::page_url( 'pengaturan' ) ); ?>" class="kgtk-card kgtk-form">
		<?php wp_nonce_field( 'kgtk_save_settings', '_kgtk_settings_nonce' ); ?>

		<label class="kgtk-field">
			<span>Lebar halaman pendaftaran</span>
			<select class="kgtk-input" name="kgtk_lebar" style="max-width:420px">
				<option value="penuh" <?php selected( 'tema' === $lebar ? 'tema' : 'penuh', 'penuh' ); ?>>Layar penuh (selebar layar, judul halaman tema disembunyikan)</option>
				<option value="tema" <?php selected( 'tema' === $lebar ? 'tema' : 'penuh', 'tema' ); ?>>Ikuti lebar kolom konten tema</option>
			</select>
			<small>Header dan footer situs tetap tampil. Jika hasilnya masih sempit, tema atau page builder Anda membatasi lebar halaman: pilih template halaman "Lebar penuh" di pengaturan halaman.</small>
		</label>

		<label class="kgtk-field">
			<span>Nomor WhatsApp Bantuan (Admin KGTK)</span>
			<input type="text" class="kgtk-input" name="kgtk_wa_help" value="<?php echo esc_attr( $wa_help ); ?>" placeholder="+62 813-6798-163" style="max-width:420px">
			<small>Nomor WhatsApp yang akan dihubungi oleh guru ketika mengklik bantuan WhatsApp di halaman pendaftaran.</small>
		</label>

		<div class="kgtk-field" style="background:#fff;border:1.5px solid #cbd5e1;border-radius:14px;padding:20px 22px;margin:20px 0;box-shadow:0 2px 8px rgba(15,23,42,0.04);">
			<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid #e2e8f0;">
				<div>
					<span style="font-size:15.5px;font-weight:800;color:#0b2d56;display:flex;align-items:center;gap:8px;">
						<span>&#128100; Kelola Opsi Peran Pendaftaran</span>
						<span id="kgtk-peran-total-badge" style="background:#dbeafe;color:#1e40af;font-size:11.5px;font-weight:700;padding:2px 10px;border-radius:999px;">
							<?php echo count( KGTK_Helpers::peran_list() ); ?> Opsi Aktif
						</span>
					</span>
					<small style="display:block;color:#475569;margin-top:4px;line-height:1.45;">
						Peran yang ditambahkan di sini <strong>otomatis tersimpan &amp; langsung aktif</strong> pada pilihan dropdown <strong>Peran</strong> di formulir pendaftaran pelatihan peserta.
					</small>
				</div>
			</div>

			<!-- Form Tambah Peran Baru (Paling Atas Agar Langsung Terlihat) -->
			<div style="background:#f0f7ff;border:1.5px solid #bfdbfe;border-radius:10px;padding:14px 16px;margin-bottom:16px;">
				<label for="kgtk-new-peran-input" style="font-size:13px;font-weight:700;color:#0b2d56;display:block;margin-bottom:6px;">
					+ Tambah Opsi Peran Baru:
				</label>
				<div style="display:flex;align-items:center;gap:10px;max-width:600px;flex-wrap:wrap;">
					<input type="text" class="kgtk-input" id="kgtk-new-peran-input" name="kgtk_new_peran_input" maxlength="100" placeholder="Ketik nama peran baru (mis. Moderator, Pendamping, Tim Teknis...)" style="flex:1;min-width:250px;background:#fff;border-color:#93c5fd;height:40px;font-size:13.5px;">
					<button type="button" class="kgtk-btn kgtk-btn--navy" id="kgtk-btn-add-peran" style="flex-shrink:0;height:40px;padding:0 18px;font-weight:700;display:inline-flex;align-items:center;gap:6px;">
						<span>+ Tambahkan &amp; Aktifkan</span>
					</button>
				</div>
				<small style="display:block;color:#475569;margin-top:6px;font-size:12px;">
					Tekan <strong>Enter</strong> atau klik <strong>+ Tambahkan &amp; Aktifkan</strong>. Peran akan langsung tersimpan ke sistem.
				</small>

				<!-- Banner Notifikasi Feedback Instan -->
				<div id="kgtk-peran-feedback" role="status" style="display:none;margin-top:10px;padding:9px 13px;border-radius:8px;font-size:12.5px;font-weight:600;align-items:center;gap:8px;"></div>
			</div>

			<!-- Daftar Peran Tambahan (Kustom Admin) -->
			<div style="margin-bottom:16px;">
				<div style="display:flex;align-items:center;justify-content:space-between;max-width:640px;margin-bottom:8px;">
					<span style="font-size:12.5px;font-weight:700;color:#0b2d56;text-transform:uppercase;letter-spacing:0.4px;">
						Daftar Peran Tambahan Admin (<span id="kgtk-custom-peran-count"><?php echo count( $custom_peran ); ?></span>):
					</span>
					<span id="kgtk-peran-saving-indicator" style="display:none;font-size:12px;color:#0284c7;font-weight:600;">&#8987; Menyimpan perubahan...</span>
				</div>

				<div id="kgtk-custom-peran-list" style="display:flex;flex-direction:column;gap:8px;max-width:640px;">
					<?php if ( ! empty( $custom_peran ) ) : ?>
						<?php foreach ( $custom_peran as $idx => $cp ) : ?>
							<div class="kgtk-custom-peran-row" data-role="<?php echo esc_attr( $cp ); ?>" style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 14px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;transition:all 0.2s;">
								<div style="display:flex;align-items:center;gap:10px;flex:1;min-width:0;">
									<span class="kgtk-peran-num" style="width:24px;height:24px;border-radius:6px;background:#e0f2fe;color:#0369a1;font-weight:700;font-size:12px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;"><?php echo (int) ( $idx + 1 ); ?></span>
									<span class="kgtk-peran-title" style="font-weight:700;font-size:14px;color:#0f172a;"><?php echo esc_html( $cp ); ?></span>
									<input type="text" class="kgtk-input kgtk-peran-edit-inp" name="kgtk_custom_peran[]" value="<?php echo esc_attr( $cp ); ?>" maxlength="100" style="display:none;flex:1;height:34px;font-size:13.5px;background:#fff;">
									<span class="kgtk-peran-status-pill" style="background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;display:inline-flex;align-items:center;gap:4px;flex-shrink:0;">
										&#10003; Aktif di Form
									</span>
								</div>
								<div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
									<button type="button" class="kgtk-btn kgtk-btn--ghost kgtk-btn-edit-peran" style="padding:5px 10px;font-size:12px;" title="Ubah nama peran ini">&#9998; Ubah</button>
									<button type="button" class="kgtk-btn kgtk-btn--navy kgtk-btn-save-edit-peran" style="display:none;padding:5px 10px;font-size:12px;">&#10003; Simpan</button>
									<button type="button" class="kgtk-btn kgtk-btn--danger kgtk-btn-del-peran" style="padding:5px 10px;font-size:12px;" title="Hapus peran ini">&#128465; Hapus</button>
								</div>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>

				<div id="kgtk-custom-peran-empty" style="max-width:640px;padding:16px;background:#f8fafc;border:1.5px dashed #cbd5e1;border-radius:10px;text-align:center;color:#64748b;font-size:13px;<?php echo ! empty( $custom_peran ) ? 'display:none;' : ''; ?>">
					Belum ada peran tambahan kustom. Ketik nama peran pada kotak biru di atas lalu klik <strong>+ Tambahkan &amp; Aktifkan</strong>.
				</div>
			</div>

			<!-- Peran Bawaan Sistem -->
			<div style="padding-top:12px;border-top:1px dashed #e2e8f0;">
				<div style="font-size:12px;font-weight:600;color:#64748b;margin-bottom:8px;">Peran Bawaan Sistem (8 Peran Standar — Selalu Aktif):</div>
				<div style="display:flex;flex-wrap:wrap;gap:6px;">
					<?php foreach ( KGTK_Helpers::PERAN as $def_p ) : ?>
						<span class="kgtk-badge" style="background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;font-weight:600;font-size:12px;padding:4px 10px;border-radius:6px;display:inline-flex;align-items:center;gap:4px;">
							<span style="color:#16a34a;">&#10003;</span> <?php echo esc_html( $def_p ); ?>
						</span>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<script>
		(function() {
			var listEl = document.getElementById('kgtk-custom-peran-list');
			var emptyEl = document.getElementById('kgtk-custom-peran-empty');
			var inpNew = document.getElementById('kgtk-new-peran-input');
			var btnAdd = document.getElementById('kgtk-btn-add-peran');
			var feedbackEl = document.getElementById('kgtk-peran-feedback');
			var countEl = document.getElementById('kgtk-custom-peran-count');
			var totalBadgeEl = document.getElementById('kgtk-peran-total-badge');
			var savingEl = document.getElementById('kgtk-peran-saving-indicator');
			var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
			var nonce = <?php echo wp_json_encode( wp_create_nonce( 'kgtk_save_settings' ) ); ?>;
			var defaultPeran = <?php echo wp_json_encode( array_map( 'strtolower', KGTK_Helpers::PERAN ) ); ?>;
			var feedbackTimer = null;

			if (!listEl || !inpNew || !btnAdd) { return; }

			function showFeedback(type, htmlMsg) {
				if (!feedbackEl) { return; }
				if (feedbackTimer) { clearTimeout(feedbackTimer); }
				if (type === 'success') {
					feedbackEl.style.background = '#dcfce7';
					feedbackEl.style.color = '#166534';
					feedbackEl.style.border = '1px solid #86efac';
				} else if (type === 'warn') {
					feedbackEl.style.background = '#fef3c7';
					feedbackEl.style.color = '#92400e';
					feedbackEl.style.border = '1px solid #fcd34d';
				} else {
					feedbackEl.style.background = '#fee2e2';
					feedbackEl.style.color = '#991b1b';
					feedbackEl.style.border = '1px solid #fca5a5';
				}
				feedbackEl.innerHTML = htmlMsg;
				feedbackEl.style.display = 'flex';
				feedbackTimer = setTimeout(function() {
					feedbackEl.style.display = 'none';
				}, 6000);
			}

			function getAllCustomValues() {
				var inputs = listEl.querySelectorAll('input[name="kgtk_custom_peran[]"]');
				var vals = [];
				for (var i = 0; i < inputs.length; i++) {
					var v = (inputs[i].value || '').trim();
					if (v) { vals.push(v); }
				}
				return vals;
			}

			function syncUI() {
				var rows = listEl.querySelectorAll('.kgtk-custom-peran-row');
				var cnt = rows.length;
				if (emptyEl) { emptyEl.style.display = cnt > 0 ? 'none' : ''; }
				if (countEl) { countEl.textContent = String(cnt); }
				if (totalBadgeEl) { totalBadgeEl.textContent = String(defaultPeran.length + cnt) + ' Opsi Aktif'; }
				for (var i = 0; i < rows.length; i++) {
					var numEl = rows[i].querySelector('.kgtk-peran-num');
					if (numEl) { numEl.textContent = String(i + 1); }
				}
			}

			function saveToServer(feedbackMessage, feedbackType, highlightRow) {
				if (savingEl) { savingEl.style.display = 'inline'; }
				var fd = new FormData();
				fd.append('action', 'kgtk_save_custom_peran');
				fd.append('nonce', nonce);
				var vals = getAllCustomValues();
				for (var i = 0; i < vals.length; i++) {
					fd.append('peran_list[]', vals[i]);
				}
				fetch(ajaxUrl, {
					method: 'POST',
					body: fd,
					credentials: 'same-origin'
				}).then(function(r) { return r.json(); }).then(function(res) {
					if (savingEl) { savingEl.style.display = 'none'; }
					if (res && res.success) {
						showFeedback(feedbackType || 'success', feedbackMessage);
						if (highlightRow) {
							highlightRow.style.borderColor = '#22c55e';
							highlightRow.style.background = '#f0fdf4';
							setTimeout(function() {
								highlightRow.style.borderColor = '#e2e8f0';
								highlightRow.style.background = '#f8fafc';
							}, 1800);
						}
					} else {
						showFeedback('error', '&#9888; Gagal menyimpan otomatis. Silakan klik tombol Simpan pengaturan di bawah.');
					}
				}).catch(function() {
					if (savingEl) { savingEl.style.display = 'none'; }
					showFeedback('warn', '&#9888; Perubahan ditambahkan ke daftar. Klik tombol Simpan pengaturan di bawah untuk menyimpan.');
				});
			}

			function createRowElement(val, index) {
				var row = document.createElement('div');
				row.className = 'kgtk-custom-peran-row';
				row.setAttribute('data-role', val);
				row.style.cssText = 'display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 14px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;transition:all 0.2s;';

				var left = document.createElement('div');
				left.style.cssText = 'display:flex;align-items:center;gap:10px;flex:1;min-width:0;';

				var num = document.createElement('span');
				num.className = 'kgtk-peran-num';
				num.style.cssText = 'width:24px;height:24px;border-radius:6px;background:#e0f2fe;color:#0369a1;font-weight:700;font-size:12px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;';
				num.textContent = String(index);

				var title = document.createElement('span');
				title.className = 'kgtk-peran-title';
				title.style.cssText = 'font-weight:700;font-size:14px;color:#0f172a;';
				title.textContent = val;

				var inp = document.createElement('input');
				inp.type = 'text';
				inp.className = 'kgtk-input kgtk-peran-edit-inp';
				inp.name = 'kgtk_custom_peran[]';
				inp.maxLength = 100;
				inp.value = val;
				inp.style.cssText = 'display:none;flex:1;height:34px;font-size:13.5px;background:#fff;';

				var pill = document.createElement('span');
				pill.className = 'kgtk-peran-status-pill';
				pill.style.cssText = 'background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;display:inline-flex;align-items:center;gap:4px;flex-shrink:0;';
				pill.innerHTML = '&#10003; Baru Ditambahkan &amp; Aktif';

				left.appendChild(num);
				left.appendChild(title);
				left.appendChild(inp);
				left.appendChild(pill);

				var right = document.createElement('div');
				right.style.cssText = 'display:flex;align-items:center;gap:6px;flex-shrink:0;';

				var btnEdit = document.createElement('button');
				btnEdit.type = 'button';
				btnEdit.className = 'kgtk-btn kgtk-btn--ghost kgtk-btn-edit-peran';
				btnEdit.style.cssText = 'padding:5px 10px;font-size:12px;';
				btnEdit.innerHTML = '&#9998; Ubah';

				var btnSaveEdit = document.createElement('button');
				btnSaveEdit.type = 'button';
				btnSaveEdit.className = 'kgtk-btn kgtk-btn--navy kgtk-btn-save-edit-peran';
				btnSaveEdit.style.cssText = 'display:none;padding:5px 10px;font-size:12px;';
				btnSaveEdit.innerHTML = '&#10003; Simpan';

				var btnDel = document.createElement('button');
				btnDel.type = 'button';
				btnDel.className = 'kgtk-btn kgtk-btn--danger kgtk-btn-del-peran';
				btnDel.style.cssText = 'padding:5px 10px;font-size:12px;';
				btnDel.innerHTML = '&#128465; Hapus';

				right.appendChild(btnEdit);
				right.appendChild(btnSaveEdit);
				right.appendChild(btnDel);

				row.appendChild(left);
				row.appendChild(right);
				return row;
			}

			function handleAddPeran() {
				var raw = (inpNew.value || '').trim();
				if (!raw) {
					showFeedback('warn', '&#9888; Silakan ketik nama peran terlebih dahulu pada kolom di atas.');
					inpNew.focus();
					return;
				}
				var parts = raw.split(',');
				var addedNames = [];
				var duplicateNames = [];
				var lastAddedRow = null;

				for (var p = 0; p < parts.length; p++) {
					var val = (parts[p] || '').trim();
					if (!val) { continue; }
					var low = val.toLowerCase();
					if (defaultPeran.indexOf(low) > -1) {
						duplicateNames.push(val + ' (sudah ada di Peran Bawaan)');
						continue;
					}
					var existing = listEl.querySelectorAll('input[name="kgtk_custom_peran[]"]');
					var isDup = false;
					for (var i = 0; i < existing.length; i++) {
						if (existing[i].value.trim().toLowerCase() === low) {
							isDup = true;
							var dupRow = existing[i].closest('.kgtk-custom-peran-row');
							if (dupRow) {
								dupRow.style.borderColor = '#f59e0b';
								dupRow.style.background = '#fffbeb';
								setTimeout((function(r) {
									return function() {
										r.style.borderColor = '#e2e8f0';
										r.style.background = '#f8fafc';
									};
								})(dupRow), 1800);
							}
							break;
						}
					}
					if (isDup) {
						duplicateNames.push(val);
						continue;
					}
					var newRow = createRowElement(val, existing.length + 1);
					listEl.appendChild(newRow);
					lastAddedRow = newRow;
					addedNames.push(val);
				}

				inpNew.value = '';
				syncUI();

				if (addedNames.length > 0) {
					var msg = '&#10003; Peran <strong>"' + addedNames.join(', ') + '"</strong> berhasil ditambahkan dan langsung aktif di form pendaftaran!';
					if (duplicateNames.length > 0) {
						msg += ' (Peran "' + duplicateNames.join(', ') + '" dilewati karena sudah ada).';
					}
					saveToServer(msg, 'success', lastAddedRow);
				} else if (duplicateNames.length > 0) {
					showFeedback('warn', '&#9888; Peran <strong>"' + duplicateNames.join(', ') + '"</strong> sudah terdaftar dan aktif di sistem.');
				}
				inpNew.focus();
			}

			btnAdd.addEventListener('click', handleAddPeran);

			inpNew.addEventListener('keydown', function(e) {
				if (e.key === 'Enter') {
					e.preventDefault();
					handleAddPeran();
				}
			});

			listEl.addEventListener('click', function(e) {
				var btnDel = e.target.closest('.kgtk-btn-del-peran');
				if (btnDel) {
					var row = btnDel.closest('.kgtk-custom-peran-row');
					if (row) {
						var inp = row.querySelector('input[name="kgtk_custom_peran[]"]');
						var roleName = inp ? inp.value : '';
						row.remove();
						syncUI();
						saveToServer('&#128465; Peran <strong>"' + roleName + '"</strong> telah dihapus dari daftar opsi pendaftaran.', 'warn');
					}
					return;
				}

				var btnEdit = e.target.closest('.kgtk-btn-edit-peran');
				if (btnEdit) {
					var rowE = btnEdit.closest('.kgtk-custom-peran-row');
					if (!rowE) { return; }
					var titleEl = rowE.querySelector('.kgtk-peran-title');
					var inpEl = rowE.querySelector('.kgtk-peran-edit-inp');
					var pillEl = rowE.querySelector('.kgtk-peran-status-pill');
					var saveBtn = rowE.querySelector('.kgtk-btn-save-edit-peran');
					if (titleEl && inpEl && saveBtn) {
						titleEl.style.display = 'none';
						if (pillEl) { pillEl.style.display = 'none'; }
						btnEdit.style.display = 'none';
						inpEl.style.display = '';
						saveBtn.style.display = '';
						inpEl.focus();
						inpEl.select();
					}
					return;
				}

				var btnSaveEdit = e.target.closest('.kgtk-btn-save-edit-peran');
				if (btnSaveEdit) {
					var rowS = btnSaveEdit.closest('.kgtk-custom-peran-row');
					if (!rowS) { return; }
					var titleS = rowS.querySelector('.kgtk-peran-title');
					var inpS = rowS.querySelector('.kgtk-peran-edit-inp');
					var pillS = rowS.querySelector('.kgtk-peran-status-pill');
					var editBtnS = rowS.querySelector('.kgtk-btn-edit-peran');
					var newVal = inpS ? inpS.value.trim() : '';
					if (!newVal) {
						showFeedback('warn', '&#9888; Nama peran tidak boleh kosong.');
						if (inpS) { inpS.focus(); }
						return;
					}
					if (titleS) { titleS.textContent = newVal; titleS.style.display = ''; }
					if (inpS) { inpS.value = newVal; inpS.style.display = 'none'; }
					if (pillS) { pillS.innerHTML = '&#10003; Diperbarui &amp; Aktif'; pillS.style.display = 'inline-flex'; }
					btnSaveEdit.style.display = 'none';
					if (editBtnS) { editBtnS.style.display = ''; }
					saveToServer('&#10003; Nama peran berhasil diperbarui menjadi <strong>"' + newVal + '"</strong>.', 'success', rowS);
				}
			});

			listEl.addEventListener('keydown', function(e) {
				if (e.key === 'Enter' && e.target.classList.contains('kgtk-peran-edit-inp')) {
					e.preventDefault();
					var rowK = e.target.closest('.kgtk-custom-peran-row');
					var saveBtnK = rowK ? rowK.querySelector('.kgtk-btn-save-edit-peran') : null;
					if (saveBtnK) { saveBtnK.click(); }
				}
			});
		})();
		</script>

		<label class="kgtk-check">
			<input type="checkbox" name="kgtk_hapus_saat_uninstall" value="1" <?php checked( 1, (int) get_option( 'kgtk_hapus_saat_uninstall', 0 ) ); ?>>
			Hapus semua tabel dan data saat plugin dihapus (uninstall)
		</label>
		<p class="kgtk-muted">Jika dicentang, pelatihan, peserta, dan data guru dihapus permanen saat plugin dihapus dari WordPress. Jika tidak, data tetap tersimpan.</p>

		<div class="kgtk-form-actions">
			<button type="submit" class="kgtk-btn">Simpan pengaturan</button>
		</div>
	</form>
</div>
