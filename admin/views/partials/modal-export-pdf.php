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
?>
<div class="kgtk-modal-overlay" id="kgtk-modal-export-pdf">
	<div class="kgtk-modal-dialog kgtk-ui" style="max-width:560px;width:95%;">
		<div class="kgtk-modal-top" style="align-items:flex-start;">
			<div class="kgtk-modal-icon is-primary">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
				</svg>
			</div>
			<div class="kgtk-modal-content" style="width:100%;">
				<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
					<div>
						<h3 class="kgtk-modal-title" style="margin:0;font-size:17px;">Export Daftar Hadir PDF</h3>
						<p class="kgtk-muted" style="margin:2px 0 12px;font-size:12.5px;">Pilih kategori daftar hadir yang ingin diunduh atau dicetak dalam format PDF resmi.</p>
					</div>
					<button type="button" class="kgtk-search-clear" id="kgtk-btn-export-pdf-close" style="position:static;width:28px;height:28px;line-height:26px;font-size:20px;" title="Tutup">&times;</button>
				</div>

				<?php if ( $is_multi_day ) : ?>
					<div class="kgtk-export-date-box">
						<div style="flex:1;min-width:180px;">
							<label for="kgtk-export-pdf-tanggal" style="font-size:12.5px;font-weight:700;color:#0b2d56;display:flex;align-items:center;gap:5px;">
								<span>&#128197; Hari / Tanggal Absen:</span>
							</label>
							<p class="kgtk-muted" style="margin:2px 0 0;font-size:11.5px;">Pilih tanggal pelaksanaan yang akan dicetak pada lembar PDF</p>
						</div>
						<select class="kgtk-input" id="kgtk-export-pdf-tanggal" style="min-width:230px;max-width:100%;height:36px;padding:4px 8px;font-size:12.5px;background:#fff;">
							<option value="all_days" selected>Semua Hari (Masing-masing Hari Dicetak - <?php echo count( $daftar_hari ); ?> Hari)</option>
							<?php foreach ( $daftar_hari as $dh ) : ?>
								<option value="<?php echo esc_attr( $dh['ymd'] ); ?>" data-label="<?php echo esc_attr( $dh['label_lengkap'] ); ?>">
									<?php echo esc_html( $dh['label_hari_ke'] ); ?>
								</option>
							<?php endforeach; ?>
							<option value="range" data-label="<?php echo esc_attr( $tgl_default ); ?>">Rentang Pelaksanaan (1 Lembar: <?php echo esc_html( $tgl_default ); ?>)</option>
						</select>
					</div>
				<?php endif; ?>

				<div class="kgtk-export-list" style="max-height:60vh;overflow-y:auto;padding-right:2px;display:flex;flex-direction:column;gap:8px;">
					<!-- Opsi 1: Semua Peran (Daftar Hadir Lengkap) -->
					<?php $url_semua = KGTK_Export::url( $p->id, 'pdf', '', $tgl_param ); ?>
					<a href="<?php echo esc_url( $url_semua ); ?>" target="_blank" class="kgtk-export-item kgtk-export-item--featured">
						<div class="kgtk-export-item-icon">&#128218;</div>
						<div class="kgtk-export-item-info">
							<div class="kgtk-export-item-title">Semua Peran (Lengkap)</div>
							<div class="kgtk-export-item-desc">Mencakup seluruh kelas & semua lembar peran dalam 1 file PDF</div>
						</div>
						<div class="kgtk-export-item-meta">
							<span class="kgtk-export-badge is-featured"><?php echo (int) $total_pendaftar; ?> orang</span>
							<span class="kgtk-export-item-btn">&#128229; Unduh</span>
						</div>
					</a>

					<div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;padding:6px 2px 2px;">Export Berdasarkan Peran</div>

					<!-- Opsi Peran Individual -->
					<?php foreach ( $peran_list as $r_name => $r_desc ) :
						$cnt = isset( $counts_raw[ $r_name ] ) ? (int) $counts_raw[ $r_name ]->cnt : 0;
						$url_peran = KGTK_Export::url( $p->id, 'pdf', $r_name, $tgl_param );

						$icon = '&#128100;';
						if ( 'Peserta' === $r_name ) {
							$icon = '&#127891;';
						} elseif ( 'Pemateri' === $r_name || 'Pengajar' === $r_name ) {
							$icon = '&#129309;';
						} elseif ( 'Narasumber' === $r_name ) {
							$icon = '&#127908;';
						} elseif ( 'Fasilitator' === $r_name ) {
							$icon = '&#128188;';
						}
					?>
						<a href="<?php echo esc_url( $url_peran ); ?>" target="_blank" class="kgtk-export-item">
							<div class="kgtk-export-item-icon"><?php echo $icon; ?></div>
							<div class="kgtk-export-item-info">
								<div class="kgtk-export-item-title"><?php echo esc_html( $r_name ); ?> Saja</div>
								<div class="kgtk-export-item-desc"><?php echo esc_html( $r_desc ); ?></div>
							</div>
							<div class="kgtk-export-item-meta">
								<?php if ( $cnt > 0 ) : ?>
									<span class="kgtk-export-badge is-active"><?php echo (int) $cnt; ?> terdaftar</span>
								<?php else : ?>
									<span class="kgtk-export-badge is-empty">Format kosong (15 baris)</span>
								<?php endif; ?>
								<span class="kgtk-export-item-btn">&#128229; Unduh</span>
							</div>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<div class="kgtk-modal-actions" style="display:flex;justify-content:flex-end;align-items:center;margin-top:16px;">
			<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-btn-export-pdf-batal">Tutup</button>
		</div>
	</div>
</div>
