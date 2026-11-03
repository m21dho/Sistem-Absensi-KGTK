<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Partial View: Modal Ubah Cepat Peran Guru / Peserta
 */
?>
<div class="kgtk-modal-overlay" id="kgtk-modal-ubah-peran" style="display:none;">
	<div class="kgtk-modal-dialog kgtk-ui" style="max-width:440px;width:95%;padding:22px 24px;box-sizing:border-box;">
		<div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:12px;border-bottom:1px solid #e2e8f0;">
			<div style="display:flex;align-items:center;gap:10px;">
				<div class="kgtk-modal-icon is-primary" style="margin:0;width:38px;height:38px;border-radius:10px;flex-shrink:0;">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
						<circle cx="8.5" cy="7" r="4"></circle>
						<polyline points="17 11 19 13 23 9"></polyline>
					</svg>
				</div>
				<div>
					<h3 class="kgtk-modal-title" style="margin:0;font-size:16px;color:#0f172a;">Ubah Peran Guru</h3>
					<p class="kgtk-muted" style="margin:2px 0 0;font-size:12px;">Sesuaikan peran guru dalam kegiatan ini</p>
				</div>
			</div>
			<button type="button" class="kgtk-search-clear" id="kgtk-btn-ubah-peran-close" style="position:static;width:28px;height:28px;line-height:26px;font-size:20px;" title="Tutup">&times;</button>
		</div>

		<div id="kgtk-ubah-peran-error" class="kgtk-alert" style="margin:12px 0 0;" hidden></div>

		<form id="kgtk-form-ubah-peran" style="margin-top:14px;">
			<input type="hidden" name="peserta_id" id="kgtk-ubah-peran-ps-id" value="0">

			<div style="background:#f8fafc;padding:10px 12px;border-radius:8px;border:1px solid #e2e8f0;margin-bottom:14px;font-size:13px;">
				<div style="color:#64748b;font-size:11.5px;">Nama Guru:</div>
				<div id="kgtk-ubah-peran-nama" style="font-weight:700;color:#0f172a;margin-top:2px;">-</div>
				<div style="margin-top:6px;display:flex;align-items:center;gap:6px;">
					<span style="color:#64748b;font-size:11.5px;">Peran saat ini:</span>
					<span id="kgtk-ubah-peran-saat-ini" class="kgtk-badge" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;font-size:11px;padding:1px 6px;">-</span>
				</div>
			</div>

			<label class="kgtk-field">
				<span style="font-weight:600;color:#0f172a;">Pilih Peran Baru: *</span>
				<select class="kgtk-input" name="peran" id="kgtk-ubah-peran-select" required style="font-size:13.5px;font-weight:600;">
					<?php foreach ( KGTK_Helpers::PERAN as $pr ) : ?>
						<option value="<?php echo esc_attr( $pr ); ?>"><?php echo esc_html( $pr ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<p class="kgtk-muted" style="font-size:11.5px;margin:4px 0 0;line-height:1.4;">
				Jika diubah menjadi <b>Peserta</b>, guru akan dihitung dalam kuota kelas dan otomatis masuk ke daftar hadir lembar peserta.
			</p>

			<div class="kgtk-modal-actions" style="margin-top:18px;padding-top:12px;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:8px;">
				<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-btn-ubah-peran-cancel">Batal</button>
				<button type="submit" class="kgtk-btn kgtk-btn--navy" id="kgtk-btn-ubah-peran-save">Simpan Peran</button>
			</div>
		</form>
	</div>
</div>

