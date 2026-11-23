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
	<div class="kgtk-modal-dialog kgtk-ui kgtk-modal-export-dialog">
		<div class="kgtk-modal-export-header">
			<div class="kgtk-modal-export-title-wrap">
				<span class="kgtk-modal-export-icon" style="background:#ecfdf5;color:#059669;">&#128202;</span>
				<div>
					<h3 class="kgtk-modal-title" style="margin:0 !important;font-size:16.5px;">Export Data Excel (.xlsx)</h3>
					<p class="kgtk-muted" style="margin:1px 0 0;font-size:12px;">Unduh rekapitulasi peserta atau format lembar hadir Excel</p>
				</div>
			</div>
			<button type="button" class="kgtk-modal-close-btn" id="kgtk-btn-export-excel-close" title="Tutup">&times;</button>
		</div>

		<div class="kgtk-modal-body-scroll">
			<div class="kgtk-export-list">
				<!-- Opsi 1: Semua Kelas (Lengkap) -->
				<?php $url_excel_semua = KGTK_Export::url( $p->id, 'xlsx' ); ?>
				<a href="<?php echo esc_url( $url_excel_semua ); ?>" class="kgtk-export-item kgtk-export-item--featured">
					<div class="kgtk-export-item-icon" style="background:#059669;color:#fff;">&#128202;</div>
					<div class="kgtk-export-item-info">
						<div class="kgtk-export-item-title">Rekap Semua Peserta (Lengkap)</div>
						<div class="kgtk-export-item-desc">Seluruh biodata peserta &amp; tim dalam format tabel</div>
					</div>
					<div class="kgtk-export-item-meta">
						<span class="kgtk-export-badge is-featured"><?php echo (int) $total_pendaftar; ?> orang</span>
						<span class="kgtk-export-item-btn" style="background:#059669;color:#fff;">&#128229; Unduh</span>
					</div>
				</a>

				<!-- Opsi Format Absen Excel -->
				<?php $url_absen_excel_semua = KGTK_Export::url( $p->id, 'xlsx_absen' ); ?>
				<a href="<?php echo esc_url( $url_absen_excel_semua ); ?>" class="kgtk-export-item">
					<div class="kgtk-export-item-icon" style="background:#e0f2fe;color:#0284c7;">&#128196;</div>
					<div class="kgtk-export-item-info">
						<div class="kgtk-export-item-title">Lembar Hadir Excel (Semua Kelas)</div>
						<div class="kgtk-export-item-desc">Format daftar hadir siap edit di Microsoft Excel</div>
					</div>
					<div class="kgtk-export-item-meta">
						<span class="kgtk-export-item-btn">&#128229; Unduh</span>
					</div>
				</a>

				<?php if ( ! empty( $kelas ) ) : ?>
					<div class="kgtk-export-section-label">Rekap Data Per Kelas / Kategori</div>
					<div class="kgtk-export-grid">
						<?php foreach ( $kelas as $k_item ) :
							$k_cnt = isset( $k_item->peserta ) ? count( $k_item->peserta ) : 0;
							$url_k = KGTK_Export::url( $p->id, 'xlsx', '', '', $k_item->id );
						?>
							<a href="<?php echo esc_url( $url_k ); ?>" class="kgtk-export-item kgtk-export-item--compact">
								<span class="kgtk-export-item-title" style="margin:0;"><?php echo esc_html( $k_item->label ); ?></span>
								<span class="kgtk-export-badge <?php echo $k_cnt > 0 ? 'is-active' : 'is-empty'; ?>"><?php echo (int) $k_cnt; ?> &#11015;</span>
							</a>
						<?php endforeach; ?>

						<?php if ( ! empty( $unassigned ) && (int) $unassigned > 0 ) :
							$url_unassigned = KGTK_Export::url( $p->id, 'xlsx', '', '', -1 );
						?>
							<a href="<?php echo esc_url( $url_unassigned ); ?>" class="kgtk-export-item kgtk-export-item--compact" style="border-color:#fcd34d;background:#fffbeb;">
								<span class="kgtk-export-item-title" style="margin:0;color:#92400e;">Belum Ada Kelas</span>
								<span class="kgtk-export-badge" style="background:#fef3c7;color:#92400e;"><?php echo (int) $unassigned; ?> &#11015;</span>
							</a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<div class="kgtk-modal-actions" style="display:flex;justify-content:flex-end;margin-top:14px;padding-top:12px;">
			<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-btn-export-excel-batal">Tutup</button>
		</div>
	</div>
</div>

