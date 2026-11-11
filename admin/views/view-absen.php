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

$kelas_json_list = array();
foreach ( $kelas as $kl_item ) {
	$kelas_json_list[] = array(
		'id'                   => (int) $kl_item->id,
		'nama'                 => (string) $kl_item->nama,
		'slot'                 => (int) $kl_item->slot,
		'terisi'               => isset( $kl_item->terisi ) ? (int) $kl_item->terisi : 0,
		'tanggal_mulai'        => ! empty( $kl_item->tanggal_mulai ) ? (string) $kl_item->tanggal_mulai : '',
		'tanggal_selesai'      => ! empty( $kl_item->tanggal_selesai ) ? (string) $kl_item->tanggal_selesai : '',
		'tanggal_label'        => ! empty( $kl_item->tanggal_label ) ? (string) $kl_item->tanggal_label : '',
		'deadline_pendaftaran' => ! empty( $kl_item->deadline_pendaftaran ) ? (string) $kl_item->deadline_pendaftaran : '',
		'deadline_formatted'   => ! empty( $kl_item->deadline_formatted ) ? (string) $kl_item->deadline_formatted : '',
		'is_deadline_passed'   => ! empty( $kl_item->is_deadline_passed ),
	);
}

$is_acara = ( isset( $p->jenis ) && 'acara' === $p->jenis );

$pel_json = wp_json_encode( array(
	'id'                   => (int) $p->id,
	'nama'                 => $p->nama,
	'jenis'                => isset( $p->jenis ) && ! empty( $p->jenis ) ? $p->jenis : 'pelatihan',
	'penyelenggara'        => $p->penyelenggara,
	'tanggal_mulai'        => $p->tanggal_mulai,
	'tanggal_selesai'      => $p->tanggal_selesai,
	'deadline_pendaftaran' => isset( $p->deadline_pendaftaran ) ? $p->deadline_pendaftaran : null,
	'lokasi'               => $p->lokasi,
	'kabkota'              => isset( $p->kabkota ) ? $p->kabkota : '',
	'penanggung_jawab'     => $p->penanggung_jawab,
	'tipe_pj'              => isset( $p->tipe_pj ) ? $p->tipe_pj : 'Penanggung Jawab',
	'keterangan'           => $p->keterangan,
	'kode'                 => $p->kode,
	'kelas'                => $kelas_json_list,
) );

