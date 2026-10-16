<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Partial View: Pane Peserta Ditetapkan Kementerian (Undangan)
 *
 * @var object $p         Data pelatihan.
 * @var array  $und_stats Statistik peserta ditetapkan.
 */
$exp_und_url = wp_nonce_url( admin_url( 'admin-post.php?action=kgtk_undangan_export&id=' . (int) $p->id ), 'kgtk_undangan_export_' . (int) $p->id );
$rows_undangan = KGTK_Undangan::get_list( $p->id );
?>
<div class="kgtk-pane" data-pane="peserta-ditetapkan">
	<div class="kgtk-card kgtk-kelola-box" id="kgtk-undangan-container" data-pelatihan="<?php echo (int) $p->id; ?>">
		<div class="kgtk-kelola-head">
			<div>
				<h2 class="kgtk-h2">Peserta Ditetapkan Kementerian</h2>
				<p class="kgtk-muted">Daftar peserta yang ditetapkan lebih dulu sebelum pelatihan dimulai. Sistem akan otomatis mencocokkan peserta saat mereka mendaftar.</p>
			</div>
			<div class="kgtk-head-actions">
				<button type="button" class="kgtk-btn" id="kgtk-btn-tambah-undangan">+ Tambah</button>
				<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-btn-impor-undangan">&#128196; Impor Excel/CSV</button>
				<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-btn-sinkron-undangan" title="Jalankan ulang pencocokan untuk seluruh peserta belum cocok">&#128260; Sinkronkan ulang</button>
				<a class="kgtk-btn kgtk-btn--navy" href="<?php echo esc_url( $exp_und_url ); ?>">&#128229; Export Excel</a>
				<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-btn-salin-belum" title="Salin daftar peserta yang belum mendaftar untuk dihubungi">&#128203; Salin Belum Mendaftar</button>
			</div>
		</div>

		<!-- Ringkasan Progress Bar & Stat Cards -->
		<div class="kgtk-undangan-summary">
			<div class="kgtk-plabel">
				<span>Progres Pendaftaran: <strong id="kgtk-undangan-pct-text"><?php echo (int) $und_stats['persen']; ?>%</strong></span>
				<strong><span id="kgtk-undangan-sudah-text"><?php echo (int) $und_stats['sudah']; ?></span> dari <span id="kgtk-undangan-total-text"><?php echo (int) $und_stats['total']; ?></span> peserta sudah mendaftar</strong>
			</div>
			<div class="kgtk-progress" id="kgtk-undangan-progressbar">
				<span style="width:<?php echo (int) $und_stats['persen']; ?>%"></span>
			</div>
		</div>

		<!-- Kartu Statistik Interaktif -->
		<div class="kgtk-stat-cards kgtk-stat-cards--undangan">
			<div class="kgtk-scard is-active" data-filter="all" role="button" tabindex="0">
				<div class="kgtk-scard-num" id="kgtk-scard-und-total"><?php echo (int) $und_stats['total']; ?></div>
				<div class="kgtk-scard-label">&#128101; Total Ditetapkan</div>
			</div>

			<div class="kgtk-scard" data-filter="sudah" role="button" tabindex="0">
				<div class="kgtk-scard-num" id="kgtk-scard-und-sudah"><?php echo (int) $und_stats['sudah']; ?></div>
				<div class="kgtk-scard-label">&#9989; Sudah Mendaftar</div>
			</div>

			<div class="kgtk-scard<?php echo $und_stats['belum'] > 0 ? ' is-warn' : ''; ?>" data-filter="belum" role="button" tabindex="0">
				<div class="kgtk-scard-num" id="kgtk-scard-und-belum"><?php echo (int) $und_stats['belum']; ?></div>
				<div class="kgtk-scard-label">&#9203; Belum Mendaftar</div>
				<?php if ( $und_stats['belum'] > 0 ) : ?>
					<span class="kgtk-scard-pill">Perlu Dihubungi</span>
				<?php endif; ?>
			</div>

			<div class="kgtk-scard<?php echo $und_stats['perlu'] > 0 ? ' is-warn' : ''; ?>" data-filter="perlu" role="button" tabindex="0">
				<div class="kgtk-scard-num" id="kgtk-scard-und-perlu"><?php echo (int) $und_stats['perlu']; ?></div>
				<div class="kgtk-scard-label">&#9888;&#65039; Perlu Konfirmasi</div>
				<?php if ( $und_stats['perlu'] > 0 ) : ?>
					<span class="kgtk-scard-pill">Pencocokan Nama</span>
				<?php endif; ?>
			</div>
		</div>

		<!-- Toolbar Pencarian dan Filter -->
		<div class="kgtk-kelola-tools">
			<div class="kgtk-search-wrap">
				<span class="kgtk-search-icon">&#128269;</span>
				<input type="search" id="kgtk-undangan-search" class="kgtk-input" placeholder="Cari nama, NIK, No. HP, atau email belajar.id...">
				<button type="button" id="kgtk-undangan-search-clear" class="kgtk-search-clear" style="display:none;">&times;</button>
			</div>
			<div class="kgtk-fbtns" role="group" aria-label="Filter undangan">
				<button type="button" class="kgtk-fbtn is-active" data-filter="all">Semua <span class="kgtk-fcount" id="kgtk-fcount-all"><?php echo (int) $und_stats['total']; ?></span></button>
				<button type="button" class="kgtk-fbtn" data-filter="sudah">Sudah Mendaftar <span class="kgtk-fcount" id="kgtk-fcount-sudah"><?php echo (int) $und_stats['sudah']; ?></span></button>
				<button type="button" class="kgtk-fbtn<?php echo $und_stats['belum'] > 0 ? ' is-warn' : ''; ?>" data-filter="belum">Belum Mendaftar <span class="kgtk-fcount" id="kgtk-fcount-belum"><?php echo (int) $und_stats['belum']; ?></span></button>
				<button type="button" class="kgtk-fbtn<?php echo $und_stats['perlu'] > 0 ? ' is-warn' : ''; ?>" data-filter="perlu">Perlu Konfirmasi <span class="kgtk-fcount" id="kgtk-fcount-perlu"><?php echo (int) $und_stats['perlu']; ?></span></button>
			</div>
		</div>

		<!-- Tabel Undangan -->
		<div class="kgtk-scroll">
			<table class="kgtk-table kgtk-table--kelola" id="kgtk-tbl-undangan">
				<thead>
					<tr>
						<th style="width:46px;text-align:center;">No</th>
						<th style="min-width:170px;">Nama</th>
						<th style="width:140px;">NIK</th>
						<th style="width:130px;">No. HP</th>
						<th style="min-width:180px;">Email Belajar.id</th>
						<th style="width:130px;text-align:center;">Status</th>
						<th style="width:120px;text-align:center;">Dicocokkan Lewat</th>
						<th style="min-width:140px;">Kelas & Peran Peserta</th>
						<th style="width:130px;">Waktu Mendaftar</th>
						<th style="width:110px;text-align:center;">Aksi</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $rows_undangan ) ) : ?>
						<tr class="kgtk-row-empty">
							<td colspan="10" style="text-align:center;padding:36px 12px;" class="kgtk-muted">
								Belum ada peserta ditetapkan untuk pelatihan ini. Klik &ldquo;+ Tambah&rdquo; atau &ldquo;Impor Excel/CSV&rdquo; untuk menambahkan data.
							</td>
						</tr>
					<?php else : ?>
						<?php
						$u_no = 1;
						foreach ( $rows_undangan as $u_item ) {
							echo KGTK_Admin::undangan_row_html( $u_item, $u_no++ );
						}
						?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
