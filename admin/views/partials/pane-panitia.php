<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Partial View: Pane Panitia Pelatihan & Lembar Hadir Panitia
 *
 * @var object $p       Data pelatihan.
 * @var array  $panitia Daftar panitia untuk pelatihan ini.
 */
// Sinkronisasi otomatis: masukkan data panitia yang ada pada pelatihan ini ke master data jika belum ada
if ( ! empty( $panitia ) && class_exists( 'KGTK_Panitia' ) ) {
	foreach ( $panitia as $p_sync ) {
		if ( ! empty( $p_sync->nama ) ) {
			KGTK_Panitia::save( array(
				'nama'     => $p_sync->nama,
				'nik'      => $p_sync->nik,
				'nip'      => $p_sync->nip ?? '',
				'jabatan'  => ! empty( $p_sync->jabatan ) ? $p_sync->jabatan : 'Panitia Pelaksana',
				'instansi' => $p_sync->sekolah ?? '',
				'kabkota'  => $p_sync->kabkota ?? '',
				'hp'       => $p_sync->hp ?? '',
				'email'    => $p_sync->email ?? '',
				'bank'     => $p_sync->bank ?? '',
				'rekening' => $p_sync->rekening ?? '',
			) );
		}
	}
}
$panitia_list_master = KGTK_Panitia::get_all();
$panitia_count       = ! empty( $panitia ) ? count( $panitia ) : 0;

$master_map_by_nik  = array();
$master_map_by_nama = array();
foreach ( $panitia_list_master as $pm_item ) {
	if ( ! empty( $pm_item->nik ) && 0 !== strpos( $pm_item->nik, '99' ) ) {
		$master_map_by_nik[ $pm_item->nik ] = (int) $pm_item->id;
	}
	$master_map_by_nama[ strtolower( trim( $pm_item->nama ) ) ] = (int) $pm_item->id;
}

