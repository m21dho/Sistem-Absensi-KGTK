<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * View: Daftar Pelatihan (Grid Kartu)
 *
 * @var array $rows       Daftar pelatihan dari database.
 * @var array $stats      Statistik kelas per pelatihan.
 * @var array $und_stats  Statistik peserta ditetapkan per pelatihan.
 */
?>
<div class="wrap kgtk-wrap kgtk-ui">
	<?php
	KGTK_Admin::notice_libs();
	KGTK_Admin::head(
		'Pelatihan KGTK',
		'Kelola pelatihan dan pantau pendaftaran guru.',
		'<a class="kgtk-btn" href="' . esc_url( KGTK_Admin::page_url( 'buat' ) ) . '">+ Buat pelatihan</a>'
	);
	?>

	<?php if ( empty( $rows ) ) : ?>
		<div class="kgtk-card kgtk-empty">
			<h2>Belum ada pelatihan</h2>
			<p>Klik &ldquo;+ Buat pelatihan&rdquo; untuk membuat pelatihan pertama.</p>
		</div>
	<?php else : ?>
		<div class="kgtk-grid">
			<?php foreach ( $rows as $p ) :
				$kelas = isset( $stats[ (int) $p->id ] ) ? $stats[ (int) $p->id ] : array();
				$tot   = KGTK_Helpers::totals( $kelas );
				$link  = KGTK_Helpers::share_url( $p->kode );
				$absen = KGTK_Admin::page_url( 'pelatihan', array( 'view' => 'absen', 'id' => (int) $p->id ) );
				$u_st       = isset( $und_stats[ (int) $p->id ] ) ? $und_stats[ (int) $p->id ] : null;
				$is_expired = KGTK_Helpers::is_deadline_passed( $p );
			?>
				<article class="kgtk-card kgtk-pcard" data-id="<?php echo (int) $p->id; ?>">
					<div class="kgtk-pcard-top">
						<h2><?php echo esc_html( $p->nama ); ?></h2>
						<?php if ( $is_expired ) : ?>
							<span class="kgtk-badge is-full">Ditutup (Deadline)</span>
						<?php elseif ( $tot['penuh'] ) : ?>
							<span class="kgtk-badge is-full">Penuh</span>
						<?php else : ?>
							<span class="kgtk-badge is-open">Dibuka</span>
						<?php endif; ?>
					</div>

					<?php if ( '' !== $p->penyelenggara ) : ?>
						<p class="kgtk-muted"><?php echo esc_html( $p->penyelenggara ); ?></p>
					<?php endif; ?>

					<ul class="kgtk-meta">
						<li><?php echo esc_html( KGTK_Helpers::rentang_tanggal( $p->tanggal_mulai, $p->tanggal_selesai ) ); ?></li>
						<?php if ( ! empty( $p->deadline_pendaftaran ) ) : ?>
							<li>&#9200; Deadline: <?php echo esc_html( KGTK_Helpers::format_deadline( $p->deadline_pendaftaran ) ); ?><?php echo $is_expired ? ' <strong style="color:#e11d48;">(Lewat)</strong>' : ''; ?></li>
						<?php endif; ?>
						<?php if ( '' !== $p->lokasi ) : ?>
							<li><?php echo esc_html( $p->lokasi ); ?></li>
						<?php endif; ?>
						<?php if ( ! empty( $p->kabkota ) ) : ?>
							<li>&#128205; <?php echo esc_html( $p->kabkota ); ?></li>
						<?php endif; ?>
						<li>Kode: <code><?php echo esc_html( $p->kode ); ?></code></li>
					</ul>

					<div class="kgtk-plabel">
						<span>Peserta terisi</span>
						<strong><?php echo (int) $tot['terisi'] . '/' . (int) $tot['slot']; ?></strong>
					</div>

					<?php
					echo KGTK_Admin::progress( $tot['persen'], $tot['penuh'] );
					echo KGTK_Admin::chips( $kelas );
					?>

					<?php if ( $u_st && $u_st['total'] > 0 ) : ?>
						<div class="kgtk-und-chip-wrap">
							<span class="kgtk-chip kgtk-chip--undangan">&#128221; Ditetapkan: <?php echo (int) $u_st['sudah'] . '/' . (int) $u_st['total']; ?> sudah mendaftar</span>
						</div>
					<?php endif; ?>

					<div class="kgtk-actions">
						<a class="kgtk-btn kgtk-btn--navy" href="<?php echo esc_url( $absen ); ?>">Kelola</a>
						<?php
						$pel_json = wp_json_encode( array(
							'id'                   => (int) $p->id,
							'nama'                 => $p->nama,
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
						) );
						?>
						<button type="button" class="kgtk-btn kgtk-btn--ghost" data-edit-pelatihan="<?php echo (int) $p->id; ?>" data-pelatihan="<?php echo esc_attr( $pel_json ); ?>" title="Edit data pelatihan">&#9998; Edit</button>
						<button type="button" class="kgtk-btn kgtk-btn--ghost" data-copy="<?php echo esc_attr( $link ); ?>">Salin link</button>
						<button type="button" class="kgtk-btn kgtk-btn--danger" data-hapus="<?php echo (int) $p->id; ?>" data-nama="<?php echo esc_attr( $p->nama ); ?>">Hapus</button>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php require KGTK_PATH . 'admin/views/partials/modal-edit-pelatihan.php'; ?>
</div>
