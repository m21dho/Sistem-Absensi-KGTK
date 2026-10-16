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
	<?php KGTK_Admin::head( 'Pengaturan', 'Pengaturan plugin KGTK Pelatihan.' ); ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" class="kgtk-card kgtk-form">
		<?php settings_fields( 'kgtk_settings' ); ?>

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
