<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * View: Kelola Pelatihan, Kelas Peserta, dan Lembar Hadir (Absen)
 *
 * @var object $p               Data pelatihan.
 * @var array  $kelas           Statistik dan data kelas pelatihan.
 * @var array  $tot             Total statistik kapasitas & slot.
 * @var array  $all_peserta     Daftar seluruh peserta terdaftar.
 * @var int    $unassigned      Jumlah peserta belum masuk kelas.
 * @var int    $total_pendaftar Jumlah total peserta terdaftar.
 * @var string $link            URL share pendaftaran.
 * @var string $tgl             Label rentang tanggal pelaksanaan.
 * @var string $lok             Lokasi kegiatan.
 * @var array  $und_stats       Statistik peserta ditetapkan.
 * @var array  $kelas_map       Peta id kelas ke label.
 * @var array  $linked_map      Peta peserta terhubung.
 * @var bool   $multi           Apakah multi kelas.
 */

$pel_json = wp_json_encode( array(
	'id'               => (int) $p->id,
	'nama'             => $p->nama,
	'penyelenggara'    => $p->penyelenggara,
	'tanggal_mulai'    => $p->tanggal_mulai,
	'tanggal_selesai'  => $p->tanggal_selesai,
	'lokasi'           => $p->lokasi,
	'kabkota'          => isset( $p->kabkota ) ? $p->kabkota : '',
	'penanggung_jawab' => $p->penanggung_jawab,
	'tipe_pj'          => isset( $p->tipe_pj ) ? $p->tipe_pj : 'Penanggung Jawab',
	'keterangan'       => $p->keterangan,
	'kode'             => $p->kode,
) );

