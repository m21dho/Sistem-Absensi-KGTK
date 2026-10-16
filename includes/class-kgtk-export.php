<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageMargins;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Download daftar hadir semua kelas: PDF (satu halaman per kelas) dan Excel (satu sheet per kelas).
 * Endpoint: admin-post.php?action=kgtk_export&format=pdf|xlsx&id=ID&_wpnonce=...
 */
class KGTK_Export {

	public static function init() {
		add_action( 'admin_post_kgtk_export', array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_kgtk_biodata_pdf', array( __CLASS__, 'handle_biodata_pdf' ) );
	}

	public static function libs_ready() {
		return class_exists( '\PhpOffice\PhpSpreadsheet\Spreadsheet' ) && class_exists( '\Dompdf\Dompdf' );
	}

	public static function url( $id, $format, $peran = '', $tanggal = '' ) {
		$args = array(
			'action' => 'kgtk_export',
			'format' => rawurlencode( $format ),
			'id'     => (int) $id,
		);
		$peran = trim( (string) $peran );
		if ( '' !== $peran && 'all' !== strtolower( $peran ) && 'semua' !== strtolower( $peran ) ) {
			$args['peran'] = $peran;
		}
		$tanggal = trim( (string) $tanggal );
		if ( '' !== $tanggal && 'all' !== strtolower( $tanggal ) && 'semua' !== strtolower( $tanggal ) ) {
			$args['tanggal'] = $tanggal;
		}
		return wp_nonce_url(
			add_query_arg( $args, admin_url( 'admin-post.php' ) ),
			'kgtk_export_' . (int) $id
		);
	}

	public static function biodata_pdf_url( $id ) {
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=kgtk_biodata_pdf&id=' . (int) $id ),
			'kgtk_biodata_pdf_' . (int) $id
		);
	}