$dummy_k = (object) array(
	'id'    => 0,
	'nama'  => 'Panitia Pelaksana',
	'label' => 'Panitia Pelaksana',
	'slot'  => 0,
);
?>
<div class="kgtk-pane" data-pane="panitia">
	<!-- Subtabs: Kelola Panitia vs Lembar Hadir Panitia -->
	<div class="kgtk-subtabs kgtk-noprint-note" style="margin-bottom:16px;">
		<button type="button" class="kgtk-subtab is-active" data-sub="panitia-kelola">
			&#128100; Kelola Panitia <small class="kgtk-tab-badge" id="kgtk-badge-sub-panitia"><?php echo (int) $panitia_count; ?></small>
		</button>
		<button type="button" class="kgtk-subtab" data-sub="panitia-lembar-absen">
			&#128424;&#65039; Lembar Hadir Panitia
		</button>
	</div>

	<!-- SUBTAB 1: DAFTAR & KELOLA PANITIA -->
	<div class="kgtk-sub is-active" data-sub="panitia-kelola">
		<div class="kgtk-card kgtk-kelola-box" id="kgtk-panitia-container" data-pelatihan="<?php echo (int) $p->id; ?>">
			<div class="kgtk-kelola-head">
				<div>
					<h2 class="kgtk-h2">Panitia</h2>
					<p class="kgtk-muted">Daftar panitia pelaksana kegiatan.</p>
				</div>
				<div class="kgtk-head-actions">
					<button type="button" class="kgtk-btn kgtk-btn--navy" id="kgtk-btn-quick-print-panitia" data-cetak="panitia-lembar-absen" title="Cetak lembar presensi resmi panitia (A4)">&#128424;&#65039; Cetak Presensi Panitia</button>
					<a href="<?php echo esc_url( KGTK_Admin::page_url( 'pelatihan', array( 'view' => 'tambah', 'id' => (int) $p->id, 'peran' => 'Panitia' ) ) ); ?>" class="kgtk-btn kgtk-btn--yellow">&#128100; + Tambah Panitia</a>
					<button type="button" class="kgtk-btn kgtk-btn--outline" id="kgtk-btn-tambah-panitia-pelatihan" title="Pilih dari master panitia tersimpan">&#128196; Dari Master Data</button>
				</div>
			</div>

			<!-- List Panitia -->
			<?php if ( empty( $panitia ) ) : ?>
				<div class="kgtk-empty-peserta" style="text-align:center;padding:36px 16px;">
					<div style="font-size:36px;margin-bottom:8px;">&#128100;</div>
					<h3 style="margin:0 0 6px;color:#0b2d56;font-size:16px;">Belum Ada Panitia Ditambahkan</h3>
					<p class="kgtk-muted" style="margin:0 0 16px;">Tambahkan panitia pelaksana untuk kegiatan ini.</p>
					<a href="<?php echo esc_url( KGTK_Admin::page_url( 'pelatihan', array( 'view' => 'tambah', 'id' => (int) $p->id, 'peran' => 'Panitia' ) ) ); ?>" class="kgtk-btn kgtk-btn--yellow" style="margin-right:8px;">&#128100; + Tambah Panitia</a>
					<button type="button" class="kgtk-btn kgtk-btn--outline" onclick="var b = document.getElementById('kgtk-btn-tambah-panitia-pelatihan'); if(b) b.click();">&#128196; Dari Master Data</button>
				</div>
			<?php else : ?>
				<div class="kgtk-kelola-tools" style="margin-bottom:12px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
					<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;flex:1 1 400px;">
						<div class="kgtk-search-wrap" style="max-width:320px;flex:1 1 200px;">
							<span class="kgtk-search-icon">&#128269;</span>
							<input type="search" id="kgtk-panitia-pel-search" class="kgtk-input" placeholder="Cari nama panitia, NIK, jabatan, atau instansi...">
						</div>
						<select id="kgtk-panitia-pel-filter-kelas" class="kgtk-input" style="max-width:240px;height:36px;font-size:12.5px;font-weight:600;background:#fff;">
							<option value="all">Semua Panitia (Semua Kelas)</option>
							<option value="0">&#9888;&#65039; Belum Ada Kelas / Umum</option>
							<?php if ( ! empty( $kelas ) ) : ?>
								<?php foreach ( $kelas as $k_fil ) : ?>
									<option value="<?php echo (int) $k_fil->id; ?>">&#127979; <?php echo esc_html( $k_fil->label ); ?></option>
								<?php endforeach; ?>
							<?php endif; ?>
						</select>
					</div>
					<div class="kgtk-muted" style="font-size:13px;">
						Total: <strong><?php echo (int) $panitia_count; ?> panitia</strong> terdaftar pada kegiatan ini.
					</div>
				</div>

				<!-- Toolbar Aksi Masal Penempatan Kelas Panitia -->
				<div class="kgtk-bulk-toolbar" id="kgtk-pan-bulk-toolbar">
					<div class="kgtk-bulk-left">
						<label class="kgtk-bulk-checkall"><input type="checkbox" id="kgtk-pan-check-all"> <span>Pilih Semua</span></label>
						<span id="kgtk-pan-selected-badge" class="kgtk-selected-badge" style="display:none;">0 dipilih</span>
					</div>
					<div class="kgtk-bulk-actions">
						<span class="kgtk-bulk-label">Tempatkan panitia terpilih ke:</span>
						<select id="kgtk-pan-bulk-target" class="kgtk-input kgtk-bulk-select">
							<option value="">-- Pilih Kelas yang Diurus --</option>
							<option value="0">&#9888;&#65039; Umum / Belum Ada Kelas (Reset)</option>
							<?php if ( ! empty( $kelas ) ) : ?>
								<?php foreach ( $kelas as $k ) : ?>
									<option value="<?php echo (int) $k->id; ?>"><?php echo esc_html( $k->label ); ?></option>
								<?php endforeach; ?>
							<?php endif; ?>
						</select>
						<button type="button" class="kgtk-btn kgtk-btn--navy" id="kgtk-pan-bulk-apply" disabled>Terapkan</button>
					</div>
				</div>

				<div class="kgtk-scroll">
					<table class="kgtk-table kgtk-table--kelola" id="kgtk-tbl-panitia-pelatihan">
						<thead>
							<tr>
								<th style="width:38px;text-align:center;"><input type="checkbox" id="kgtk-pan-th-check-all" title="Pilih Semua Panitia"></th>
								<th style="width:46px;text-align:center;">No</th>
								<th style="min-width:200px;">Nama Panitia</th>
								<th style="min-width:150px;">Tugas / Jabatan</th>
								<th style="min-width:170px;">Asal Instansi</th>
								<th style="width:150px;">NIK / NIP</th>
								<th style="min-width:175px;">Kelas yang Diurus</th>
								<th style="width:140px;text-align:center;">Aksi</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$no = 1;
							foreach ( $panitia as $pan ) :
								$pan_kelas_id = isset( $pan->kelas_id ) ? (int) $pan->kelas_id : 0;
								if ( $pan_kelas_id > 0 && ! isset( $kelas_map[ $pan_kelas_id ] ) ) {
									$pan_kelas_id = 0;
								}
								$search_text = strtolower( $pan->nama . ' ' . $pan->nik . ' ' . ( $pan->nip ?? '' ) . ' ' . ( $pan->jabatan ?? '' ) . ' ' . $pan->sekolah . ' ' . ( $pan->hp ?? '' ) );
								$wa_num      = preg_replace( '/^0/', '62', preg_replace( '/\D/', '', (string) ( $pan->hp ?? '' ) ) );
								$pan_json    = wp_json_encode( array(
									'id'             => (int) $pan->id,
									'kelas_id'       => $pan_kelas_id,
									'nama'           => $pan->nama,
									'gelar_depan'    => $pan->gelar_depan ?? '',
									'gelar_belakang' => $pan->gelar_belakang ?? '',
									'jabatan'        => $pan->jabatan ?: 'Panitia Pelaksana',
									'nik'            => $pan->nik,
									'nip'            => $pan->nip ?? '',
									'nuptk'          => $pan->nuptk ?? '',
									'instansi'       => $pan->sekolah ?? '',
									'kabkota'        => $pan->kabkota ?? '',
									'hp'             => $pan->hp ?? '',
									'email'          => $pan->email ?? '',
									'bank'           => $pan->bank ?? '',
									'rekening'       => $pan->rekening ?? '',
									'npwp'           => $pan->npwp ?? '',
								) );
							?>
								<tr data-id="<?php echo (int) $pan->id; ?>" data-kelas="<?php echo (int) $pan_kelas_id; ?>" data-search="<?php echo esc_attr( $search_text ); ?>" data-json="<?php echo esc_attr( $pan_json ); ?>">
									<td class="c-no"><input type="checkbox" class="kgtk-pan-check" value="<?php echo (int) $pan->id; ?>" aria-label="Pilih <?php echo esc_attr( $pan->nama ); ?>"></td>
									<td class="c-no"><span class="kgtk-row-num"><?php echo (int) $no++; ?>.</span></td>
									<td>
										<div class="kgtk-guru-cell">
											<strong class="kgtk-guru-nama" style="font-size:13.5px;color:#0b2d56;"><?php echo esc_html( KGTK_Helpers::format_nama_gelar( $pan ) ); ?></strong>
											<?php if ( ! empty( $pan->hp ) && '-' !== $pan->hp ) : ?>
												<div class="kgtk-guru-kontak">
													<a href="https://wa.me/<?php echo esc_attr( $wa_num ); ?>" target="_blank" rel="noopener noreferrer" class="kgtk-wa-link" title="Kirim pesan WhatsApp">
														<span class="kgtk-wa-icon">&#128172;</span> <?php echo esc_html( $pan->hp ); ?>
													</a>
												</div>
											<?php endif; ?>
											<?php if ( ! empty( $pan->bank ) || ! empty( $pan->rekening ) ) : ?>
												<div class="kgtk-muted" style="font-size:11px;color:#0b2d56;background:#f8fafc;padding:1px 6px;border-radius:4px;border:1px solid #e2e8f0;margin-top:2px;">
													&#127974; <strong><?php echo esc_html( $pan->bank ?: 'Bank' ); ?>:</strong> <?php echo esc_html( $pan->rekening ?: '-' ); ?>
												</div>
											<?php endif; ?>
										</div>
									</td>
									<td>
										<span class="kgtk-badge" style="background:#eff6ff;color:#1e40af;font-size:12px;font-weight:600;padding:3px 8px;border-radius:4px;border:1px solid #bfdbfe;">
											&#128188; <?php echo esc_html( $pan->jabatan ?: 'Panitia Pelaksana' ); ?>
										</span>
									</td>
									<td>
										<div class="kgtk-sekolah-cell">
											<span class="kgtk-sekolah-nama" style="font-size:13px;"><?php echo esc_html( $pan->sekolah ?: '-' ); ?></span>
											<?php if ( ! empty( $pan->kabkota ) && 'Lainnya' !== $pan->kabkota && '-' !== $pan->kabkota ) : ?>
												<div class="kgtk-kab-tag">&#128205; <?php echo esc_html( $pan->kabkota ); ?></div>
											<?php endif; ?>
										</div>
									</td>
									<td>
										<div class="kgtk-nik-cell">
											<?php if ( ! empty( $pan->nik ) && 0 !== strpos( $pan->nik, '99' ) ) : ?>
												<code class="kgtk-nik-code" style="font-size:11.5px;"><?php echo esc_html( $pan->nik ); ?></code>
											<?php else : ?>
												<span class="kgtk-muted">-</span>
											<?php endif; ?>
										</div>
										<?php if ( ! empty( $pan->nip ) && '-' !== $pan->nip ) : ?>
											<div class="kgtk-muted" style="font-size:11px;margin-top:2px;">NIP: <?php echo esc_html( $pan->nip ); ?></div>
										<?php endif; ?>
									</td>
									<td>
										<select class="kgtk-input kgtk-pan-kelas-select" data-pan-id="<?php echo (int) $pan->id; ?>" style="min-width:155px;height:34px;padding:4px 8px;font-size:12.5px;font-weight:600;color:<?php echo $pan_kelas_id > 0 ? '#1e40af' : '#92400e'; ?>;background:<?php echo $pan_kelas_id > 0 ? '#eff6ff' : '#fffbeb'; ?>;border-color:<?php echo $pan_kelas_id > 0 ? '#bfdbfe' : '#fde68a'; ?>;">
											<option value="0" <?php selected( 0, $pan_kelas_id ); ?>>&#9888;&#65039; Umum / Belum Ada Kelas</option>
											<?php if ( ! empty( $kelas ) ) : ?>
												<?php foreach ( $kelas as $k ) : ?>
													<option value="<?php echo (int) $k->id; ?>" <?php selected( (int) $k->id, $pan_kelas_id ); ?>>&#127979; <?php echo esc_html( $k->label ); ?></option>
												<?php endforeach; ?>
											<?php endif; ?>
										</select>
									</td>
									<td style="text-align:center;">
										<?php
										$m_id = 0;
										if ( ! empty( $pan->nik ) && isset( $master_map_by_nik[ $pan->nik ] ) ) {
											$m_id = $master_map_by_nik[ $pan->nik ];
										} elseif ( isset( $master_map_by_nama[ strtolower( trim( $pan->nama ) ) ] ) ) {
											$m_id = $master_map_by_nama[ strtolower( trim( $pan->nama ) ) ];
										}
										?>
										<div style="display:inline-flex;align-items:center;gap:5px;justify-content:center;">
											<button type="button" class="kgtk-iconbtn kgtk-iconbtn--view" data-edit-panitia-pelatihan="<?php echo (int) $pan->id; ?>" title="Edit Biodata Panitia">&#9998;&#65039;</button>
											<button type="button" class="kgtk-iconbtn kgtk-iconbtn--del" data-del-panitia-pelatihan="<?php echo (int) $pan->id; ?>" data-master-id="<?php echo (int) $m_id; ?>" data-nama="<?php echo esc_attr( $pan->nama ); ?>" title="Hapus panitia dari pelatihan ini">&#128465;&#65039;</button>
											<?php if ( $m_id > 0 ) : ?>
												<button type="button" class="kgtk-btn kgtk-btn--ghost" data-del-master-panitia="<?php echo (int) $m_id; ?>" data-nama="<?php echo esc_attr( $pan->nama ); ?>" title="Hapus panitia ini dari Master Data" style="color:#b91c1c;background:#fef2f2;border:1px solid #fecaca;padding:3px 7px;font-size:11px;font-weight:600;border-radius:4px;cursor:pointer;white-space:nowrap;line-height:1.4;">
													&#128465; Master
												</button>
											<?php endif; ?>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<!-- SUBTAB 2: LEMBAR HADIR / PRESENSI PANITIA (A4 FORMAL) -->
	<?php
	$panitia_sheet_rows = ! empty( $panitia ) ? count( $panitia ) : KGTK_Helpers::BARIS_HALAMAN_AWAL;
	KGTK_Admin::sheet_html( $p, $dummy_k, 'Panitia', $panitia, $panitia_sheet_rows, 'panitia-lembar-absen', false, false );
	?>
