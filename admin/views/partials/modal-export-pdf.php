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
if ( ! isset( $daftar_hari ) ) {
	$daftar_hari  = isset( $p ) ? KGTK_Helpers::daftar_hari_pelatihan( $p->tanggal_mulai, $p->tanggal_selesai ) : array();
	$is_multi_day = count( $daftar_hari ) > 1;
	$tgl_default  = isset( $p ) ? KGTK_Helpers::rentang_tanggal( $p->tanggal_mulai, $p->tanggal_selesai ) : '';
	$tgl_param    = $is_multi_day ? 'all_days' : '';
}
if ( ! isset( $peran_list ) ) {
	$peran_list = array(
		'Peserta'     => 'Daftar hadir seluruh peserta pelatihan',
		'Panitia'     => 'Lembar khusus panitia pelaksana kegiatan',
		'Fasilitator' => 'Lembar khusus fasilitator kegiatan',
		'Pengajar'    => 'Lembar khusus pengajar / instruktur',
		'Narasumber'  => 'Lembar khusus narasumber kegiatan',
		'Pemateri'    => 'Lembar khusus pemateri kegiatan',
	);
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
?>
<div class="kgtk-modal-overlay" id="kgtk-modal-export-pdf" style="display:none;">
	<div class="kgtk-modal-dialog kgtk-ui" style="max-width:520px;width:95%;padding:20px 22px;">
		<div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid #e2e8f0;">
			<h3 class="kgtk-modal-title" style="margin:0;font-size:17px;display:flex;align-items:center;gap:8px;">
				<span>&#128196;</span> <span>Export &amp; Cetak PDF</span>
			</h3>
			<button type="button" class="kgtk-search-clear" id="kgtk-btn-export-pdf-close" style="position:static;width:28px;height:28px;line-height:26px;font-size:20px;" title="Tutup">&times;</button>
		</div>

		<?php if ( $is_multi_day ) : ?>
			<div class="kgtk-export-date-box" style="padding:8px 12px;margin-bottom:12px;">
				<label for="kgtk-export-pdf-tanggal" style="font-size:12.5px;font-weight:700;color:#0b2d56;margin:0;">
					&#128197; Tanggal Absen:
				</label>
				<select class="kgtk-input" id="kgtk-export-pdf-tanggal" style="flex:1;min-width:200px;height:34px;padding:4px 8px;font-size:12.5px;background:#fff;">
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

		<div class="kgtk-export-list" style="display:flex;flex-direction:column;gap:8px;">
			<!-- Opsi 1: Semua Peran (Daftar Hadir Lengkap) -->
			<?php $url_semua = KGTK_Export::url( $p->id, 'pdf', '', $tgl_param ); ?>
			<a href="<?php echo esc_url( $url_semua ); ?>" target="_blank" class="kgtk-export-item kgtk-export-item--featured">
				<div class="kgtk-export-item-icon">&#128218;</div>
				<div class="kgtk-export-item-info">
					<div class="kgtk-export-item-title">Daftar Hadir Lengkap (Semua)</div>
				</div>
				<div class="kgtk-export-item-meta">
					<span class="kgtk-export-badge is-featured"><?php echo (int) $total_pendaftar; ?> orang</span>
					<span class="kgtk-export-item-btn">&#128229; Unduh</span>
				</div>
			</a>

			<!-- Opsi Biodata Seluruh Peserta -->
			<?php $url_bio_semua = KGTK_Export::semua_biodata_pdf_url( $p->id ); ?>
			<a href="<?php echo esc_url( $url_bio_semua ); ?>" target="_blank" class="kgtk-export-item" data-skip-date="1">
				<div class="kgtk-export-item-icon" style="background:#eff6ff;color:#2563eb;">&#128100;</div>
				<div class="kgtk-export-item-info">
					<div class="kgtk-export-item-title">Biodata Seluruh Peserta</div>
				</div>
				<div class="kgtk-export-item-meta">
					<span class="kgtk-export-badge"><?php echo (int) $total_pendaftar; ?> lembar</span>
					<span class="kgtk-export-item-btn">&#128424;&#65039; Cetak</span>
				</div>
			</a>

			<!-- Opsi Daftar Tanda Terima Barang / Alat -->
			<?php $url_tt_semua = KGTK_Export::tanda_terima_pdf_url( $p->id, 'Baju' ); ?>
			<a href="<?php echo esc_url( $url_tt_semua ); ?>" target="_blank" class="kgtk-export-item" data-skip-date="1">
				<div class="kgtk-export-item-icon" style="background:#fef3c7;color:#d97706;">&#128230;</div>
				<div class="kgtk-export-item-info">
					<div class="kgtk-export-item-title">Tanda Terima Barang / Alat</div>
				</div>
				<div class="kgtk-export-item-meta">
					<span class="kgtk-export-badge"><?php echo (int) $total_pendaftar; ?> orang</span>
					<span class="kgtk-export-item-btn">&#128424;&#65039; Cetak</span>
				</div>
			</a>

			<div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;padding:6px 2px 0;">Daftar Hadir Per Kategori / Peran</div>

			<div class="kgtk-export-grid">
				<?php foreach ( $peran_list as $r_name => $r_desc ) :
					$cnt = isset( $counts_raw[ $r_name ] ) ? (int) $counts_raw[ $r_name ]->cnt : 0;
					if ( $is_acara_export && ! in_array( $r_name, array( 'Peserta', 'Panitia' ), true ) && 0 === $cnt ) {
						continue;
					}
					$url_peran = KGTK_Export::url( $p->id, 'pdf', $r_name, $tgl_param );
				?>
					<a href="<?php echo esc_url( $url_peran ); ?>" target="_blank" class="kgtk-export-item kgtk-export-item--compact">
						<span class="kgtk-export-item-title" style="margin:0;"><?php echo esc_html( $r_name ); ?></span>
						<span class="kgtk-export-badge <?php echo $cnt > 0 ? 'is-active' : 'is-empty'; ?>"><?php echo (int) $cnt; ?> &#11015;</span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="kgtk-modal-actions" style="display:flex;justify-content:flex-end;align-items:center;margin-top:14px;">
			<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-btn-export-pdf-batal">Tutup</button>
		</div>
	</div>
</div>