	public static function handle_biodata_pdf() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Anda tidak memiliki akses.', 'Akses ditolak', array( 'response' => 403 ) );
		}
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'kgtk_biodata_pdf_' . $id );

		global $wpdb;
		$tp = KGTK_DB::table( 'peserta' );
		$tk = KGTK_DB::table( 'kelas' );
		$peserta = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT ps.*, COALESCE(k.nama, '') AS kelas_label
				 FROM {$tp} ps
				 LEFT JOIN {$tk} k ON k.id = ps.kelas_id
				 WHERE ps.id = %d",
				$id
			)
		);
		if ( ! $peserta ) {
			wp_die( 'Peserta tidak ditemukan.', 'Tidak ditemukan', array( 'response' => 404 ) );
		}
		$pelatihan = KGTK_Helpers::get_by_id( $peserta->pelatihan_id );
		if ( ! $pelatihan ) {
			wp_die( 'Pelatihan tidak ditemukan.', 'Tidak ditemukan', array( 'response' => 404 ) );
		}
		self::download_biodata_pdf( $peserta, $pelatihan );
	}

	public static function handle() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Anda tidak memiliki akses.', 'Akses ditolak', array( 'response' => 403 ) );
		}
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'kgtk_export_' . $id );

		$format = isset( $_GET['format'] ) ? sanitize_key( wp_unslash( $_GET['format'] ) ) : '';
		if ( ! in_array( $format, array( 'pdf', 'xlsx' ), true ) ) {
			wp_die( 'Format tidak dikenal.', 'Permintaan tidak valid', array( 'response' => 400 ) );
		}
		if ( ! self::libs_ready() ) {
			wp_die(
				'Library PDF/Excel belum terpasang. Jalankan <code>composer install --no-dev</code> di folder plugin KGTK Pelatihan (lihat README.md).',
				'Library belum terpasang',
				array( 'response' => 500 )
			);
		}
		$data = KGTK_Helpers::absen_data( $id );
		if ( ! $data ) {
			wp_die( 'Pelatihan tidak ditemukan.', 'Tidak ditemukan', array( 'response' => 404 ) );
		}

		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			wp_raise_memory_limit( 'admin' );
		}
		@set_time_limit( 180 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors

		$peran_filter = isset( $_GET['peran'] ) ? sanitize_text_field( wp_unslash( $_GET['peran'] ) ) : '';
		$peran_filter = trim( $peran_filter );
		$is_filtered  = ( '' !== $peran_filter && 'all' !== strtolower( $peran_filter ) && 'semua' !== strtolower( $peran_filter ) );

		$tanggal_filter = isset( $_GET['tanggal'] ) ? sanitize_text_field( wp_unslash( $_GET['tanggal'] ) ) : '';
		$tanggal_filter = trim( $tanggal_filter );
		$has_tanggal    = ( '' !== $tanggal_filter && 'all' !== strtolower( $tanggal_filter ) && 'semua' !== strtolower( $tanggal_filter ) );

		$suffix = '';
		if ( $is_filtered ) {
			$suffix .= '-' . sanitize_title( $peran_filter );
		}
		if ( 'all_days' === $tanggal_filter ) {
			$suffix .= '-semua-hari';
		} elseif ( 'range' === $tanggal_filter ) {
			$suffix .= '-rentang';
		} elseif ( $has_tanggal ) {
			$suffix .= '-' . sanitize_title( $tanggal_filter );
		}

		$base = sanitize_file_name( 'absen-' . strtolower( $data['pelatihan']->kode ) . $suffix );
		if ( 'pdf' === $format ) {
			self::pdf( $data, $base . '.pdf', $peran_filter, $tanggal_filter );
		} else {
			self::xlsx( $data, $base . '.xlsx', $peran_filter, $tanggal_filter );
		}
		exit;
	}

	/** Judul sheet Excel: maks 31 karakter, tanpa karakter terlarang, unik. Akhiran (peran) dipertahankan. */
	private static function sheet_title( $label, $suffix, array &$used ) {
		$bad   = array( '\\', '/', '?', '*', '[', ']', ':' );
		$clean = function ( $x ) use ( $bad ) {
			return trim( str_replace( $bad, ' ', (string) $x ), " '" );
		};
		$label  = $clean( $label );
		$suffix = $clean( $suffix );
		$suffix = '' === $suffix ? '' : ' ' . $suffix;
		$room   = max( 3, 28 - ( function_exists( 'mb_strlen' ) ? mb_strlen( $suffix, 'UTF-8' ) : strlen( $suffix ) ) );
		$label  = function_exists( 'mb_substr' ) ? mb_substr( $label, 0, $room, 'UTF-8' ) : substr( $label, 0, $room );
		$t      = trim( $label . $suffix );
		if ( '' === $t ) {
			$t = 'Kelas';
		}
		$base = $t;
		$n    = 2;
		while ( isset( $used[ strtolower( $t ) ] ) ) {
			$t = $base . ' ' . $n++;
		}
		$used[ strtolower( $t ) ] = true;
		return $t;
	}

	private static function clean_buffers() {
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}
	}

	/* ====================== Excel ====================== */

	/**
	 * Satu sheet per kelas per peran. Isi mengikuti formulir daftar hadir: tiap halaman berisi
	 * judul, kegiatan, tabel 15 baris (No, Nama, Instansi, Tanda Tangan), dan blok penanggung jawab.
	 */
	private static function xlsx( $data, $filename, $peran_filter = '', $tanggal_filter = '' ) {
		$p            = $data['pelatihan'];
		$daftar_hari  = KGTK_Helpers::daftar_hari_pelatihan( $p->tanggal_mulai, $p->tanggal_selesai );
		$is_multi_day = count( $daftar_hari ) > 1;

		$mode_all_days = ( 'all_days' === $tanggal_filter || ( empty( $tanggal_filter ) && $is_multi_day ) );

		$ss = new Spreadsheet();
		$ss->getDefaultStyle()->getFont()->setName( 'Times New Roman' );
		$title_suffix = ( '' !== $peran_filter && 'all' !== strtolower( $peran_filter ) && 'semua' !== strtolower( $peran_filter ) ) ? ' - ' . ucwords( strtolower( $peran_filter ) ) : '';
		if ( $is_multi_day && $mode_all_days ) {
			$title_suffix .= ' - Semua Hari';
		} elseif ( 'range' === $tanggal_filter ) {
			$title_suffix .= ' - ' . KGTK_Helpers::rentang_tanggal( $p->tanggal_mulai, $p->tanggal_selesai );
		} elseif ( '' !== $tanggal_filter && 'all' !== strtolower( $tanggal_filter ) && 'semua' !== strtolower( $tanggal_filter ) ) {
			$title_suffix .= ' - ' . KGTK_Helpers::format_hari_tanggal( $tanggal_filter );
		}
		$ss->getProperties()->setTitle( 'Daftar Hadir ' . $p->nama . $title_suffix );
		$used = array();

		// Susun jobs lembar yang akan dibuat
		$ws_jobs = array();
		$sheets  = KGTK_Helpers::absen_sheets( $data, $peran_filter );
		if ( $is_multi_day && $mode_all_days ) {
			foreach ( $sheets as $sh ) {
				foreach ( $daftar_hari as $dh ) {
					$ws_jobs[] = array(
						'sh'      => $sh,
						'tanggal' => $dh['ymd'],
						'suffix'  => 'H' . $dh['hari_ke'],
					);
				}
			}
		} else {
			$tgl_single = ( 'range' === $tanggal_filter ) ? '' : $tanggal_filter;
			foreach ( $sheets as $sh ) {
				$ws_jobs[] = array(
					'sh'      => $sh,
					'tanggal' => $tgl_single,
					'suffix'  => '',
				);
			}
		}

		foreach ( $ws_jobs as $i => $job ) {
			$sh    = $job['sh'];
			$k     = $sh['kelas'];
			$peran = $sh['peran'];
			$tgl_j = $job['tanggal'];

			$ws = 0 === $i ? $ss->getActiveSheet() : $ss->createSheet();
			$title_part = '' !== $peran ? '- ' . $peran : '';
			if ( '' !== $job['suffix'] ) {
				$title_part .= ' ' . $job['suffix'];
			}
			$ws->setTitle( self::sheet_title( $k->label, $title_part, $used ) );

			$ws->getColumnDimension( 'A' )->setWidth( 6 );
			$ws->getColumnDimension( 'B' )->setWidth( 32 );
			$ws->getColumnDimension( 'C' )->setWidth( 38 );
			$ws->getColumnDimension( 'D' )->setWidth( 14 );
			$ws->getColumnDimension( 'E' )->setWidth( 14 );

			$r           = 1;
			$pages       = KGTK_Helpers::halaman( $sh['rows'], $sh['jumlah'] );
			$total_pages = count( $pages );

			foreach ( $pages as $pi => $halaman ) {
				$is_last = ( $pi === $total_pages - 1 );

				if ( 0 === $pi ) {
					// Kop surat Excel
					$ws->setCellValueExplicit( 'A' . $r, 'Kementerian Pendidikan Dasar dan Menengah', DataType::TYPE_STRING );
					$ws->mergeCells( 'A' . $r . ':E' . $r );
					$ws->getStyle( 'A' . $r )->getFont()->setBold( true )->setSize( 12 )->getColor()->setRGB( '0083C4' );
					$ws->getStyle( 'A' . $r )->getAlignment()->setHorizontal( Alignment::HORIZONTAL_CENTER );
					$r++;

					$ws->setCellValueExplicit( 'A' . $r, 'Kantor Guru dan Tenaga Kependidikan Provinsi Kepulauan Riau', DataType::TYPE_STRING );
					$ws->mergeCells( 'A' . $r . ':E' . $r );
					$ws->getStyle( 'A' . $r )->getFont()->setBold( true )->setSize( 10 );
					$ws->getStyle( 'A' . $r )->getAlignment()->setHorizontal( Alignment::HORIZONTAL_CENTER );
					$r++;

					$ws->setCellValueExplicit( 'A' . $r, 'Jalan Tata Bumi Km. 20, Ceruk Ijuk, Toapaya, Bintan, 29151', DataType::TYPE_STRING );
					$ws->mergeCells( 'A' . $r . ':E' . $r );
					$ws->getStyle( 'A' . $r )->getFont()->setSize( 9 )->getColor()->setRGB( '555555' );
					$ws->getStyle( 'A' . $r )->getAlignment()->setHorizontal( Alignment::HORIZONTAL_CENTER );
					$r++;

					$ws->setCellValueExplicit( 'A' . $r, 'www.kemendikdasmen.go.id | 08136798163 | 177', DataType::TYPE_STRING );
					$ws->mergeCells( 'A' . $r . ':E' . $r );
					$ws->getStyle( 'A' . $r )->getFont()->setSize( 9 )->getColor()->setRGB( '555555' );
					$ws->getStyle( 'A' . $r )->getAlignment()->setHorizontal( Alignment::HORIZONTAL_CENTER );
					$r += 2;

					// Judul dan baris kegiatan. Semua teks disimpan eksplisit sebagai string (mencegah formula injection).
					$ws->setCellValueExplicit( 'A' . $r, KGTK_Helpers::judul_absen( $peran ), DataType::TYPE_STRING );
					$ws->mergeCells( 'A' . $r . ':E' . $r );
					$ws->getStyle( 'A' . $r )->getFont()->setBold( true )->setSize( 14 );
					$ws->getStyle( 'A' . $r )->getAlignment()->setHorizontal( Alignment::HORIZONTAL_CENTER );
					$ws->getRowDimension( $r )->setRowHeight( 28 );
					$r++;

					foreach ( KGTK_Helpers::baris_info_array( $p, $tgl_j ) as $info_line ) {
						$ws->setCellValueExplicit( 'A' . $r, $info_line, DataType::TYPE_STRING );
						$ws->mergeCells( 'A' . $r . ':E' . $r );
						$ws->getStyle( 'A' . $r )->getFont()->setSize( 12 );
						$ws->getStyle( 'A' . $r )->getAlignment()->setHorizontal( Alignment::HORIZONTAL_CENTER )->setVertical( Alignment::VERTICAL_CENTER );
						$ws->getRowDimension( $r )->setRowHeight( 22 );
						$r++;
					}

					if ( $sh['tampil_kelas'] ) {
						$ws->setCellValueExplicit( 'A' . $r, (string) $k->label, DataType::TYPE_STRING );
						$ws->mergeCells( 'A' . $r . ':E' . $r );
						$ws->getStyle( 'A' . $r )->getFont()->setBold( true )->setSize( 12 );
						$ws->getStyle( 'A' . $r )->getAlignment()->setHorizontal( Alignment::HORIZONTAL_CENTER );
						$r++;
					}
					$r++; // baris kosong
				}

				// Header tabel tanpa warna biru.
				$hr = $r;
				$ws->setCellValueExplicit( 'A' . $hr, 'No', DataType::TYPE_STRING );
				$ws->setCellValueExplicit( 'B' . $hr, 'Nama', DataType::TYPE_STRING );
				$ws->setCellValueExplicit( 'C' . $hr, 'Instansi', DataType::TYPE_STRING );
				$ws->setCellValueExplicit( 'D' . $hr, 'Tanda Tangan', DataType::TYPE_STRING );
				$ws->mergeCells( 'D' . $hr . ':E' . $hr );

				$ws->getStyle( 'A' . $hr . ':E' . $hr )->getFont()->setBold( true )->setSize( 12 )->getColor()->setRGB( '000000' );
				$ws->getStyle( 'A' . $hr . ':E' . $hr )->getFill()->setFillType( 'none' );
				$ws->getStyle( 'A' . $hr . ':E' . $hr )->getAlignment()->setHorizontal( Alignment::HORIZONTAL_CENTER )->setVertical( Alignment::VERTICAL_CENTER );
				$ws->getRowDimension( $hr )->setRowHeight( 26 );
				$r++;

				$first = $r;
				for ( $n = 0; $n < $halaman['jumlah']; $n++ ) {
					$no_urutan = ( $halaman['mulai'] + $n + 1 );
					$is_odd    = ( $no_urutan % 2 !== 0 );
					$ws->setCellValueExplicit( 'A' . $r, (string) $no_urutan, DataType::TYPE_STRING );
					if ( isset( $halaman['rows'][ $n ] ) ) {
						$ws->setCellValueExplicit( 'B' . $r, (string) $halaman['rows'][ $n ]->nama, DataType::TYPE_STRING );
						$ws->setCellValueExplicit( 'C' . $r, (string) $halaman['rows'][ $n ]->sekolah, DataType::TYPE_STRING );
					}
					if ( $is_odd ) {
						$ws->setCellValueExplicit( 'D' . $r, $no_urutan . '.', DataType::TYPE_STRING );
						$ws->setCellValueExplicit( 'E' . $r, '', DataType::TYPE_STRING );
					} else {
						$ws->setCellValueExplicit( 'D' . $r, '', DataType::TYPE_STRING );
						$ws->setCellValueExplicit( 'E' . $r, $no_urutan . '.', DataType::TYPE_STRING );
					}
					$ws->getRowDimension( $r )->setRowHeight( 30 );
					$r++;
				}
				$range = 'A' . $hr . ':E' . ( $r - 1 );
				$ws->getStyle( $range )->getBorders()->getAllBorders()->setBorderStyle( Border::BORDER_THIN );
				$ws->getStyle( 'A' . $first . ':E' . ( $r - 1 ) )->getFont()->setSize( 12 );
				$ws->getStyle( 'A' . $first . ':E' . ( $r - 1 ) )->getAlignment()->setVertical( Alignment::VERTICAL_CENTER );
				$ws->getStyle( 'A' . $first . ':A' . ( $r - 1 ) )->getAlignment()->setHorizontal( Alignment::HORIZONTAL_CENTER );
				$ws->getStyle( 'D' . $first . ':E' . ( $r - 1 ) )->getAlignment()->setHorizontal( Alignment::HORIZONTAL_LEFT );
				$r++; // baris kosong

				// Blok penanggung jawab (hanya muncul di halaman terakhir lembar).
				if ( $is_last ) {
					$ws->setCellValueExplicit( 'D' . $r, KGTK_Helpers::label_pj( $p ), DataType::TYPE_STRING );
					$ws->mergeCells( 'D' . $r . ':E' . $r );
					$ws->getStyle( 'D' . $r )->getFont()->setItalic( true )->setSize( 12 );
					$ws->getStyle( 'D' . $r )->getAlignment()->setHorizontal( Alignment::HORIZONTAL_CENTER );
					$r += 4; // ruang tanda tangan
					$ws->setCellValueExplicit( 'D' . $r, KGTK_Helpers::nama_pj( $p ), DataType::TYPE_STRING );
					$ws->mergeCells( 'D' . $r . ':E' . $r );
					$ws->getStyle( 'D' . $r )->getFont()->setBold( true )->setSize( 12 );
					$ws->getStyle( 'D' . $r )->getAlignment()->setHorizontal( Alignment::HORIZONTAL_CENTER );
				}

				// Halaman berikutnya mulai di halaman cetak baru.
				if ( $pi < count( $pages ) - 1 ) {
					$ws->setBreak( 'A' . $r, Worksheet::BREAK_ROW );
				}
				$r += 2;
			}

			$ws->getPageSetup()->setPaperSize( PageSetup::PAPERSIZE_A4 );
			$ws->getPageSetup()->setOrientation( PageSetup::ORIENTATION_PORTRAIT );
			$ws->getPageSetup()->setFitToPage( true );
			$ws->getPageSetup()->setFitToWidth( 1 );
			$ws->getPageSetup()->setFitToHeight( 0 );

			// Margin: Atas 0 cm, Bawah 0 cm, Kiri 1.0 cm, Kanan 1.0 cm, Header 0.51 cm, Footer 0.51 cm.
			$ws->getPageMargins()->setTop( 0 );
			$ws->getPageMargins()->setBottom( 0 );
			$ws->getPageMargins()->setLeft( PageMargins::fromCentimeters( 1.0 ) );
			$ws->getPageMargins()->setRight( PageMargins::fromCentimeters( 1.0 ) );
			$ws->getPageMargins()->setHeader( 0.2 );
			$ws->getPageMargins()->setFooter( 0.2 );
		}
		$ss->setActiveSheetIndex( 0 );

		self::clean_buffers();
		nocache_headers();
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		$writer = new Xlsx( $ss );
		$writer->save( 'php://output' );
		$ss->disconnectWorksheets();
	}

	/* ====================== PDF ====================== */

	private static function pdf( $data, $filename, $peran_filter = '', $tanggal_filter = '' ) {
		$p            = $data['pelatihan'];
		$daftar_hari  = KGTK_Helpers::daftar_hari_pelatihan( $p->tanggal_mulai, $p->tanggal_selesai );
		$is_multi_day = count( $daftar_hari ) > 1;

		$mode_all_days = ( 'all_days' === $tanggal_filter || ( empty( $tanggal_filter ) && $is_multi_day ) );

		$title_suffix = ( '' !== $peran_filter && 'all' !== strtolower( $peran_filter ) && 'semua' !== strtolower( $peran_filter ) ) ? ' ' . ucwords( strtolower( $peran_filter ) ) : '';
		if ( $is_multi_day && $mode_all_days ) {
			$title_suffix .= ' - Semua Hari';
		} elseif ( 'range' === $tanggal_filter ) {
			$title_suffix .= ' - ' . KGTK_Helpers::rentang_tanggal( $p->tanggal_mulai, $p->tanggal_selesai );
		} elseif ( '' !== $tanggal_filter && 'all' !== strtolower( $tanggal_filter ) && 'semua' !== strtolower( $tanggal_filter ) ) {
			$title_suffix .= ' - ' . KGTK_Helpers::format_hari_tanggal( $tanggal_filter );
		}
		$html         = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Daftar Hadir' . esc_html( $title_suffix ) . ' - ' . esc_html( $p->nama ) . '</title><style>
@page { margin: 0.51cm 1.0cm 0.51cm 1.0cm; }
body { font-family: "Times New Roman", Times, "Times-Roman", serif; font-size: 12pt; color: #000; }
.kgtk-kop-wrap { width: 100%; text-align: center; margin: 0 auto 16px; }
.kgtk-kop-tbl { width: 100%; max-width: 760px; margin: 0 auto; border-collapse: collapse; border: 0; }
.kgtk-kop-tbl td { border: 0 !important; vertical-align: middle; }
.page { page-break-after: always; }
.page.last { page-break-after: auto; }
h1 { text-align: center; font-size: 14pt; font-weight: bold; letter-spacing: 1px; margin: 0 0 6px; }
.kgtk-doc-info { text-align: center; margin: 6px 0 12px; font-size: 12pt; line-height: 1.4; }
.kgtk-doc-info div { font-size: 12pt; }
p.info { text-align: center; margin: 0 0 4px; font-size: 12pt; }
p.kelas { text-align: center; margin: 0 0 4px; font-size: 12pt; font-weight: bold; }
table.abs { width: 100%; border-collapse: collapse; margin-top: 14px; margin-bottom: 18px; table-layout: fixed; }
table.abs th, table.abs td { border: 1px solid #000; padding: 0 8px; height: 33px; font-size: 12pt; }
table.abs th { background: none; color: #000; text-align: center; height: 26px; font-size: 12pt; font-weight: bold; }
th.no, td.no { text-align: center; width: 7%; font-size: 12pt; padding: 0 2px; }
th.nama, td.nama { width: 30%; font-size: 12pt; padding: 0 6px; }
th.inst, td.inst { width: 37%; font-size: 12pt; padding: 0 6px; }
th.ttd { width: 26%; white-space: nowrap; text-align: center; }
td.ttd-col { width: 13%; text-align: left; vertical-align: middle; padding-left: 6px; font-size: 11pt; }
table.ttd { width: 100%; margin-top: 18px; border-collapse: collapse; }
table.ttd td { border: 0; vertical-align: top; }
td.ttd-box { width: 250px; text-align: center; font-size: 12pt; }
div.space { height: 62px; }
</style></head><body>';

		$items  = array();
		$sheets = KGTK_Helpers::absen_sheets( $data, $peran_filter );

		if ( $is_multi_day && $mode_all_days ) {
			foreach ( $sheets as $sh ) {
				foreach ( $daftar_hari as $dh ) {
					$pages       = KGTK_Helpers::halaman( $sh['rows'], $sh['jumlah'] );
					$total_pages = count( $pages );
					foreach ( $pages as $pi => $halaman ) {
						$items[] = array(
							'sh'         => $sh,
							'halaman'    => $halaman,
							'is_last'    => ( $pi === $total_pages - 1 ),
							'page_index' => $pi,
							'tanggal'    => $dh['ymd'],
						);
					}
				}
			}
		} else {
			$tgl_single = ( 'range' === $tanggal_filter ) ? '' : $tanggal_filter;
			foreach ( $sheets as $sh ) {
				$pages       = KGTK_Helpers::halaman( $sh['rows'], $sh['jumlah'] );
				$total_pages = count( $pages );
				foreach ( $pages as $pi => $halaman ) {
					$items[] = array(
						'sh'         => $sh,
						'halaman'    => $halaman,
						'is_last'    => ( $pi === $total_pages - 1 ),
						'page_index' => $pi,
						'tanggal'    => $tgl_single,
					);
				}
			}
		}

		$total = count( $items );
		foreach ( $items as $idx => $it ) {
			$sh    = $it['sh'];
			$html .= '<div class="page' . ( $idx === $total - 1 ? ' last' : '' ) . '">';
			if ( 0 === $it['page_index'] ) {
				$html .= KGTK_Helpers::kop_html();
				$html .= '<h1>' . esc_html( KGTK_Helpers::judul_absen( $sh['peran'] ) ) . '</h1>';
				$html .= KGTK_Helpers::baris_info_html( $p, $it['tanggal'] );
				if ( $sh['tampil_kelas'] ) {
					$html .= '<p class="kelas">' . esc_html( $sh['kelas']->label ) . '</p>';
				}
			}
			$html .= '<table class="abs">';
			$html .= '<colgroup><col style="width:7%;"><col style="width:30%;"><col style="width:37%;"><col style="width:13%;"><col style="width:13%;"></colgroup>';
			$html .= '<thead><tr><th class="no">No</th><th class="nama">Nama</th><th class="inst">Instansi</th><th class="ttd" colspan="2" style="white-space:nowrap;text-align:center;">Tanda Tangan</th></tr></thead><tbody>';
			for ( $n = 0; $n < $it['halaman']['jumlah']; $n++ ) {
				$row       = isset( $it['halaman']['rows'][ $n ] ) ? $it['halaman']['rows'][ $n ] : null;
				$no_urutan = $it['halaman']['mulai'] + $n + 1;
				$is_odd    = ( $no_urutan % 2 !== 0 );
				$html     .= '<tr><td class="no">' . $no_urutan . '</td><td class="nama">' . ( $row ? esc_html( $row->nama ) : '&nbsp;' )
					. '</td><td class="inst">' . ( $row ? esc_html( $row->sekolah ) : '&nbsp;' ) . '</td>';
				if ( $is_odd ) {
					$html .= '<td class="ttd-col">' . $no_urutan . '.</td><td class="ttd-col">&nbsp;</td>';
				} else {
					$html .= '<td class="ttd-col">&nbsp;</td><td class="ttd-col">' . $no_urutan . '.</td>';
				}
				$html .= '</tr>';
			}
			$html .= '</tbody></table>';

			if ( $it['is_last'] ) {
				$html .= '<table class="ttd"><tr><td>&nbsp;</td><td class="ttd-box"><em>' . esc_html( KGTK_Helpers::label_pj( $p ) ) . '</em><div class="space">&nbsp;</div><strong>' . esc_html( KGTK_Helpers::nama_pj( $p ) ) . '</strong></td></tr></table>';
			}
			$html .= '</div>';
		}
		$html .= '</body></html>';

		$opt = new Options();
		$opt->set( 'isRemoteEnabled', false );
		$opt->set( 'defaultFont', 'Times-Roman' );
		$pdf = new Dompdf( $opt );
		$pdf->loadHtml( $html, 'UTF-8' );
		$pdf->setPaper( 'A4', 'portrait' );
		$pdf->render();

		self::clean_buffers();
		nocache_headers();
		$pdf->stream( $filename, array( 'Attachment' => true ) );
	}

	/** Download PDF Biodata Peserta per Pelatihan sesuai template resmi. */
	public static function download_biodata_pdf( $peserta, $pelatihan ) {
		if ( ! self::libs_ready() ) {
			wp_die( 'Library PDF belum terpasang.', 'Error', array( 'response' => 500 ) );
		}

		$nama_file = sanitize_file_name( 'Biodata-' . $peserta->nama . '-' . $pelatihan->kode . '.pdf' );

		$peran   = ! empty( $peserta->peran ) ? $peserta->peran : 'PESERTA';
		$kabkota = ! empty( $peserta->kabkota ) && 'Lainnya' !== $peserta->kabkota ? $peserta->kabkota : 'Bintan';
		$tgl_ttd = KGTK_Helpers::format_tanggal( $pelatihan->tanggal_mulai );
		$lokasi  = ! empty( $pelatihan->lokasi ) ? $pelatihan->lokasi : 'Kepulauan Riau';

		$html  = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>';
		/* Margin disamakan dengan cetak absen: Atas 0.51cm, Bawah 0.51cm, Kiri 1.0cm, Kanan 1.0cm */
		$html .= '@page { margin: 0.51cm 1.0cm 0.51cm 1.0cm; }';
		$html .= 'body { font-family: "Times New Roman", Times, "Times-Roman", serif; font-size: 12pt; color: #000; line-height: 1.35; }';
		$html .= '.kgtk-kop-wrap { width: 100%; text-align: center; margin: 0 auto 16px; }';
		$html .= '.kgtk-kop-tbl { width: 100%; max-width: 760px; margin: 0 auto; border-collapse: collapse; border: 0; }';
		$html .= '.kgtk-kop-tbl td { border: 0 !important; vertical-align: middle; }';
		$html .= '.title-box { text-align: center; margin: 8pt 0; }';
		$html .= '.title-main { font-size: 16pt; font-weight: bold; text-transform: uppercase; margin-bottom: 3px; }';
		$html .= '.title-sub { font-size: 14pt; font-weight: bold; margin-bottom: 2px; }';
		$html .= '.title-date { font-size: 14pt; margin-bottom: 2px; }';
		$html .= '.title-loc { font-size: 14pt; margin-bottom: 14pt; }';
		$html .= 'table.bio-box { width: 100%; border-collapse: collapse; border: 1.5px solid #000; margin-bottom: 18px; }';
		$html .= 'table.bio-box td { padding: 4px 8px; font-size: 12pt; vertical-align: top; border: 0; }';
		$html .= 'td.lbl { width: 36%; }';
		$html .= 'td.sep { width: 4%; text-align: center; }';
		$html .= 'td.val { width: 60%; }';
		$html .= 'table.ttd-tbl { width: 100%; border-collapse: collapse; margin-top: 10px; }';
		$html .= 'table.ttd-tbl td { border: 0; vertical-align: top; }';
		$html .= 'td.ttd-right { width: 250px; text-align: center; font-size: 12pt; }';
		$html .= '.space { height: 60px; }';
		$html .= '</style></head><body>';

		$html .= KGTK_Helpers::kop_html();

		$html .= '<div class="title-box">';
		$html .= '<div class="title-main">BIODATA ' . esc_html( strtoupper( $peran ) ) . '</div>';
		$html .= '<div class="title-sub">' . esc_html( $pelatihan->nama ) . '</div>';
		$lok_txt = 0 === strpos( strtolower( trim( $lokasi ) ), 'di ' ) ? $lokasi : 'di ' . $lokasi;
		$html .= '<div class="title-loc">' . esc_html( $lok_txt ) . '</div>';
		$html .= '</div>';

		$tgl_lahir_fmt = ! empty( $peserta->tanggal_lahir ) && '-' !== $peserta->tanggal_lahir ? KGTK_Helpers::format_tanggal( $peserta->tanggal_lahir ) : '';
		$ttl_parts     = array();
		if ( ! empty( $peserta->tempat_lahir ) && '-' !== $peserta->tempat_lahir ) {
			$ttl_parts[] = $peserta->tempat_lahir;
		}
		if ( ! empty( $tgl_lahir_fmt ) ) {
			$ttl_parts[] = $tgl_lahir_fmt;
		}
		$ttl           = ! empty( $ttl_parts ) ? implode( ', ', $ttl_parts ) : '-';
		$nip           = ! empty( $peserta->nip ) ? $peserta->nip : '-';
		$nuptk         = ! empty( $peserta->nuptk ) ? $peserta->nuptk : ( ! empty( $peserta->nisn ) ? $peserta->nisn : '-' );
		$sekolah       = ! empty( $peserta->sekolah ) ? $peserta->sekolah : '-';
		$kabkota_val   = ! empty( $peserta->kabkota ) && 'Lainnya' !== $peserta->kabkota ? $peserta->kabkota : '-';
		$jabatan       = ! empty( $peserta->jenis_gtk ) ? $peserta->jenis_gtk : '-';
		$hp            = ! empty( $peserta->hp ) ? $peserta->hp : '-';
		$bank          = ! empty( $peserta->bank ) ? $peserta->bank : '-';
		$rekening      = ! empty( $peserta->rekening ) ? $peserta->rekening : '-';
		$email         = ! empty( $peserta->email ) ? $peserta->email : ( ! empty( $peserta->email_belajar ) ? $peserta->email_belajar : '-' );
		$simpkb        = ! empty( $peserta->simpkb ) ? $peserta->simpkb : ( ! empty( $peserta->email_guru ) ? $peserta->email_guru : '-' );

		$html .= '<table class="bio-box">';
		$html .= '<tr><td class="lbl">Nama lengkap</td><td class="sep">:</td><td class="val">' . esc_html( $peserta->nama ) . '</td></tr>';
		$html .= '<tr><td class="lbl">Tempat, Tanggal Lahir</td><td class="sep">:</td><td class="val">' . esc_html( $ttl ) . '</td></tr>';
		$html .= '<tr><td class="lbl">NUPTK</td><td class="sep">:</td><td class="val">' . esc_html( $nuptk ) . '</td></tr>';
		$html .= '<tr><td class="lbl">NIP</td><td class="sep">:</td><td class="val">' . esc_html( $nip ) . '</td></tr>';
		$html .= '<tr><td class="lbl">Instansi</td><td class="sep">:</td><td class="val">' . esc_html( $sekolah ) . '</td></tr>';
		$html .= '<tr><td class="lbl">Kabupaten/Kota</td><td class="sep">:</td><td class="val">' . esc_html( $kabkota_val ) . '</td></tr>';
		$html .= '<tr><td class="lbl">Jabatan</td><td class="sep">:</td><td class="val">' . esc_html( $jabatan ) . '</td></tr>';
		$html .= '<tr><td class="lbl">No. HP/WhatsApp</td><td class="sep">:</td><td class="val">' . esc_html( $hp ) . '</td></tr>';
		$html .= '<tr><td class="lbl">Nama Bank</td><td class="sep">:</td><td class="val">' . esc_html( $bank ) . '</td></tr>';
		$html .= '<tr><td class="lbl">Nomor Rekening</td><td class="sep">:</td><td class="val">' . esc_html( $rekening ) . '</td></tr>';
		$html .= '<tr><td class="lbl">Email</td><td class="sep">:</td><td class="val">' . esc_html( $email ) . '</td></tr>';
		$html .= '<tr><td class="lbl">SIMPKB</td><td class="sep">:</td><td class="val">' . esc_html( $simpkb ) . '</td></tr>';
		$html .= '</table>';

		$html .= '<table class="ttd-tbl">';
		$html .= '<tr><td>&nbsp;</td><td class="ttd-right">';
		$html .= '<div>' . esc_html( $kabkota ) . ', ' . esc_html( $tgl_ttd ) . '</div>';
		$html .= '<div class="space">&nbsp;</div>';
		$html .= '<div><u><strong>' . esc_html( $peserta->nama ) . '</strong></u></div>';
		$html .= '</td></tr></table>';

		$html .= '</body></html>';

		$opt = new Options();
		$opt->set( 'isRemoteEnabled', false );
		$opt->set( 'defaultFont', 'Times-Roman' );
		$pdf = new Dompdf( $opt );
		$pdf->loadHtml( $html, 'UTF-8' );
		$pdf->setPaper( 'A4', 'portrait' );
		$pdf->render();

		self::clean_buffers();
		nocache_headers();
		$pdf->stream( $nama_file, array( 'Attachment' => true ) );
		exit;
	}
}