?>
<div class="wrap kgtk-wrap kgtk-ui">
	<?php KGTK_Admin::notice_libs(); ?>

	<!-- Header Detail Pelatihan -->
	<hr class="wp-header-end">
	<div class="kgtk-head kgtk-head--detail">
		<div class="kgtk-head-top">
			<div class="kgtk-head-info">
				<a class="kgtk-head-back" href="<?php echo esc_url( KGTK_Admin::page_url( 'pelatihan' ) ); ?>">&larr; Kembali ke Daftar <?php echo $is_acara ? 'Acara' : 'Pelatihan'; ?></a>
				<div style="display:flex;align-items:center;gap:10px;margin-top:2px;">
					<h1 class="kgtk-head-title" style="margin:0;"><?php echo esc_html( $p->nama ); ?></h1>
					<?php if ( $is_acara ) : ?>
						<span class="kgtk-badge" style="background:#f0fdfa;color:#0d9488;border:1px solid #99f6e4;font-size:11.5px;font-weight:700;">Absen Acara</span>
					<?php else : ?>
						<span class="kgtk-badge" style="background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;font-size:11.5px;font-weight:700;">Pelatihan</span>
					<?php endif; ?>
				</div>
				<div class="kgtk-head-sub">
					<span><?php echo $is_acara ? 'Presensi kehadiran peserta acara &amp; tamu undangan' : 'Kelola kelas peserta &amp; daftar hadir'; ?></span>
					<span class="kgtk-head-divider">&middot;</span>
					<code class="kgtk-head-code">&#127991; <?php echo esc_html( $p->kode ); ?></code>
				</div>
			</div>
			<div class="kgtk-head-primary-actions" style="align-items:flex-start;">
				<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-btn-edit-pelatihan-detail" data-pelatihan="<?php echo esc_attr( $pel_json ); ?>" title="<?php echo $is_acara ? 'Edit informasi dan jadwal acara' : 'Edit informasi dan jadwal pelatihan'; ?>">&#9998; <?php echo $is_acara ? 'Edit Acara' : 'Edit Pelatihan'; ?></button>
				<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-btn-impor-header" title="Impor Data Peserta dari berkas Excel atau CSV">&#128196; Impor Data</button>
				<div style="display:flex;flex-direction:column;gap:8px;align-items:stretch;">
					<a class="kgtk-btn kgtk-btn--yellow" href="<?php echo esc_url( KGTK_Admin::page_url( 'pelatihan', array( 'view' => 'tambah', 'id' => (int) $p->id ) ) ); ?>" title="Tambah Peserta, Panitia, Narasumber, Pengajar, Fasilitator, atau Penceramah">+ Tambah Peserta / Panitia / Tim</a>
					<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-btn-kelola-kelas" data-pelatihan-id="<?php echo (int) $p->id; ?>" data-kelas="<?php echo esc_attr( wp_json_encode( $kelas_json_list ) ); ?>" title="<?php echo $is_acara ? 'Tambah atau kelola kategori/kelas peserta acara' : 'Tambah atau kelola kelas peserta pelatihan'; ?>" style="justify-content:center;">+ <?php echo $is_acara ? 'Tambah Kategori / Kelas' : 'Tambah Kelas'; ?></button>
				</div>
			</div>
		</div>

		<div class="kgtk-head-toolbar">
			<div class="kgtk-head-toolbar-label">&#128196; Cetak &amp; Export Dokumen:</div>
			<div class="kgtk-head-toolbar-group">
				<button type="button" class="kgtk-btn kgtk-btn--navy-glass" id="kgtk-btn-export-pdf" title="Pilih kategori export PDF">&#128196; Export PDF &#9662;</button>
				<button type="button" class="kgtk-btn kgtk-btn--navy-glass" id="kgtk-btn-export-excel" title="Pilih kategori export Excel">&#128202; Export Excel &#9662;</button>
			</div>
		</div>
	</div>

	<!-- Notifikasi bila ada pendaftar baru yang belum ditentukan kelasnya -->
	<?php if ( $unassigned > 0 ) : ?>
		<div class="kgtk-unassigned-banner">
			<div class="kgtk-unassigned-icon">&#9888;&#65039;</div>
			<div class="kgtk-unassigned-text">
				<strong>Ada <?php echo (int) $unassigned; ?> <?php echo $is_acara ? 'peserta/tamu' : 'guru'; ?> yang baru mendaftar dan belum ditentukan <?php echo $is_acara ? 'kategori/kelasnya' : 'kelasnya'; ?>!</strong>
				<p><?php echo $is_acara ? 'Peserta mendaftar acara tanpa memilih kategori/kelas. Silakan tentukan kategori/kelas untuk mereka pada tab <b>Atur Kategori / Kelas Peserta</b> di bawah ini.' : 'Guru mendaftar pelatihan tanpa memilih kelas. Silakan pilihkan kelas untuk mereka pada tab <b>Atur Kelas Peserta</b> di bawah ini.'; ?></p>
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
				'Kode ' . ( $is_acara ? 'Acara' : 'Pelatihan' ) => $p->kode,
			);
			if ( ! empty( $p->deadline_pendaftaran ) ) {
				$dl_info = KGTK_Helpers::format_deadline( $p->deadline_pendaftaran );
				if ( KGTK_Helpers::is_deadline_passed( $p ) ) {
					$dl_info .= ' (Ditutup / Lewat)';
				}
				$items['Batas Pendaftaran'] = $dl_info;
			}
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
		<button type="button" role="tab" class="kgtk-tab" data-tab="peserta-ditetapkan">
			&#128221; Peserta Ditetapkan <small class="kgtk-tab-badge" id="kgtk-tab-badge-undangan"><?php echo (int) $und_stats['sudah'] . '/' . (int) $und_stats['total']; ?></small>
		</button>
		<button type="button" role="tab" class="kgtk-tab" data-tab="panitia">
			&#128100; Panitia <small class="kgtk-tab-badge" id="kgtk-tab-badge-panitia"><?php echo ! empty( $panitia ) ? count( $panitia ) : 0; ?></small>
		</button>
		<button type="button" role="tab" class="kgtk-tab" data-tab="tanda-terima">
			&#128230; Tanda Terima
		</button>
		<button type="button" role="tab" class="kgtk-tab" data-tab="berkas-spt-sppd">
			&#128221; Berkas SPT &amp; SPPD
		</button>
	</div>

	<!-- Kartu Navigasi & Statistik Kelas (Selalu Tampil untuk Akses Cepat) -->
	<?php
	$pan_by_kelas   = array();
	$pan_unassigned = 0;
	if ( ! empty( $panitia ) ) {
		foreach ( $panitia as $pan_item ) {
			$kid = isset( $pan_item->kelas_id ) ? (int) $pan_item->kelas_id : 0;
			if ( $kid > 0 && isset( $kelas_map[ $kid ] ) ) {
				$pan_by_kelas[ $kid ] = isset( $pan_by_kelas[ $kid ] ) ? $pan_by_kelas[ $kid ] + 1 : 1;
			} else {
				$pan_unassigned++;
			}
		}
	}
	$total_panitia_count = ! empty( $panitia ) ? count( $panitia ) : 0;
	$total_semua_anggota = $total_pendaftar + $total_panitia_count;
	$total_semua_unassigned = $unassigned + $pan_unassigned;
	?>
	<div class="kgtk-card kgtk-kelola-box" style="margin-bottom:14px;">
		<div class="kgtk-kelola-head">
			<div>
				<h2 class="kgtk-h2">Penempatan Kelas Peserta</h2>
				<p class="kgtk-muted">Klik kotak kelas di bawah untuk melihat daftar peserta, panitia kelas, dan lembar absen.</p>
			</div>
			<div class="kgtk-kelola-head-badge">
				<span class="kgtk-badge <?php echo $total_semua_unassigned > 0 ? 'is-alert' : 'is-open'; ?>">
					<?php echo $total_semua_unassigned > 0 ? (int) $total_semua_unassigned . ' Belum Masuk Kelas' : 'Semua Terbagi Kelas'; ?>
				</span>
			</div>
		</div>

		<!-- Kartu Statistik Cepat Filter -->
		<div class="kgtk-stat-cards" style="margin-bottom:0;">
			<div class="kgtk-scard is-active" data-filter="all" data-nama="Semua Pendaftar &amp; Panitia" data-desc="Menampilkan seluruh peserta dan panitia terdaftar pada kegiatan ini." role="button" tabindex="0" title="Klik untuk melihat semua pendaftar dan panitia">
				<div class="kgtk-scard-num"><?php echo (int) $total_semua_anggota; ?></div>
				<div class="kgtk-scard-label">&#128101; Total Pendaftar</div>
				<div style="font-size:11px;color:#64748b;margin-top:2px;">
					<?php echo (int) $total_pendaftar; ?> Peserta &middot; <?php echo (int) $total_panitia_count; ?> Panitia
				</div>
			</div>

			<div class="kgtk-scard<?php echo $total_semua_unassigned > 0 ? ' is-warn' : ''; ?>" data-filter="0" data-nama="Belum Ada Kelas / Perlu Penempatan" data-desc="Daftar peserta dan panitia yang belum ditempatkan ke kelas manapun." role="button" tabindex="0" title="Klik untuk filter: Belum ada kelas">
				<div class="kgtk-scard-num"><?php echo (int) $total_semua_unassigned; ?></div>
				<div class="kgtk-scard-label">&#9888;&#65039; Belum Ada Kelas</div>
				<?php if ( $total_semua_unassigned > 0 ) : ?>
					<span class="kgtk-scard-pill">Perlu Penempatan</span>
				<?php endif; ?>
			</div>

			<?php foreach ( $kelas as $k ) :
				$peserta_slot = isset( $k->terisi ) ? (int) $k->terisi : 0;
				$pan_count    = isset( $pan_by_kelas[ (int) $k->id ] ) ? (int) $pan_by_kelas[ (int) $k->id ] : 0;
				$lain_count   = isset( $k->lain ) ? (int) $k->lain : 0;
				$pct          = min( 100, round( $peserta_slot / max( 1, (int) $k->slot ) * 100 ) );
			?>
				<div class="kgtk-scard" data-filter="<?php echo (int) $k->id; ?>" data-nama="<?php echo esc_attr( $k->label ); ?>" data-slot="<?php echo (int) $peserta_slot . '/' . (int) $k->slot; ?>" data-panitia="<?php echo (int) $pan_count; ?>" data-lain="<?php echo (int) $lain_count; ?>" data-tanggal="<?php echo esc_attr( $k->tanggal_label ?? '' ); ?>" data-deadline="<?php echo esc_attr( $k->deadline_formatted ?? '' ); ?>" role="button" tabindex="0" title="Klik untuk membuka <?php echo esc_attr( $k->label ); ?>">
					<div class="kgtk-scard-num">
						<?php echo (int) $peserta_slot; ?><small>/<?php echo (int) $k->slot; ?></small>
					</div>
					<div class="kgtk-scard-label">&#127979; <?php echo esc_html( $k->label ); ?></div>
					<div style="display:flex;gap:4px;flex-wrap:wrap;margin-top:3px;">
						<?php if ( $pan_count > 0 ) : ?>
							<span style="font-size:10.5px;color:#0369a1;background:#e0f2fe;font-weight:600;padding:1px 6px;border-radius:4px;" title="<?php echo (int) $pan_count; ?> panitia mengurus kelas ini">&#128100; <?php echo (int) $pan_count; ?> Panitia</span>
						<?php endif; ?>
						<?php if ( $lain_count > 0 ) : ?>
							<span style="font-size:10.5px;color:#b45309;background:#fef3c7;font-weight:600;padding:1px 6px;border-radius:4px;" title="<?php echo (int) $lain_count; ?> pengajar / non-peserta di kelas ini">+<?php echo (int) $lain_count; ?> Pengajar</span>
						<?php endif; ?>
					</div>
					<?php if ( ! empty( $k->tanggal_label ) ) : ?>
						<div style="font-size:11px;color:#0284c7;font-weight:600;margin-top:3px;pointer-events:none;">&#128197; <?php echo esc_html( $k->tanggal_label ); ?></div>
					<?php endif; ?>
					<?php if ( ! empty( $k->deadline_formatted ) ) : ?>
						<div style="font-size:10.5px;color:<?php echo ! empty( $k->is_deadline_passed ) ? '#dc2626' : '#64748b'; ?>;font-weight:600;margin-top:2px;pointer-events:none;">
							&#9200; Batas: <?php echo esc_html( $k->deadline_formatted . ( ! empty( $k->is_deadline_passed ) ? ' (Tutup)' : '' ) ); ?>
						</div>
					<?php endif; ?>
					<div style="margin-top:8px;display:flex;gap:4px;flex-wrap:wrap;">
						<button type="button" class="kgtk-btn-lembar-kelas" data-open-pane="<?php echo (int) $k->id; ?>" style="flex:1;font-size:11px;padding:4px 8px;border-radius:6px;border:1px solid #bfdbfe;background:#eff6ff;color:#1d4ed8;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:4px;" title="Lihat lembar daftar hadir <?php echo esc_attr( $k->label ); ?>">
							&#128196; Lembar Absen
						</button>
						<button type="button" class="kgtk-btn-deadline-kelas" data-atur-deadline-kelas="<?php echo (int) $k->id; ?>" style="font-size:11px;padding:4px 8px;border-radius:6px;border:1px solid #cbd5e1;background:#f8fafc;color:#0b2d56;font-weight:600;cursor:pointer;" title="Tambah atau ubah batas pendaftaran untuk <?php echo esc_attr( $k->label ); ?>">
							&#9200;
						</button>
					</div>
					<div class="kgtk-scard-bar"><span style="width:<?php echo (int) $pct; ?>%"></span></div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="kgtk-sheets">

		<!-- PANE 1: Kelola Penempatan Kelas Peserta -->
		<div class="kgtk-pane is-active" data-pane="kelola-peserta">
			<div class="kgtk-card kgtk-kelola-box">

				<!-- Banner Kelas Aktif & Akses Langsung Lembar Absen -->
				<div class="kgtk-active-kelas-header" id="kgtk-active-kelas-header" style="background:#f0f7ff;border:1.5px solid #bfdbfe;border-radius:10px;padding:12px 16px;margin-bottom:14px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
					<div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
						<span style="font-size:26px;line-height:1;" id="kgtk-akh-icon">&#128101;</span>
						<div>
							<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
								<h3 style="margin:0;font-size:16px;font-weight:700;color:#0b2d56;" id="kgtk-akh-title">Semua Pendaftar</h3>
								<span class="kgtk-badge" id="kgtk-akh-badge-slot" style="background:#dbeafe;color:#1e40af;font-weight:700;font-size:12px;"><?php echo (int) $total_semua_anggota; ?> orang</span>
								<span class="kgtk-badge" id="kgtk-akh-badge-panitia" style="background:#fef3c7;color:#92400e;font-weight:700;font-size:12px;display:none;">0 panitia</span>
							</div>
						</div>
					</div>
					<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
						<button type="button" class="kgtk-btn kgtk-btn--navy" id="kgtk-akh-btn-absen" style="display:none;font-weight:700;padding:7px 14px;box-shadow:0 1px 3px rgba(0,0,0,0.1);" title="Buka dan cetak lembar presensi resmi untuk kelas ini">
							&#128196; Buka Lembar Absen Kelas
						</button>
						<button type="button" class="kgtk-btn kgtk-btn--outline" id="kgtk-akh-btn-deadline" style="display:none;font-size:12px;padding:6px 12px;font-weight:600;" title="Atur batas waktu pendaftaran kelas ini">
							&#9200; Atur Deadline
						</button>
					</div>
				</div>

				<!-- Filter Cepat Peran di Kelas (Semua / Peserta / Panitia / Lainnya) -->
				<div class="kgtk-role-filters" id="kgtk-role-filters" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin-bottom:12px;">
					<span style="font-size:12px;font-weight:600;color:#64748b;margin-right:4px;">Filter Tampilan:</span>
					<button type="button" class="kgtk-fbtn is-active" data-role-filter="all">&#9776; Semua Anggota (<span id="kgtk-role-cnt-all">0</span>)</button>
					<button type="button" class="kgtk-fbtn" data-role-filter="peserta">&#128101; Peserta (<span id="kgtk-role-cnt-peserta">0</span>)</button>
					<button type="button" class="kgtk-fbtn" data-role-filter="panitia">&#128100; <span id="kgtk-role-lbl-panitia">Panitia Kelas</span> (<span id="kgtk-role-cnt-panitia">0</span>)</button>
					<button type="button" class="kgtk-fbtn" data-role-filter="lain">&#127891; Pengajar / Lainnya (<span id="kgtk-role-cnt-lain">0</span>)</button>
				</div>

				<!-- Toolbar Pencarian -->
				<div class="kgtk-kelola-tools" style="display:flex;align-items:center;gap:10px;justify-content:space-between;flex-wrap:wrap;margin-bottom:12px;">
					<div class="kgtk-search-wrap" style="flex:1 1 300px;max-width:100%;">
						<span class="kgtk-search-icon">&#128269;</span>
						<input type="search" id="kgtk-peserta-search" class="kgtk-input" placeholder="Cari nama guru, NIK, asal sekolah/instansi, atau nomor HP...">
						<button type="button" id="kgtk-search-clear" class="kgtk-search-clear" title="Bersihkan pencarian" style="display:none;">&times;</button>
					</div>
					<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-btn-open-pulihkan" title="Cari atau pulihkan guru dari Master Data yang tidak sengaja terhapus atau belum masuk pelatihan ini" style="flex-shrink:0;white-space:nowrap;">
						&#128101; Cari Guru di Master Data / Pulihkan
					</button>
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
									<th style="width:115px;text-align:center;">Aksi</th>
								</tr>
							</thead>
							<tbody>
								<?php
								$no = 1;
								foreach ( $all_peserta as $ps ) :
									$search_text   = strtolower( $ps->nama . ' ' . $ps->nik . ' ' . $ps->sekolah . ' ' . $ps->kabkota . ' ' . $ps->hp . ' ' . ( $ps->peran ?? '' ) );
									$is_unassigned = ( 0 === (int) $ps->kelas_id );
									$wa_num        = preg_replace( '/^0/', '62', preg_replace( '/\D/', '', $ps->hp ) );
									$is_ditetapkan = ! empty( $linked_map[ (int) $ps->id ] );
									$label_k       = isset( $kelas_map[ (int) $ps->kelas_id ] ) ? $kelas_map[ (int) $ps->kelas_id ] : ( $is_unassigned ? 'Belum Ada Kelas' : 'Kelas ' . (int) $ps->kelas_id );
									$peran_clean   = ! empty( $ps->peran ) ? $ps->peran : 'Peserta';

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
											'peran'              => $peran_clean,
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
											'pelatihan_tgl'      => ( ! empty( $ps->kelas_tanggal_mulai ) && '0000-00-00' !== $ps->kelas_tanggal_mulai ) ? KGTK_Helpers::rentang_tanggal( $ps->kelas_tanggal_mulai, ! empty( $ps->kelas_tanggal_selesai ) && '0000-00-00' !== $ps->kelas_tanggal_selesai ? $ps->kelas_tanggal_selesai : $ps->kelas_tanggal_mulai ) : $tgl,
											'pelatihan_lok'      => $lok,
											'pelatihan_kabkota'  => isset( $p->kabkota ) ? $p->kabkota : '',
											'pdf_url'            => KGTK_Export::biodata_pdf_url( $ps->id ),
										)
									);
								?>
									<tr data-id="<?php echo (int) $ps->id; ?>" data-kelas="<?php echo (int) $ps->kelas_id; ?>" data-peran="<?php echo esc_attr( strtolower( $peran_clean ) ); ?>" data-is-panitia="0" data-search="<?php echo esc_attr( $search_text ); ?>" data-biodata="<?php echo esc_attr( $bio_json ); ?>"<?php echo $is_unassigned ? ' class="is-row-unassigned"' : ''; ?>>
										<td class="c-no"><input type="checkbox" class="kgtk-ps-check" value="<?php echo (int) $ps->id; ?>" aria-label="Pilih <?php echo esc_attr( $nama_lengkap_tampil ); ?>"></td>
										<td class="c-no"><span class="kgtk-row-num"><?php echo (int) $no++; ?>.</span></td>
										<td>
											<div class="kgtk-guru-cell">
												<strong class="kgtk-guru-nama">
													<a href="#" class="kgtk-link-biodata" data-view-bio title="Klik untuk lihat biodata lengkap"><?php echo esc_html( $nama_lengkap_tampil ); ?></a>
													<?php if ( $is_ditetapkan ) : ?>
														<span class="kgtk-badge kgtk-badge--undangan" title="Peserta Ditetapkan Kementerian">&#10003; Ditetapkan</span>
													<?php endif; ?>
													<?php if ( ! empty( $ps->peran ) && 'Peserta' !== $ps->peran ) : ?>
														<button type="button" class="kgtk-badge kgtk-btn-ubah-peran" data-id="<?php echo (int) $ps->id; ?>" data-nama="<?php echo esc_attr( $nama_lengkap_tampil ); ?>" data-peran="<?php echo esc_attr( $ps->peran ); ?>" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;font-size:11px;padding:2px 8px;border-radius:4px;margin-left:4px;cursor:pointer;display:inline-flex;align-items:center;gap:4px;vertical-align:middle;text-decoration:none;" title="Guru ini terdaftar sebagai <?php echo esc_attr( $ps->peran ); ?>. Klik untuk mengubah ke peran lain (mis. Peserta).">
															<span>&#128100; <?php echo esc_html( $ps->peran ); ?></span>
															<span style="font-size:9.5px;text-decoration:underline;opacity:0.85;">(Ubah)</span>
														</button>
													<?php endif; ?>
												</strong>
												<?php if ( $ps->hp ) : ?>
													<div class="kgtk-guru-kontak">
														<a href="https://wa.me/<?php echo esc_attr( $wa_num ); ?>" target="_blank" rel="noopener noreferrer" class="kgtk-wa-link" title="Kirim pesan WhatsApp">
															<span class="kgtk-wa-icon">&#128172;</span> <?php echo esc_html( $ps->hp ); ?>
														</a>
													</div>
												<?php endif; ?>
												<?php if ( empty( $ps->email_belajar ) && 'Peserta' === ( $ps->peran ?? 'Peserta' ) ) : ?>
													<div style="margin-top:2px;">
														<span class="kgtk-badge" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;font-size:10.5px;padding:1px 6px;border-radius:4px;display:inline-flex;align-items:center;gap:3px;" title="Guru ini belum memiliki akun email @belajar.id">&#9888; Belum ada Belajar.id</span>
													</div>
												<?php endif; ?>
												<?php if ( ! empty( $ps->created_at ) && '0000-00-00 00:00:00' !== $ps->created_at && '-' !== $ps->created_at ) : ?>
													<div class="kgtk-guru-waktu" style="font-size:11px;color:#64748b;margin-top:3px;display:flex;align-items:center;gap:4px;" title="Waktu Mendaftar">
														<span style="opacity:0.8;">&#128344;</span>
														<span>Daftar: <?php echo esc_html( date_i18n( 'j M Y, H:i', strtotime( $ps->created_at ) ) ); ?> WIB</span>
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
											<div class="kgtk-row-actions" style="display:inline-flex;gap:4px;align-items:center;justify-content:center;">
												<button type="button" class="kgtk-iconbtn kgtk-iconbtn--view" data-view-bio title="Lihat Biodata">&#128065;</button>
												<button type="button" class="kgtk-iconbtn kgtk-iconbtn--edit" data-edit-peserta="<?php echo (int) $ps->id; ?>" title="Edit data peserta">&#9998;</button>
												<button type="button" class="kgtk-iconbtn kgtk-iconbtn--del" data-del-peserta="<?php echo (int) $ps->id; ?>" data-nama="<?php echo esc_attr( $ps->nama ); ?>" title="Hapus pendaftaran">&#128465;&#65039;</button>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>

								<?php
								// Tampilkan juga panitia yang terdaftar/ditempatkan pada kegiatan ini di dalam tabel
								if ( ! empty( $panitia ) ) :
									foreach ( $panitia as $pan ) :
										$pan_kelas_id = isset( $pan->kelas_id ) ? (int) $pan->kelas_id : 0;
										if ( $pan_kelas_id > 0 && ! isset( $kelas_map[ $pan_kelas_id ] ) ) {
											$pan_kelas_id = 0;
										}
										$search_text   = strtolower( $pan->nama . ' ' . $pan->nik . ' ' . ( $pan->nip ?? '' ) . ' ' . ( $pan->jabatan ?? '' ) . ' ' . $pan->sekolah . ' ' . ( $pan->hp ?? '' ) . ' panitia' );
										$is_unassigned = ( 0 === $pan_kelas_id );
										$wa_num        = preg_replace( '/^0/', '62', preg_replace( '/\D/', '', (string) ( $pan->hp ?? '' ) ) );
										$label_k       = isset( $kelas_map[ $pan_kelas_id ] ) ? $kelas_map[ $pan_kelas_id ] : ( $is_unassigned ? 'Umum / Belum Ada Kelas' : 'Kelas ' . $pan_kelas_id );
										$nama_lengkap_tampil = KGTK_Helpers::format_nama_gelar( $pan );
										$pan_bio_json  = wp_json_encode(
											array(
												'id'                 => (int) $pan->id,
												'nama'               => $pan->nama,
												'nama_lengkap'       => $nama_lengkap_tampil,
												'gelar_depan'        => ! empty( $pan->gelar_depan ) ? $pan->gelar_depan : '',
												'gelar_belakang'     => ! empty( $pan->gelar_belakang ) ? $pan->gelar_belakang : '',
												'nik'                => $pan->nik,
												'tempat_lahir'       => '-',
												'tanggal_lahir'      => '-',
												'jenis_kelamin'      => '-',
												'alamat'             => '-',
												'alamat_instansi'    => '-',
												'pangkat_golongan'   => '-',
												'agama'              => '-',
												'pendidikan'         => '-',
												'bank'               => ! empty( $pan->bank ) ? $pan->bank : '-',
												'rekening'           => ! empty( $pan->rekening ) ? $pan->rekening : '-',
												'npwp'               => ! empty( $pan->npwp ) ? $pan->npwp : '-',
												'pengalaman_jabatan' => '-',
												'nuptk'              => ! empty( $pan->nuptk ) ? $pan->nuptk : '-',
												'nip'                => ! empty( $pan->nip ) ? $pan->nip : '-',
												'simpkb'             => '-',
												'sekolah'            => $pan->sekolah ?? '',
												'kabkota'            => ! empty( $pan->kabkota ) ? $pan->kabkota : '-',
												'peran'              => 'Panitia',
												'jenis_gtk'          => $pan->jabatan ?: 'Panitia Pelaksana',
												'jabatan'            => $pan->jabatan ?: 'Panitia Pelaksana',
												'kelas_id'           => $pan_kelas_id,
												'kelas_label'        => $label_k,
												'hp'                 => ! empty( $pan->hp ) ? $pan->hp : '-',
												'email'              => ! empty( $pan->email ) ? $pan->email : '-',
												'email_belajar'      => '-',
												'created_at'         => ! empty( $pan->created_at ) ? $pan->created_at : '-',
												'pelatihan'          => $p->nama,
												'pelatihan_kode'     => $p->kode,
												'pelatihan_tgl'      => $tgl,
												'pelatihan_lok'      => $lok,
												'pelatihan_kabkota'  => isset( $p->kabkota ) ? $p->kabkota : '',
												'pdf_url'            => KGTK_Export::biodata_pdf_url( $pan->id ),
											)
										);
								?>
									<tr data-id="<?php echo (int) $pan->id; ?>" data-kelas="<?php echo (int) $pan_kelas_id; ?>" data-peran="panitia" data-is-panitia="1" data-search="<?php echo esc_attr( $search_text ); ?>" data-biodata="<?php echo esc_attr( $pan_bio_json ); ?>"<?php echo $is_unassigned ? ' class="is-row-unassigned"' : ''; ?>>
										<td class="c-no"><input type="checkbox" class="kgtk-ps-check" value="<?php echo (int) $pan->id; ?>" aria-label="Pilih <?php echo esc_attr( $nama_lengkap_tampil ); ?>"></td>
										<td class="c-no"><span class="kgtk-row-num"><?php echo (int) $no++; ?>.</span></td>
										<td>
											<div class="kgtk-guru-cell">
												<strong class="kgtk-guru-nama">
													<a href="#" class="kgtk-link-biodata" data-view-bio title="Klik untuk lihat biodata panitia"><?php echo esc_html( $nama_lengkap_tampil ); ?></a>
													<span class="kgtk-badge" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;font-size:11px;padding:2px 8px;border-radius:4px;margin-left:4px;display:inline-flex;align-items:center;gap:3px;vertical-align:middle;" title="Panitia Pelaksana">
														&#128100; Panitia (<?php echo esc_html( $pan->jabatan ?: 'Pelaksana' ); ?>)
													</span>
												</strong>
												<?php if ( ! empty( $pan->hp ) ) : ?>
													<div class="kgtk-guru-kontak">
														<a href="https://wa.me/<?php echo esc_attr( $wa_num ); ?>" target="_blank" rel="noopener noreferrer" class="kgtk-wa-link" title="Kirim pesan WhatsApp">
															<span class="kgtk-wa-icon">&#128172;</span> <?php echo esc_html( $pan->hp ); ?>
														</a>
													</div>
												<?php endif; ?>
											</div>
										</td>
										<td>
											<div class="kgtk-nik-cell">
												<?php if ( ! empty( $pan->nik ) && 0 !== strpos( $pan->nik, '99' ) ) : ?>
													<code class="kgtk-nik-code"><?php echo esc_html( $pan->nik ); ?></code>
													<button type="button" class="kgtk-copy-btn" data-copy-nik="<?php echo esc_attr( $pan->nik ); ?>" title="Salin NIK">&#128203;</button>
												<?php else : ?>
													<span class="kgtk-muted">-</span>
												<?php endif; ?>
											</div>
											<?php if ( ! empty( $pan->nip ) && '-' !== $pan->nip ) : ?>
												<div class="kgtk-muted" style="font-size:11px;margin-top:2px;">NIP: <?php echo esc_html( $pan->nip ); ?></div>
											<?php endif; ?>
										</td>
										<td>
											<div class="kgtk-sekolah-cell">
												<span class="kgtk-sekolah-nama"><?php echo esc_html( $pan->sekolah ?? '-' ); ?></span>
												<?php if ( ! empty( $pan->kabkota ) ) : ?>
													<div class="kgtk-kab-tag">&#128205; <?php echo esc_html( $pan->kabkota ); ?></div>
												<?php endif; ?>
											</div>
										</td>
										<td>
											<select class="kgtk-input kgtk-pan-kelas-select" data-pan-id="<?php echo (int) $pan->id; ?>" style="min-width:140px;height:32px;padding:3px 6px;font-size:12px;font-weight:600;color:<?php echo $pan_kelas_id > 0 ? '#1e40af' : '#92400e'; ?>;background:<?php echo $pan_kelas_id > 0 ? '#eff6ff' : '#fffbeb'; ?>;border-color:<?php echo $pan_kelas_id > 0 ? '#bfdbfe' : '#fde68a'; ?>;">
												<option value="0" <?php selected( 0, $pan_kelas_id ); ?>>&#9888;&#65039; Belum Ada Kelas</option>
												<?php foreach ( $kelas as $k_opt ) : ?>
													<option value="<?php echo (int) $k_opt->id; ?>" <?php selected( (int) $k_opt->id, $pan_kelas_id ); ?>>&#127979; <?php echo esc_html( $k_opt->label ); ?></option>
												<?php endforeach; ?>
											</select>
										</td>
										<td style="text-align:center;">
											<div class="kgtk-row-actions" style="display:inline-flex;gap:4px;align-items:center;justify-content:center;">
												<button type="button" class="kgtk-iconbtn kgtk-iconbtn--view" data-view-bio title="Lihat Biodata Panitia">&#128065;</button>
												<button type="button" class="kgtk-iconbtn kgtk-iconbtn--del" data-del-panitia-pelatihan="<?php echo (int) $pan->id; ?>" data-nama="<?php echo esc_attr( $pan->nama ); ?>" title="Hapus dari susunan panitia">&#128465;&#65039;</button>
											</div>
										</td>
									</tr>
								<?php endforeach; endif; ?>
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

		<!-- PANE 5: Daftar Tanda Terima Berkas SPT & SPPD Peserta -->
		<?php require KGTK_PATH . 'admin/views/partials/pane-berkas-spt-sppd.php'; ?>

		<!-- PANES KELAS: Lembar Absen Cetak Per Kelas -->
		<?php
		foreach ( $kelas as $k ) :
			$groups = KGTK_Helpers::kelompok_absen( $k->peserta, true );
		?>
			<div class="kgtk-pane" data-pane="<?php echo (int) $k->id; ?>">
				<!-- Top Bar: Tombol Kembali ke Daftar Anggota Kelas -->
				<div class="kgtk-absen-header-bar kgtk-noprint-note" style="background:#f8fafc;border:1.5px solid #cbd5e1;border-radius:8px;padding:10px 14px;margin-bottom:14px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
					<div style="display:flex;align-items:center;gap:10px;">
						<button type="button" class="kgtk-btn kgtk-btn--outline kgtk-btn-back-to-peserta" data-back-kelas="<?php echo (int) $k->id; ?>" style="font-size:12px;padding:6px 14px;font-weight:700;display:inline-flex;align-items:center;gap:6px;background:#fff;">
							&larr; Kembali ke Daftar Anggota Kelas
						</button>
						<div style="font-weight:700;font-size:14.5px;color:#0b2d56;">
							&#128196; Lembar Hadir Resmi: <?php echo esc_html( $k->label ); ?>
						</div>
					</div>
					<div style="font-size:12px;color:#64748b;">
						Format resmi presensi cetak (A4) per peran
					</div>
				</div>

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
	require KGTK_PATH . 'admin/views/partials/modal-edit-peserta.php';
	require KGTK_PATH . 'admin/views/partials/modal-ubah-peran.php';
	require KGTK_PATH . 'admin/views/partials/modal-export-pdf.php';
	require KGTK_PATH . 'admin/views/partials/modal-export-excel.php';
	require KGTK_PATH . 'admin/views/partials/modal-edit-pelatihan.php';
	require KGTK_PATH . 'admin/views/partials/modal-kelola-kelas.php';
	require KGTK_PATH . 'admin/views/partials/modal-pulihkan-guru.php';
	?>
</div>
