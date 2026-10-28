<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * View: Tambah Peserta & Tim Kegiatan Manual oleh Admin (Fungsi Tunggal Terpadu)
 *
 * @var object $p     Objek data pelatihan.
 * @var array  $kelas Statistik kelas pelatihan.
 * @var string $back  URL kembali ke detail absen.
 */

$req_peran = isset( $_GET['peran'] ) ? sanitize_text_field( wp_unslash( $_GET['peran'] ) ) : 'Peserta';
if ( ! in_array( $req_peran, KGTK_Helpers::PERAN, true ) ) {
	$req_peran = 'Peserta';
}
?>
<div class="wrap kgtk-wrap kgtk-ui">
	<?php
	KGTK_Admin::head(
		'Tambah Peserta & Tim Kegiatan',
		$p->nama,
		'<a class="kgtk-btn kgtk-btn--ghost" href="' . esc_url( $back ) . '">&larr; Kembali ke Detail Pelatihan</a>'
	);
	?>

	<form id="kgtk-form-tambah" class="kgtk-card kgtk-form" data-pelatihan="<?php echo (int) $p->id; ?>" novalidate>
		<p class="kgtk-muted" style="margin-top:0;margin-bottom:18px;font-size:13.5px;line-height:1.5;">
			Gunakan satu fungsi ini untuk menambahkan <strong>Peserta</strong>, <strong>Panitia</strong>, <strong>Narasumber</strong>, <strong>Pengajar</strong>, <strong>Fasilitator</strong>, <strong>Penceramah</strong>, atau <strong>Pemateri</strong>. Jika NIK pernah tersimpan, data guru/petugas akan terisi otomatis dan dapat diedit. Tanda * wajib diisi.
		</p>

		<div id="kgtk-tambah-ok" class="kgtk-ok" role="status" hidden></div>
		<div id="kgtk-tambah-error" class="kgtk-alert" role="alert" hidden></div>

		<!-- Pilih Peran Utama (Paling Atas) -->
		<div class="kgtk-field" style="background:#f0f7ff;padding:16px 18px;border-radius:14px;border:1.5px solid #bfdbfe;margin-bottom:20px;">
			<label for="kgtk-tambah-peran" style="font-weight:800;font-size:14px;color:#0b2d56;display:flex;align-items:center;gap:8px;margin-bottom:8px;">
				<span style="font-size:18px;">&#128100;</span>
				<span>Pilih Peran Dalam Kegiatan Ini *</span>
			</label>
			<select class="kgtk-input" name="peran" id="kgtk-tambah-peran" style="font-size:14.5px;font-weight:700;height:44px;background:#fff;border-color:#3b82f6;">
				<?php foreach ( KGTK_Helpers::PERAN as $peran ) : ?>
					<option value="<?php echo esc_attr( $peran ); ?>" <?php selected( $req_peran, $peran ); ?>>
						<?php
						$label_icon = '';
						switch ( $peran ) {
							case 'Peserta': $label_icon = 'Peserta (Guru Peserta Pelatihan)'; break;
							case 'Panitia': $label_icon = 'Panitia (Panitia Pelaksana Kegiatan)'; break;
							case 'Narasumber': $label_icon = 'Narasumber'; break;
							case 'Pengajar': $label_icon = 'Pengajar / Instruktur'; break;
							case 'Fasilitator': $label_icon = 'Fasilitator'; break;
							case 'Penceramah': $label_icon = 'Penceramah'; break;
							case 'Pemateri': $label_icon = 'Pemateri'; break;
							default: $label_icon = $peran;
						}
						echo esc_html( $label_icon );
						?>
					</option>
				<?php endforeach; ?>
			</select>
			<small class="kgtk-muted" id="kgtk-peran-hint" style="margin-top:6px;display:block;font-size:12.5px;">
				Peran menentukan hak slot, pencatatan di lembar hadir, dan format data.
			</small>
		</div>

		<!-- Pilihan Identitas Khusus Panitia -->
		<div id="kgtk-wrap-tipe-identitas-panitia" style="display:none;background:#f8fafc;padding:14px 18px;border-radius:12px;border:1.5px solid #cbd5e1;margin-bottom:18px;">
			<span style="font-weight:700;font-size:13.5px;color:#0b2d56;display:block;margin-bottom:8px;">
				&#127380; Pilih Nomor Identitas yang Digunakan Panitia:
			</span>
			<div style="display:flex;gap:24px;align-items:center;flex-wrap:wrap;">
				<label style="display:inline-flex;align-items:center;gap:6px;font-size:13.5px;font-weight:600;color:#1e293b;cursor:pointer;">
					<input type="radio" name="panitia_id_type" value="nik" id="kgtk-radio-id-nik" checked>
					<span>Gunakan NIK (16 Digit)</span>
				</label>
				<label style="display:inline-flex;align-items:center;gap:6px;font-size:13.5px;font-weight:600;color:#1e293b;cursor:pointer;">
					<input type="radio" name="panitia_id_type" value="nip" id="kgtk-radio-id-nip">
					<span>Gunakan NIP (18 Digit)</span>
				</label>
			</div>
			<small class="kgtk-muted" id="kgtk-panitia-id-hint" style="margin-top:6px;display:block;font-size:12px;">
				Pilih apakah ingin mendaftarkan panitia menggunakan NIK atau NIP pegawai.
			</small>
		</div>

		<div id="kgtk-wrap-nik-field">
			<?php echo KGTK_Admin::field( 'NIK (16 digit) *', 'nik', 'type="text" inputmode="numeric" maxlength="16" autocomplete="off" required placeholder="16 digit angka NIK"' ); ?>
		</div>

		<div id="kgtk-wrap-nip-panitia-field" style="display:none;">
			<label class="kgtk-field">
				<span id="kgtk-lbl-nip-panitia">NIP (18 Digit) *</span>
				<input type="text" class="kgtk-input" id="kgtk-inp-nip-panitia" inputmode="numeric" maxlength="18" placeholder="18 digit angka NIP">
				<small class="kgtk-muted">Masukkan 18 digit angka NIP resmi panitia.</small>
			</label>
		</div>

		<div id="kgtk-tambah-info" class="kgtk-note" hidden></div>

		<label class="kgtk-field">
			<span>Nama Lengkap * <small style="color:#dc2626;font-size:12.5px;font-weight:normal;margin-left:4px;">(Hanya nama tanpa gelar)</small></span>
			<input type="text" class="kgtk-input" name="nama" maxlength="100" placeholder="Contoh: Muhammad Ridho" required>
		</label>

		<div class="kgtk-row2">
			<?php echo KGTK_Admin::field( 'Gelar Depan', 'gelar_depan', 'type="text" maxlength="30" placeholder="mis. Dr. / Drs."' ); ?>
			<?php echo KGTK_Admin::field( 'Gelar Belakang', 'gelar_belakang', 'type="text" maxlength="50" placeholder="mis. S.Pd. / M.Pd."' ); ?>
		</div>

		<label class="kgtk-field" id="kgtk-wrap-nip-umum">
			<span>NIP <small style="color:#dc2626;font-size:12.5px;font-weight:normal;margin-left:4px;">*Hanya angka, tanpa spasi atau karakter lain</small></span>
			<input type="text" class="kgtk-input" name="nip" id="kgtk-inp-nip-umum" inputmode="numeric" maxlength="18" placeholder="199602082025211022">
			<small>18 digit angka NIP (kosongkan jika non-PNS/belum ada).</small>
		</label>

		<div class="kgtk-row2">
			<?php echo KGTK_Admin::field( 'Tempat Lahir *', 'tempat_lahir', 'type="text" maxlength="80" required placeholder="Tempat lahir"' ); ?>
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
			<span>Pendidikan Terakhir *</span>
			<select class="kgtk-input" name="pendidikan" required>
				<option value="">-- Pilih Pendidikan Terakhir --</option>
				<?php foreach ( KGTK_Helpers::PENDIDIKAN as $pd ) : ?>
					<option value="<?php echo esc_attr( $pd ); ?>"><?php echo esc_html( $pd ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>

		<div class="kgtk-row2" id="kgtk-wrap-bank-rek">
			<?php echo KGTK_Admin::field( 'Nama Bank *', 'bank', 'type="text" maxlength="60" placeholder="mis. Bank Riau Kepri Syariah / BRI" required' ); ?>
			<?php echo KGTK_Admin::field( 'Nomor Rekening *', 'rekening', 'type="text" maxlength="50" inputmode="numeric" required placeholder="Hanya angka rekening"' ); ?>
		</div>

		<div id="kgtk-wrap-npwp">
			<?php echo KGTK_Admin::field( 'NPWP (opsional)', 'npwp', 'type="text" maxlength="30" autocomplete="off"', 'Kosongkan jika belum ada NPWP.' ); ?>
		</div>

		<label class="kgtk-field" id="kgtk-wrap-pengalaman">
			<span>Pengalaman Jabatan *</span>
			<textarea class="kgtk-input" name="pengalaman_jabatan" rows="2" maxlength="255" required placeholder="Contoh: Guru Kelas (5 Tahun), Wakasek (2 Tahun), atau Praktisi"></textarea>
		</label>

		<div class="kgtk-row2" id="kgtk-wrap-nuptk-simpkb">
			<?php echo KGTK_Admin::field( 'NUPTK (isi jika ada)', 'nuptk', 'type="text" inputmode="numeric" maxlength="16" autocomplete="off"', '16 digit angka (opsional).' ); ?>
			<?php echo KGTK_Admin::field( 'SIMPKB (isi jika ada)', 'simpkb', 'type="text" maxlength="100" autocomplete="off"', 'No. UKG / Akun SIMPKB (opsional).' ); ?>
		</div>

		<div id="kgtk-sekolah-slot"></div>

		<label class="kgtk-field">
			<span>Alamat Sekolah / Instansi *</span>
			<textarea class="kgtk-input" name="alamat_instansi" rows="2" maxlength="500" required placeholder="Alamat lengkap instansi / tempat bertugas"></textarea>
		</label>

		<!-- Field Jabatan Khusus Peserta -->
		<label class="kgtk-field" id="kgtk-admin-wrap-jenis-gtk">
			<span>Jabatan GTK *</span>
			<select class="kgtk-input" name="jenis_gtk" id="kgtk-tambah-jenis-gtk">
				<option value="">-- Pilih Jabatan GTK --</option>
				<?php foreach ( KGTK_Helpers::JABATAN as $jg ) : ?>
					<option value="<?php echo esc_attr( $jg ); ?>"><?php echo esc_html( $jg ); ?></option>
				<?php endforeach; ?>
			</select>
			<small>Khusus peserta: pilih jabatan guru atau tenaga kependidikan.</small>
		</label>

		<!-- Field Tugas / Jabatan Tim untuk Non-Peserta (Panitia/Narasumber/Pengajar/Fasilitator/dll) -->
		<label class="kgtk-field" id="kgtk-admin-wrap-tugas-tim" style="display:none;">
			<span id="kgtk-lbl-tugas-tim">Tugas / Jabatan Dalam Kegiatan *</span>
			<input type="text" class="kgtk-input" name="tugas_tim" id="kgtk-tambah-tugas-tim" list="kgtk-tugas-tim-list" placeholder="Contoh: Ketua Panitia, Sekretaris, Narasumber Utama...">
			<datalist id="kgtk-tugas-tim-list">
				<?php foreach ( KGTK_Helpers::JABATAN_PANITIA as $jp ) : ?>
					<option value="<?php echo esc_attr( $jp ); ?>"></option>
				<?php endforeach; ?>
				<option value="Narasumber Utama"></option>
				<option value="Narasumber Ahli"></option>
				<option value="Fasilitator Pembelajaran"></option>
				<option value="Pengajar Praktik"></option>
				<option value="Pemateri Sesi"></option>
				<option value="Penceramah Tamu"></option>
			</datalist>
			<small>Ketik tugas atau pilih dari saran. Data ini akan tercetak pada lembar hadir dan tanda terima.</small>
		</label>

		<?php
		echo KGTK_Admin::field( 'No. HP/WhatsApp *', 'hp', 'type="tel" maxlength="20" required placeholder="08xxxxxxxxxx"' );
		echo KGTK_Admin::field( 'Email *', 'email', 'type="email" maxlength="100" required placeholder="nama@email.com"' );
		?>

		<label class="kgtk-field" id="kgtk-admin-wrap-email-belajar">
			<span id="kgtk-lbl-email-belajar">Email belajar.id *</span>
			<input type="email" class="kgtk-input" name="email_belajar" id="kgtk-input-email-belajar" maxlength="100" placeholder="nama@belajar.id" required>
			<small id="kgtk-hint-email-belajar">Wajib berakhiran belajar.id untuk peran Peserta (opsional untuk tim/panitia luar).</small>
		</label>

		<label class="kgtk-field" id="kgtk-admin-wrap-kelas">
			<span>Kelas Pelatihan</span>
			<select class="kgtk-input" name="kelas_id" id="kgtk-tambah-kelas">
				<option value="0">Otomatis (sesuai urutan kelas)</option>
				<?php foreach ( $kelas as $k ) : ?>
					<option value="<?php echo (int) $k->id; ?>" data-penuh="<?php echo $k->sisa < 1 ? '1' : '0'; ?>">
						<?php echo esc_html( $k->label . ' - ' . ( $k->sisa < 1 ? 'slot peserta penuh' : $k->sisa . ' slot peserta tersisa' ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<small id="kgtk-kelas-hint">Slot hanya berlaku untuk peran Peserta. Panitia, Narasumber, dan Tim Pengajar tidak mengurangi kuota slot peserta.</small>
		</label>

		<div class="kgtk-form-actions" style="margin-top:24px;">
			<a class="kgtk-btn kgtk-btn--ghost" href="<?php echo esc_url( $back ); ?>">Selesai</a>
			<button type="submit" class="kgtk-btn kgtk-btn--yellow" id="kgtk-btn-submit-tambah">Simpan Data</button>
		</div>
	</form>
</div>
