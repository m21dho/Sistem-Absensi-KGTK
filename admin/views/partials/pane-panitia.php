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
$panitia_list_master = KGTK_Panitia::get_all();
$panitia_count       = ! empty( $panitia ) ? count( $panitia ) : 0;
?>
<div class="kgtk-pane" data-pane="panitia">
	<div class="kgtk-card kgtk-kelola-box" id="kgtk-panitia-container" data-pelatihan="<?php echo (int) $p->id; ?>">
		<div class="kgtk-kelola-head">
			<div>
				<h2 class="kgtk-h2">Panitia Pelatihan</h2>
				<p class="kgtk-muted">Daftar panitia pelaksana kegiatan pelatihan.</p>
			</div>
			<div class="kgtk-head-actions">
				<button type="button" class="kgtk-btn kgtk-btn--primary" id="kgtk-btn-tambah-panitia-pelatihan">&#128100; + Tambah Panitia</button>
			</div>
		</div>

		<!-- List Panitia Pelatihan -->
		<?php if ( empty( $panitia ) ) : ?>
			<div class="kgtk-empty-peserta" style="text-align:center;padding:36px 16px;">
				<div style="font-size:36px;margin-bottom:8px;">&#128100;</div>
				<h3 style="margin:0 0 6px;color:#0b2d56;font-size:16px;">Belum Ada Panitia Ditambahkan</h3>
				<p class="kgtk-muted" style="margin:0 0 16px;">Tambahkan panitia pelaksana untuk kegiatan pelatihan ini.</p>
				<button type="button" class="kgtk-btn kgtk-btn--primary" onclick="var b = document.getElementById('kgtk-btn-tambah-panitia-pelatihan'); if(b) b.click();">&#128100; + Tambah Panitia Sekarang</button>
			</div>
		<?php else : ?>
			<div class="kgtk-kelola-tools" style="margin-bottom:12px;">
				<div class="kgtk-search-wrap" style="max-width:360px;">
					<span class="kgtk-search-icon">&#128269;</span>
					<input type="search" id="kgtk-panitia-pel-search" class="kgtk-input" placeholder="Cari nama panitia, NIK, jabatan, atau instansi...">
				</div>
				<div class="kgtk-muted" style="font-size:13px;">
					Total: <strong><?php echo (int) $panitia_count; ?> panitia</strong> terdaftar pada kegiatan ini.
				</div>
			</div>

			<div class="kgtk-scroll">
				<table class="kgtk-table kgtk-table--kelola" id="kgtk-tbl-panitia-pelatihan">
					<thead>
						<tr>
							<th style="width:46px;text-align:center;">No</th>
							<th style="min-width:210px;">Nama Panitia</th>
							<th style="min-width:160px;">Tugas / Jabatan</th>
							<th style="min-width:180px;">Asal Instansi</th>
							<th style="width:160px;">NIK / NIP</th>
							<th style="width:65px;text-align:center;">Aksi</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$no = 1;
						foreach ( $panitia as $pan ) :
							$search_text = strtolower( $pan->nama . ' ' . $pan->nik . ' ' . ( $pan->nip ?? '' ) . ' ' . ( $pan->jabatan ?? '' ) . ' ' . $pan->sekolah . ' ' . ( $pan->hp ?? '' ) );
							$wa_num      = preg_replace( '/^0/', '62', preg_replace( '/\D/', '', (string) ( $pan->hp ?? '' ) ) );
						?>
							<tr data-id="<?php echo (int) $pan->id; ?>" data-search="<?php echo esc_attr( $search_text ); ?>">
								<td class="c-no"><span class="kgtk-row-num"><?php echo (int) $no++; ?>.</span></td>
								<td>
									<div class="kgtk-guru-cell">
										<strong class="kgtk-guru-nama" style="font-size:13.5px;color:#0b2d56;"><?php echo esc_html( $pan->nama ); ?></strong>
										<?php if ( ! empty( $pan->hp ) && '-' !== $pan->hp ) : ?>
											<div class="kgtk-guru-kontak">
												<a href="https://wa.me/<?php echo esc_attr( $wa_num ); ?>" target="_blank" rel="noopener noreferrer" class="kgtk-wa-link" title="Kirim pesan WhatsApp">
													<span class="kgtk-wa-icon">&#128172;</span> <?php echo esc_html( $pan->hp ); ?>
												</a>
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
								<td style="text-align:center;">
									<button type="button" class="kgtk-iconbtn kgtk-iconbtn--del" data-del-panitia-pelatihan="<?php echo (int) $pan->id; ?>" data-nama="<?php echo esc_attr( $pan->nama ); ?>" title="Hapus panitia dari pelatihan ini">&#128465;&#65039;</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>
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
						<h3 class="kgtk-modal-title" style="margin:0;font-size:17px;">Tambah Panitia ke Pelatihan</h3>
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
											'npwp'     => $pm->npwp,
										) );
										$s_text = strtolower( $pm->nama . ' ' . $pm->nik . ' ' . $pm->jabatan . ' ' . $pm->instansi );
									?>
										<tr data-search="<?php echo esc_attr( $s_text ); ?>">
											<td style="text-align:center;">
												<input type="checkbox" class="kgtk-pan-pel-item-check" data-json="<?php echo esc_attr( $json_pm ); ?>">
											</td>
											<td><strong><?php echo esc_html( $pm->nama ); ?></strong></td>
											<td><span class="kgtk-badge" style="background:#eff6ff;color:#1e40af;font-size:11px;"><?php echo esc_html( $pm->jabatan ?: 'Panitia Pelaksana' ); ?></span></td>
											<td><?php echo esc_html( $pm->instansi ?: '-' ); ?></td>
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
