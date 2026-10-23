<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * View: Tambah Peserta Manual oleh Admin
 *
 * @var object $p     Objek data pelatihan.
 * @var array  $kelas Statistik kelas pelatihan.
 * @var string $back  URL kembali ke detail absen.
 */
?>
<div class="wrap kgtk-wrap kgtk-ui">
	<?php
	KGTK_Admin::head(
		'Tambah peserta',
		$p->nama,
		'<a class="kgtk-btn kgtk-btn--ghost" href="' . esc_url( $back ) . '">&larr; Kembali ke absen</a>'
	);
	?>

	<form id="kgtk-form-tambah" class="kgtk-card kgtk-form" data-pelatihan="<?php echo (int) $p->id; ?>" novalidate>
		<p class="kgtk-muted">Untuk mendaftarkan guru yang kesulitan mendaftar sendiri. Isi sesuai data guru. Jika NIK pernah tersimpan, data terisi otomatis dan bisa diedit. Tanda * wajib diisi.</p>
		<div id="kgtk-tambah-ok" class="kgtk-ok" role="status" hidden></div>
		<div id="kgtk-tambah-error" class="kgtk-alert" role="alert" hidden></div>

		<?php echo KGTK_Admin::field( 'NIK (16 digit) *', 'nik', 'type="text" inputmode="numeric" maxlength="16" autocomplete="off" required' ); ?>
		<div id="kgtk-tambah-info" class="kgtk-note" hidden></div>
		<label class="kgtk-field">
			<span>Nama * <small style="color:#dc2626;font-size:12.5px;font-weight:normal;margin-left:4px;">Hanya nama tanpa gelar</small></span>
			<input type="text" class="kgtk-input" name="nama" maxlength="100" placeholder="Anggy Putri Oktavia" required>
		</label>

		<div class="kgtk-row2">
			<?php echo KGTK_Admin::field( 'Gelar Depan', 'gelar_depan', 'type="text" maxlength="30" placeholder="mis. Dr. / Drs."' ); ?>
			<?php echo KGTK_Admin::field( 'Gelar Belakang', 'gelar_belakang', 'type="text" maxlength="50" placeholder="mis. S.Pd. / S.I.Kom"' ); ?>
		</div>

		<label class="kgtk-field">
			<span>NIP <small style="color:#dc2626;font-size:12.5px;font-weight:normal;margin-left:4px;">*Hanya angka, tanpa spasi atau karakter lain</small></span>
			<input type="text" class="kgtk-input" name="nip" inputmode="numeric" maxlength="18" placeholder="199602082025211022">
			<small>18 digit angka NIP (kosongkan jika non-PNS/belum ada).</small>
		</label>

		<div class="kgtk-row2">
			<?php echo KGTK_Admin::field( 'Tempat Lahir *', 'tempat_lahir', 'type="text" maxlength="80" required' ); ?>
			<?php echo KGTK_Admin::field( 'Tanggal Lahir *', 'tanggal_lahir', 'type="date" required' ); ?>
		</div>

		<div class="kgtk-row2">
			<label class="kgtk-field">
				<span>Jenis Kelamin *</span>
				<select class="kgtk-input" name="jenis_kelamin" required>
					<option value="">-- Pilih Jenis Kelamin --</option>
					<?php foreach ( KGTK_Helpers::JENIS_KELAMIN as $jk ) : ?>
						<option value="<?php echo esc_attr( $jk ); ?>"><?php echo esc_html( $jk ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label class="kgtk-field">
				<span>Agama *</span>
				<select class="kgtk-input" name="agama" required>
					<option value="">-- Pilih Agama --</option>
					<?php foreach ( KGTK_Helpers::AGAMA as $ag ) : ?>
						<option value="<?php echo esc_attr( $ag ); ?>"><?php echo esc_html( $ag ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		</div>

		<label class="kgtk-field">
			<span>Pangkat / Golongan</span>
			<input type="text" class="kgtk-input" name="pangkat_golongan" list="kgtk-pangkat-list" placeholder="mis. Penata Muda (III/a) atau Non ASN">
			<datalist id="kgtk-pangkat-list">
				<?php foreach ( KGTK_Helpers::PANGKAT_GOLONGAN as $pg ) : ?>
					<option value="<?php echo esc_attr( $pg ); ?>"></option>
				<?php endforeach; ?>
			</datalist>
			<small>Pilih dari daftar atau ketik langsung (opsional untuk non-ASN).</small>
		</label>

		<label class="kgtk-field">
			<span>Alamat Rumah / Tempat Tinggal *</span>
			<textarea class="kgtk-input" name="alamat" rows="2" maxlength="500" required placeholder="Alamat rumah / domisili tempat tinggal"></textarea>
		</label>

		<label class="kgtk-field">
			<span>Alamat Sekolah / Instansi *</span>
			<textarea class="kgtk-input" name="alamat_instansi" rows="2" maxlength="500" required placeholder="Alamat lengkap instansi / tempat bertugas"></textarea>
		</label>

		<label class="kgtk-field">
			<span>Pendidikan Terakhir *</span>
			<select class="kgtk-input" name="pendidikan" required>
				<option value="">-- Pilih Pendidikan Terakhir --</option>
				<?php foreach ( KGTK_Helpers::PENDIDIKAN as $pd ) : ?>
					<option value="<?php echo esc_attr( $pd ); ?>"><?php echo esc_html( $pd ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>

		<div class="kgtk-row2">
			<?php echo KGTK_Admin::field( 'Nama Bank *', 'bank', 'type="text" maxlength="60" placeholder="mis. Bank Riau Kepri Syariah / BRI" required' ); ?>
			<?php echo KGTK_Admin::field( 'Nomor Rekening *', 'rekening', 'type="text" maxlength="50" inputmode="numeric" required' ); ?>
		</div>

		<?php echo KGTK_Admin::field( 'NPWP (opsional)', 'npwp', 'type="text" maxlength="30" autocomplete="off"', 'Kosongkan jika belum ada NPWP.' ); ?>

		<label class="kgtk-field">
			<span>Pengalaman Jabatan *</span>
			<textarea class="kgtk-input" name="pengalaman_jabatan" rows="2" maxlength="255" required placeholder="Contoh: Guru Kelas (5 Tahun), Wakasek (2 Tahun)"></textarea>
		</label>

		<div class="kgtk-row2">
			<?php echo KGTK_Admin::field( 'NUPTK (isi jika ada)', 'nuptk', 'type="text" inputmode="numeric" maxlength="16" autocomplete="off"', '16 digit angka (opsional).' ); ?>
			<?php echo KGTK_Admin::field( 'SIMPKB (isi jika ada)', 'simpkb', 'type="text" maxlength="100" autocomplete="off"', 'No. UKG / Akun SIMPKB (opsional).' ); ?>
		</div>
		<div id="kgtk-sekolah-slot"></div>

		<label class="kgtk-field">
			<span>Peran *</span>
			<select class="kgtk-input" name="peran">
				<?php foreach ( KGTK_Helpers::PERAN as $peran ) : ?>
					<option value="<?php echo esc_attr( $peran ); ?>" <?php selected( 'Peserta', $peran ); ?>><?php echo esc_html( $peran ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>

		<label class="kgtk-field" id="kgtk-admin-wrap-jenis-gtk">
			<span>Jabatan *</span>
			<select class="kgtk-input" name="jenis_gtk">
				<option value="">-- Pilih Jabatan --</option>
				<?php foreach ( KGTK_Helpers::JABATAN as $jg ) : ?>
					<option value="<?php echo esc_attr( $jg ); ?>"><?php echo esc_html( $jg ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>

		<?php
		echo KGTK_Admin::field( 'No. HP/WhatsApp *', 'hp', 'type="tel" maxlength="20" required' );
		echo KGTK_Admin::field( 'Email *', 'email', 'type="email" maxlength="100" required' );
		echo KGTK_Admin::field( 'Email belajar.id *', 'email_belajar', 'type="email" maxlength="100" placeholder="nama@belajar.id" required' );
		?>

		<label class="kgtk-field">
			<span>Kelas</span>
			<select class="kgtk-input" name="kelas_id" id="kgtk-tambah-kelas">
				<option value="0">Otomatis (sesuai urutan kelas)</option>
				<?php foreach ( $kelas as $k ) : ?>
					<option value="<?php echo (int) $k->id; ?>" data-penuh="<?php echo $k->sisa < 1 ? '1' : '0'; ?>">
						<?php echo esc_html( $k->label . ' - ' . ( $k->sisa < 1 ? 'slot peserta penuh' : $k->sisa . ' slot peserta tersisa' ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<small>Slot hanya berlaku untuk peran Peserta. Peran lain bisa dimasukkan ke kelas mana pun, termasuk yang slot pesertanya sudah penuh.</small>
		</label>

		<div class="kgtk-form-actions">
			<a class="kgtk-btn kgtk-btn--ghost" href="<?php echo esc_url( $back ); ?>">Selesai</a>
			<button type="submit" class="kgtk-btn">Simpan peserta</button>
		</div>
	</form>
</div>
