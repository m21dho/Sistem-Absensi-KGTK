<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Partial View: Pane Daftar Tanda Terima Barang / Alat Pelatihan & ATK Pengajar
 *
 * @var object $p           Data pelatihan.
 * @var array  $kelas       Data kelas.
 * @var array  $all_peserta Daftar seluruh peserta.
 * @var bool   $multi       Apakah multi kelas.
 */

// Filter peserta yang menerima barang (seluruh peserta, tidak termasuk panitia)
$tt_peserta = array_values( array_filter( (array) $all_peserta, function( $item ) {
	return 'Panitia' !== ( $item->peran ?? '' );
} ) );

$tt_count = count( $tt_peserta );

// Susun peserta per kelas
$by_kelas = array();
foreach ( $kelas as $k ) {
	$by_kelas[ (int) $k->id ] = array_values( array_filter( $tt_peserta, function( $item ) use ( $k ) {
		return (int) $item->kelas_id === (int) $k->id;
	} ) );
}

$pages_all = KGTK_Helpers::halaman( $tt_peserta, max( KGTK_Helpers::BARIS_HALAMAN_AWAL, $tt_count ) );

$default_extra   = 10;
$default_pdf_url = KGTK_Export::tanda_terima_pdf_url( $p->id, 'Baju', 0, $default_extra );

// Data untuk ATK Pengajar / Fasilitator / Pemateri
$atk_items = KGTK_Helpers::get_atk_items( $p->id );
if ( empty( $atk_items ) ) {
	$atk_items = KGTK_Helpers::default_atk_items();
}

$pengajar_list = array_values( array_filter( (array) $all_peserta, function( $item ) {
	return in_array( $item->peran ?? '', array( 'Pengajar', 'Fasilitator', 'Narasumber', 'Penceramah', 'Pemateri' ), true );
} ) );