</div>


<!-- Modal Tambah Panitia ke Pelatihan -->
<div class="kgtk-modal-overlay" id="kgtk-modal-tambah-panitia-pelatihan" style="display:none;">
	<div class="kgtk-modal-dialog kgtk-ui" style="max-width:680px;width:95%;">
		<div class="kgtk-modal-top" style="align-items:flex-start;">
			<div class="kgtk-modal-icon is-primary">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/>
				</svg>
			</div>
			<div class="kgtk-modal-content" style="width:100%;">
				<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
					<div>
						<h3 class="kgtk-modal-title" style="margin:0;font-size:17px;">Tambah Panitia</h3>
						<p class="kgtk-muted" style="margin:2px 0 10px;font-size:12.5px;">Pilih dari data panitia yang sudah tersimpan atau masukkan panitia baru.</p>
					</div>
					<button type="button" class="kgtk-search-clear" id="kgtk-btn-modal-pan-pel-close" style="position:static;width:28px;height:28px;font-size:20px;" title="Tutup">&times;</button>
				</div>

				<div id="kgtk-modal-pan-pel-error" class="kgtk-alert" role="alert" hidden style="margin-bottom:12px;"></div>

				<!-- Navigasi Tab Modal: Pilih Tersimpan vs Input Baru -->
				<div class="kgtk-subtabs" style="margin-bottom:12px;">
					<button type="button" class="kgtk-subtab is-active" id="kgtk-tab-pan-pick">
						&#128100; Dari Data Tersimpan
					</button>
					<button type="button" class="kgtk-subtab" id="kgtk-tab-pan-manual">
						+ Tambah Panitia Baru
					</button>
				</div>

				<!-- Bagian 1: Pilih dari master data -->
				<div id="kgtk-box-pan-pick">
					<div class="kgtk-search-wrap" style="margin-bottom:10px;">
						<span class="kgtk-search-icon">&#128269;</span>
						<input type="search" id="kgtk-modal-pan-pel-search" class="kgtk-input" placeholder="Cari nama, NIK, jabatan, atau instansi...">
					</div>

					<div style="max-height:280px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:6px;">
						<?php if ( empty( $panitia_list_master ) ) : ?>
							<div class="kgtk-muted" style="padding:24px;text-align:center;font-size:13px;">
								Belum ada master data panitia tersimpan. Silakan beralih ke tab <b>+ Tambah Panitia Baru</b> di atas.
							</div>
						<?php else : ?>
							<table class="kgtk-table" id="kgtk-modal-pan-pel-table" style="margin:0;font-size:12.5px;">
								<thead>
									<tr>
										<th style="width:36px;text-align:center;"><input type="checkbox" id="kgtk-pan-pel-checkall"></th>
										<th>Nama Panitia</th>
										<th>Jabatan</th>
										<th>Instansi</th>
										<th style="width:50px;text-align:center;">Hapus</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $panitia_list_master as $pm ) :
										$json_pm = wp_json_encode( array(
											'nama'     => $pm->nama,
											'jabatan'  => $pm->jabatan ?: 'Panitia Pelaksana',
											'nik'      => $pm->nik,
											'nip'      => $pm->nip,
											'nuptk'    => $pm->nuptk,
											'instansi' => $pm->instansi,
											'kabkota'  => $pm->kabkota,
											'hp'       => $pm->hp,
											'email'    => $pm->email,
											'bank'     => $pm->bank ?? '',
											'rekening' => $pm->rekening ?? '',
											'npwp'     => $pm->npwp,
										) );
										$s_text = strtolower( $pm->nama . ' ' . $pm->nik . ' ' . $pm->jabatan . ' ' . $pm->instansi );
									?>
										<tr data-search="<?php echo esc_attr( $s_text ); ?>" data-master-row="<?php echo (int) $pm->id; ?>">
											<td style="text-align:center;">
												<input type="checkbox" class="kgtk-pan-pel-item-check" data-json="<?php echo esc_attr( $json_pm ); ?>">
											</td>
											<td><strong><?php echo esc_html( $pm->nama ); ?></strong></td>
											<td><span class="kgtk-badge" style="background:#eff6ff;color:#1e40af;font-size:11px;"><?php echo esc_html( $pm->jabatan ?: 'Panitia Pelaksana' ); ?></span></td>
											<td><?php echo esc_html( $pm->instansi ?: '-' ); ?></td>
											<td style="text-align:center;">
												<button type="button" class="kgtk-iconbtn kgtk-iconbtn--del" data-del-master-panitia="<?php echo (int) $pm->id; ?>" data-nama="<?php echo esc_attr( $pm->nama ); ?>" title="Hapus dari Master Data Panitia">&#128465;&#65039;</button>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>
				</div>

				<!-- Bagian 2: Form manual -->
				<div id="kgtk-box-pan-manual" style="display:none;">
					<form id="kgtk-form-pan-pel-manual" novalidate style="margin-top:6px;">
						<div class="kgtk-modal-body-scroll" style="max-height:calc(100vh - 280px);max-height:calc(100dvh - 280px);overflow-y:auto;padding-right:6px;">
							<div class="kgtk-field">
								<span>Nama Lengkap Panitia <b>*</b></span>
								<input type="text" class="kgtk-input" id="kgtk-pan-m-nama" required placeholder="Contoh: Dr. H. Ahmad Fauzi, M.Pd.">
							</div>
							<div class="kgtk-row2">
								<div class="kgtk-field">
									<span>Tugas / Jabatan <b>*</b></span>
									<input type="text" class="kgtk-input" id="kgtk-pan-m-jabatan" required list="kgtk-jabatan-panitia-list-opt" value="Panitia Pelaksana">
									<datalist id="kgtk-jabatan-panitia-list-opt">
										<?php foreach ( KGTK_Helpers::JABATAN_PANITIA as $jp ) : ?>
											<option value="<?php echo esc_attr( $jp ); ?>"></option>
										<?php endforeach; ?>
									</datalist>
								</div>
								<div class="kgtk-field">
									<span>Nomor HP / WhatsApp <b>*</b></span>
									<input type="text" class="kgtk-input" id="kgtk-pan-m-hp" required placeholder="08xxxxxxxxxx">
								</div>
							</div>
							<div class="kgtk-row2">
								<div class="kgtk-field">
									<span>Asal Instansi / Unit Kerja</span>
									<input type="text" class="kgtk-input" id="kgtk-pan-m-instansi" placeholder="Contoh: BPMP Provinsi Kepulauan Riau">
								</div>
								<div class="kgtk-field">
									<span>Kabupaten / Kota</span>
									<select class="kgtk-input" id="kgtk-pan-m-kabkota">
										<option value="">-- Pilih Wilayah --</option>
										<?php foreach ( KGTK_Helpers::kabkota_list() as $kab ) : ?>
											<option value="<?php echo esc_attr( $kab ); ?>"><?php echo esc_html( $kab ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>
							<div class="kgtk-row2">
								<div class="kgtk-field">
									<span>NIK (16 Digit - opsional)</span>
									<input type="text" class="kgtk-input" id="kgtk-pan-m-nik" inputmode="numeric" maxlength="16" placeholder="16 digit angka">
								</div>
								<div class="kgtk-field">
									<span>NIP (18 Digit - opsional)</span>
									<input type="text" class="kgtk-input" id="kgtk-pan-m-nip" inputmode="numeric" maxlength="18" placeholder="18 digit angka (bila PNS)">
								</div>
							</div>
							<div class="kgtk-row2">
								<div class="kgtk-field">
									<span>Nama Bank</span>
									<input type="text" class="kgtk-input" id="kgtk-pan-m-bank" maxlength="60" placeholder="mis. Bank Riau Kepri Syariah / BRI / Mandiri">
								</div>
								<div class="kgtk-field">
									<span>Nomor Rekening Bank</span>
									<input type="text" class="kgtk-input" id="kgtk-pan-m-rekening" maxlength="50" inputmode="numeric" placeholder="Nomor rekening bank">
								</div>
							</div>
						</div>
					</form>
				</div>

				<div class="kgtk-modal-actions" style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px;">
					<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-btn-modal-pan-pel-batal">Batal</button>
					<button type="button" class="kgtk-btn kgtk-btn--navy" id="kgtk-btn-modal-pan-pel-submit">&#10003; Tambahkan Panitia</button>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Modal Edit Biodata Panitia Pelatihan -->
<div class="kgtk-modal-overlay" id="kgtk-modal-edit-panitia-pelatihan" style="display:none;">
	<div class="kgtk-modal-dialog kgtk-ui" style="max-width:640px;width:95%;">
		<div class="kgtk-modal-top" style="align-items:flex-start;">
			<div class="kgtk-modal-icon is-primary" style="flex-shrink:0;">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
				</svg>
			</div>
			<div class="kgtk-modal-content" style="width:100%;min-width:0;">
				<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:12px;">
					<div>
						<h3 class="kgtk-modal-title" style="margin:0;font-size:17px;">Edit Biodata Panitia</h3>
						<p class="kgtk-muted" style="margin:2px 0 0;font-size:12.5px;">Perbarui data identitas dan penugasan panitia untuk kegiatan ini.</p>
					</div>
					<button type="button" class="kgtk-search-clear" id="kgtk-btn-edit-pan-pel-close" style="position:static;width:28px;height:28px;font-size:20px;flex-shrink:0;" title="Tutup">&times;</button>
				</div>

				<form id="kgtk-form-edit-panitia-pelatihan" novalidate>
					<input type="hidden" id="kgtk-edit-pan-pel-id" name="peserta_id" value="0">
					<div id="kgtk-edit-pan-pel-error" class="kgtk-alert" role="alert" hidden style="margin-bottom:12px;"></div>

					<div class="kgtk-modal-body-scroll" style="max-height:calc(100vh - 250px);max-height:calc(100dvh - 250px);overflow-y:auto;padding-right:6px;">
						<!-- Pilihan Identitas NIK vs NIP -->
						<div style="background:#f8fafc;padding:12px 16px;border-radius:10px;border:1px solid #cbd5e1;margin-bottom:16px;">
							<span style="font-weight:700;font-size:13px;color:#0b2d56;display:block;margin-bottom:6px;">
								&#127380; Nomor Identitas Utama:
							</span>
							<div style="display:flex;gap:20px;align-items:center;flex-wrap:wrap;">
								<label style="display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:#1e293b;cursor:pointer;">
									<input type="radio" name="edit_panitia_id_type" value="nik" id="kgtk-edit-pan-radio-nik" checked>
									<span>Gunakan NIK (16 Digit)</span>
								</label>
								<label style="display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:#1e293b;cursor:pointer;">
									<input type="radio" name="edit_panitia_id_type" value="nip" id="kgtk-edit-pan-radio-nip">
									<span>Gunakan NIP (18 Digit)</span>
								</label>
							</div>
						</div>

						<!-- Input NIK -->
						<div id="kgtk-edit-pan-wrap-nik">
							<label class="kgtk-field">
								<span>NIK (16 Digit) *</span>
								<input type="text" class="kgtk-input" id="kgtk-edit-pan-nik" name="nik" inputmode="numeric" maxlength="16" placeholder="16 digit angka NIK">
							</label>
						</div>

						<!-- Input NIP Top (saat radio NIP aktif) -->
						<div id="kgtk-edit-pan-wrap-nip-top" style="display:none;">
							<label class="kgtk-field">
								<span>NIP (18 Digit) *</span>
								<input type="text" class="kgtk-input" id="kgtk-edit-pan-nip-top" inputmode="numeric" maxlength="18" placeholder="18 digit angka NIP">
							</label>
						</div>

						<!-- Nama Lengkap -->
						<label class="kgtk-field">
							<span>Nama Lengkap * <small style="color:#dc2626;font-size:12px;font-weight:normal;">(Tanpa gelar)</small></span>
							<input type="text" class="kgtk-input" id="kgtk-edit-pan-nama" name="nama" maxlength="100" required placeholder="Nama lengkap panitia">
						</label>

						<!-- Gelar Depan & Belakang -->
						<div class="kgtk-row2">
							<label class="kgtk-field">
								<span>Gelar Depan</span>
								<input type="text" class="kgtk-input" id="kgtk-edit-pan-gelar-depan" name="gelar_depan" maxlength="30" placeholder="mis. Dr. / Drs.">
							</label>
							<label class="kgtk-field">
								<span>Gelar Belakang</span>
								<input type="text" class="kgtk-input" id="kgtk-edit-pan-gelar-belakang" name="gelar_belakang" maxlength="50" placeholder="mis. S.Pd. / M.Pd.">
							</label>
						</div>

						<!-- NIP Bottom (saat radio NIK aktif) -->
						<div id="kgtk-edit-pan-wrap-nip-bottom">
							<label class="kgtk-field">
								<span>NIP (18 Digit)</span>
								<input type="text" class="kgtk-input" id="kgtk-edit-pan-nip" name="nip" inputmode="numeric" maxlength="18" placeholder="18 digit angka NIP (bila PNS)">
							</label>
						</div>

						<!-- Tugas / Jabatan -->
						<label class="kgtk-field">
							<span>Tugas / Jabatan Panitia *</span>
							<input type="text" class="kgtk-input" id="kgtk-edit-pan-jabatan" name="jabatan" list="kgtk-edit-pan-jabatan-list" required placeholder="Ketua Panitia / Sekretaris / Panitia Pelaksana">
							<datalist id="kgtk-edit-pan-jabatan-list">
								<?php foreach ( KGTK_Helpers::JABATAN_PANITIA as $jp ) : ?>
									<option value="<?php echo esc_attr( $jp ); ?>"></option>
								<?php endforeach; ?>
							</datalist>
						</label>

						<!-- Asal Instansi & Kab/Kota -->
						<div class="kgtk-row2">
							<label class="kgtk-field">
								<span>Asal Instansi / Unit Kerja *</span>
								<input type="text" class="kgtk-input" id="kgtk-edit-pan-instansi" name="instansi" maxlength="200" placeholder="Contoh: BPMP Provinsi Kepulauan Riau">
							</label>
							<label class="kgtk-field">
								<span>Kabupaten / Kota</span>
								<select class="kgtk-input" id="kgtk-edit-pan-kabkota" name="kabkota">
									<option value="">-- Pilih Wilayah --</option>
									<?php foreach ( KGTK_Helpers::kabkota_list() as $kab ) : ?>
										<option value="<?php echo esc_attr( $kab ); ?>"><?php echo esc_html( $kab ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
						</div>

						<!-- Kontak HP & Email -->
						<div class="kgtk-row2">
							<label class="kgtk-field">
								<span>Nomor HP / WhatsApp *</span>
								<input type="text" class="kgtk-input" id="kgtk-edit-pan-hp" name="hp" required placeholder="08xxxxxxxxxx">
							</label>
							<label class="kgtk-field">
								<span>Alamat Email</span>
								<input type="email" class="kgtk-input" id="kgtk-edit-pan-email" name="email" placeholder="nama@email.com">
							</label>
						</div>

						<!-- Bank & Rekening -->
						<div class="kgtk-row2">
							<label class="kgtk-field">
								<span>Nama Bank</span>
								<input type="text" class="kgtk-input" id="kgtk-edit-pan-bank" name="bank" maxlength="60" placeholder="mis. Bank Riau Kepri Syariah / BRI">
							</label>
							<label class="kgtk-field">
								<span>Nomor Rekening Bank</span>
								<input type="text" class="kgtk-input" id="kgtk-edit-pan-rekening" name="rekening" maxlength="50" inputmode="numeric" placeholder="Nomor rekening">
							</label>
						</div>
					</div>

					<div class="kgtk-modal-actions" style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px;padding-top:14px;border-top:1px solid var(--line);">
						<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-btn-edit-pan-pel-batal">Batal</button>
						<button type="submit" class="kgtk-btn kgtk-btn--navy" id="kgtk-btn-edit-pan-pel-save">Simpan Perubahan</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
