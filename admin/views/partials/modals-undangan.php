<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Partial View: Modals untuk Peserta Ditetapkan (Tambah/Ubah, Hubungkan, Impor Excel/CSV)
 *
 * @var object $p Data pelatihan.
 */
?>
<!-- Modal Tambah / Ubah Undangan -->
<div class="kgtk-modal-overlay" id="kgtk-modal-undangan" style="display:none;">
	<div class="kgtk-modal-dialog kgtk-ui" style="max-width:520px;">
		<div class="kgtk-modal-top">
			<div class="kgtk-modal-icon is-primary">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7.5" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/>
				</svg>
			</div>
			<div class="kgtk-modal-content">
				<h3 class="kgtk-modal-title" id="kgtk-undangan-form-title">Tambah Peserta Ditetapkan</h3>
				<p class="kgtk-muted" style="margin:4px 0 12px;font-size:12px;">Cukup isi salah satu saja, sisanya boleh dikosongkan.</p>
				<div id="kgtk-undangan-modal-error" class="kgtk-alert" style="margin-bottom:12px;" hidden></div>
				<form id="kgtk-form-undangan-modal" novalidate>
					<input type="hidden" name="undangan_id" value="0">
					<label class="kgtk-field"><span>Nama Lengkap</span><input type="text" class="kgtk-input" name="nama" maxlength="100" placeholder="Nama peserta"></label>
					<label class="kgtk-field"><span>NIK (16 digit)</span><input type="text" class="kgtk-input" name="nik" maxlength="16" inputmode="numeric" placeholder="3201..."></label>
					<label class="kgtk-field"><span>No. HP / WhatsApp</span><input type="tel" class="kgtk-input" name="hp" maxlength="20" placeholder="0812..."></label>
					<label class="kgtk-field"><span>Email Belajar.id</span><input type="email" class="kgtk-input" name="email_belajar" maxlength="100" placeholder="nama@belajar.id"></label>
					<label class="kgtk-field"><span>Catatan (opsional)</span><input type="text" class="kgtk-input" name="catatan" maxlength="200" placeholder="Mis. Ditetapkan Ditjen GTK"></label>
				</form>
			</div>
		</div>
		<div class="kgtk-modal-actions">
			<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-btn-undangan-cancel">Batal</button>
			<button type="button" class="kgtk-btn kgtk-btn--navy" id="kgtk-btn-undangan-save">Simpan</button>
		</div>
	</div>
</div>

<!-- Modal Hubungkan ke Peserta -->
<div class="kgtk-modal-overlay" id="kgtk-modal-hubungkan" style="display:none;">
	<div class="kgtk-modal-dialog kgtk-ui" style="max-width:540px;">
		<div class="kgtk-modal-top">
			<div class="kgtk-modal-icon is-primary">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
				</svg>
			</div>
			<div class="kgtk-modal-content">
				<h3 class="kgtk-modal-title">Hubungkan ke Peserta Terdaftar</h3>
				<p class="kgtk-muted" style="margin:4px 0 12px;font-size:12px;">Pilih guru yang sudah mendaftar tetapi belum terhubung ke baris peserta ditetapkan manapun.</p>
				<div id="kgtk-hubungkan-info" style="margin-bottom:10px;padding:8px 12px;background:#f1f5f9;border-radius:6px;font-size:12px;"></div>
				<div id="kgtk-hubungkan-modal-error" class="kgtk-alert" style="margin-bottom:12px;" hidden></div>
				<label class="kgtk-field"><span>Pilih Peserta Terdaftar:</span>
					<select class="kgtk-input" id="kgtk-select-unlinked-peserta"><option value="">-- Memuat daftar peserta... --</option></select>
				</label>
			</div>
		</div>
		<div class="kgtk-modal-actions">
			<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-btn-hubungkan-cancel">Batal</button>
			<button type="button" class="kgtk-btn kgtk-btn--navy" id="kgtk-btn-hubungkan-save">Hubungkan</button>
		</div>
	</div>
</div>

<!-- Modal Impor Excel/CSV -->
<div class="kgtk-modal-overlay" id="kgtk-modal-impor" style="display:none;">
	<div class="kgtk-modal-dialog kgtk-ui" style="max-width:620px;">
		<div class="kgtk-modal-top">
			<div class="kgtk-modal-icon is-primary">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
				</svg>
			</div>
			<div class="kgtk-modal-content" style="width:100%;">
				<h3 class="kgtk-modal-title">Impor Peserta Ditetapkan dari Excel/CSV</h3>
				<p class="kgtk-muted" style="margin:4px 0 12px;font-size:12px;">Unggah berkas (.xlsx, .xls, .csv). Cukup salah satu kolom (Nama, NIK, HP, atau Email belajar.id) yang terisi.</p>
				<div id="kgtk-impor-modal-error" class="kgtk-alert" style="margin-bottom:12px;" hidden></div>

				<div id="kgtk-impor-step-1">
					<label class="kgtk-field"><span>Pilih Berkas (.xlsx, .xls, .csv, maks. 10 MB)</span>
						<input type="file" class="kgtk-input" id="kgtk-impor-file-input" accept=".xlsx,.xls,.csv">
					</label>
					<div style="margin-top:12px;font-size:12px;" class="kgtk-muted">Format berkas: Baris pertama berisi judul kolom (mis. Nama, NIK, No. HP, Email). Baris-baris berikutnya berisi data calon peserta.</div>
				</div>

				<div id="kgtk-impor-step-2" style="display:none;">
					<p style="font-size:13px;font-weight:600;margin-bottom:8px;">Cocokkan Kolom Berkas:</p>
					<div class="kgtk-row2" style="gap:10px;">
						<label class="kgtk-field"><span>Kolom Nama:</span><select class="kgtk-input" id="kgtk-map-nama"></select></label>
						<label class="kgtk-field"><span>Kolom NIK:</span><select class="kgtk-input" id="kgtk-map-nik"></select></label>
					</div>
					<div class="kgtk-row2" style="gap:10px;margin-top:8px;">
						<label class="kgtk-field"><span>Kolom No. HP:</span><select class="kgtk-input" id="kgtk-map-hp"></select></label>
						<label class="kgtk-field"><span>Kolom Email Belajar.id:</span><select class="kgtk-input" id="kgtk-map-email"></select></label>
					</div>
					<div style="margin-top:10px;font-size:12px;" class="kgtk-muted">Minimal satu kolom wajib dipetakan. Sisanya boleh &ldquo;-- Lewati --&rdquo;.</div>
					<div id="kgtk-impor-preview" style="margin-top:12px;max-height:160px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:6px;"></div>
				</div>

				<div id="kgtk-impor-step-3" style="display:none;">
					<div class="kgtk-ok" id="kgtk-impor-result-msg"></div>
					<div id="kgtk-impor-reasons-wrap" style="max-height:180px;overflow-y:auto;margin-top:10px;font-size:12px;background:#f8fafc;padding:8px;border-radius:6px;"></div>
				</div>
			</div>
		</div>
		<div class="kgtk-modal-actions">
			<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-btn-impor-cancel">Batal</button>
			<button type="button" class="kgtk-btn kgtk-btn--navy" id="kgtk-btn-impor-next">Lanjutkan</button>
		</div>
	</div>
</div>