$default_atk_pdf_url = KGTK_Export::tanda_terima_atk_pdf_url( $p->id );
?>
<div class="kgtk-pane" data-pane="tanda-terima" data-pelatihan="<?php echo (int) $p->id; ?>">
	<!-- Subtab Navigasi: Peserta vs Pengajar/Fasilitator -->
	<div class="kgtk-subtabs kgtk-noprint-note" style="margin-bottom:16px;">
		<button type="button" class="kgtk-subtab is-active" data-sub="tanda-terima-peserta">
			&#128230; Tanda Terima Peserta (Barang / Baju)
		</button>
		<button type="button" class="kgtk-subtab" data-sub="tanda-terima-atk">
			&#128221; Tanda Terima ATK (Pengajar / Pemateri / Fasilitator)
		</button>
	</div>

	<!-- =====================================================================
	     SUBTAB 1: TANDA TERIMA PESERTA (BARANG / BAJU / PERLENGKAPAN)
	     ===================================================================== -->
	<div class="kgtk-sub is-active" data-sub="tanda-terima-peserta">
		<!-- Kontrol Pengaturan Tanda Terima Peserta -->
		<div class="kgtk-card kgtk-kelola-box kgtk-noprint-note" style="margin-bottom:16px;">
			<div class="kgtk-kelola-head">
				<div>
					<h2 class="kgtk-h2">&#128230; Daftar Tanda Terima Barang / Alat Peserta</h2>
					<p class="kgtk-muted">Cetak lembar tanda terima barang atau perlengkapan (misal: Baju, ATK, Modul, Seminar Kit) yang dibagikan kepada peserta.</p>
				</div>
				<div class="kgtk-head-actions">
					<button type="button" class="kgtk-btn kgtk-btn--navy" id="kgtk-btn-cetak-tt" data-cetak="tanda-terima-peserta" title="Cetak langsung lembar tanda terima ke printer/kertas A4">&#128424;&#65039; Cetak Lembar</button>
					<a class="kgtk-btn kgtk-btn--primary" id="kgtk-btn-export-pdf-tt" href="<?php echo esc_url( $default_pdf_url ); ?>" data-base-url="<?php echo esc_url( $default_pdf_url ); ?>" target="_blank" title="Download dokumen PDF tanda terima">&#128196; Export PDF</a>
				</div>
			</div>

			<div class="kgtk-row2" style="align-items:flex-start;gap:20px;margin-top:14px;">
				<div class="kgtk-field" style="margin-bottom:0;">
					<label for="kgtk-tt-item-input" style="font-weight:700;font-size:13px;color:#0b2d56;display:block;margin-bottom:5px;">
						&#128230; Barang / Alat Yang Diterima:
					</label>
					<input type="text" class="kgtk-input" id="kgtk-tt-item-input" value="Baju" placeholder="Contoh: Baju, Seminar Kit, ATK, Modul...">
					<div class="kgtk-muted" style="font-size:12px;margin-top:5px;">
						Judul pada dokumen: <strong style="color:#0b2d56;">DAFTAR TANDA TERIMA <span class="kgtk-tt-preview-label">BAJU</span></strong>
					</div>
				</div>

				<div class="kgtk-field" style="margin-bottom:0;">
					<label for="kgtk-tt-kelas-select" style="font-weight:700;font-size:13px;color:#0b2d56;display:block;margin-bottom:5px;">
						&#127979; Cakupan Peserta:
					</label>
					<select class="kgtk-input" id="kgtk-tt-kelas-select">
						<option value="all">Semua Peserta (Seluruh Kelas - <?php echo (int) $tt_count; ?> orang)</option>
						<?php foreach ( $kelas as $k ) :
							$k_cnt = isset( $by_kelas[ (int) $k->id ] ) ? count( $by_kelas[ (int) $k->id ] ) : 0;
						?>
							<option value="<?php echo (int) $k->id; ?>"><?php echo esc_html( $k->label ); ?> (<?php echo (int) $k_cnt; ?> orang)</option>
						<?php endforeach; ?>
					</select>
					<div class="kgtk-muted" style="font-size:12px;margin-top:5px;">
						Pilih untuk menampilkan semua peserta sekaligus atau disaring per kelas.
					</div>
				</div>
			</div>

			<div style="margin-top:14px;padding:10px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
				<label for="kgtk-tt-extra-slots" style="font-weight:700;font-size:13px;color:#0b2d56;display:inline-flex;align-items:center;gap:6px;">
					<span>&#9997;&#65039; Slot Tambahan Baris Kosong (Tanda Tangan Manual):</span>
				</label>
				<input type="number" class="kgtk-input" id="kgtk-tt-extra-slots" value="10" min="0" max="60" step="2" style="width:75px;padding:4px 8px;font-weight:700;text-align:center;">
				<span class="kgtk-muted" style="font-size:12px;">baris kosong disediakan di akhir lembar tanda terima untuk peserta tambahan/susulan di lokasi.</span>
			</div>
		</div>

		<!-- Lembar Tanda Terima Peserta Siap Cetak (A4) -->
		<div class="kgtk-card kgtk-sheet" id="kgtk-tt-sheet-container">
			<!-- Group 1: Semua Peserta -->
			<?php
			$base_all_rows = (int) ( ceil( $tt_count / 2 ) * 2 );
			$tot_all_rows  = $base_all_rows + $default_extra;
			if ( 0 !== $tot_all_rows % 2 ) {
				$tot_all_rows++;
			}
			?>
			<div class="kgtk-tt-group is-active" data-tt-group="all" data-base-count="<?php echo (int) $tt_count; ?>">
				<section class="kgtk-page kgtk-page--first">
					<?php echo KGTK_Helpers::kop_html(); ?>
					<h2 class="kgtk-doc-title">DAFTAR TANDA TERIMA<span class="kgtk-tt-item-display"> BAJU</span></h2>
					<?php echo KGTK_Helpers::baris_info_html( $p ); ?>

					<div class="kgtk-scroll">
						<table class="kgtk-table kgtk-tt-table" style="table-layout:fixed;width:100%;">
							<colgroup>
								<col style="width:7%;">
								<col style="width:30%;">
								<col style="width:37%;">
								<col style="width:13%;">
								<col style="width:13%;">
							</colgroup>
							<thead>
								<tr>
									<th class="c-no">No</th>
									<th class="c-nama">Nama Peserta</th>
									<th class="c-inst">Asal Sekolah / Instansi</th>
									<th class="c-ttd" colspan="2" style="white-space:nowrap;text-align:center;">Tanda Tangan</th>
								</tr>
							</thead>
							<tbody class="kgtk-tt-tbody">
							<?php
							for ( $i = 0; $i < $tot_all_rows; $i += 2 ) :
								$row1     = isset( $tt_peserta[ $i ] ) ? $tt_peserta[ $i ] : null;
								$no1      = (int) ( $i + 1 );
								$has_pair = ( $i + 1 < $tot_all_rows );
								$row2     = ( $has_pair && isset( $tt_peserta[ $i + 1 ] ) ) ? $tt_peserta[ $i + 1 ] : null;
								$no2      = (int) ( $i + 2 );
								$is_extra = ( $i >= $base_all_rows );
							?>
								<tr class="<?php echo $is_extra ? 'kgtk-tt-extra-row' : ''; ?>" data-row-idx="<?php echo (int) $i; ?>">
									<td class="c-no"><?php echo (int) $no1; ?></td>
									<td class="c-nama"><?php echo $row1 ? esc_html( KGTK_Helpers::format_nama_gelar( $row1 ) ) : '&nbsp;'; ?></td>
									<td class="c-inst"><?php echo $row1 ? esc_html( $row1->sekolah ) : '&nbsp;'; ?></td>
									<?php if ( $has_pair ) : ?>
										<td class="c-ttd-col c-ttd-left" rowspan="2"><?php echo (int) $no1; ?>.</td>
										<td class="c-ttd-col c-ttd-right" rowspan="2"><?php echo (int) ( $no1 + 1 ); ?>.</td>
									<?php else : ?>
										<td class="c-ttd-col c-ttd-left"><?php echo (int) $no1; ?>.</td>
										<td class="c-ttd-col c-ttd-right">&nbsp;</td>
									<?php endif; ?>
								</tr>
								<?php if ( $has_pair ) : ?>
									<tr class="<?php echo $is_extra ? 'kgtk-tt-extra-row' : ''; ?>" data-row-idx="<?php echo (int) ( $i + 1 ); ?>">
										<td class="c-no"><?php echo (int) $no2; ?></td>
										<td class="c-nama"><?php echo $row2 ? esc_html( KGTK_Helpers::format_nama_gelar( $row2 ) ) : '&nbsp;'; ?></td>
										<td class="c-inst"><?php echo $row2 ? esc_html( $row2->sekolah ) : '&nbsp;'; ?></td>
									</tr>
								<?php endif; ?>
							<?php endfor; ?>
							</tbody>
						</table>
					</div>

					<?php echo KGTK_Helpers::ttd_blok_html( $p ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</section>
			</div>

			<!-- Group Per Kelas (Bila Multi Kelas atau spesifik kelas) -->
			<?php foreach ( $kelas as $k ) :
				$rows_k      = isset( $by_kelas[ (int) $k->id ] ) ? $by_kelas[ (int) $k->id ] : array();
				$k_count     = count( $rows_k );
				$base_k_rows = (int) ( ceil( $k_count / 2 ) * 2 );
				$tot_k_rows  = $base_k_rows + $default_extra;
				if ( 0 !== $tot_k_rows % 2 ) {
					$tot_k_rows++;
				}
			?>
				<div class="kgtk-tt-group is-hidden" data-tt-group="<?php echo (int) $k->id; ?>" data-base-count="<?php echo (int) $k_count; ?>" style="display:none;">
					<section class="kgtk-page kgtk-page--first">
						<?php echo KGTK_Helpers::kop_html(); ?>
						<h2 class="kgtk-doc-title">DAFTAR TANDA TERIMA<span class="kgtk-tt-item-display"> BAJU</span></h2>
						<?php echo KGTK_Helpers::baris_info_html( $p, '', $k ); ?>
						<p class="kgtk-doc-kelas"><?php echo esc_html( $k->label ); ?></p>

						<div class="kgtk-scroll">
							<table class="kgtk-table kgtk-tt-table" style="table-layout:fixed;width:100%;">
								<colgroup>
									<col style="width:7%;">
									<col style="width:30%;">
									<col style="width:37%;">
									<col style="width:13%;">
									<col style="width:13%;">
								</colgroup>
								<thead>
									<tr>
										<th class="c-no">No</th>
										<th class="c-nama">Nama Peserta</th>
										<th class="c-inst">Asal Sekolah / Instansi</th>
										<th class="c-ttd" colspan="2" style="white-space:nowrap;text-align:center;">Tanda Tangan</th>
									</tr>
								</thead>
								<tbody class="kgtk-tt-tbody">
								<?php
								for ( $i = 0; $i < $tot_k_rows; $i += 2 ) :
									$row1     = isset( $rows_k[ $i ] ) ? $rows_k[ $i ] : null;
									$no1      = (int) ( $i + 1 );
									$has_pair = ( $i + 1 < $tot_k_rows );
									$row2     = ( $has_pair && isset( $rows_k[ $i + 1 ] ) ) ? $rows_k[ $i + 1 ] : null;
									$no2      = (int) ( $i + 2 );
									$is_extra = ( $i >= $base_k_rows );
								?>
									<tr class="<?php echo $is_extra ? 'kgtk-tt-extra-row' : ''; ?>" data-row-idx="<?php echo (int) $i; ?>">
										<td class="c-no"><?php echo (int) $no1; ?></td>
										<td class="c-nama"><?php echo $row1 ? esc_html( KGTK_Helpers::format_nama_gelar( $row1 ) ) : '&nbsp;'; ?></td>
										<td class="c-inst"><?php echo $row1 ? esc_html( $row1->sekolah ) : '&nbsp;'; ?></td>
										<?php if ( $has_pair ) : ?>
											<td class="c-ttd-col c-ttd-left" rowspan="2"><?php echo (int) $no1; ?>.</td>
											<td class="c-ttd-col c-ttd-right" rowspan="2"><?php echo (int) ( $no1 + 1 ); ?>.</td>
										<?php else : ?>
											<td class="c-ttd-col c-ttd-left"><?php echo (int) $no1; ?>.</td>
											<td class="c-ttd-col c-ttd-right">&nbsp;</td>
										<?php endif; ?>
									</tr>
									<?php if ( $has_pair ) : ?>
										<tr class="<?php echo $is_extra ? 'kgtk-tt-extra-row' : ''; ?>" data-row-idx="<?php echo (int) ( $i + 1 ); ?>">
											<td class="c-no"><?php echo (int) $no2; ?></td>
											<td class="c-nama"><?php echo $row2 ? esc_html( KGTK_Helpers::format_nama_gelar( $row2 ) ) : '&nbsp;'; ?></td>
											<td class="c-inst"><?php echo $row2 ? esc_html( $row2->sekolah ) : '&nbsp;'; ?></td>
										</tr>
									<?php endif; ?>
								<?php endfor; ?>
								</tbody>
							</table>
						</div>

						<?php echo KGTK_Helpers::ttd_blok_html( $p, '', $k ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</section>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- =====================================================================
	     SUBTAB 2: TANDA TERIMA ATK PENGAJAR / FASILITATOR / PEMATERI
	     ===================================================================== -->
	<div class="kgtk-sub" data-sub="tanda-terima-atk">
		<!-- Box Pengaturan & Input Manual ATK -->
		<div class="kgtk-card kgtk-kelola-box kgtk-noprint-note" style="margin-bottom:16px;">
			<div class="kgtk-kelola-head">
				<div>
					<h2 class="kgtk-h2">&#128221; Kelola &amp; Cetak Tanda Terima ATK Pengajar / Fasilitator</h2>
					<p class="kgtk-muted">Tanda terima perlengkapan ATK untuk Pengajar, Fasilitator, atau Pemateri. Admin dapat menambahkan, mengubah, atau menghapus item ATK secara manual di bawah, lalu mencetak lembar A4 atau download PDF.</p>
				</div>
				<div class="kgtk-head-actions">
					<button type="button" class="kgtk-btn kgtk-btn--navy" id="kgtk-btn-cetak-atk" data-cetak="tanda-terima-atk" title="Cetak langsung lembar tanda terima ATK ke printer/kertas A4">&#128424;&#65039; Cetak Lembar ATK</button>
					<a class="kgtk-btn kgtk-btn--primary" id="kgtk-btn-export-pdf-atk" href="<?php echo esc_url( $default_atk_pdf_url ); ?>" data-base-url="<?php echo esc_url( $default_atk_pdf_url ); ?>" target="_blank" title="Download dokumen PDF tanda terima ATK">&#128196; Export PDF</a>
				</div>
			</div>

			<!-- Form Pengaturan Penerima & Penyerah -->
			<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;margin-top:14px;">
				<div class="kgtk-row2" style="align-items:flex-start;gap:16px;">
					<div class="kgtk-field" style="margin-bottom:0;">
						<label for="kgtk-atk-penerima-select" style="font-weight:700;font-size:13px;color:#0b2d56;display:block;margin-bottom:5px;">
							Penerima (Pengajar / Pemateri / Fasilitator):
						</label>
						<select class="kgtk-input" id="kgtk-atk-penerima-select">
							<option value="0" data-nama="" data-nip="" data-instansi="" data-peran="Pengajar / Fasilitator">-- Format Terbuka (Titik-titik / Ditulis Manual) --</option>
							<option value="custom" data-nama="" data-nip="" data-instansi="" data-peran="Pengajar">Input Nama Manual...</option>
							<?php if ( ! empty( $pengajar_list ) ) : ?>
								<optgroup label="Pengajar / Fasilitator Terdaftar">
									<?php foreach ( $pengajar_list as $pgj ) : ?>
										<option value="<?php echo (int) $pgj->id; ?>"
											data-nama="<?php echo esc_attr( $pgj->nama ); ?>"
											data-nip="<?php echo esc_attr( $pgj->nip ?? '' ); ?>"
											data-instansi="<?php echo esc_attr( $pgj->sekolah ?? '' ); ?>"
											data-peran="<?php echo esc_attr( $pgj->peran ?? 'Pengajar' ); ?>">
											<?php echo esc_html( $pgj->nama ); ?> (<?php echo esc_html( $pgj->peran ?? 'Pengajar' ); ?><?php echo ! empty( $pgj->sekolah ) ? ' - ' . esc_html( $pgj->sekolah ) : ''; ?>)
										</option>
									<?php endforeach; ?>
								</optgroup>
							<?php endif; ?>
						</select>

						<!-- Input Nama Manual jika opsi "Input Nama Manual" dipilih -->
						<div id="kgtk-atk-custom-wrap" style="display:none;margin-top:8px;">
							<input type="text" class="kgtk-input" id="kgtk-atk-custom-nama" placeholder="Ketik nama lengkap & gelar penerima...">
							<div style="display:flex;gap:8px;margin-top:6px;">
								<input type="text" class="kgtk-input" id="kgtk-atk-custom-instansi" placeholder="Asal sekolah / instansi (opsional)">
								<input type="text" class="kgtk-input" id="kgtk-atk-custom-nip" placeholder="NIP (opsional)">
							</div>
						</div>
					</div>

					<div class="kgtk-row2" style="gap:12px;margin-bottom:0;">
						<div class="kgtk-field" style="margin-bottom:0;">
							<label for="kgtk-atk-penyerah-input" style="font-weight:700;font-size:13px;color:#0b2d56;display:block;margin-bottom:5px;">
								&#128101; Yang Menyerahkan (Panitia):
							</label>
							<input type="text" class="kgtk-input" id="kgtk-atk-penyerah-input" value="<?php echo esc_attr( KGTK_Helpers::nama_pj( $p ) ); ?>" placeholder="Nama Panitia Pelaksana">
						</div>

						<div class="kgtk-field" style="margin-bottom:0;">
							<label for="kgtk-atk-tanggal-input" style="font-weight:700;font-size:13px;color:#0b2d56;display:block;margin-bottom:5px;">
								&#128197; Tanggal Penyerahan:
							</label>
							<input type="text" class="kgtk-input" id="kgtk-atk-tanggal-input" value="<?php echo esc_attr( KGTK_Helpers::format_tanggal( $p->tanggal_mulai ) ); ?>" placeholder="Contoh: 21 Oktober 2026">
						</div>
					</div>
				</div>
			</div>

			<!-- Interactive Table Editor: Tambah Manual ATK -->
			<div style="margin-top:18px;">
				<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
					<h3 style="font-size:14px;font-weight:700;color:#0b2d56;margin:0;">
						&#128221; Daftar Barang / ATK (Edit Manual oleh Admin):
					</h3>
					<span class="kgtk-muted" style="font-size:12px;">Perubahan pada tabel ini akan otomatis tampil di pratinjau lembar cetak di bawah.</span>
				</div>

				<div class="kgtk-scroll" style="border:1px solid #cbd5e1;border-radius:8px;background:#fff;">
					<table class="kgtk-table" id="kgtk-atk-editor-table" style="margin:0;width:100%;table-layout:fixed;">
						<colgroup>
							<col style="width:6%;">
							<col style="width:40%;">
							<col style="width:14%;">
							<col style="width:16%;">
							<col style="width:18%;">
							<col style="width:6%;">
						</colgroup>
						<thead>
							<tr>
								<th style="text-align:center;">No</th>
								<th>Nama ATK / Barang</th>
								<th style="text-align:center;">Jumlah ATK</th>
								<th style="text-align:center;">Kuantitas (Satuan)</th>
								<th>Keterangan / Spesifikasi</th>
								<th style="text-align:center;">Aksi</th>
							</tr>
						</thead>
						<tbody id="kgtk-atk-editor-tbody">
							<?php
							$row_no = 1;
							foreach ( $atk_items as $it ) :
							?>
								<tr class="kgtk-atk-row">
									<td class="kgtk-atk-row-no" style="text-align:center;font-weight:700;color:#475569;"><?php echo (int) $row_no++; ?></td>
									<td>
										<input type="text" class="kgtk-input kgtk-atk-nama" value="<?php echo esc_attr( $it['nama'] ?? '' ); ?>" placeholder="Nama ATK (misal: Spidol Whiteboard)" style="font-size:13px;padding:6px 8px;">
									</td>
									<td>
										<input type="text" class="kgtk-input kgtk-atk-jumlah" value="<?php echo esc_attr( $it['jumlah'] ?? '1' ); ?>" placeholder="1" style="text-align:center;font-size:13px;padding:6px 8px;">
									</td>
									<td>
										<input type="text" list="kgtk-atk-satuan-list" class="kgtk-input kgtk-atk-kuantitas" value="<?php echo esc_attr( $it['kuantitas'] ?? 'Buah' ); ?>" placeholder="Buah / Rim / Set" style="text-align:center;font-size:13px;padding:6px 8px;">
									</td>
									<td>
										<input type="text" class="kgtk-input kgtk-atk-keterangan" value="<?php echo esc_attr( $it['keterangan'] ?? '' ); ?>" placeholder="Merk/warna/tipe (opsional)" style="font-size:13px;padding:6px 8px;">
									</td>
									<td style="text-align:center;">
										<button type="button" class="kgtk-btn kgtk-btn--danger kgtk-btn-del-atk" title="Hapus baris ini" style="padding:4px 8px;font-size:12px;line-height:1;">&#128465;&#65039;</button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<!-- Datalist Satuan Kuantitas -->
				<datalist id="kgtk-atk-satuan-list">
					<option value="Buah">
					<option value="Rim">
					<option value="Kotak">
					<option value="Pack">
					<option value="Set">
					<option value="Lusin">
					<option value="Pad">
					<option value="Pcs">
					<option value="Batang">
					<option value="Buku">
					<option value="Lembar">
				</datalist>

				<!-- Tombol Aksi Editor ATK -->
				<div style="display:flex;align-items:center;flex-wrap:wrap;gap:10px;margin-top:12px;">
					<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-btn-add-atk" style="border:1.5px dashed #0083C4;color:#0083C4;font-weight:700;">
						&#10133; Tambah Item ATK
					</button>
					<button type="button" class="kgtk-btn kgtk-btn--secondary" id="kgtk-btn-reset-atk" title="Kembalikan ke daftar default rekomendasi">
						&#128260; Reset ke Default ATK
					</button>

					<div style="margin-left:auto;display:flex;align-items:center;gap:12px;">
						<span id="kgtk-atk-save-msg" style="font-size:13px;font-weight:600;"></span>
						<button type="button" class="kgtk-btn kgtk-btn--navy" id="kgtk-btn-save-atk">
							&#128190; Simpan Perubahan ATK
						</button>
					</div>
				</div>
			</div>
		</div>

		<!-- Lembar Tanda Terima ATK Siap Cetak (A4) -->
		<div class="kgtk-card kgtk-sheet" id="kgtk-atk-sheet-container">
			<section class="kgtk-page">
				<?php echo KGTK_Helpers::kop_html(); ?>

				<h2 class="kgtk-doc-title">DAFTAR TANDA TERIMA ALAT TULIS KANTOR (ATK)</h2>
				<div style="text-align:center;font-size:11.5pt;font-weight:bold;color:#222;margin:-2px 0 12px;letter-spacing:0.3px;">
					BAGI PENGAJAR / PEMATERI / FASILITATOR PELATIHAN
				</div>

				<!-- Info Pelatihan & Penerima ATK -->
				<table style="width:100%;margin:0 0 12px;border-collapse:collapse;border:0;font-size:11.5pt;">
					<tr>
						<td style="width:20%;font-weight:bold;padding:2px 4px;border:0;">Nama Pelatihan</td>
						<td style="width:2%;padding:2px 4px;border:0;">:</td>
						<td style="padding:2px 4px;border:0;"><?php echo esc_html( $p->nama ); ?></td>
					</tr>
					<tr>
						<td style="font-weight:bold;padding:2px 4px;border:0;">Waktu Pelaksanaan</td>
						<td style="padding:2px 4px;border:0;">:</td>
						<td style="padding:2px 4px;border:0;"><?php echo esc_html( KGTK_Helpers::rentang_tanggal( $p->tanggal_mulai, $p->tanggal_selesai ) ); ?></td>
					</tr>
					<tr>
						<td style="font-weight:bold;padding:2px 4px;border:0;">Tempat Pelaksanaan</td>
						<td style="padding:2px 4px;border:0;">:</td>
						<td style="padding:2px 4px;border:0;"><?php echo esc_html( $p->lokasi ?: '-' ); ?></td>
					</tr>
					<tr>
						<td style="font-weight:bold;padding:2px 4px;border:0;">Nama Penerima</td>
						<td style="padding:2px 4px;border:0;">:</td>
						<td style="padding:2px 4px;border:0;">
							<strong id="kgtk-preview-penerima-nama">...........................................................................................</strong>
							<span id="kgtk-preview-penerima-peran" style="color:#333;"></span>
						</td>
					</tr>
					<tr id="kgtk-preview-instansi-row" style="display:none;">
						<td style="font-weight:bold;padding:2px 4px;border:0;">Asal Instansi</td>
						<td style="padding:2px 4px;border:0;">:</td>
						<td style="padding:2px 4px;border:0;" id="kgtk-preview-penerima-instansi">-</td>
					</tr>
				</table>

				<!-- Tabel Item ATK Lembar Cetak -->
				<div class="kgtk-scroll">
					<table class="kgtk-table" id="kgtk-atk-preview-table" style="table-layout:fixed;width:100%;margin:12px 0 18px;">
						<colgroup>
							<col style="width:7%;">
							<col style="width:43%;">
							<col style="width:14%;">
							<col style="width:16%;">
							<col style="width:20%;">
						</colgroup>
						<thead>
							<tr>
								<th class="c-no" style="text-align:center;">No</th>
								<th>Nama ATK / Perlengkapan</th>
								<th style="text-align:center;">Jumlah</th>
								<th style="text-align:center;">Kuantitas</th>
								<th>Keterangan</th>
							</tr>
						</thead>
						<tbody id="kgtk-atk-preview-tbody">
							<?php
							$p_no = 1;
							foreach ( $atk_items as $it ) :
							?>
								<tr>
									<td class="c-no" style="text-align:center;"><?php echo (int) $p_no++; ?></td>
									<td class="c-nama"><?php echo esc_html( $it['nama'] ?? '' ); ?></td>
									<td style="text-align:center;font-weight:bold;"><?php echo esc_html( $it['jumlah'] ?? '1' ); ?></td>
									<td style="text-align:center;"><?php echo esc_html( $it['kuantitas'] ?? 'Buah' ); ?></td>
									<td><?php echo esc_html( $it['keterangan'] ?? '-' ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<!-- Dual Signature: Yang Menyerahkan & Yang Menerima -->
				<div class="kgtk-ttd-dual" style="display:flex;justify-content:space-between;margin-top:24px;width:100%;">
					<div class="kgtk-ttd-box" style="width:46%;text-align:center;font-size:11.5pt;">
						<div>Yang Menyerahkan,</div>
						<div style="font-weight:bold;">Panitia Pelaksana</div>
						<div class="kgtk-ttd-space" style="height:62px;"></div>
						<strong id="kgtk-preview-penyerah-nama"><?php echo esc_html( KGTK_Helpers::nama_pj( $p ) ); ?></strong>
					</div>

					<div class="kgtk-ttd-box" style="width:46%;text-align:center;font-size:11.5pt;">
						<div>
							<span id="kgtk-preview-lokasi"><?php echo esc_html( $p->lokasi ?: 'Makassar' ); ?></span>,
							<span id="kgtk-preview-tanggal"><?php echo esc_html( KGTK_Helpers::format_tanggal( $p->tanggal_mulai ) ); ?></span>
						</div>
						<div>Yang Menerima,</div>
						<div style="font-weight:bold;" id="kgtk-preview-ttd-peran">Pengajar / Fasilitator</div>
						<div class="kgtk-ttd-space" style="height:62px;"></div>
						<strong id="kgtk-preview-ttd-penerima">( ............................................................ )</strong>
						<div id="kgtk-preview-ttd-nip-wrap" style="display:none;font-size:10.5pt;color:#333;margin-top:3px;">
							NIP. <span id="kgtk-preview-ttd-nip"></span>
						</div>
					</div>
				</div>
			</section>
		</div>
	</div>
</div>
