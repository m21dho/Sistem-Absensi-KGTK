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
				$u_st  = isset( $und_stats[ (int) $p->id ] ) ? $und_stats[ (int) $p->id ] : null;
			?>
				<article class="kgtk-card kgtk-pcard" data-id="<?php echo (int) $p->id; ?>">
					<div class="kgtk-pcard-top">
						<h2><?php echo esc_html( $p->nama ); ?></h2>
						<span class="kgtk-badge <?php echo $tot['penuh'] ? 'is-full' : 'is-open'; ?>">
							<?php echo $tot['penuh'] ? 'Penuh' : 'Dibuka'; ?>
						</span>
					</div>

					<?php if ( '' !== $p->penyelenggara ) : ?>
						<p class="kgtk-muted"><?php echo esc_html( $p->penyelenggara ); ?></p>
					<?php endif; ?>

					<ul class="kgtk-meta">
						<li><?php echo esc_html( KGTK_Helpers::rentang_tanggal( $p->tanggal_mulai, $p->tanggal_selesai ) ); ?></li>
						<?php if ( '' !== $p->lokasi ) : ?>
							<li><?php echo esc_html( $p->lokasi ); ?></li>
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
						<button type="button" class="kgtk-btn kgtk-btn--ghost" data-copy="<?php echo esc_attr( $link ); ?>">Salin link</button>
						<button type="button" class="kgtk-btn kgtk-btn--danger" data-hapus="<?php echo (int) $p->id; ?>" data-nama="<?php echo esc_attr( $p->nama ); ?>">Hapus</button>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
