<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Partial View: Pane Lembar Registrasi Kamar Penginapan / Hotel Peserta
 *
 * @var object $p           Data pelatihan.
 * @var array  $kelas       Data kelas.
 * @var array  $all_peserta Daftar seluruh peserta.
 * @var bool   $multi       Apakah multi kelas.
 */

// Siapkan opsi tanggal kegiatan
$daftar_hari = array();
if ( ! empty( $p->tanggal_mulai ) && ! empty( $p->tanggal_selesai ) ) {
	$daftar_hari = KGTK_Helpers::daftar_hari_pelatihan( $p->tanggal_mulai, $p->tanggal_selesai );
}

$default_tanggal_label = '';
if ( ! empty( $daftar_hari ) ) {
	$first_day             = reset( $daftar_hari );
	$default_tanggal_label = $first_day['label_lengkap'] ?? '';
} elseif ( ! empty( $p->tanggal_mulai ) && '0000-00-00' !== $p->tanggal_mulai ) {
	$default_tanggal_label = KGTK_Helpers::format_hari_tanggal( $p->tanggal_mulai );
} else {
	$default_tanggal_label = KGTK_Helpers::rentang_tanggal( $p->tanggal_mulai, $p->tanggal_selesai );
}

// Siapkan nama tempat / lokasi kegiatan
$lok_raw      = ! empty( $p->lokasi ) ? $p->lokasi : ( ! empty( $p->kabkota ) ? $p->kabkota : '' );
$tempat_clean = preg_replace( '/^di\s+/i', '', trim( (string) $lok_raw ) );
if ( empty( $tempat_clean ) ) {
	$tempat_clean = 'BPMP Provinsi Kepulauan Riau';
}

$default_rows    = 30;
$default_pdf_url = class_exists( 'KGTK_Export' ) && method_exists( 'KGTK_Export', 'registrasi_kamar_pdf_url' )
	? KGTK_Export::registrasi_kamar_pdf_url( $p->id, $default_rows, $default_tanggal_label, $tempat_clean )
	: '#';
