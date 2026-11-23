<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Partial View: Modal Export PDF
 *
 * @var object $p               Data pelatihan.
 * @var array  $counts_raw      Jumlah peserta per peran.
 * @var array  $peran_list      Daftar peran dan deskripsinya.
 * @var int    $total_pendaftar Total seluruh peserta terdaftar.
 * @var array  $daftar_hari     Daftar hari pelaksanaan pelatihan.
 * @var bool   $is_multi_day    Apakah pelatihan berlangsung lebih dari 1 hari.
 * @var string $tgl_default     Label rentang tanggal default.
 * @var string $tgl_param       Parameter tanggal default.
 */
if ( ! isset( $daftar_hari ) || ! is_array( $daftar_hari ) ) {
	$daftar_hari = isset( $p ) ? KGTK_Helpers::daftar_hari_pelatihan( $p->tanggal_mulai, $p->tanggal_selesai ) : array();
}
$is_multi_day = count( $daftar_hari ) > 1;
$tgl_default  = isset( $p ) ? KGTK_Helpers::rentang_tanggal( $p->tanggal_mulai, $p->tanggal_selesai ) : '';
$tgl_param    = $is_multi_day ? 'all_days' : '';
if ( ! isset( $peran_list ) ) {
	$peran_list = array(
		'Peserta'     => 'Daftar hadir seluruh peserta pelatihan',
		'Panitia'     => 'Lembar khusus panitia pelaksana kegiatan',
		'Fasilitator' => 'Lembar khusus fasilitator kegiatan',
		'Pengajar'    => 'Lembar khusus pengajar / instruktur',
		'Narasumber'  => 'Lembar khusus narasumber kegiatan',
		'Pemateri'    => 'Lembar khusus pemateri kegiatan',
	);
	foreach ( KGTK_Helpers::custom_peran_list() as $cp_export ) {
		if ( ! isset( $peran_list[ $cp_export ] ) ) {
			$peran_list[ $cp_export ] = 'Lembar khusus ' . strtolower( $cp_export ) . ' kegiatan';
		}
	}
}
if ( ! isset( $counts_raw ) && isset( $p->id ) ) {
	global $wpdb;
	$tp_cnt = KGTK_DB::table( 'peserta' );
	$counts_raw = (array) $wpdb->get_results(
		$wpdb->prepare( "SELECT peran, COUNT(*) as cnt FROM {$tp_cnt} WHERE pelatihan_id = %d GROUP BY peran", $p->id ),
		OBJECT_K
	);
}
$is_acara_export = ( isset( $p->jenis ) && 'acara' === $p->jenis );
$total_semua_peran = 0;
if ( ! empty( $counts_raw ) ) {
	foreach ( $counts_raw as $cr_obj ) {
		$total_semua_peran += isset( $cr_obj->cnt ) ? (int) $cr_obj->cnt : 0;
	}
}
if ( $total_semua_peran < (int) $total_pendaftar ) {
	$total_semua_peran = (int) $total_pendaftar;
}
?>
<div class="kgtk-modal-overlay" id="kgtk-modal-export-pdf" style="display:none;">
	<div class="kgtk-modal-dialog kgtk-ui kgtk-modal-export-dialog">
		<div class="kgtk-modal-export-header">
			<div class="kgtk-modal-export-title-wrap">
				<span class="kgtk-modal-export-icon">&#128196;</span>
				<div>
					<h3 class="kgtk-modal-title" style="margin:0 !important;font-size:16.5px;">Export &amp; Cetak PDF</h3>
					<p class="kgtk-muted" style="margin:1px 0 0;font-size:12px;">Pilih dokumen daftar hadir atau biodata PDF yang ingin diunduh</p>
				</div>
			</div>
			<button type="button" class="kgtk-modal-close-btn" id="kgtk-btn-export-pdf-close" title="Tutup">&times;</button>
		</div>

		<div class="kgtk-export-options-panel">
			<?php if ( $is_multi_day ) : ?>
				<div class="kgtk-export-opt-row">
					<label for="kgtk-export-pdf-tanggal" class="kgtk-export-opt-label">
						&#128197; Tanggal Absen
					</label>
					<select class="kgtk-input kgtk-export-opt-input" id="kgtk-export-pdf-tanggal">
						<option value="all_days" selected>Semua Hari (<?php echo count( $daftar_hari ); ?> Hari)</option>
						<?php foreach ( $daftar_hari as $dh ) : ?>
							<option value="<?php echo esc_attr( $dh['ymd'] ); ?>" data-label="<?php echo esc_attr( $dh['label_lengkap'] ); ?>">
								<?php echo esc_html( $dh['label_hari_ke'] ); ?>
							</option>
						<?php endforeach; ?>
						<option value="range" data-label="<?php echo esc_attr( $tgl_default ); ?>">1 Lembar (<?php echo esc_html( $tgl_default ); ?>)</option>
					</select>
				</div>
			<?php endif; ?>

			<div class="kgtk-export-opt-row">
				<label for="kgtk-export-pdf-kegiatan" class="kgtk-export-opt-label">
					&#128221; Absen Kegiatan
				</label>
				<input type="text" class="kgtk-input kgtk-export-opt-input" id="kgtk-export-pdf-kegiatan" placeholder="Opsional: nama kegiatan/sesi (mis. Makan Siang)..." maxlength="150">
			</div>
		</div>

		<div class="kgtk-modal-body-scroll">
			<div class="kgtk-export-list">
				<!-- Opsi 1: Semua Peran (Daftar Hadir Lengkap) -->
				<?php $url_semua = KGTK_Export::url( $p->id, 'pdf', '', $tgl_param ); ?>
				<a href="<?php echo esc_url( $url_semua ); ?>" target="_blank" class="kgtk-export-item kgtk-export-item--featured">
					<div class="kgtk-export-item-icon">&#128218;</div>
					<div class="kgtk-export-item-info">
						<div class="kgtk-export-item-title">Daftar Hadir Lengkap (Semua)</div>
						<div class="kgtk-export-item-desc">Seluruh kelas &amp; peran dalam satu dokumen</div>
					</div>
					<div class="kgtk-export-item-meta">
						<span class="kgtk-export-badge is-featured"><?php echo (int) $total_semua_peran; ?> orang</span>
						<span class="kgtk-export-item-btn">&#128229; Unduh</span>
					</div>
				</a>

				<!-- Opsi Biodata Seluruh Peserta -->
				<?php $url_bio_semua = KGTK_Export::semua_biodata_pdf_url( $p->id ); ?>
				<a href="<?php echo esc_url( $url_bio_semua ); ?>" target="_blank" class="kgtk-export-item" data-skip-date="1">
					<div class="kgtk-export-item-icon" style="background:#eff6ff;color:#2563eb;">&#128100;</div>
					<div class="kgtk-export-item-info">
						<div class="kgtk-export-item-title">Biodata Seluruh Peserta &amp; Tim</div>
						<div class="kgtk-export-item-desc">Lembar biodata individu seluruh peran siap cetak</div>
					</div>
					<div class="kgtk-export-item-meta">
						<span class="kgtk-export-badge"><?php echo (int) $total_semua_peran; ?> lembar</span>
						<span class="kgtk-export-item-btn">&#128424;&#65039; Cetak</span>
					</div>
				</a>

				<!-- Opsi Daftar Tanda Terima Barang / Alat -->
				<?php $url_tt_semua = KGTK_Export::tanda_terima_pdf_url( $p->id, 'Baju' ); ?>
				<a href="<?php echo esc_url( $url_tt_semua ); ?>" target="_blank" class="kgtk-export-item" data-skip-date="1">
					<div class="kgtk-export-item-icon" style="background:#fef3c7;color:#d97706;">&#128230;</div>
					<div class="kgtk-export-item-info">
						<div class="kgtk-export-item-title">Tanda Terima Barang / Alat</div>
						<div class="kgtk-export-item-desc">Daftar tanda terima perlengkapan peserta</div>
					</div>
					<div class="kgtk-export-item-meta">
						<span class="kgtk-export-badge"><?php echo (int) $total_pendaftar; ?> orang</span>
						<span class="kgtk-export-item-btn">&#128424;&#65039; Cetak</span>
					</div>
				</a>

				<div class="kgtk-export-section-label">&#128203; Daftar Hadir Per Kategori / Peran</div>

				<div class="kgtk-export-grid">
					<?php foreach ( $peran_list as $r_name => $r_desc ) :
						$cnt = isset( $counts_raw[ $r_name ] ) ? (int) $counts_raw[ $r_name ]->cnt : 0;
						if ( $is_acara_export && ! in_array( $r_name, array( 'Peserta', 'Panitia' ), true ) && 0 === $cnt ) {
							continue;
						}
						$url_peran = KGTK_Export::url( $p->id, 'pdf', $r_name, $tgl_param );
					?>
						<a href="<?php echo esc_url( $url_peran ); ?>" target="_blank" class="kgtk-export-item kgtk-export-item--compact" title="Unduh Daftar Hadir <?php echo esc_attr( $r_name ); ?>">
							<span class="kgtk-export-item-title" style="margin:0;"><?php echo esc_html( $r_name ); ?></span>
							<span class="kgtk-export-badge <?php echo $cnt > 0 ? 'is-active' : 'is-empty'; ?>"><?php echo (int) $cnt; ?> &#11015;</span>
						</a>
					<?php endforeach; ?>
				</div>

				<div class="kgtk-export-section-label">&#128100; Biodata Per Kategori / Peran (Unduh PDF)</div>

				<div class="kgtk-export-grid">
					<?php foreach ( $peran_list as $r_name => $r_desc ) :
						$cnt = isset( $counts_raw[ $r_name ] ) ? (int) $counts_raw[ $r_name ]->cnt : 0;
						if ( $is_acara_export && ! in_array( $r_name, array( 'Peserta', 'Panitia' ), true ) && 0 === $cnt ) {
							continue;
						}
						$url_bio_peran = KGTK_Export::semua_biodata_pdf_url( $p->id, $r_name, 0, '', true );
					?>
						<a href="<?php echo esc_url( $url_bio_peran ); ?>" target="_blank" class="kgtk-export-item kgtk-export-item--compact kgtk-export-item--bio" data-skip-date="1" title="Unduh Biodata PDF <?php echo esc_attr( $r_name ); ?>">
							<span class="kgtk-export-item-title" style="margin:0;"><?php echo esc_html( $r_name ); ?></span>
							<span class="kgtk-export-badge <?php echo $cnt > 0 ? 'is-active-bio' : 'is-empty'; ?>"><?php echo (int) $cnt; ?> &#11015;</span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<div class="kgtk-modal-actions" style="display:flex;justify-content:flex-end;align-items:center;margin-top:14px;padding-top:12px;">
			<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-btn-export-pdf-batal">Tutup</button>
		</div>
	</div>
</div>
