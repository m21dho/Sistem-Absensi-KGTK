<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * View: Manajemen Data Master Panitia Pelatihan
 *
 * @var array $panitia_list Daftar objek panitia dari database.
 */

$total_panitia = count( $panitia_list );
$with_nik      = 0;
$total_tugas   = 0;
foreach ( $panitia_list as $p_item ) {
	if ( ! empty( $p_item->nik ) && 0 !== strpos( $p_item->nik, '99' ) ) {
		$with_nik++;
	}
	$total_tugas += (int) ( $p_item->total_pelatihan ?? 0 );
}

$act = '<button type="button" class="kgtk-btn kgtk-btn--primary" id="kgtk-btn-tambah-panitia-master">+ Tambah Panitia</button>';
?>
<div class="wrap kgtk-wrap kgtk-ui">
	<?php
	KGTK_Admin::notice_libs();
	KGTK_Admin::head( 'Data Panitia Pelatihan', 'Kelola daftar panitia pelaksana kegiatan pelatihan yang tersimpan dalam sistem.', $act );
	?>

	<!-- KPI Summary Banner -->
	<section class="kgtk-card kgtk-summary" style="margin-bottom:20px;">
		<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px;">
			<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px 18px;">
				<div class="kgtk-muted" style="font-size:12.5px;font-weight:600;text-transform:uppercase;">Total Panitia Tersimpan</div>
				<div style="font-size:26px;font-weight:800;color:#0b2d56;margin-top:4px;"><?php echo (int) $total_panitia; ?> <span style="font-size:14px;font-weight:600;color:#64748b;">orang</span></div>
			</div>
			<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px 18px;">
				<div class="kgtk-muted" style="font-size:12.5px;font-weight:600;text-transform:uppercase;">NIK Resmi Terdaftar</div>
				<div style="font-size:26px;font-weight:800;color:#0b2d56;margin-top:4px;"><?php echo (int) $with_nik; ?> <span style="font-size:14px;font-weight:600;color:#64748b;">orang</span></div>
			</div>
			<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px 18px;">
				<div class="kgtk-muted" style="font-size:12.5px;font-weight:600;text-transform:uppercase;">Total Penugasan Pelatihan</div>
				<div style="font-size:26px;font-weight:800;color:#0b2d56;margin-top:4px;"><?php echo (int) $total_tugas; ?> <span style="font-size:14px;font-weight:600;color:#64748b;">kali bertugas</span></div>
			</div>
		</div>
	</section>

	<!-- Main Card Data Panitia -->
	<div class="kgtk-card">
		<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
			<div>
				<h2 class="kgtk-h2" style="margin:0 0 4px;">Daftar Panitia Tersimpan</h2>
				<p class="kgtk-muted" style="margin:0;font-size:13px;">Data panitia ini dapat langsung dipilih saat membuat pelatihan baru tanpa perlu mengetik ulang.</p>
			</div>
			<div style="min-width:260px;flex:1;max-width:380px;">
				<input type="text" class="kgtk-input" id="kgtk-search-panitia-master" placeholder="Cari nama, NIK, jabatan, atau instansi..." style="height:38px;font-size:13px;">
			</div>
		</div>

		<?php if ( empty( $panitia_list ) ) : ?>
			<div id="kgtk-empty-panitia-master" style="text-align:center;padding:48px 16px;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:8px;">
				<div style="font-size:36px;margin-bottom:8px;">&#128100;</div>
				<h3 style="margin:0 0 6px;color:#0b2d56;">Belum Ada Data Panitia</h3>
				<p class="kgtk-muted" style="margin:0 0 16px;font-size:13.5px;">Klik tombol di bawah untuk menambahkan data panitia pertama kali.</p>
				<button type="button" class="kgtk-btn kgtk-btn--primary" id="kgtk-btn-empty-tambah-panitia">+ Tambah Panitia Baru</button>
			</div>
		<?php else : ?>
			<div class="kgtk-scroll">
				<table class="kgtk-table kgtk-table--kelola" id="kgtk-tbl-panitia-master">
					<thead>
						<tr>
							<th style="width:46px;text-align:center;">No</th>
							<th style="min-width:200px;">Nama Lengkap & NIK</th>
							<th style="min-width:160px;">Tugas / Jabatan Utama</th>
							<th style="min-width:180px;">Asal Instansi / Unit Kerja</th>
							<th style="width:140px;">Kabupaten / Kota</th>
							<th style="min-width:160px;">Kontak</th>
							<th style="width:110px;text-align:center;">Penugasan</th>
							<th style="width:85px;text-align:center;">Aksi</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$no = 1;
						foreach ( $panitia_list as $pan ) :
							$search_text = strtolower( $pan->nama . ' ' . $pan->nik . ' ' . $pan->nip . ' ' . $pan->jabatan . ' ' . $pan->instansi . ' ' . $pan->kabkota . ' ' . $pan->hp . ' ' . $pan->email );
							$wa_num      = preg_replace( '/^0/', '62', preg_replace( '/\D/', '', $pan->hp ) );
							$pan_json    = wp_json_encode( array(
								'id'       => (int) $pan->id,
								'nama'     => $pan->nama,
								'jabatan'  => $pan->jabatan ?: 'Panitia Pelaksana',
								'nik'      => $pan->nik,
								'nip'      => $pan->nip,
								'nuptk'    => $pan->nuptk,
								'instansi' => $pan->instansi,
								'kabkota'  => $pan->kabkota,
								'hp'       => $pan->hp,
								'email'    => $pan->email,
								'npwp'     => $pan->npwp,
								'catatan'  => $pan->catatan,
							) );
						?>
							<tr data-id="<?php echo (int) $pan->id; ?>" data-search="<?php echo esc_attr( $search_text ); ?>" data-json="<?php echo esc_attr( $pan_json ); ?>">
								<td class="c-no"><span class="kgtk-row-num"><?php echo (int) $no++; ?>.</span></td>
								<td>
									<div class="kgtk-guru-cell">
										<strong class="kgtk-guru-nama" style="font-size:13.5px;color:#0b2d56;"><?php echo esc_html( $pan->nama ); ?></strong>
										<?php if ( ! empty( $pan->nik ) && 0 !== strpos( $pan->nik, '99' ) ) : ?>
											<div class="kgtk-nik-cell" style="margin-top:2px;">
												<code class="kgtk-nik-code" style="font-size:11.5px;"><?php echo esc_html( $pan->nik ); ?></code>
											</div>
										<?php endif; ?>
										<?php if ( ! empty( $pan->nip ) ) : ?>
											<div class="kgtk-muted" style="font-size:11px;">NIP: <?php echo esc_html( $pan->nip ); ?></div>
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
										<span class="kgtk-sekolah-nama" style="font-size:13px;"><?php echo esc_html( $pan->instansi ?: '-' ); ?></span>
									</div>
								</td>
								<td>
									<?php if ( ! empty( $pan->kabkota ) && 'Lainnya' !== $pan->kabkota ) : ?>
										<div class="kgtk-kab-tag">&#128205; <?php echo esc_html( $pan->kabkota ); ?></div>
									<?php else : ?>
										<span class="kgtk-muted">-</span>
									<?php endif; ?>
								</td>
								<td>
									<div style="font-size:12.5px;display:flex;flex-direction:column;gap:3px;">
										<?php if ( ! empty( $pan->hp ) ) : ?>
											<a href="https://wa.me/<?php echo esc_attr( $wa_num ); ?>" target="_blank" rel="noopener noreferrer" class="kgtk-wa-link" title="Chat WhatsApp">
												<span class="kgtk-wa-icon">&#128172;</span> <?php echo esc_html( $pan->hp ); ?>
											</a>
										<?php endif; ?>
										<?php if ( ! empty( $pan->email ) ) : ?>
											<a href="mailto:<?php echo esc_attr( $pan->email ); ?>" style="color:#2563eb;text-decoration:none;font-size:11.5px;">
												&#9993; <?php echo esc_html( $pan->email ); ?>
											</a>
										<?php endif; ?>
									</div>
								</td>
								<td style="text-align:center;">
									<span class="kgtk-badge is-active" title="Jumlah pelatihan yang pernah diikuti sebagai panitia">
										<?php echo (int) ( $pan->total_pelatihan ?? 0 ); ?> Pelatihan
									</span>
								</td>
								<td style="text-align:center;">
									<div class="kgtk-row-actions">
										<button type="button" class="kgtk-iconbtn kgtk-iconbtn--view" data-edit-panitia="<?php echo (int) $pan->id; ?>" title="Edit Data Panitia">&#9998;&#65039;</button>
										<button type="button" class="kgtk-iconbtn kgtk-iconbtn--del" data-del-panitia="<?php echo (int) $pan->id; ?>" data-nama="<?php echo esc_attr( $pan->nama ); ?>" title="Hapus Panitia">&#128465;&#65039;</button>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>

	<!-- Modal Tambah / Edit Panitia Master -->
	<div class="kgtk-modal-overlay" id="kgtk-modal-form-panitia" style="display:none;">
		<div class="kgtk-modal-dialog kgtk-ui" style="max-width:640px;width:95%;">
			<div class="kgtk-modal-top" style="align-items:flex-start;">
				<div class="kgtk-modal-icon is-primary" style="flex-shrink:0;">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
					</svg>
				</div>
				<div class="kgtk-modal-content" style="width:100%;min-width:0;">
					<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:12px;">
						<div>
							<h3 class="kgtk-modal-title" id="kgtk-modal-panitia-title" style="margin:0;font-size:17px;">Tambah Data Panitia</h3>
							<p class="kgtk-muted" style="margin:2px 0 0;font-size:12.5px;">Isi profil panitia untuk disimpan dalam database master panitia.</p>
						</div>
						<button type="button" class="kgtk-search-clear" id="kgtk-btn-form-panitia-close" style="position:static;width:28px;height:28px;font-size:20px;flex-shrink:0;" title="Tutup">&times;</button>
					</div>

					<form id="kgtk-form-panitia-master" novalidate>
						<input type="hidden" id="kgtk-pan-field-id" name="id" value="0">
						<div id="kgtk-panitia-form-error" class="kgtk-alert" role="alert" hidden style="margin-bottom:12px;"></div>

						<div class="kgtk-modal-body-scroll" style="max-height:calc(100vh - 250px);max-height:calc(100dvh - 250px);overflow-y:auto;padding-right:6px;">
							<label class="kgtk-field">
								<span>Nama Lengkap Beserta Gelar <b>*</b></span>
								<input type="text" class="kgtk-input" id="kgtk-pan-field-nama" name="nama" maxlength="100" required placeholder="Contoh: Dr. Ahmad Fauzi, S.Kom., M.M.">
							</label>

							<div class="kgtk-row2">
								<label class="kgtk-field">
									<span>Tugas / Jabatan Panitia <b>*</b></span>
									<input type="text" class="kgtk-input" id="kgtk-pan-field-jabatan" name="jabatan" list="kgtk-jabatan-panitia-options-master" required placeholder="Ketua Panitia / Sekretaris / dll." value="Panitia Pelaksana">
									<datalist id="kgtk-jabatan-panitia-options-master">
										<?php foreach ( KGTK_Helpers::JABATAN_PANITIA as $jp ) : ?>
											<option value="<?php echo esc_attr( $jp ); ?>"></option>
										<?php endforeach; ?>
									</datalist>
								</label>

								<label class="kgtk-field">
									<span>NIK (16 Digit)</span>
									<input type="text" class="kgtk-input" id="kgtk-pan-field-nik" name="nik" inputmode="numeric" maxlength="16" placeholder="16 digit angka">
								</label>
							</div>

							<div class="kgtk-row2">
								<label class="kgtk-field">
									<span>NIP (18 Digit)</span>
									<input type="text" class="kgtk-input" id="kgtk-pan-field-nip" name="nip" inputmode="numeric" maxlength="18" placeholder="18 digit angka (opsional)">
								</label>

								<label class="kgtk-field">
									<span>NUPTK (16 Digit)</span>
									<input type="text" class="kgtk-input" id="kgtk-pan-field-nuptk" name="nuptk" inputmode="numeric" maxlength="16" placeholder="16 digit angka (opsional)">
								</label>
							</div>

							<div class="kgtk-row2">
								<label class="kgtk-field">
									<span>Asal Instansi / Unit Kerja <b>*</b></span>
									<input type="text" class="kgtk-input" id="kgtk-pan-field-instansi" name="instansi" maxlength="200" placeholder="Contoh: Balai Guru Penggerak Provinsi Kepulauan Riau">
								</label>

								<label class="kgtk-field">
									<span>Kabupaten / Kota</span>
									<select class="kgtk-input" id="kgtk-pan-field-kabkota" name="kabkota">
										<option value="">-- Pilih Wilayah --</option>
										<?php foreach ( KGTK_Helpers::kabkota_list() as $kab ) : ?>
											<option value="<?php echo esc_attr( $kab ); ?>"><?php echo esc_html( $kab ); ?></option>
										<?php endforeach; ?>
									</select>
								</label>
							</div>

							<div class="kgtk-row2">
								<label class="kgtk-field">
									<span>Nomor HP / WhatsApp <b>*</b></span>
									<input type="text" class="kgtk-input" id="kgtk-pan-field-hp" name="hp" required placeholder="08xxxxxxxxxx">
								</label>

								<label class="kgtk-field">
									<span>Alamat Email</span>
									<input type="email" class="kgtk-input" id="kgtk-pan-field-email" name="email" placeholder="nama@email.com">
								</label>
							</div>

							<label class="kgtk-field">
								<span>NPWP (opsional)</span>
								<input type="text" class="kgtk-input" id="kgtk-pan-field-npwp" name="npwp" placeholder="Nomor Pokok Wajib Pajak">
							</label>
						</div>

						<div class="kgtk-modal-actions" style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px;padding-top:14px;border-top:1px solid var(--line);">
							<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-btn-form-panitia-batal">Batal</button>
							<button type="submit" class="kgtk-btn kgtk-btn--navy" id="kgtk-btn-form-panitia-save">Simpan Data Panitia</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