?>
<div class="kgtk-pane" data-pane="registrasi-kamar" data-pelatihan="<?php echo (int) $p->id; ?>">
	<div class="kgtk-sub is-active" data-sub="registrasi-kamar">

		<!-- Kontrol Panel Pengaturan Registrasi Kamar -->
		<div class="kgtk-card kgtk-kelola-box kgtk-noprint-note" style="margin-bottom:16px;">
			<div class="kgtk-kelola-head">
				<div>
					<h2 class="kgtk-h2">&#128716; Formulir Registrasi Kamar Peserta</h2>
					<p class="kgtk-muted">Cetak formulir registrasi penempatan kamar hotel / wisma bagi peserta saat kedatangan/check-in. Kolom nama, no HP, dan no kamar dibiarkan kosong untuk diisi langsung di lokasi.</p>
				</div>
				<div class="kgtk-head-actions">
					<button type="button" class="kgtk-btn kgtk-btn--navy" id="kgtk-btn-cetak-regkamar" data-cetak="registrasi-kamar" title="Cetak langsung lembar formulir registrasi kamar ke printer / kertas A4">&#128424;&#65039; Cetak Formulir</button>
					<a class="kgtk-btn kgtk-btn--primary" id="kgtk-btn-export-pdf-regkamar" href="<?php echo esc_url( $default_pdf_url ); ?>" data-base-url="<?php echo esc_url( $default_pdf_url ); ?>" target="_blank" title="Download formulir registrasi kamar dalam format PDF">&#128196; Export PDF</a>
				</div>
			</div>

			<!-- Pengaturan Jumlah Baris & Metadata Kop -->
			<div style="margin-top:14px;display:flex;flex-direction:column;gap:14px;">
				<div class="kgtk-row2" style="align-items:flex-start;gap:20px;">
					<!-- Kontrol Jumlah Baris -->
					<div class="kgtk-field" style="margin-bottom:0;flex:1;">
						<label for="kgtk-regkamar-rows-input" style="font-weight:700;font-size:13px;color:#0b2d56;display:flex;align-items:center;gap:6px;margin-bottom:6px;">
							<span>&#9997;&#65039; Tentukan Jumlah Baris yang Ingin Dicetak:</span>
						</label>
						<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
							<input type="number" class="kgtk-input" id="kgtk-regkamar-rows-input" value="30" min="2" max="300" step="2" style="width:85px;padding:6px 10px;font-weight:700;text-align:center;font-size:14px;">
							<span class="kgtk-muted" style="font-size:12px;">baris (kelipatan 2)</span>
							<div class="kgtk-regkamar-presets" style="display:flex;gap:5px;flex-wrap:wrap;">
								<button type="button" class="kgtk-btn kgtk-btn--outline kgtk-btn-sm kgtk-regkamar-preset" data-rows="30" title="1 Halaman penuh (30 baris)">30 (1 Hal)</button>
								<button type="button" class="kgtk-btn kgtk-btn--outline kgtk-btn-sm kgtk-regkamar-preset" data-rows="60" title="2 Halaman penuh (60 baris)">60 (2 Hal)</button>
								<button type="button" class="kgtk-btn kgtk-btn--outline kgtk-btn-sm kgtk-regkamar-preset" data-rows="90" title="3 Halaman penuh (90 baris)">90 (3 Hal)</button>
								<button type="button" class="kgtk-btn kgtk-btn--outline kgtk-btn-sm kgtk-regkamar-preset" data-rows="120" title="4 Halaman penuh (120 baris)">120 (4 Hal)</button>
							</div>
						</div>
						<div class="kgtk-muted" style="font-size:11.5px;margin-top:5px;">
							Satu halaman A4 muat maksimal 30 baris. Jika memilih lebih dari 30 baris, halaman berikutnya otomatis berlanjut rapi.
						</div>
					</div>

					<!-- Pilihan Hari / Tanggal -->
					<div class="kgtk-field" style="margin-bottom:0;flex:1;">
						<label for="kgtk-regkamar-tanggal-select" style="font-weight:700;font-size:13px;color:#0b2d56;display:block;margin-bottom:6px;">
							&#128197; Hari / Tanggal Registrasi:
						</label>
						<?php if ( ! empty( $daftar_hari ) ) : ?>
							<select class="kgtk-input" id="kgtk-regkamar-tanggal-select" style="margin-bottom:6px;">
								<?php foreach ( $daftar_hari as $dh ) : ?>
									<option value="<?php echo esc_attr( $dh['label_lengkap'] ); ?>"><?php echo esc_html( $dh['label_hari_ke'] . ' - ' . $dh['label_lengkap'] ); ?></option>
								<?php endforeach; ?>
								<option value="custom">Ketik Tanggal Sendiri...</option>
							</select>
						<?php endif; ?>
						<input type="text" class="kgtk-input" id="kgtk-regkamar-tanggal-input" value="<?php echo esc_attr( $default_tanggal_label ); ?>" placeholder="Contoh: Sabtu, 12 September 2026">
					</div>
				</div>

				<!-- Baris Lokasi / Tempat -->
				<div class="kgtk-field" style="margin-bottom:0;">
					<label for="kgtk-regkamar-tempat-input" style="font-weight:700;font-size:13px;color:#0b2d56;display:block;margin-bottom:5px;">
						&#127970; Tempat / Lokasi Kegiatan:
					</label>
					<input type="text" class="kgtk-input" id="kgtk-regkamar-tempat-input" value="<?php echo esc_attr( $tempat_clean ); ?>" placeholder="Contoh: BPMP Provinsi Kepulauan Riau">
				</div>
			</div>
		</div>

		<!-- Lembar Formulir Registrasi Kamar Siap Cetak (A4) -->
		<div class="kgtk-card kgtk-sheet" id="kgtk-regkamar-sheet-container" data-pel-nama="<?php echo esc_attr( $p->nama ); ?>">
			<?php
			$per_page_limit = 30;
			$total_pages    = max( 1, (int) ceil( $default_rows / $per_page_limit ) );
			$row_cursor     = 0;

			for ( $page_i = 1; $page_i <= $total_pages; $page_i++ ) :
				$is_first_page = ( 1 === $page_i );
				$rows_on_page  = min( $per_page_limit, $default_rows - $row_cursor );
				if ( 0 !== $rows_on_page % 2 ) {
					$rows_on_page++;
				}
			?>
				<section class="kgtk-page <?php echo $is_first_page ? 'kgtk-page--first' : 'kgtk-page--next'; ?>" data-page-num="<?php echo (int) $page_i; ?>">
					<?php echo KGTK_Helpers::kop_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>

					<h2 class="kgtk-doc-title" style="margin:2px 0 3px;font-size:13pt;font-weight:bold;text-align:center;text-transform:uppercase;letter-spacing:0.5px;">REGISTRASI KAMAR</h2>

					<div class="kgtk-doc-info kgtk-regkamar-info-block" style="text-align:center;margin:1px 0 4px;font-size:12pt;line-height:1.2;">
						<div class="kgtk-doc-nama kgtk-regkamar-val-nama" style="font-size:12pt;text-align:center;font-weight:normal;"><?php echo esc_html( $p->nama ); ?></div>
						<div class="kgtk-doc-tanggal kgtk-regkamar-val-tanggal" style="font-size:12pt;text-align:center;margin:1px 0;font-weight:normal;"><?php echo esc_html( $default_tanggal_label ); ?></div>
						<div class="kgtk-doc-lokasi kgtk-regkamar-val-tempat" style="font-size:12pt;text-align:center;margin:1px 0;font-weight:normal;"><?php echo esc_html( $tempat_clean ); ?></div>
					</div>

					<div class="kgtk-scroll">
						<table class="kgtk-table kgtk-regkamar-table" style="table-layout:fixed;width:100%;box-sizing:border-box;">
							<colgroup>
								<col style="width:6%;">
								<col style="width:34%;">
								<col style="width:20%;">
								<col style="width:18%;">
								<col style="width:11%;">
								<col style="width:11%;">
							</colgroup>
							<thead>
								<tr>
									<th class="c-no" rowspan="2" style="text-align:center;vertical-align:middle;padding:3px 2px;">No.</th>
									<th class="c-nama" rowspan="2" style="text-align:center;vertical-align:middle;padding:3px 4px;">Nama</th>
									<th class="c-nohp" rowspan="2" style="text-align:center;vertical-align:middle;padding:3px 4px;">No HP</th>
									<th class="c-kamar" rowspan="2" style="text-align:center;vertical-align:middle;padding:3px 4px;line-height:1.2;">No Kamar<br><span style="font-weight:normal;font-size:10pt;">(L/P)</span></th>
									<th class="c-ttd" colspan="2" style="text-align:center;vertical-align:middle;padding:3px 4px;">TTD</th>
								</tr>
							</thead>
							<tbody class="kgtk-regkamar-tbody">
								<?php
								for ( $i = 0; $i < $rows_on_page; $i += 2 ) :
									$no1      = $row_cursor + $i + 1;
									$has_pair = ( $i + 1 < $rows_on_page );
									$no2      = $no1 + 1;
								?>
									<tr class="kgtk-regkamar-row">
										<td class="c-no" style="text-align:center;"><?php echo (int) $no1; ?></td>
										<td class="c-nama">&nbsp;</td>
										<td class="c-nohp">&nbsp;</td>
										<td class="c-kamar">&nbsp;</td>
										<?php if ( $has_pair ) : ?>
											<td class="c-ttd-col c-ttd-left" rowspan="2" style="vertical-align:top;text-align:left;padding:2px 4px;font-weight:bold;"><?php echo (int) $no1; ?>.</td>
											<td class="c-ttd-col c-ttd-right" rowspan="2" style="vertical-align:bottom;text-align:right;padding:2px 4px;font-weight:bold;"><?php echo (int) $no2; ?>.</td>
										<?php else : ?>
											<td class="c-ttd-col c-ttd-left" style="vertical-align:top;text-align:left;padding:2px 4px;font-weight:bold;"><?php echo (int) $no1; ?>.</td>
											<td class="c-ttd-col c-ttd-right">&nbsp;</td>
										<?php endif; ?>
									</tr>
									<?php if ( $has_pair ) : ?>
										<tr class="kgtk-regkamar-row">
											<td class="c-no" style="text-align:center;"><?php echo (int) $no2; ?></td>
											<td class="c-nama">&nbsp;</td>
											<td class="c-nohp">&nbsp;</td>
											<td class="c-kamar">&nbsp;</td>
										</tr>
									<?php endif; ?>
								<?php endfor; ?>
							</tbody>
						</table>
					</div>
				</section>
			<?php
				$row_cursor += $rows_on_page;
			endfor;
			?>
		</div>
	</div>
</div>

