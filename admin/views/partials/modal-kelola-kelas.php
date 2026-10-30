<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Partial View: Modal Tambah Kelas Baru Pelatihan
 */
?>
<div class="kgtk-modal-overlay" id="kgtk-modal-kelola-kelas" style="display:none;">
	<div class="kgtk-modal-dialog kgtk-ui" style="max-width:920px;width:96%;">
		<div class="kgtk-modal-top" style="align-items:flex-start;">
			<div class="kgtk-modal-icon is-primary">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<rect x="3" y="3" width="7" height="7" rx="1"></rect>
					<rect x="14" y="3" width="7" height="7" rx="1"></rect>
					<rect x="14" y="14" width="7" height="7" rx="1"></rect>
					<rect x="3" y="14" width="7" height="7" rx="1"></rect>
				</svg>
			</div>
			<div class="kgtk-modal-content" style="width:100%;">
				<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
					<div>
						<h3 class="kgtk-modal-title" style="margin:0;font-size:17px;">Tambah Kelas Baru</h3>
						<p class="kgtk-muted" style="margin:2px 0 12px;font-size:12.5px;">Tambahkan kelas baru ke dalam pelatihan ini tanpa mengubah kelas yang sudah ada.</p>
					</div>
					<button type="button" class="kgtk-search-clear" id="kgtk-btn-kelola-kelas-close" style="position:static;width:28px;height:28px;font-size:20px;" title="Tutup">&times;</button>
				</div>

				<form id="kgtk-form-kelola-kelas" novalidate>
					<input type="hidden" id="kgtk-kelola-kelas-pelatihan-id" name="pelatihan_id" value="0">
					<div id="kgtk-kelola-kelas-error" class="kgtk-alert" role="alert" hidden style="margin-bottom:12px;"></div>

					<div style="max-height:60vh;overflow-y:auto;padding-right:4px;">
						<div id="kgtk-existing-kelas-box" style="margin-bottom:14px;padding:10px 12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;">
							<div style="font-size:12.5px;font-weight:700;color:#0b2d56;margin-bottom:4px;">Kelas yang Sudah Dibuka (Atur Jadwal &amp; Batas Pendaftaran):</div>
							<small class="kgtk-muted" style="display:block;margin-bottom:8px;font-size:11.5px;">Anda dapat menambahkan atau mengubah tanggal mulai, selesai, dan batas pendaftaran kelas yang sudah ada di bawah ini tanpa menimpa kelas maupun peserta:</small>
							<div id="kgtk-existing-kelas-chips" style="display:flex;flex-direction:column;gap:8px;margin:0;"></div>
						</div>

						<div style="font-size:13px;font-weight:700;color:#0b2d56;margin-bottom:4px;">Tambah Kelas Baru (opsional):</div>
						<div class="kgtk-kelas" id="kgtk-kelola-kelas-list"></div>
						<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-kelola-kelas-add" style="margin-top:6px;">+ Tambah Baris Kelas Baru</button>
					</div>

					<div class="kgtk-modal-actions" style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px;">
						<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-btn-kelola-kelas-batal">Batal</button>
						<button type="submit" class="kgtk-btn kgtk-btn--navy" id="kgtk-btn-kelola-kelas-simpan">&#128190; Simpan Kelas &amp; Deadline</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
