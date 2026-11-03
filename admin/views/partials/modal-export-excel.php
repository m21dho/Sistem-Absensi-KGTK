<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Partial View: Modal Export Excel
 *
 * @var object $p               Data pelatihan.
 * @var array  $kelas           Statistik dan data kelas pelatihan.
 * @var int    $total_pendaftar Total seluruh peserta terdaftar.
 * @var int    $unassigned      Jumlah peserta belum masuk kelas.
 */
?>
<div class="kgtk-modal-overlay" id="kgtk-modal-export-excel" style="display:none;">
	<div class="kgtk-modal-dialog kgtk-ui" style="max-width:580px;width:95%;">
		<div class="kgtk-modal-top" style="align-items:flex-start;">
			<div class="kgtk-modal-icon is-primary" style="background:#ecfdf5;color:#059669;">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/>
				</svg>
			</div>
			<div class="kgtk-modal-content" style="width:100%;">
				<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
					<div>
						<h3 class="kgtk-modal-title" style="margin:0;font-size:17px;">Export Data Excel (.xlsx)</h3>
						<p class="kgtk-muted" style="margin:2px 0 12px;font-size:12.5px;">Unduh rekap data peserta ke dalam berkas Microsoft Excel (.xlsx) secara menyeluruh atau per kelas.</p>
					</div>
					<button type="button" class="kgtk-search-clear" id="kgtk-btn-export-excel-close" style="position:static;width:28px;height:28px;line-height:26px;font-size:20px;" title="Tutup">&times;</button>
				</div>

				<div class="kgtk-export-list" style="max-height:60vh;overflow-y:auto;padding-right:2px;display:flex;flex-direction:column;gap:8px;">
					<!-- Opsi 1: Semua Kelas (Lengkap) -->
					<?php $url_excel_semua = KGTK_Export::url( $p->id, 'xlsx' ); ?>
					<a href="<?php echo esc_url( $url_excel_semua ); ?>" class="kgtk-export-item kgtk-export-item--featured">
						<div class="kgtk-export-item-icon" style="background:#ecfdf5;color:#059669;">&#128202;</div>
						<div class="kgtk-export-item-info">
							<div class="kgtk-export-item-title">Semua Kelas (Seluruh Peserta)</div>
							<div class="kgtk-export-item-desc">Mencakup rekap 28 kolom data lengkap seluruh peserta dari semua kelas</div>
						</div>
						<div class="kgtk-export-item-meta">
							<span class="kgtk-export-badge is-featured"><?php echo (int) $total_pendaftar; ?> peserta</span>
							<span class="kgtk-export-item-btn" style="background:#059669;color:#fff;">&#128229; Unduh Excel</span>
						</div>
					</a>

					<div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;padding:6px 2px 2px;">Export Data Berdasarkan Kelas</div>

					<!-- Opsi Per Kelas -->
					<?php foreach ( $kelas as $k_item ) :
						$k_cnt = isset( $k_item->peserta ) ? count( $k_item->peserta ) : 0;
						$url_k = KGTK_Export::url( $p->id, 'xlsx', '', '', $k_item->id );
					?>
						<a href="<?php echo esc_url( $url_k ); ?>" class="kgtk-export-item">
							<div class="kgtk-export-item-icon" style="background:#f1f5f9;color:#0b2d56;">&#127979;</div>
							<div class="kgtk-export-item-info">
								<div class="kgtk-export-item-title"><?php echo esc_html( $k_item->label ); ?></div>
								<div class="kgtk-export-item-desc">Rekap 28 kolom data peserta khusus <?php echo esc_html( $k_item->label ); ?></div>
							</div>
							<div class="kgtk-export-item-meta">
								<span class="kgtk-export-badge"><?php echo (int) $k_cnt; ?> peserta</span>
								<span class="kgtk-export-item-btn">&#128229; Unduh</span>
							</div>
						</a>
					<?php endforeach; ?>

					<?php if ( ! empty( $unassigned ) && (int) $unassigned > 0 ) :
						$url_unassigned = KGTK_Export::url( $p->id, 'xlsx', '', '', -1 );
					?>
						<a href="<?php echo esc_url( $url_unassigned ); ?>" class="kgtk-export-item" style="border-left: 3px solid #f59e0b;">
							<div class="kgtk-export-item-icon" style="background:#fffbeb;color:#d97706;">&#9888;&#65039;</div>
							<div class="kgtk-export-item-info">
								<div class="kgtk-export-item-title">Belum Ada Kelas</div>
								<div class="kgtk-export-item-desc">Peserta yang sudah terdaftar namun belum ditempatkan ke dalam kelas</div>
							</div>
							<div class="kgtk-export-item-meta">
								<span class="kgtk-export-badge is-featured" style="background:#fef3c7;color:#92400e;"><?php echo (int) $unassigned; ?> guru</span>
								<span class="kgtk-export-item-btn">&#128229; Unduh</span>
							</div>
						</a>
					<?php endif; ?>

					<div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;padding:8px 2px 2px;margin-top:4px;">Format Lembar Hadir (Absen) Excel</div>

					<!-- Opsi Format Absen Excel -->
					<?php $url_absen_excel_semua = KGTK_Export::url( $p->id, 'xlsx_absen' ); ?>
					<a href="<?php echo esc_url( $url_absen_excel_semua ); ?>" class="kgtk-export-item" style="border-left: 3px solid #0284c7;">
						<div class="kgtk-export-item-icon" style="background:#e0f2fe;color:#0284c7;">&#128196;</div>
						<div class="kgtk-export-item-info">
							<div class="kgtk-export-item-title">Lembar Hadir Excel (Semua Kelas)</div>
							<div class="kgtk-export-item-desc">Format daftar hadir tanda tangan per peran &amp; per kelas (.xlsx)</div>
						</div>
						<div class="kgtk-export-item-meta">
							<span class="kgtk-export-item-btn">&#128229; Unduh</span>
						</div>
					</a>

					<?php if ( count( $kelas ) > 1 ) : ?>
						<?php foreach ( $kelas as $k_item ) :
							$url_absen_k = KGTK_Export::url( $p->id, 'xlsx_absen', '', '', $k_item->id );
						?>
							<a href="<?php echo esc_url( $url_absen_k ); ?>" class="kgtk-export-item">
								<div class="kgtk-export-item-icon" style="background:#f0fdf4;color:#16a34a;">&#128221;</div>
								<div class="kgtk-export-item-info">
									<div class="kgtk-export-item-title">Lembar Hadir <?php echo esc_html( $k_item->label ); ?></div>
									<div class="kgtk-export-item-desc">Format absensi tanda tangan khusus <?php echo esc_html( $k_item->label ); ?></div>
								</div>
								<div class="kgtk-export-item-meta">
									<span class="kgtk-export-item-btn">&#128229; Unduh</span>
								</div>
							</a>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>

				<div class="kgtk-modal-actions" style="display:flex;justify-content:flex-end;margin-top:16px;">
					<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-btn-export-excel-batal">Tutup</button>
				</div>
			</div>
		</div>
	</div>
</div>

