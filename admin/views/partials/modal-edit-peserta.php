<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Partial View: Modal Edit Data Peserta / Tim Kegiatan oleh Admin
 *
 * @var object $p     Data pelatihan.
 * @var array  $kelas Daftar kelas pelatihan.
 */
?>
<div class="kgtk-modal-overlay" id="kgtk-modal-edit-peserta" style="display:none;">
	<div class="kgtk-modal-dialog kgtk-ui" style="max-width:700px;width:95%;max-height:calc(100vh - 40px);max-height:calc(100dvh - 40px);display:flex;flex-direction:column;overflow:hidden;padding:20px 24px 16px;box-sizing:border-box;">
		
		<!-- Header Dialog (Tetap / Tidak Ikut Tergeser) -->
		<div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:12px;border-bottom:1px solid #e2e8f0;flex-shrink:0;">
			<div style="display:flex;align-items:center;gap:12px;">
				<div class="kgtk-modal-icon is-primary" style="margin:0;width:40px;height:40px;border-radius:10px;flex-shrink:0;">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
					</svg>
				</div>
				<div>
					<h3 class="kgtk-modal-title" style="margin:0;font-size:16.5px;color:#0f172a;">Edit Data Peserta / Petugas</h3>
					<p class="kgtk-muted" style="margin:2px 0 0;font-size:12px;">Perbarui data pendaftar pelatihan ini secara langsung</p>
				</div>
			</div>
			<button type="button" class="kgtk-search-clear" id="kgtk-btn-edit-peserta-close" style="position:static;width:28px;height:28px;line-height:26px;font-size:20px;" title="Tutup">&times;</button>
		</div>

		<!-- Kotak Pesan Error (Tetap / Di atas Form Scroll) -->
		<div id="kgtk-edit-peserta-error" class="kgtk-alert" style="margin:10px 0 0;flex-shrink:0;" hidden></div>

		<!-- Body Formulir (Area yang Bergulir / Scrollable) -->
		<form id="kgtk-form-edit-peserta" novalidate style="flex:1 1 auto;min-height:0;overflow-y:auto;padding-right:8px;margin:12px 0 0;">
			<input type="hidden" name="peserta_id" id="kgtk-edit-ps-id" value="0">

			<!-- Penempatan Peran & Kelas -->
			<div style="background:#f0f7ff;padding:12px 14px;border-radius:10px;border:1px solid #bfdbfe;margin-bottom:14px;">
				<div class="kgtk-row2" style="gap:10px;">
					<label class="kgtk-field" style="margin:0;">
						<span style="font-weight:700;color:#0b2d56;">Peran dalam Pelatihan *</span>
						<select class="kgtk-input" name="peran" id="kgtk-edit-ps-peran" required>
							<?php foreach ( KGTK_Helpers::PERAN as $peran ) : ?>
								<option value="<?php echo esc_attr( $peran ); ?>"><?php echo esc_html( $peran ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label class="kgtk-field" style="margin:0;">
						<span style="font-weight:700;color:#0b2d56;">Kelas Penempatan</span>
						<select class="kgtk-input" name="kelas_id" id="kgtk-edit-ps-kelas">
							<option value="0">&#9888;&#65039; Belum Ada Kelas (Unassigned)</option>
							<?php if ( ! empty( $kelas ) ) : foreach ( $kelas as $k ) : ?>
								<option value="<?php echo (int) $k->id; ?>"><?php echo esc_html( $k->label . ' (' . (int) $k->terisi . '/' . (int) $k->slot . ')' ); ?></option>
							<?php endforeach; endif; ?>
						</select>
					</label>
				</div>
			</div>

			<!-- Identitas Utama -->
			<label class="kgtk-field">
				<span>Nama Lengkap * <small style="color:#dc2626;font-size:12px;font-weight:normal;">(Tanpa gelar)</small></span>
				<input type="text" class="kgtk-input" name="nama" id="kgtk-edit-ps-nama" maxlength="100" required>
			</label>

			<div class="kgtk-row2">
				<label class="kgtk-field">
					<span>Gelar Depan</span>
					<input type="text" class="kgtk-input" name="gelar_depan" id="kgtk-edit-ps-gelar-depan" maxlength="30" placeholder="mis. Dr. / Drs.">
				</label>
				<label class="kgtk-field">
					<span>Gelar Belakang</span>
					<input type="text" class="kgtk-input" name="gelar_belakang" id="kgtk-edit-ps-gelar-belakang" maxlength="50" placeholder="mis. S.Pd. / M.Pd.">
				</label>
			</div>

			<div class="kgtk-row2">
				<label class="kgtk-field">
					<span>NIK (16 Digit) *</span>
					<input type="text" class="kgtk-input" name="nik" id="kgtk-edit-ps-nik" inputmode="numeric" maxlength="16" required>
				</label>
				<label class="kgtk-field">
					<span>NIP (18 Digit)</span>
					<input type="text" class="kgtk-input" name="nip" id="kgtk-edit-ps-nip" inputmode="numeric" maxlength="18" placeholder="18 digit angka NIP">
				</label>
			</div>

			<div class="kgtk-row2">
				<label class="kgtk-field">
					<span>Tempat Lahir *</span>
					<input type="text" class="kgtk-input" name="tempat_lahir" id="kgtk-edit-ps-tempat-lahir" maxlength="80" required>
				</label>
				<label class="kgtk-field">
					<span>Tanggal Lahir *</span>
					<input type="date" class="kgtk-input" name="tanggal_lahir" id="kgtk-edit-ps-tanggal-lahir" required>
				</label>
			</div>

			<div class="kgtk-row2">
				<label class="kgtk-field">
					<span>Jenis Kelamin *</span>
					<select class="kgtk-input" name="jenis_kelamin" id="kgtk-edit-ps-jk" required>
						<option value="">-- Pilih --</option>
						<?php foreach ( KGTK_Helpers::JENIS_KELAMIN as $jk ) : ?>
							<option value="<?php echo esc_attr( $jk ); ?>"><?php echo esc_html( $jk ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="kgtk-field">
					<span>Agama *</span>
					<select class="kgtk-input" name="agama" id="kgtk-edit-ps-agama" required>
						<option value="">-- Pilih --</option>
						<?php foreach ( KGTK_Helpers::AGAMA as $ag ) : ?>
							<option value="<?php echo esc_attr( $ag ); ?>"><?php echo esc_html( $ag ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</div>

			<!-- Kepegawaian & Instansi -->
			<div class="kgtk-row2">
				<label class="kgtk-field">
					<span>Asal Sekolah / Instansi *</span>
					<input type="text" class="kgtk-input" name="sekolah" id="kgtk-edit-ps-sekolah" maxlength="200" required>
				</label>
				<label class="kgtk-field">
					<span>Kabupaten / Kota *</span>
					<input type="text" class="kgtk-input" name="kabkota" id="kgtk-edit-ps-kabkota" maxlength="80" required>
				</label>
			</div>

			<label class="kgtk-field">
				<span>Alamat Sekolah / Instansi</span>
				<textarea class="kgtk-input" name="alamat_instansi" id="kgtk-edit-ps-alamat-instansi" rows="2" placeholder="Alamat lengkap kantor / sekolah"></textarea>
			</label>

			<div class="kgtk-row2">
				<label class="kgtk-field">
					<span>Jabatan / Jenis GTK</span>
					<input type="text" class="kgtk-input" name="jenis_gtk" id="kgtk-edit-ps-jabatan" placeholder="mis. Guru Kelas, Kepala Sekolah">
				</label>
				<label class="kgtk-field">
					<span>Pangkat / Golongan</span>
					<input type="text" class="kgtk-input" name="pangkat_golongan" id="kgtk-edit-ps-pangkat" list="kgtk-edit-pangkat-list" placeholder="mis. Penata Muda (III/a)">
					<datalist id="kgtk-edit-pangkat-list">
						<?php foreach ( KGTK_Helpers::PANGKAT_GOLONGAN as $pg ) : ?>
							<option value="<?php echo esc_attr( $pg ); ?>"></option>
						<?php endforeach; ?>
					</datalist>
				</label>
			</div>

			<div class="kgtk-row2">
				<label class="kgtk-field">
					<span>Pendidikan Terakhir</span>
					<input type="text" class="kgtk-input" name="pendidikan" id="kgtk-edit-ps-pendidikan" placeholder="mis. D4 / S1 Pendidikan">
				</label>
				<label class="kgtk-field">
					<span>NUPTK / NISN</span>
					<input type="text" class="kgtk-input" name="nuptk" id="kgtk-edit-ps-nuptk" placeholder="Nomor NUPTK">
				</label>
			</div>

			<!-- Kontak & Akun -->
			<div class="kgtk-row2">
				<label class="kgtk-field">
					<span>No. HP / WhatsApp *</span>
					<input type="tel" class="kgtk-input" name="hp" id="kgtk-edit-ps-hp" maxlength="20" required placeholder="08...">
				</label>
				<label class="kgtk-field">
					<span>Email Pribadi</span>
					<input type="email" class="kgtk-input" name="email" id="kgtk-edit-ps-email" placeholder="nama@gmail.com">
				</label>
			</div>

			<div class="kgtk-row2">
				<label class="kgtk-field">
					<span>Email Belajar.id</span>
					<input type="email" class="kgtk-input" name="email_belajar" id="kgtk-edit-ps-email-belajar" placeholder="user@guru.sd.belajar.id">
				</label>
				<label class="kgtk-field">
					<span>SIMPKB / Akun Guru</span>
					<input type="text" class="kgtk-input" name="simpkb" id="kgtk-edit-ps-simpkb" placeholder="Nomor SIMPKB / UKG">
				</label>
			</div>

			<!-- Rekening & Keuangan -->
			<div class="kgtk-row2">
				<label class="kgtk-field">
					<span>Nama Bank</span>
					<input type="text" class="kgtk-input" name="bank" id="kgtk-edit-ps-bank" placeholder="mis. Bank Riau Kepri Syariah / BRI">
				</label>
				<label class="kgtk-field">
					<span>Nomor Rekening Bank</span>
					<input type="text" class="kgtk-input" name="rekening" id="kgtk-edit-ps-rekening" placeholder="Nomor rekening">
				</label>
			</div>

			<label class="kgtk-field">
				<span>NPWP</span>
				<input type="text" class="kgtk-input" name="npwp" id="kgtk-edit-ps-npwp" placeholder="Nomor Pokok Wajib Pajak">
			</label>

			<label class="kgtk-field">
				<span>Pengalaman Jabatan</span>
				<textarea class="kgtk-input" name="pengalaman_jabatan" id="kgtk-edit-ps-pengalaman-jabatan" rows="2" placeholder="mis. Guru Matematika (2018 - sekarang)"></textarea>
			</label>

			<label class="kgtk-field">
				<span>Alamat Tempat Tinggal / Rumah *</span>
				<textarea class="kgtk-input" name="alamat" id="kgtk-edit-ps-alamat" rows="2" placeholder="Alamat lengkap domisili" required></textarea>
			</label>
		</form>

		<!-- Footer Tombol Aksi (TETAP DI BAWAH / TERKUNCI DALAM CARD) -->
		<div class="kgtk-modal-actions" style="margin-top:12px;padding-top:12px;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;align-items:center;gap:10px;flex-shrink:0;">
			<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-btn-edit-peserta-cancel">Batal</button>
			<button type="button" class="kgtk-btn kgtk-btn--navy" id="kgtk-btn-edit-peserta-save">Simpan Perubahan</button>
		</div>
	</div>
</div>
