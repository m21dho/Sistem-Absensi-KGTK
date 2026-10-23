<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Partial View: Modal Edit Data Pelatihan
 */
?>
<div class="kgtk-modal-overlay" id="kgtk-modal-edit-pelatihan" style="display:none;">
	<div class="kgtk-modal-dialog kgtk-ui" style="max-width:680px;width:95%;">
		<div class="kgtk-modal-top" style="align-items:flex-start;">
			<div class="kgtk-modal-icon is-primary">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
					<path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
				</svg>
			</div>
			<div class="kgtk-modal-content" style="width:100%;">
				<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
					<div>
						<h3 class="kgtk-modal-title" style="margin:0;font-size:17px;">Edit Data Pelatihan</h3>
						<p class="kgtk-muted" style="margin:2px 0 12px;font-size:12.5px;">Perbarui rincian informasi dan jadwal pelatihan.</p>
					</div>
					<button type="button" class="kgtk-search-clear" id="kgtk-btn-edit-pelatihan-close" style="position:static;width:28px;height:28px;font-size:20px;" title="Tutup">&times;</button>
				</div>

				<form id="kgtk-form-edit-pelatihan" novalidate>
					<input type="hidden" id="kgtk-edit-pel-id" name="id" value="0">
					<div id="kgtk-edit-pel-error" class="kgtk-alert" role="alert" hidden style="margin-bottom:12px;"></div>

					<div style="max-height:65vh;overflow-y:auto;padding-right:4px;">
						<label class="kgtk-field">
							<span>Nama pelatihan <b>*</b></span>
							<input type="text" class="kgtk-input" id="kgtk-edit-pel-nama" name="nama" maxlength="200" required placeholder="Nama pelatihan">
						</label>

						<label class="kgtk-field">
							<span>Penyelenggara</span>
							<input type="text" class="kgtk-input" id="kgtk-edit-pel-penyelenggara" name="penyelenggara" maxlength="200" placeholder="Penyelenggara pelatihan">
						</label>

						<div class="kgtk-row2">
							<label class="kgtk-field">
								<span>Tanggal mulai <b>*</b></span>
								<input type="date" class="kgtk-input" id="kgtk-edit-pel-tgl-mulai" name="tanggal_mulai" required>
							</label>
							<label class="kgtk-field">
								<span>Tanggal selesai</span>
								<input type="date" class="kgtk-input" id="kgtk-edit-pel-tgl-selesai" name="tanggal_selesai">
								<small>Kosongkan jika sama dengan tanggal mulai.</small>
							</label>
						</div>

						<div class="kgtk-row2">
							<label class="kgtk-field">
								<span>Lokasi / Tempat Pelatihan</span>
								<input type="text" class="kgtk-input" id="kgtk-edit-pel-lokasi" name="lokasi" maxlength="255" placeholder="mis. Hotel CK / Zoom Meeting / Aula Gedung">
								<small>Nama gedung, hotel, platform, atau ruang kegiatan.</small>
							</label>
							<label class="kgtk-field">
								<span>Kab/Kota Tempat Pelatihan</span>
								<select class="kgtk-input" id="kgtk-edit-pel-kabkota" name="kabkota">
									<option value="">-- Pilih Kab/Kota Pelatihan --</option>
									<?php foreach ( KGTK_Helpers::kabkota_pelatihan_list() as $kab ) : ?>
										<option value="<?php echo esc_attr( $kab ); ?>"><?php echo esc_html( $kab ); ?></option>
									<?php endforeach; ?>
								</select>
								<small>Wilayah tempat kegiatan dijalankan.</small>
							</label>
						</div>

						<div class="kgtk-row2">
							<label class="kgtk-field">
								<span>Jabatan Penandatangan</span>
								<select class="kgtk-input" id="kgtk-edit-pel-tipe-pj" name="tipe_pj">
									<option value="Penanggung Jawab">Penanggung Jawab</option>
									<option value="Ketua">Ketua</option>
								</select>
								<small>Tercetak di atas tanda tangan lembar hadir.</small>
							</label>
							<label class="kgtk-field">
								<span>Nama Penandatangan</span>
								<input type="text" class="kgtk-input" id="kgtk-edit-pel-penanggung-jawab" name="penanggung_jawab" maxlength="150" placeholder="Nama beserta gelar">
								<small>Tercetak di bagian bawah daftar hadir.</small>
							</label>
						</div>

						<label class="kgtk-field">
							<span>Kode pelatihan <b>*</b></span>
							<input type="text" class="kgtk-input" id="kgtk-edit-pel-kode" name="kode" maxlength="20" pattern="[A-Za-z0-9-]{3,20}" style="text-transform:uppercase" required>
							<small>3-20 karakter: huruf, angka, tanda hubung. Harus unik.</small>
						</label>

						<label class="kgtk-field">
							<span>Keterangan</span>
							<textarea class="kgtk-input" id="kgtk-edit-pel-keterangan" name="keterangan" rows="3" maxlength="2000" placeholder="Catatan tambahan (opsional)"></textarea>
						</label>
					</div>

					<div class="kgtk-modal-actions" style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px;">
						<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-btn-edit-pelatihan-batal">Batal</button>
						<button type="submit" class="kgtk-btn kgtk-btn--navy" id="kgtk-btn-edit-pelatihan-simpan">&#128190; Simpan Perubahan</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
