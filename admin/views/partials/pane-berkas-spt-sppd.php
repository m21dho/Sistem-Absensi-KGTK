<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Partial View: Pane Lembar Checklist & Tanda Terima Berkas SPT & SPPD Peserta
 *
 * @var object $p           Data pelatihan.
 * @var array  $kelas       Data kelas.
 * @var array  $all_peserta Daftar seluruh peserta.
 * @var bool   $multi       Apakah multi kelas.
 */

// Filter peserta yang wajib menyerahkan SPT & SPPD (seluruh peserta reguler, tidak termasuk panitia)
$spt_peserta = array_values( array_filter( (array) $all_peserta, function( $item ) {
	return 'Panitia' !== ( $item->peran ?? '' );
} ) );

$spt_count = count( $spt_peserta );

// Susun peserta per kelas
$by_kelas_spt = array();
foreach ( $kelas as $k ) {
	$by_kelas_spt[ (int) $k->id ] = array_values( array_filter( $spt_peserta, function( $item ) use ( $k ) {
		return (int) $item->kelas_id === (int) $k->id;
	} ) );
}

$pages_all_spt = KGTK_Helpers::halaman( $spt_peserta, max( KGTK_Helpers::BARIS_HALAMAN_AWAL, $spt_count ) );
?>
<div class="kgtk-pane" data-pane="berkas-spt-sppd" data-pelatihan="<?php echo (int) $p->id; ?>">
	<div class="kgtk-sub is-active" data-sub="berkas-spt-sppd">
		<!-- Kontrol Toolbar & Filter -->
		<div class="kgtk-card kgtk-kelola-box kgtk-noprint-note" style="margin-bottom:16px;">
			<div class="kgtk-kelola-head">
				<div>
					<h2 class="kgtk-h2">&#128221; Daftar Tanda Terima Berkas SPT &amp; SPPD Peserta</h2>
					<p class="kgtk-muted">Lembar checklist dan verifikasi fisik penyerahan berkas SPT dan SPPD dari peserta saat registrasi kegiatan.</p>
				</div>
				<div class="kgtk-head-actions">
					<button type="button" class="kgtk-btn kgtk-btn--navy" id="kgtk-btn-cetak-spt-sppd" data-cetak="berkas-spt-sppd" title="Cetak langsung lembar checklist SPT &amp; SPPD ke printer/kertas A4">&#128424;&#65039; Cetak Checklist SPT &amp; SPPD</button>
				</div>
			</div>

			<div class="kgtk-row2" style="align-items:flex-start;gap:20px;margin-top:14px;">
				<div class="kgtk-field" style="margin-bottom:0;">
					<label for="kgtk-spt-sppd-kelas-select" style="font-weight:700;font-size:13px;color:#0b2d56;display:block;margin-bottom:5px;">
						&#127979; Cakupan Peserta:
					</label>
					<select class="kgtk-input" id="kgtk-spt-sppd-kelas-select">
						<option value="all">Semua Peserta (Seluruh Kelas - <?php echo (int) $spt_count; ?> orang)</option>
						<?php foreach ( $kelas as $k ) :
							$k_cnt = isset( $by_kelas_spt[ (int) $k->id ] ) ? count( $by_kelas_spt[ (int) $k->id ] ) : 0;
						?>
							<option value="<?php echo (int) $k->id; ?>"><?php echo esc_html( $k->label ); ?> (<?php echo (int) $k_cnt; ?> orang)</option>
						<?php endforeach; ?>
					</select>
					<div class="kgtk-muted" style="font-size:12px;margin-top:5px;">
						Pilih untuk menampilkan semua peserta sekaligus atau disaring per kelas.
					</div>
				</div>
			</div>
		</div>

		<!-- Lembar Checklist SPT & SPPD Siap Cetak (A4) -->
		<div class="kgtk-card kgtk-sheet" id="kgtk-spt-sppd-sheet-container">
			<!-- Group 1: Semua Peserta -->
			<div class="kgtk-spt-group" data-spt-group="all">
				<?php
				$tot_all_spt_rows = max( KGTK_Helpers::BARIS_HALAMAN_AWAL, $spt_count );
				?>
				<section class="kgtk-page kgtk-page--first">
					<?php echo KGTK_Helpers::kop_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<h2 class="kgtk-doc-title">DAFTAR TANDA TERIMA BERKAS SPT &amp; SPPD PESERTA</h2>
					<?php echo KGTK_Helpers::baris_info_html( $p ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

					<div class="kgtk-scroll">
						<table class="kgtk-table" style="table-layout:fixed;width:100%;box-sizing:border-box;">
							<colgroup>
								<col style="width:6%;">
								<col style="width:38%;">
								<col style="width:40%;">
								<col style="width:8%;">
								<col style="width:8%;">
							</colgroup>
							<thead>
								<tr>
									<th rowspan="2" class="c-no">No</th>
									<th rowspan="2" class="c-nama" style="text-align:center;">Nama Peserta</th>
									<th rowspan="2" class="c-inst" style="text-align:center;">Asal Sekolah / Instansi</th>
									<th colspan="2" style="text-align:center;padding:2px 4px;">Kelengkapan Berkas</th>
								</tr>
								<tr>
									<th style="text-align:center;font-size:10.5pt;font-weight:bold;padding:2px 2px;">SPT</th>
									<th style="text-align:center;font-size:10.5pt;font-weight:bold;padding:2px 2px;">SPPD</th>
								</tr>
							</thead>
							<tbody>
								<?php
								for ( $i = 0; $i < $tot_all_spt_rows; $i++ ) :
									$row       = isset( $spt_peserta[ $i ] ) ? $spt_peserta[ $i ] : null;
									$no_urutan = (int) ( $i + 1 );
									$nama      = $row ? KGTK_Helpers::format_nama_gelar( $row ) : '';
									$instansi  = $row && ! empty( $row->sekolah ) ? $row->sekolah : '';
								?>
									<tr>
										<td class="c-no"><?php echo (int) $no_urutan; ?></td>
										<td class="c-nama"><?php echo $row ? esc_html( $nama ) : '&nbsp;'; ?></td>
										<td class="c-inst"><?php echo $row ? esc_html( $instansi ) : '&nbsp;'; ?></td>
										<td style="text-align:center;vertical-align:middle;padding:2px 2px;">
											<span class="kgtk-cb-square"></span>
										</td>
										<td style="text-align:center;vertical-align:middle;padding:2px 2px;">
											<span class="kgtk-cb-square"></span>
										</td>
									</tr>
								<?php endfor; ?>
							</tbody>
						</table>
					</div>

					<?php echo KGTK_Helpers::ttd_blok_html( $p ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</section>
			</div>

			<!-- Group Per Kelas -->
			<?php foreach ( $kelas as $k ) :
				$rows_k         = isset( $by_kelas_spt[ (int) $k->id ] ) ? $by_kelas_spt[ (int) $k->id ] : array();
				$tot_k_spt_rows = max( KGTK_Helpers::BARIS_HALAMAN_AWAL, count( $rows_k ) );
			?>
				<div class="kgtk-spt-group" data-spt-group="<?php echo (int) $k->id; ?>" style="display:none;">
					<section class="kgtk-page kgtk-page--first">
						<?php echo KGTK_Helpers::kop_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<h2 class="kgtk-doc-title">DAFTAR TANDA TERIMA BERKAS SPT &amp; SPPD PESERTA</h2>
						<?php echo KGTK_Helpers::baris_info_html( $p, '', $k ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<p class="kgtk-doc-kelas"><?php echo esc_html( $k->label ); ?></p>

						<div class="kgtk-scroll">
							<table class="kgtk-table" style="table-layout:fixed;width:100%;box-sizing:border-box;">
								<colgroup>
									<col style="width:6%;">
									<col style="width:38%;">
									<col style="width:40%;">
									<col style="width:8%;">
									<col style="width:8%;">
								</colgroup>
								<thead>
									<tr>
										<th rowspan="2" class="c-no">No</th>
										<th rowspan="2" class="c-nama" style="text-align:center;">Nama Peserta</th>
										<th rowspan="2" class="c-inst" style="text-align:center;">Asal Sekolah / Instansi</th>
										<th colspan="2" style="text-align:center;padding:2px 4px;">Kelengkapan Berkas</th>
									</tr>
									<tr>
										<th style="text-align:center;font-size:10.5pt;font-weight:bold;padding:2px 2px;">SPT</th>
										<th style="text-align:center;font-size:10.5pt;font-weight:bold;padding:2px 2px;">SPPD</th>
									</tr>
								</thead>
								<tbody>
									<?php
									for ( $i = 0; $i < $tot_k_spt_rows; $i++ ) :
										$row       = isset( $rows_k[ $i ] ) ? $rows_k[ $i ] : null;
										$no_urutan = (int) ( $i + 1 );
										$nama      = $row ? KGTK_Helpers::format_nama_gelar( $row ) : '';
										$instansi  = $row && ! empty( $row->sekolah ) ? $row->sekolah : '';
									?>
										<tr>
											<td class="c-no"><?php echo (int) $no_urutan; ?></td>
											<td class="c-nama"><?php echo $row ? esc_html( $nama ) : '&nbsp;'; ?></td>
											<td class="c-inst"><?php echo $row ? esc_html( $instansi ) : '&nbsp;'; ?></td>
											<td style="text-align:center;vertical-align:middle;padding:2px 2px;">
												<span class="kgtk-cb-square"></span>
											</td>
											<td style="text-align:center;vertical-align:middle;padding:2px 2px;">
												<span class="kgtk-cb-square"></span>
											</td>
										</tr>
									<?php endfor; ?>
								</tbody>
							</table>
						</div>

						<?php echo KGTK_Helpers::ttd_blok_html( $p ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</section>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