?>
<div class="wrap kgtk-wrap kgtk-ui">
	<?php KGTK_Admin::notice_libs(); ?>

	<!-- Header Detail Pelatihan -->
	<hr class="wp-header-end">
	<div class="kgtk-head kgtk-head--detail">
		<div class="kgtk-head-top">
			<div class="kgtk-head-info">
				<a class="kgtk-head-back" href="<?php echo esc_url( KGTK_Admin::page_url( 'pelatihan' ) ); ?>">&larr; Kembali ke Daftar Pelatihan</a>
				<h1 class="kgtk-head-title"><?php echo esc_html( $p->nama ); ?></h1>
				<div class="kgtk-head-sub">
					<span>Kelola kelas peserta &amp; daftar hadir</span>
					<span class="kgtk-head-divider">&middot;</span>
					<code class="kgtk-head-code">&#127991; <?php echo esc_html( $p->kode ); ?></code>
				</div>
			</div>
			<div class="kgtk-head-primary-actions">
				<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-btn-edit-pelatihan-detail" data-pelatihan="<?php echo esc_attr( $pel_json ); ?>" title="Edit informasi dan jadwal pelatihan">&#9998; Edit Pelatihan</button>
				<a class="kgtk-btn kgtk-btn--yellow" href="<?php echo esc_url( KGTK_Admin::page_url( 'pelatihan', array( 'view' => 'tambah', 'id' => (int) $p->id ) ) ); ?>">+ Tambah Peserta</a>
			</div>
		</div>

		<div class="kgtk-head-toolbar">
			<div class="kgtk-head-toolbar-label">&#128196; Cetak &amp; Export Dokumen:</div>
			<div class="kgtk-head-toolbar-group">
				<a class="kgtk-btn kgtk-btn--navy-glass" href="<?php echo esc_url( KGTK_Export::semua_biodata_pdf_url( $p->id ) ); ?>" target="_blank" title="Download dan cetak seluruh lembar biodata peserta (1 halaman per orang)">&#128100; Download Semua Biodata</a>
				<button type="button" class="kgtk-btn kgtk-btn--navy-glass" id="kgtk-btn-export-pdf" title="Pilih kategori export PDF">&#128196; Export PDF &#9662;</button>
				<a class="kgtk-btn kgtk-btn--navy-glass" href="<?php echo esc_url( KGTK_Export::url( $p->id, 'xlsx' ) ); ?>" title="Download rekap kehadiran format Excel (XLSX)">&#128202; Export Excel</a>
			</div>
		</div>
	</div>

	<!-- Notifikasi bila ada pendaftar baru yang belum ditentukan kelasnya -->
	<?php if ( $unassigned > 0 ) : ?>
		<div class="kgtk-unassigned-banner">
			<div class="kgtk-unassigned-icon">&#9888;&#65039;</div>
			<div class="kgtk-unassigned-text">
				<strong>Ada <?php echo (int) $unassigned; ?> guru yang baru mendaftar dan belum ditentukan kelasnya!</strong>
				<p>Guru mendaftar pelatihan tanpa memilih kelas. Silakan pilihkan kelas untuk mereka pada tab <b>Atur Kelas Peserta</b> di bawah ini.</p>
			</div>
		</div>
	<?php endif; ?>

	<!-- Ringkasan Info Pelatihan -->
	<section class="kgtk-card kgtk-summary">
		<dl class="kgtk-dl">
			<?php
			$pj_label = ( isset( $p->tipe_pj ) && 'Ketua' === $p->tipe_pj ) ? 'Ketua' : 'Penanggung jawab';
			$items = array(
				'Penyelenggara'  => '' !== $p->penyelenggara ? $p->penyelenggara : '-',
				'Tanggal'        => $tgl,
				'Lokasi'         => $lok,
				$pj_label        => '' !== trim( (string) $p->penanggung_jawab ) ? $p->penanggung_jawab : '-',
				'Kode Pelatihan' => $p->kode,
			);
			foreach ( $items as $k => $v ) {
				echo '<div><dt>' . esc_html( $k ) . '</dt><dd>' . esc_html( $v ) . '</dd></div>';
			}
			?>
		</dl>

		<?php if ( '' !== (string) $p->keterangan ) : ?>
			<p class="kgtk-ket"><?php echo nl2br( esc_html( $p->keterangan ) ); ?></p>
		<?php endif; ?>

		<div class="kgtk-plabel">
			<span>Total pendaftar: <strong><?php echo (int) $total_pendaftar; ?> orang</strong> (Di kelas: <?php echo (int) $tot['terisi']; ?> &middot; Belum ada kelas: <?php echo (int) $unassigned; ?>)</span>
			<strong>Kapasitas slot: <?php echo (int) $tot['terisi'] . '/' . (int) $tot['slot']; ?> &middot; sisa <?php echo (int) $tot['sisa']; ?> slot</strong>
		</div>

		<?php
		echo KGTK_Admin::progress( $tot['persen'], $tot['penuh'] );
		echo KGTK_Admin::chips( $kelas );
		?>

		<?php if ( $tot['lain'] > 0 ) : ?>
			<p class="kgtk-muted kgtk-lain">Peran lain (panitia, fasilitator, pengajar, narasumber, penceramah): <?php echo (int) $tot['lain']; ?> orang. Tidak memakai slot.</p>
		<?php endif; ?>

		<div class="kgtk-share">
			<input type="text" class="kgtk-input" readonly value="<?php echo esc_attr( $link ); ?>" aria-label="Link pendaftaran">
			<button type="button" class="kgtk-btn kgtk-btn--ghost" data-copy="<?php echo esc_attr( $link ); ?>">Salin link</button>
		</div>
	</section>

	<!-- Tabs Navigasi -->
	<div class="kgtk-tabs" role="tablist">
		<button type="button" role="tab" class="kgtk-tab is-active" data-tab="kelola-peserta">
			&#128101; Atur Kelas Peserta <small class="kgtk-tab-badge<?php echo $unassigned > 0 ? ' is-alert' : ''; ?>"><?php echo $unassigned > 0 ? (int) $unassigned . ' belum ada kelas' : (int) $total_pendaftar . ' guru'; ?></small>
		</button>
		<button type="button" role="tab" class="kgtk-tab" data-tab="peserta-ditetapkan">
			&#128221; Peserta Ditetapkan <small class="kgtk-tab-badge" id="kgtk-tab-badge-undangan"><?php echo (int) $und_stats['sudah'] . '/' . (int) $und_stats['total']; ?></small>
		</button>
		<button type="button" role="tab" class="kgtk-tab" data-tab="panitia">
			&#128100; Panitia Pelatihan <small class="kgtk-tab-badge" id="kgtk-tab-badge-panitia"><?php echo ! empty( $panitia ) ? count( $panitia ) : 0; ?></small>
		</button>
		<button type="button" role="tab" class="kgtk-tab" data-tab="tanda-terima">
			&#128230; Tanda Terima
		</button>
		<?php foreach ( $kelas as $k ) : ?>
			<button type="button" role="tab" class="kgtk-tab" data-tab="<?php echo (int) $k->id; ?>">
				<?php echo esc_html( $k->label ); ?> <small><?php echo (int) $k->terisi . '/' . (int) $k->slot; ?></small>
			</button>
		<?php endforeach; ?>
	</div>

	<div class="kgtk-sheets">

		<!-- PANE 1: Kelola Penempatan Kelas Peserta -->
		<div class="kgtk-pane is-active" data-pane="kelola-peserta">
			<div class="kgtk-card kgtk-kelola-box">
				<div class="kgtk-kelola-head">
					<div>
						<h2 class="kgtk-h2">Penempatan Kelas Peserta</h2>
						<p class="kgtk-muted">Pilihkan kelas yang akan diikuti oleh masing-masing guru. Kelas otomatis tersimpan saat Anda memilih.</p>
					</div>
					<div class="kgtk-kelola-head-badge">
						<span class="kgtk-badge <?php echo $unassigned > 0 ? 'is-alert' : 'is-open'; ?>">
							<?php echo $unassigned > 0 ? (int) $unassigned . ' Belum Masuk Kelas' : 'Semua Terbagi Kelas'; ?>
						</span>
					</div>
				</div>

				<!-- Kartu Statistik Cepat Filter -->
				<div class="kgtk-stat-cards">
					<div class="kgtk-scard is-active" data-filter="all" role="button" tabindex="0" title="Klik untuk filter: Semua pendaftar">
						<div class="kgtk-scard-num"><?php echo (int) $total_pendaftar; ?></div>
						<div class="kgtk-scard-label">&#128101; Total Pendaftar</div>
					</div>

					<div class="kgtk-scard<?php echo $unassigned > 0 ? ' is-warn' : ''; ?>" data-filter="0" role="button" tabindex="0" title="Klik untuk filter: Belum ada kelas">
						<div class="kgtk-scard-num"><?php echo (int) $unassigned; ?></div>
						<div class="kgtk-scard-label">&#9888;&#65039; Belum Ada Kelas</div>
						<?php if ( $unassigned > 0 ) : ?>
							<span class="kgtk-scard-pill">Perlu Penempatan</span>
						<?php endif; ?>
					</div>

					<?php foreach ( $kelas as $k ) :
						$terisi_count = count( $k->peserta );
						$pct          = min( 100, round( $terisi_count / max( 1, (int) $k->slot ) * 100 ) );
					?>
						<div class="kgtk-scard" data-filter="<?php echo (int) $k->id; ?>" role="button" tabindex="0" title="Klik untuk filter: <?php echo esc_attr( $k->label ); ?>">
							<div class="kgtk-scard-num"><?php echo (int) $terisi_count; ?><small>/<?php echo (int) $k->slot; ?></small></div>
							<div class="kgtk-scard-label">&#127979; <?php echo esc_html( $k->label ); ?></div>
							<div class="kgtk-scard-bar"><span style="width:<?php echo (int) $pct; ?>%"></span></div>
						</div>
					<?php endforeach; ?>
				</div>

				<!-- Toolbar Pencarian & Filter -->
				<div class="kgtk-kelola-tools">
					<div class="kgtk-search-wrap">
						<span class="kgtk-search-icon">&#128269;</span>
						<input type="search" id="kgtk-peserta-search" class="kgtk-input" placeholder="Cari nama guru, NIK, asal sekolah/instansi, atau nomor HP...">
						<button type="button" id="kgtk-search-clear" class="kgtk-search-clear" title="Bersihkan pencarian" style="display:none;">&times;</button>
					</div>
					<div class="kgtk-fbtns" role="group" aria-label="Filter kelas">
						<button type="button" class="kgtk-fbtn is-active" data-filter="all">Semua <span class="kgtk-fcount"><?php echo (int) $total_pendaftar; ?></span></button>
						<button type="button" class="kgtk-fbtn<?php echo $unassigned > 0 ? ' is-warn' : ''; ?>" data-filter="0">Belum Ada Kelas <span class="kgtk-fcount"><?php echo (int) $unassigned; ?></span></button>
						<?php foreach ( $kelas as $k ) :
							$terisi_count = count( $k->peserta );
						?>
							<button type="button" class="kgtk-fbtn" data-filter="<?php echo (int) $k->id; ?>"><?php echo esc_html( $k->label ); ?> <span class="kgtk-fcount"><?php echo (int) $terisi_count; ?></span></button>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Toolbar Aksi Masal -->
				<div class="kgtk-bulk-toolbar">
					<div class="kgtk-bulk-left">
						<label class="kgtk-bulk-checkall"><input type="checkbox" id="kgtk-check-all"> <span>Pilih Semua</span></label>
						<span id="kgtk-selected-badge" class="kgtk-selected-badge" style="display:none;">0 dipilih</span>
					</div>
					<div class="kgtk-bulk-actions">
						<span class="kgtk-bulk-label">Pindahkan terpilih ke:</span>
						<select id="kgtk-bulk-target" class="kgtk-input kgtk-bulk-select">
							<option value="">-- Pilih Kelas Tujuan --</option>
							<option value="0">&#9888;&#65039; Belum Ada Kelas (Reset)</option>
							<?php foreach ( $kelas as $k ) : ?>
								<option value="<?php echo (int) $k->id; ?>"><?php echo esc_html( $k->label . ' (' . (int) $k->terisi . '/' . (int) $k->slot . ')' ); ?></option>
							<?php endforeach; ?>
						</select>
						<button type="button" class="kgtk-btn kgtk-btn--navy" id="kgtk-bulk-apply" disabled>Terapkan</button>
						<button type="button" class="kgtk-btn kgtk-btn--primary" id="kgtk-btn-bulk-biodata" data-bio-url="<?php echo esc_url( KGTK_Export::semua_biodata_pdf_url( $p->id ) ); ?>" disabled title="Cetak atau unduh biodata peserta yang dicentang">&#128100; Cetak Biodata Terpilih (<span id="kgtk-bio-checked-count">0</span>)</button>
					</div>
				</div>

				<!-- Tabel Peserta -->
				<?php if ( empty( $all_peserta ) ) : ?>
					<div class="kgtk-empty-peserta">
						<p>Belum ada guru yang mendaftar pada pelatihan ini.</p>
						<p class="kgtk-muted">Bagikan link pendaftaran untuk mulai menerima pendaftar.</p>
					</div>
				<?php else : ?>
					<div class="kgtk-scroll">
						<table class="kgtk-table kgtk-table--kelola" id="kgtk-tbl-peserta" data-pelatihan="<?php echo (int) $p->id; ?>">
							<thead>
								<tr>
									<th style="width:38px;text-align:center;"><input type="checkbox" id="kgtk-th-check-all" title="Pilih Semua Guru"></th>
									<th style="width:46px;text-align:center;">No</th>
									<th style="min-width:190px;">Nama Guru</th>
									<th style="width:180px;">NIK</th>
									<th style="min-width:170px;">Asal Sekolah / Instansi</th>
									<th style="min-width:160px;">Kelas Saat Ini</th>
									<th style="width:65px;text-align:center;">Aksi</th>
								</tr>
							</thead>
							<tbody>
								<?php
								$no = 1;
								foreach ( $all_peserta as $ps ) :
									$search_text   = strtolower( $ps->nama . ' ' . $ps->nik . ' ' . $ps->sekolah . ' ' . $ps->kabkota . ' ' . $ps->hp );
									$is_unassigned = ( 0 === (int) $ps->kelas_id );
									$wa_num        = preg_replace( '/^0/', '62', preg_replace( '/\D/', '', $ps->hp ) );
									$is_ditetapkan = ! empty( $linked_map[ (int) $ps->id ] );
									$label_k       = isset( $kelas_map[ (int) $ps->kelas_id ] ) ? $kelas_map[ (int) $ps->kelas_id ] : ( $is_unassigned ? 'Belum Ada Kelas' : 'Kelas ' . (int) $ps->kelas_id );

									$nama_lengkap_tampil = KGTK_Helpers::format_nama_gelar( $ps );
									$bio_json = wp_json_encode(
										array(
											'id'                 => (int) $ps->id,
											'nama'               => $ps->nama,
											'nama_lengkap'       => $nama_lengkap_tampil,
											'gelar_depan'        => ! empty( $ps->gelar_depan ) ? $ps->gelar_depan : '',
											'gelar_belakang'     => ! empty( $ps->gelar_belakang ) ? $ps->gelar_belakang : '',
											'nik'                => $ps->nik,
											'tempat_lahir'       => ! empty( $ps->tempat_lahir ) ? $ps->tempat_lahir : '-',
											'tanggal_lahir'      => ! empty( $ps->tanggal_lahir ) ? $ps->tanggal_lahir : '-',
											'jenis_kelamin'      => ! empty( $ps->jenis_kelamin ) ? $ps->jenis_kelamin : '-',
											'alamat'             => ! empty( $ps->alamat ) ? $ps->alamat : '-',
											'alamat_instansi'    => ! empty( $ps->alamat_instansi ) ? $ps->alamat_instansi : '-',
											'pangkat_golongan'   => ! empty( $ps->pangkat_golongan ) ? $ps->pangkat_golongan : '-',
											'agama'              => ! empty( $ps->agama ) ? $ps->agama : '-',
											'pendidikan'         => ! empty( $ps->pendidikan ) ? $ps->pendidikan : '-',
											'bank'               => ! empty( $ps->bank ) ? $ps->bank : '-',
											'rekening'           => ! empty( $ps->rekening ) ? $ps->rekening : '-',
											'npwp'               => ! empty( $ps->npwp ) ? $ps->npwp : '-',
											'pengalaman_jabatan' => ! empty( $ps->pengalaman_jabatan ) ? $ps->pengalaman_jabatan : '-',
											'nuptk'              => ! empty( $ps->nuptk ) ? $ps->nuptk : ( ! empty( $ps->nisn ) ? $ps->nisn : '-' ),
											'nip'                => ! empty( $ps->nip ) ? $ps->nip : '-',
											'simpkb'             => ! empty( $ps->simpkb ) ? $ps->simpkb : ( ! empty( $ps->email_guru ) ? $ps->email_guru : '-' ),
											'sekolah'            => $ps->sekolah,
											'kabkota'            => ! empty( $ps->kabkota ) ? $ps->kabkota : '-',
											'peran'              => ! empty( $ps->peran ) ? $ps->peran : 'Peserta',
											'jenis_gtk'          => ! empty( $ps->jenis_gtk ) ? $ps->jenis_gtk : '-',
											'jabatan'            => ! empty( $ps->jenis_gtk ) ? $ps->jenis_gtk : '-',
											'kelas_id'           => (int) $ps->kelas_id,
											'kelas_label'        => $label_k,
											'hp'                 => ! empty( $ps->hp ) ? $ps->hp : '-',
											'email'              => ! empty( $ps->email ) ? $ps->email : '-',
											'email_belajar'      => ! empty( $ps->email_belajar ) ? $ps->email_belajar : '-',
											'created_at'         => ! empty( $ps->created_at ) ? $ps->created_at : '-',
											'pelatihan'          => $p->nama,
											'pelatihan_kode'     => $p->kode,
											'pelatihan_tgl'      => $tgl,
											'pelatihan_lok'      => $lok,
											'pelatihan_kabkota'  => isset( $p->kabkota ) ? $p->kabkota : '',
											'pdf_url'            => KGTK_Export::biodata_pdf_url( $ps->id ),
										)
									);
								?>
									<tr data-id="<?php echo (int) $ps->id; ?>" data-kelas="<?php echo (int) $ps->kelas_id; ?>" data-search="<?php echo esc_attr( $search_text ); ?>" data-biodata="<?php echo esc_attr( $bio_json ); ?>"<?php echo $is_unassigned ? ' class="is-row-unassigned"' : ''; ?>>
										<td class="c-no"><input type="checkbox" class="kgtk-ps-check" value="<?php echo (int) $ps->id; ?>" aria-label="Pilih <?php echo esc_attr( $nama_lengkap_tampil ); ?>"></td>
										<td class="c-no"><span class="kgtk-row-num"><?php echo (int) $no++; ?>.</span></td>
										<td>
											<div class="kgtk-guru-cell">
												<strong class="kgtk-guru-nama">
													<a href="#" class="kgtk-link-biodata" data-view-bio title="Klik untuk lihat biodata lengkap"><?php echo esc_html( $nama_lengkap_tampil ); ?></a>
													<?php if ( $is_ditetapkan ) : ?>
														<span class="kgtk-badge kgtk-badge--undangan" title="Peserta Ditetapkan Kementerian">&#10003; Ditetapkan</span>
													<?php endif; ?>
												</strong>
												<?php if ( $ps->hp ) : ?>
													<div class="kgtk-guru-kontak">
														<a href="https://wa.me/<?php echo esc_attr( $wa_num ); ?>" target="_blank" rel="noopener noreferrer" class="kgtk-wa-link" title="Kirim pesan WhatsApp">
															<span class="kgtk-wa-icon">&#128172;</span> <?php echo esc_html( $ps->hp ); ?>
														</a>
													</div>
												<?php endif; ?>
											</div>
										</td>
										<td>
											<div class="kgtk-nik-cell">
												<code class="kgtk-nik-code"><?php echo esc_html( $ps->nik ); ?></code>
												<button type="button" class="kgtk-copy-btn" data-copy-nik="<?php echo esc_attr( $ps->nik ); ?>" title="Salin NIK">&#128203;</button>
											</div>
										</td>
										<td>
											<div class="kgtk-sekolah-cell">
												<span class="kgtk-sekolah-nama"><?php echo esc_html( $ps->sekolah ); ?></span>
												<?php if ( $ps->kabkota ) : ?>
													<div class="kgtk-kab-tag">&#128205; <?php echo esc_html( $ps->kabkota ); ?></div>
												<?php endif; ?>
											</div>
										</td>
										<td>
											<?php if ( $is_unassigned ) : ?>
												<span class="kgtk-badge is-unassigned-tag">&#9888;&#65039; Belum Ada Kelas</span>
											<?php else : ?>
												<span class="kgtk-badge is-assigned-tag">&#127979; <?php echo esc_html( $label_k ); ?></span>
											<?php endif; ?>
										</td>
										<td style="text-align:center;">
											<div class="kgtk-row-actions">
												<button type="button" class="kgtk-iconbtn kgtk-iconbtn--view" data-view-bio title="Lihat Biodata">&#128065;</button>
												<button type="button" class="kgtk-iconbtn kgtk-iconbtn--del" data-del-peserta="<?php echo (int) $ps->id; ?>" data-nama="<?php echo esc_attr( $ps->nama ); ?>" title="Hapus pendaftaran">&#128465;&#65039;</button>
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

		<!-- PANE 2: Peserta Ditetapkan Kementerian (Undangan) -->
		<?php require KGTK_PATH . 'admin/views/partials/pane-undangan.php'; ?>

		<!-- PANE 3: Panitia Pelatihan -->
		<?php require KGTK_PATH . 'admin/views/partials/pane-panitia.php'; ?>

		<!-- PANE 4: Daftar Tanda Terima Barang / Alat Pelatihan -->
		<?php require KGTK_PATH . 'admin/views/partials/pane-tanda-terima.php'; ?>

		<!-- PANES KELAS: Lembar Absen Cetak Per Kelas -->
		<?php
		foreach ( $kelas as $k ) :
			$groups = KGTK_Helpers::kelompok_absen( $k->peserta );
		?>
			<div class="kgtk-pane" data-pane="<?php echo (int) $k->id; ?>">
				<div class="kgtk-subtabs" role="tablist" aria-label="Peran">
					<?php
					$n = 0;
					foreach ( $groups as $peran => $list ) :
						$info = KGTK_Helpers::PERAN_SLOT === $peran ? count( $list ) . '/' . (int) $k->slot : (string) count( $list );
					?>
						<button type="button" class="kgtk-subtab<?php echo 0 === $n ? ' is-active' : ''; ?>" data-sub="k<?php echo (int) $k->id; ?>-r<?php echo (int) $n; ?>">
							<?php echo esc_html( $peran ); ?> <small><?php echo esc_html( $info ); ?></small>
						</button>
					<?php
						$n++;
					endforeach;
					?>
				</div>
				<?php
				$n = 0;
				foreach ( $groups as $peran => $list ) {
					KGTK_Admin::sheet_html( $p, $k, (string) $peran, $list, KGTK_Helpers::baris_lembar( (string) $peran, $list, $k ), 'k' . (int) $k->id . '-r' . (int) $n, 0 === $n, $multi );
					$n++;
				}
				?>
			</div>
		<?php endforeach; ?>

	</div>

	<!-- Modal Dialogs: Undangan, Biodata, & Export PDF -->
	<?php
	require KGTK_PATH . 'admin/views/partials/modals-undangan.php';
	require KGTK_PATH . 'admin/views/partials/modal-biodata.php';
	require KGTK_PATH . 'admin/views/partials/modal-export-pdf.php';
	require KGTK_PATH . 'admin/views/partials/modal-edit-pelatihan.php';
	?>
</div>
