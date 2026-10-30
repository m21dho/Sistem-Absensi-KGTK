<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
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

	const DEFAULT_PROVINSI    = 'Kepulauan Riau';
	const STATUS_VERIFIED     = 'Terverifikasi';
	const PLACEHOLDER_EMPTY   = '-';

	const BULAN_SINGKAT = array(
		'01' => 'Jan',
		'02' => 'Feb',
		'03' => 'Mar',
		'04' => 'Apr',
		'05' => 'Mei',
		'06' => 'Jun',
		'07' => 'Jul',
		'08' => 'Agu',
		'09' => 'Sep',
		'10' => 'Okt',
		'11' => 'Nov',
		'12' => 'Des',
	);

	const JENJANG_PATTERNS = array(
		'SMK'  => '/\b(SMK|SMKN)\b/i',
		'SMA'  => '/\b(SMA|SMAN)\b/i',
		'SMP'  => '/\b(SMP|SMPN)\b/i',
		'SD'   => '/\b(SD|SDN)\b/i',
		'SLB'  => '/\b(SLB|SLBN)\b/i',
		'TK'   => '/\b(TK|TKN)\b/i',
		'PAUD' => '/\b(PAUD)\b/i',
	);

	const DATA_GURU_HEADERS = array(
		'Nama',
		'NIP/NIK',
		'Tempat',
		'Tanggal Lahir',
		'Jenis Kelamin',
		'NPSN',
		'NUPTK',
		'Instansi',
		'Alamat Instansi',
		'Kabupaten/Kota',
		'Alamat Rumah',
		'Jenjang',
		'Pangkat/Golongan',
		'Agama',
		'Pendidikan Terakhir',
		'Jabatan',
		'Pengelaman Jabatan',
		'Nama BANK',
		'Nomor Rekening',
		'NPWP',
		'Pengalaman Jabatan ',
		'NO HP',
		'Email ',
		'Email Belajar ID',
		'SIMPKB',
		'Provinsi',
		'Nomor HP',
		'Status',
	);

	public static function init() {
		add_action( 'admin_post_kgtk_export', array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_kgtk_biodata_pdf', array( __CLASS__, 'handle_biodata_pdf' ) );
		add_action( 'admin_post_kgtk_semua_biodata_pdf', array( __CLASS__, 'handle_semua_biodata_pdf' ) );
		add_action( 'admin_post_kgtk_tanda_terima_pdf', array( __CLASS__, 'handle_tanda_terima_pdf' ) );
		add_action( 'admin_post_kgtk_tanda_terima_atk_pdf', array( __CLASS__, 'handle_tanda_terima_atk_pdf' ) );
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

	public static function semua_biodata_pdf_url( $id, $peran = '', $kelas_id = 0, $ids = '', $download = false ) {
		$args = array(
			'action' => 'kgtk_semua_biodata_pdf',
			'id'     => (int) $id,
		);
		$peran = trim( (string) $peran );
		if ( '' !== $peran && 'all' !== strtolower( $peran ) && 'semua' !== strtolower( $peran ) ) {
			$args['peran'] = $peran;
		}
		if ( (int) $kelas_id > 0 ) {
			$args['kelas_id'] = (int) $kelas_id;
		}
		$ids = trim( (string) $ids );
		if ( '' !== $ids ) {
			$args['ids'] = $ids;
		}
		if ( $download ) {
			$args['dl'] = '1';
		}
		return wp_nonce_url(
			add_query_arg( $args, admin_url( 'admin-post.php' ) ),
			'kgtk_semua_biodata_pdf_' . (int) $id
		);
	}

	public static function tanda_terima_pdf_url( $id, $barang = 'Baju', $kelas_id = 0 ) {
		$args = array(
			'action' => 'kgtk_tanda_terima_pdf',
			'id'     => (int) $id,
		);
		$barang = trim( (string) $barang );
		if ( '' !== $barang ) {
			$args['barang'] = $barang;
		}
		if ( (int) $kelas_id > 0 ) {
			$args['kelas_id'] = (int) $kelas_id;
		}
		return wp_nonce_url(
			add_query_arg( $args, admin_url( 'admin-post.php' ) ),
			'kgtk_tanda_terima_pdf_' . (int) $id
		);
	}

	public static function tanda_terima_atk_pdf_url( $id, $penerima_id = 0, $penerima_nama = '', $penyerah_nama = '', $tanggal = '' ) {
		$args = array(
			'action' => 'kgtk_tanda_terima_atk_pdf',
			'id'     => (int) $id,
		);
		if ( (int) $penerima_id > 0 ) {
			$args['penerima_id'] = (int) $penerima_id;
		}
		$penerima_nama = trim( (string) $penerima_nama );
		if ( '' !== $penerima_nama ) {
			$args['penerima_nama'] = $penerima_nama;
		}
		$penyerah_nama = trim( (string) $penyerah_nama );
		if ( '' !== $penyerah_nama ) {
			$args['penyerah_nama'] = $penyerah_nama;
		}
		$tanggal = trim( (string) $tanggal );
		if ( '' !== $tanggal ) {
			$args['tanggal'] = $tanggal;
		}
		return wp_nonce_url(
			add_query_arg( $args, admin_url( 'admin-post.php' ) ),
			'kgtk_tanda_terima_atk_pdf_' . (int) $id
		);
	}

	public static function handle_tanda_terima_pdf() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Anda tidak memiliki akses.', 'Akses ditolak', array( 'response' => 403 ) );
		}
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'kgtk_tanda_terima_pdf_' . $id );

		$pelatihan = KGTK_Helpers::get_by_id( $id );
		if ( ! $pelatihan ) {
			wp_die( 'Pelatihan tidak ditemukan.', 'Tidak ditemukan', array( 'response' => 404 ) );
		}

		if ( ! self::libs_ready() ) {
			wp_die( 'Library PDF belum terpasang.', 'Error', array( 'response' => 500 ) );
		}

		$barang   = isset( $_GET['barang'] ) ? sanitize_text_field( wp_unslash( $_GET['barang'] ) ) : 'Baju';
		$kelas_id = isset( $_GET['kelas_id'] ) ? absint( $_GET['kelas_id'] ) : 0;

		self::pdf_tanda_terima( $pelatihan, $barang, $kelas_id );
	}

	public static function handle_semua_biodata_pdf() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Anda tidak memiliki akses.', 'Akses ditolak', array( 'response' => 403 ) );
		}
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'kgtk_semua_biodata_pdf_' . $id );

		$pelatihan = KGTK_Helpers::get_by_id( $id );
		if ( ! $pelatihan ) {
			wp_die( 'Pelatihan tidak ditemukan.', 'Tidak ditemukan', array( 'response' => 404 ) );
		}

		if ( ! self::libs_ready() ) {
			wp_die( 'Library PDF belum terpasang.', 'Error', array( 'response' => 500 ) );
		}

		$peran_filter = isset( $_GET['peran'] ) ? sanitize_text_field( wp_unslash( $_GET['peran'] ) ) : '';
		$peran_filter = trim( $peran_filter );

		$kelas_id = isset( $_GET['kelas_id'] ) ? absint( $_GET['kelas_id'] ) : 0;

		$ids_raw  = isset( $_GET['ids'] ) ? sanitize_text_field( wp_unslash( $_GET['ids'] ) ) : '';
		$ids_list = ! empty( $ids_raw ) ? wp_parse_id_list( $ids_raw ) : array();

		$download = isset( $_GET['dl'] ) && '1' === (string) $_GET['dl'];

		global $wpdb;
		$tp = KGTK_DB::table( 'peserta' );
		$tk = KGTK_DB::table( 'kelas' );

		$where  = array( 'ps.pelatihan_id = %d' );
		$params = array( $id );

		if ( '' !== $peran_filter && 'all' !== strtolower( $peran_filter ) && 'semua' !== strtolower( $peran_filter ) ) {
			$where[]  = 'ps.peran = %s';
			$params[] = $peran_filter;
		}

		if ( $kelas_id > 0 ) {
			$where[]  = 'ps.kelas_id = %d';
			$params[] = $kelas_id;
		}

		if ( ! empty( $ids_list ) ) {
			$in_placeholders = implode( ',', array_fill( 0, count( $ids_list ), '%d' ) );
			$where[]         = "ps.id IN ($in_placeholders)";
			$params          = array_merge( $params, $ids_list );
		}

		$where_sql = implode( ' AND ', $where );
		$sql       = "SELECT ps.*, COALESCE(k.nama, '') AS kelas_label, k.tanggal_mulai AS kelas_tanggal_mulai, k.tanggal_selesai AS kelas_tanggal_selesai
					  FROM {$tp} ps
					  LEFT JOIN {$tk} k ON k.id = ps.kelas_id
					  WHERE {$where_sql}
					  ORDER BY ps.kelas_id ASC, ps.nama ASC";

		$pesertas = $wpdb->get_results( $wpdb->prepare( $sql, $params ) );

		if ( empty( $pesertas ) ) {
			wp_die(
				'Tidak ada data biodata peserta yang dapat dicetak dengan kriteria yang dipilih.',
				'Biodata Tidak Ditemukan',
				array( 'response' => 200, 'back_link' => true )
			);
		}

		self::download_semua_biodata_pdf( $pesertas, $pelatihan, $peran_filter, ! empty( $ids_list ), $download );
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
				"SELECT ps.*, COALESCE(k.nama, '') AS kelas_label, k.tanggal_mulai AS kelas_tanggal_mulai, k.tanggal_selesai AS kelas_tanggal_selesai
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
		if ( ! in_array( $format, array( 'pdf', 'xlsx', 'xlsx_absen' ), true ) ) {
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

		if ( 'pdf' === $format ) {
			$base = sanitize_file_name( 'absen-' . strtolower( $data['pelatihan']->kode ) . $suffix );
			self::pdf( $data, $base . '.pdf', $peran_filter, $tanggal_filter );
		} elseif ( 'xlsx_absen' === $format ) {
			$base = sanitize_file_name( 'absen-' . strtolower( $data['pelatihan']->kode ) . $suffix );
			self::xlsx( $data, $base . '.xlsx', $peran_filter, $tanggal_filter );
		} else {
			$base = sanitize_file_name( 'data-guru-' . strtolower( $data['pelatihan']->kode ) . $suffix );
			self::xlsx_data_guru( $id, $data, $base . '.xlsx', $peran_filter );
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
		if ( $mode_all_days ) {
			foreach ( $sheets as $sh ) {
				$hari_kelas = KGTK_Helpers::daftar_hari_kelas( $p, $sh['kelas'] );
				$multi_k    = count( $hari_kelas ) > 1;
				foreach ( $hari_kelas as $dh ) {
					$ws_jobs[] = array(
						'sh'      => $sh,
						'tanggal' => $dh['ymd'],
						'suffix'  => $multi_k ? 'H' . $dh['hari_ke'] : '',
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

					foreach ( KGTK_Helpers::baris_info_array( $p, $tgl_j, $k ) as $info_line ) {
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
					$ws->setCellValueExplicit( 'A' . $r, (string) $no_urutan, DataType::TYPE_STRING );
					if ( isset( $halaman['rows'][ $n ] ) ) {
						$ws->setCellValueExplicit( 'B' . $r, (string) $halaman['rows'][ $n ]->nama, DataType::TYPE_STRING );
						$ws->setCellValueExplicit( 'C' . $r, (string) $halaman['rows'][ $n ]->sekolah, DataType::TYPE_STRING );
					}
					$ws->getRowDimension( $r )->setRowHeight( 30 );
					$r++;
				}
				$range = 'A' . $hr . ':E' . ( $r - 1 );
				$ws->getStyle( $range )->getBorders()->getAllBorders()->setBorderStyle( Border::BORDER_THIN );
				$ws->getStyle( 'A' . $first . ':E' . ( $r - 1 ) )->getFont()->setSize( 12 );
				$ws->getStyle( 'A' . $first . ':C' . ( $r - 1 ) )->getAlignment()->setVertical( Alignment::VERTICAL_CENTER );
				$ws->getStyle( 'A' . $first . ':A' . ( $r - 1 ) )->getAlignment()->setHorizontal( Alignment::HORIZONTAL_CENTER );

				for ( $n = 0; $n < $halaman['jumlah']; $n += 2 ) {
					$row_top    = $first + $n;
					$no_urutan  = ( $halaman['mulai'] + $n + 1 );
					$has_pair   = ( $n + 1 < $halaman['jumlah'] );
					$ws->setCellValueExplicit( 'D' . $row_top, (string) $no_urutan, DataType::TYPE_STRING );
					$ws->getStyle( 'D' . $row_top )->getAlignment()->setHorizontal( Alignment::HORIZONTAL_LEFT )->setVertical( Alignment::VERTICAL_TOP );
					if ( $has_pair ) {
						$row_bottom = $row_top + 1;
						$ws->mergeCells( 'D' . $row_top . ':D' . $row_bottom );
						$ws->setCellValueExplicit( 'E' . $row_top, (string) ( $no_urutan + 1 ), DataType::TYPE_STRING );
						$ws->mergeCells( 'E' . $row_top . ':E' . $row_bottom );
						$ws->getStyle( 'E' . $row_top )->getAlignment()->setHorizontal( Alignment::HORIZONTAL_RIGHT )->setVertical( Alignment::VERTICAL_BOTTOM );
					} else {
						$ws->setCellValueExplicit( 'E' . $row_top, '', DataType::TYPE_STRING );
					}
				}
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

		self::stream_xlsx( $ss, $filename );
	}

	/**
	 * Mengirim berkas Spreadsheet (.xlsx) ke output browser dan membebaskan memori.
	 *
	 * @param Spreadsheet $ss       Instance Spreadsheet.
	 * @param string      $filename Nama berkas unduhan.
	 */
	private static function stream_xlsx( Spreadsheet $ss, $filename ) {
		self::clean_buffers();
		nocache_headers();
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		$writer = new Xlsx( $ss );
		$writer->save( 'php://output' );
		$ss->disconnectWorksheets();
	}

	/**
	 * Sanitasi nilai string sebelum ditulis ke sel Excel untuk mencegah CSV/Excel Formula Injection.
	 *
	 * @param mixed $val Nilai input.
	 * @return string
	 */
	private static function sanitize_excel_cell( $val ) {
		$str = trim( (string) $val );
		if ( '' === $str ) {
			return '';
		}
		if ( in_array( $str[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			return "'" . $str;
		}
		return $str;
	}

	/**
	 * Normalisasi field opsional yang menggunakan tanda '-' sebagai placeholder kosong.
	 *
	 * @param mixed $val Nilai kolom dari database.
	 * @return string
	 */
	private static function clean_optional_field( $val ) {
		$cleaned = trim( (string) $val );
		return self::PLACEHOLDER_EMPTY === $cleaned ? '' : $cleaned;
	}

	/**
	 * Format tanggal lahir sesuai standar template: "18 Agu 1988".
	 *
	 * @param string $tgl Tanggal mentah (YYYY-MM-DD).
	 * @return string
	 */
	public static function format_tgl_lahir_excel( $tgl ) {
		$raw = trim( (string) $tgl );
		if ( '' === $raw ) {
			return '';
		}

		$timestamp = strtotime( $raw );
		if ( false === $timestamp ) {
			return $raw;
		}

		$month_key   = date( 'm', $timestamp );
		$month_label = isset( self::BULAN_SINGKAT[ $month_key ] ) ? self::BULAN_SINGKAT[ $month_key ] : date( 'M', $timestamp );

		return date( 'd', $timestamp ) . ' ' . $month_label . ' ' . date( 'Y', $timestamp );
	}

	/**
	 * Deteksi jenjang sekolah (SD, SMP, SMA, SMK, PAUD, TK, SLB) dari nama instansi.
	 *
	 * @param string $sekolah Nama sekolah/instansi.
	 * @return string
	 */
	public static function detect_jenjang( $sekolah ) {
		$name = trim( (string) $sekolah );
		if ( '' === $name ) {
			return '';
		}

		foreach ( self::JENJANG_PATTERNS as $jenjang => $pattern ) {
			if ( preg_match( $pattern, $name ) ) {
				return $jenjang;
			}
		}

		return '';
	}

	/**
	 * Mengambil daftar peserta pelatihan beserta data sekolah dan kelas untuk kebutuhan export.
	 *
	 * @param int    $pelatihan_id ID pelatihan.
	 * @param string $peran_filter Filter peran (opsional).
	 * @return array<int, object>
	 */
	private static function fetch_peserta_for_export( $pelatihan_id, $peran_filter = '' ) {
		global $wpdb;
		$tp = KGTK_DB::table( 'peserta' );
		$tk = KGTK_DB::table( 'kelas' );
		$ts = KGTK_DB::table( 'sekolah' );

		$where  = array( 'ps.pelatihan_id = %d' );
		$params = array( (int) $pelatihan_id );

		$peran_clean = trim( (string) $peran_filter );
		$is_all      = in_array( strtolower( $peran_clean ), array( '', 'all', 'semua' ), true );

		if ( ! $is_all ) {
			$where[]  = 'ps.peran = %s';
			$params[] = $peran_clean;
		}

		$where_sql = implode( ' AND ', $where );
		$sql       = "SELECT ps.*,
		                     COALESCE(k.nama, '') AS kelas_label,
		                     COALESCE(s.npsn, '') AS sekolah_npsn,
		                     COALESCE(s.jenjang, '') AS sekolah_jenjang
		              FROM {$tp} ps
		              LEFT JOIN {$tk} k ON k.id = ps.kelas_id
		              LEFT JOIN {$ts} s ON s.id = ps.sekolah_id
		              WHERE {$where_sql}
		              ORDER BY ps.kelas_id ASC, ps.nama ASC";

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return is_array( $results ) ? $results : array();
	}

	/**
	 * Memetakan satu objek peserta menjadi array 28 kolom sesuai urutan DATA_GURU_HEADERS.
	 *
	 * @param object $row Objek baris peserta dari database.
	 * @return array<int, string>
	 */
	private static function map_peserta_to_excel_row( $row ) {
		$nama_lengkap = KGTK_Helpers::format_nama_gelar( $row->nama, $row->gelar_depan, $row->gelar_belakang );

		$nip     = trim( (string) $row->nip );
		$nik     = trim( (string) $row->nik );
		$nip_nik = ( '' !== $nip && '' !== $nik ) ? ( $nip . '/' . $nik ) : ( '' !== $nip ? $nip : $nik );

		$npsn = ! empty( $row->sekolah_npsn )
			? (string) $row->sekolah_npsn
			: ( ! empty( $row->npsn ) ? (string) $row->npsn : '' );

		$jenjang = ! empty( $row->sekolah_jenjang )
			? (string) $row->sekolah_jenjang
			: self::detect_jenjang( $row->sekolah );

		$pengalaman = self::clean_optional_field( $row->pengalaman_jabatan );
		$bank       = self::clean_optional_field( $row->bank );
		$rekening   = self::clean_optional_field( $row->rekening );

		$hp_raw   = trim( (string) $row->hp );
		$hp_digit = preg_replace( '/\D/', '', $hp_raw );
		$no_hp    = '' !== $hp_digit ? ltrim( $hp_digit, '0' ) : $hp_raw;

		return array(
			$nama_lengkap,
			$nip_nik,
			trim( (string) $row->tempat_lahir ),
			self::format_tgl_lahir_excel( $row->tanggal_lahir ),
			trim( (string) $row->jenis_kelamin ),
			$npsn,
			trim( (string) $row->nuptk ),
			trim( (string) $row->sekolah ),
			trim( (string) $row->alamat_instansi ),
			trim( (string) $row->kabkota ),
			trim( (string) $row->alamat ),
			$jenjang,
			trim( (string) $row->pangkat_golongan ),
			trim( (string) $row->agama ),
			trim( (string) $row->pendidikan ),
			trim( (string) $row->jenis_gtk ),
			$pengalaman,
			$bank,
			$rekening,
			trim( (string) $row->npwp ),
			$pengalaman,
			$no_hp,
			trim( (string) $row->email ),
			trim( (string) $row->email_belajar ),
			trim( (string) $row->simpkb ),
			self::DEFAULT_PROVINSI,
			$hp_raw,
			! empty( $row->terverifikasi ) ? self::STATUS_VERIFIED : '',
		);
	}

	/**
	 * Export data guru yang mengikuti pelatihan dalam format Excel (.xlsx)
	 * sesuai template Google Spreadsheet (28 kolom data guru lengkap).
	 *
	 * @param int    $pelatihan_id ID pelatihan.
	 * @param array  $data         Data konteks pelatihan.
	 * @param string $filename     Nama berkas keluaran.
	 * @param string $peran_filter Filter peran peserta (opsional).
	 */
	public static function xlsx_data_guru( $pelatihan_id, $data, $filename, $peran_filter = '' ) {
		$peserta_list = self::fetch_peserta_for_export( $pelatihan_id, $peran_filter );

		$ss = new Spreadsheet();
		$ss->getDefaultStyle()->getFont()->setName( 'Arial' )->setSize( 10 );

		$ws = $ss->getActiveSheet();
		$ws->setTitle( 'Data Guru Pelatihan' );

		$headers   = self::DATA_GURU_HEADERS;
		$col_count = count( $headers );

		// Precompute huruf kolom (A..AB) satu kali di luar loop baris untuk efisiensi O(1)
		$col_letters = array();
		for ( $i = 1; $i <= $col_count; $i++ ) {
			$col_letters[ $i - 1 ] = Coordinate::stringFromColumnIndex( $i );
		}

		// Tulis baris header (Baris 1)
		foreach ( $headers as $idx => $header_text ) {
			$ws->setCellValueExplicit( $col_letters[ $idx ] . '1', $header_text, DataType::TYPE_STRING );
		}

		// Styling Header & Freeze Baris Pertama
		$header_range = 'A1:' . $col_letters[ $col_count - 1 ] . '1';
		$ws->getStyle( $header_range )->getFont()->setBold( true );
		$ws->getStyle( $header_range )->getAlignment()->setVertical( Alignment::VERTICAL_CENTER );
		$ws->freezePane( 'A2' );

		// Tulis baris data peserta (Baris 2 dst)
		$row_idx = 2;
		foreach ( $peserta_list as $peserta ) {
			$row_values = self::map_peserta_to_excel_row( $peserta );
			foreach ( $row_values as $col_idx => $cell_value ) {
				$ws->setCellValueExplicit(
					$col_letters[ $col_idx ] . $row_idx,
					self::sanitize_excel_cell( $cell_value ),
					DataType::TYPE_STRING
				);
			}
			$row_idx++;
		}

		// Auto-size seluruh kolom
		foreach ( $col_letters as $col_letter ) {
			$ws->getColumnDimension( $col_letter )->setAutoSize( true );
		}

		self::stream_xlsx( $ss, $filename );
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
.kgtk-kop-tbl td { border: 0 !important; vertical-align: middle; }
.kgtk-kop-tbl td.kgtk-kop-line { width: 1px !important; max-width: 1px !important; min-width: 1px !important; background-color: #0083C4 !important; padding: 0 !important; border: 0 !important; font-size: 1px !important; line-height: 1px !important; }
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
td.ttd-col { width: 13%; padding: 2px 6px; font-size: 11pt; line-height: 1.15; }
td.ttd-left { text-align: left; vertical-align: top; }
td.ttd-right { text-align: right; vertical-align: bottom; }
table.ttd { width: 100%; margin-top: 18px; border-collapse: collapse; }
table.ttd td { border: 0; vertical-align: top; }
td.ttd-box { width: 250px; text-align: center; font-size: 12pt; }
div.space { height: 62px; }
</style></head><body>';

		$items  = array();
		$sheets = KGTK_Helpers::absen_sheets( $data, $peran_filter );

		if ( $mode_all_days ) {
			foreach ( $sheets as $sh ) {
				$hari_kelas = KGTK_Helpers::daftar_hari_kelas( $p, $sh['kelas'] );
				foreach ( $hari_kelas as $dh ) {
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
				$html .= KGTK_Helpers::baris_info_html( $p, $it['tanggal'], $sh['kelas'] );
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
				$is_first  = ( 0 === $n % 2 );
				$has_pair  = ( $n + 1 < $it['halaman']['jumlah'] );
				$html     .= '<tr><td class="no">' . $no_urutan . '</td><td class="nama">' . ( $row ? esc_html( $row->nama ) : '&nbsp;' )
					. '</td><td class="inst">' . ( $row ? esc_html( $row->sekolah ) : '&nbsp;' ) . '</td>';
				if ( $is_first ) {
					if ( $has_pair ) {
						$html .= '<td class="ttd-col ttd-left" rowspan="2">' . $no_urutan . '</td><td class="ttd-col ttd-right" rowspan="2">' . ( $no_urutan + 1 ) . '</td>';
					} else {
						$html .= '<td class="ttd-col ttd-left">' . $no_urutan . '</td><td class="ttd-col ttd-right">&nbsp;</td>';
					}
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

	/**
	 * Render HTML 1 lembar biodata peserta sesuai standar resmi Kemendikdasmen.
	 *
	 * @param object $peserta   Data peserta.
	 * @param object $pelatihan Data pelatihan.
	 * @param string $kop_html  HTML Kop resmi (opsional).
	 * @return string HTML lembar biodata dalam div.bio-page.
	 */
	public static function render_single_biodata_html( $peserta, $pelatihan, $kop_html = '' ) {
		if ( empty( $kop_html ) ) {
			$kop_html = KGTK_Helpers::kop_html();
		}

		$peran   = ! empty( $peserta->peran ) ? $peserta->peran : 'PESERTA';
		$kabkota = KGTK_Helpers::kabkota_tempat_kegiatan( $pelatihan );
		$tgl_mulai_bio = ( ! empty( $peserta->kelas_tanggal_mulai ) && '0000-00-00' !== $peserta->kelas_tanggal_mulai ) ? $peserta->kelas_tanggal_mulai : $pelatihan->tanggal_mulai;
		$tgl_ttd = KGTK_Helpers::format_tanggal( $tgl_mulai_bio );
		$lokasi  = ! empty( $pelatihan->lokasi ) ? $pelatihan->lokasi : 'Kepulauan Riau';

		$h  = '<div class="bio-page">';
		$h .= $kop_html;

		$h .= '<div class="title-box">';
		$h .= '<div class="title-main">BIODATA ' . esc_html( strtoupper( $peran ) ) . '</div>';
		$h .= '<div class="title-sub">' . esc_html( $pelatihan->nama ) . '</div>';
		$lok_txt = 0 === strpos( strtolower( trim( $lokasi ) ), 'di ' ) ? $lokasi : 'di ' . $lokasi;
		$h .= '<div class="title-loc">' . esc_html( $lok_txt ) . '</div>';
		$h .= '</div>';

		$tgl_lahir_fmt = ! empty( $peserta->tanggal_lahir ) && '-' !== $peserta->tanggal_lahir ? KGTK_Helpers::format_tanggal( $peserta->tanggal_lahir ) : '';
		$ttl_parts     = array();
		if ( ! empty( $peserta->tempat_lahir ) && '-' !== $peserta->tempat_lahir ) {
			$ttl_parts[] = $peserta->tempat_lahir;
		}
		if ( ! empty( $tgl_lahir_fmt ) ) {
			$ttl_parts[] = $tgl_lahir_fmt;
		}
		$ttl             = ! empty( $ttl_parts ) ? implode( ', ', $ttl_parts ) : '-';
		$nip             = ! empty( $peserta->nip ) ? $peserta->nip : '-';
		$pangkat_gol     = ! empty( $peserta->pangkat_golongan ) ? $peserta->pangkat_golongan : '-';
		$pendidikan      = ! empty( $peserta->pendidikan ) ? $peserta->pendidikan : '-';
		$jenis_kelamin   = ! empty( $peserta->jenis_kelamin ) ? $peserta->jenis_kelamin : '-';
		$agama           = ! empty( $peserta->agama ) ? $peserta->agama : '-';
		$jabatan         = ! empty( $peserta->jenis_gtk ) ? $peserta->jenis_gtk : ( ! empty( $peserta->jabatan ) ? $peserta->jabatan : '-' );
		$sekolah         = ! empty( $peserta->sekolah ) ? $peserta->sekolah : '-';
		$alamat_instansi = ! empty( $peserta->alamat_instansi ) ? $peserta->alamat_instansi : '-';
		$hp              = ! empty( $peserta->hp ) ? $peserta->hp : '-';
		$email           = ! empty( $peserta->email ) ? $peserta->email : ( ! empty( $peserta->email_belajar ) ? $peserta->email_belajar : '-' );
		$bank            = ! empty( $peserta->bank ) ? $peserta->bank : '-';
		$rekening        = ! empty( $peserta->rekening ) ? $peserta->rekening : '-';

		$nama_lengkap    = KGTK_Helpers::format_nama_gelar( $peserta );

		$h .= '<table class="bio-box">';
		$h .= '<tr><td class="lbl">NAMA</td><td class="sep">:</td><td class="val">' . esc_html( $nama_lengkap ) . '</td></tr>';
		$h .= '<tr><td class="lbl">NIP</td><td class="sep">:</td><td class="val">' . esc_html( $nip ) . '</td></tr>';
		$h .= '<tr><td class="lbl">TEMPAT/TANGGAL LAHIR</td><td class="sep">:</td><td class="val">' . esc_html( $ttl ) . '</td></tr>';
		$h .= '<tr><td class="lbl">PANGKAT/GOLONGAN</td><td class="sep">:</td><td class="val">' . esc_html( $pangkat_gol ) . '</td></tr>';
		$h .= '<tr><td class="lbl">PENDIDIKAN TERAKHIR</td><td class="sep">:</td><td class="val">' . esc_html( $pendidikan ) . '</td></tr>';
		$h .= '<tr><td class="lbl">JENIS KELAMIN</td><td class="sep">:</td><td class="val">' . esc_html( $jenis_kelamin ) . '</td></tr>';
		$h .= '<tr><td class="lbl">AGAMA</td><td class="sep">:</td><td class="val">' . esc_html( $agama ) . '</td></tr>';
		$h .= '<tr><td class="lbl">JABATAN</td><td class="sep">:</td><td class="val">' . esc_html( $jabatan ) . '</td></tr>';
		$h .= '<tr><td class="lbl">INSTANSI TEMPAT TUGAS</td><td class="sep">:</td><td class="val">' . esc_html( $sekolah ) . '</td></tr>';
		$h .= '<tr><td class="lbl">ALAMAT INSTANSI</td><td class="sep">:</td><td class="val">' . esc_html( $alamat_instansi ) . '</td></tr>';
		$h .= '<tr><td class="lbl">NO. HP/WHATSAPP</td><td class="sep">:</td><td class="val">' . esc_html( $hp ) . '</td></tr>';
		$h .= '<tr><td class="lbl">EMAIL</td><td class="sep">:</td><td class="val">' . esc_html( $email ) . '</td></tr>';
		$h .= '<tr><td class="lbl">NAMA BANK</td><td class="sep">:</td><td class="val">' . esc_html( $bank ) . '</td></tr>';
		$h .= '<tr><td class="lbl">NOMOR REKENING</td><td class="sep">:</td><td class="val">' . esc_html( $rekening ) . '</td></tr>';
		$h .= '</table>';

		$h .= '<table class="ttd-tbl">';
		$h .= '<tr><td>&nbsp;</td><td class="ttd-right">';
		$h .= '<div>' . esc_html( $kabkota ) . ', ' . esc_html( $tgl_ttd ) . '</div>';
		$h .= '<div class="space">&nbsp;</div>';
		$h .= '<div><u><strong>' . esc_html( $nama_lengkap ) . '</strong></u></div>';
		$h .= '</td></tr></table>';

		$h .= '</div>';
		return $h;
	}

	/** Download PDF Biodata 1 Peserta */
	public static function download_biodata_pdf( $peserta, $pelatihan ) {
		self::download_semua_biodata_pdf( array( $peserta ), $pelatihan, '', false, true );
	}

	/**
	 * Download / Cetak PDF Semua Biodata Peserta per Pelatihan.
	 * Format: 1 Halaman Per Peserta, siap print fisik.
	 *
	 * @param array  $pesertas     Daftar objek peserta.
	 * @param object $pelatihan    Data pelatihan.
	 * @param string $peran_filter Filter peran jika ada.
	 * @param bool   $is_terpilih  True jika cetak hasil centang.
	 * @param bool   $download     True jika force download attachment.
	 */
	public static function download_semua_biodata_pdf( array $pesertas, $pelatihan, $peran_filter = '', $is_terpilih = false, $download = false ) {
		if ( ! self::libs_ready() ) {
			wp_die( 'Library PDF belum terpasang.', 'Error', array( 'response' => 500 ) );
		}

		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			wp_raise_memory_limit( 'admin' );
		}
		@ini_set( 'memory_limit', '512M' );
		@set_time_limit( 300 );

		if ( count( $pesertas ) === 1 ) {
			$first     = reset( $pesertas );
			$nama_file = sanitize_file_name( 'Biodata-' . $first->nama . '-' . $pelatihan->kode . '.pdf' );
			$doc_title = 'Biodata ' . $first->nama . ' - ' . $pelatihan->nama;
		} elseif ( $is_terpilih ) {
			$nama_file = sanitize_file_name( 'Biodata-Peserta-Terpilih-' . $pelatihan->kode . '.pdf' );
			$doc_title = 'Biodata Peserta Terpilih - ' . $pelatihan->nama;
		} elseif ( ! empty( $peran_filter ) && 'all' !== strtolower( $peran_filter ) && 'semua' !== strtolower( $peran_filter ) ) {
			$nama_file = sanitize_file_name( 'Biodata-' . sanitize_title( $peran_filter ) . '-' . $pelatihan->kode . '.pdf' );
			$doc_title = 'Biodata ' . $peran_filter . ' - ' . $pelatihan->nama;
		} else {
			$nama_file = sanitize_file_name( 'Biodata-Semua-Peserta-' . $pelatihan->kode . '.pdf' );
			$doc_title = 'Biodata Semua Peserta - ' . $pelatihan->nama;
		}

		$kop_html = KGTK_Helpers::kop_html();

		$html  = '<!DOCTYPE html><html><head><meta charset="utf-8">';
		$html .= '<title>' . esc_html( $doc_title ) . '</title>';
		$html .= '<style>';
		/* Margin disamakan dengan cetak absen: Atas 0.51cm, Bawah 0.51cm, Kiri 1.0cm, Kanan 1.0cm */
		$html .= '@page { margin: 0.51cm 1.0cm 0.51cm 1.0cm; size: A4 portrait; }';
		$html .= 'body { font-family: "Times New Roman", Times, "Times-Roman", serif; font-size: 12pt; color: #000; line-height: 1.35; margin: 0; padding: 0; }';
		$html .= '.bio-page { page-break-after: always; clear: both; }';
		$html .= '.bio-page:last-child { page-break-after: avoid; }';
		$html .= '.kgtk-kop-wrap { width: 100%; text-align: center; margin: 0 auto 12px; }';
		$html .= '.kgtk-kop-tbl td { border: 0 !important; vertical-align: middle; }';
		$html .= '.kgtk-kop-tbl td.kgtk-kop-line { width: 1px !important; max-width: 1px !important; min-width: 1px !important; background-color: #0083C4 !important; padding: 0 !important; border: 0 !important; font-size: 1px !important; line-height: 1px !important; }';
		$html .= '.title-box { text-align: center; margin: 6pt 0 10pt; }';
		$html .= '.title-main { font-size: 15pt; font-weight: bold; text-transform: uppercase; margin-bottom: 2px; }';
		$html .= '.title-sub { font-size: 13pt; font-weight: bold; margin-bottom: 2px; line-height: 1.25; }';
		$html .= '.title-loc { font-size: 13pt; margin-bottom: 8pt; }';
		$html .= 'table.bio-box { width: 100%; border-collapse: collapse; border: 1.5px solid #000; margin-bottom: 14px; }';
		$html .= 'table.bio-box td { padding: 3.5px 8px; font-size: 11.5pt; vertical-align: top; border: 0; }';
		$html .= 'td.lbl { width: 34%; }';
		$html .= 'td.sep { width: 4%; text-align: center; }';
		$html .= 'td.val { width: 62%; }';
		$html .= 'table.ttd-tbl { width: 100%; border-collapse: collapse; margin-top: 8px; }';
		$html .= 'table.ttd-tbl td { border: 0; vertical-align: top; }';
		$html .= 'td.ttd-right { width: 250px; text-align: center; font-size: 12pt; }';
		$html .= '.space { height: 50px; }';
		$html .= '</style></head><body>';

		foreach ( $pesertas as $ps ) {
			$html .= self::render_single_biodata_html( $ps, $pelatihan, $kop_html );
		}

		$html .= '</body></html>';

		$opt = new Options();
		$opt->set( 'isRemoteEnabled', false );
		$opt->set( 'defaultFont', 'Times-Roman' );
		$opt->set( 'isHtml5ParserEnabled', true );
		$pdf = new Dompdf( $opt );
		$pdf->loadHtml( $html, 'UTF-8' );
		$pdf->setPaper( 'A4', 'portrait' );
		$pdf->render();

		self::clean_buffers();
		nocache_headers();
		$pdf->stream( $nama_file, array( 'Attachment' => (bool) $download ) );
		exit;
	}

	/**
	 * Export dokumen PDF resmi: Daftar Tanda Terima Barang / Alat Pelatihan.
	 *
	 * @param object $p        Data pelatihan.
	 * @param string $barang   Nama barang yang diterima.
	 * @param int    $kelas_id ID kelas (0 untuk semua kelas).
	 */
	public static function pdf_tanda_terima( $p, $barang = 'Baju', $kelas_id = 0 ) {
		global $wpdb;
		$tp = KGTK_DB::table( 'peserta' );
		$tk = KGTK_DB::table( 'kelas' );

		$kelas_label = '';
		if ( $kelas_id > 0 ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ps.*, COALESCE(k.nama, '') AS kelas_nama
					 FROM {$tp} ps
					 LEFT JOIN {$tk} k ON ps.kelas_id = k.id
					 WHERE ps.pelatihan_id = %d AND ps.kelas_id = %d AND ps.peran != 'Panitia'
					 ORDER BY ps.id ASC",
					$p->id,
					$kelas_id
				)
			);
			$k_obj = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tk} WHERE id = %d", $kelas_id ) );
			if ( $k_obj ) {
				$kelas_label = $k_obj->nama;
			}
		} else {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ps.*, COALESCE(k.nama, '') AS kelas_nama
					 FROM {$tp} ps
					 LEFT JOIN {$tk} k ON ps.kelas_id = k.id
					 WHERE ps.pelatihan_id = %d AND ps.peran != 'Panitia'
					 ORDER BY ps.id ASC",
					$p->id
				)
			);
		}

		$barang_clean = trim( (string) $barang );
		$barang_upper = $barang_clean ? strtoupper( $barang_clean ) : '';
		$judul        = 'DAFTAR TANDA TERIMA' . ( $barang_upper ? ' ' . $barang_upper : '' );

		$pages       = KGTK_Helpers::halaman( $rows, max( KGTK_Helpers::BARIS_HALAMAN_AWAL, count( $rows ) ) );
		$total_pages = count( $pages );

		$html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' . esc_html( $judul . ' - ' . $p->nama ) . '</title><style>
@page { margin: 0.51cm 1.0cm 0.51cm 1.0cm; }
body { font-family: "Times New Roman", Times, "Times-Roman", serif; font-size: 12pt; color: #000; }
.kgtk-kop-wrap { width: 100%; text-align: center; margin: 0 auto 16px; }
.kgtk-kop-tbl td { border: 0 !important; vertical-align: middle; }
.kgtk-kop-tbl td.kgtk-kop-line { width: 1px !important; max-width: 1px !important; min-width: 1px !important; background-color: #0083C4 !important; padding: 0 !important; border: 0 !important; font-size: 1px !important; line-height: 1px !important; }
.page { page-break-after: always; }
.page.last { page-break-after: auto; }
h1 { text-align: center; font-size: 14pt; font-weight: bold; letter-spacing: 1px; margin: 0 0 6px; }
.kgtk-doc-info { text-align: center; margin: 6px 0 12px; font-size: 12pt; line-height: 1.4; }
.kgtk-doc-info div { font-size: 12pt; }
p.kelas { text-align: center; margin: 0 0 4px; font-size: 12pt; font-weight: bold; }
table.abs { width: 100%; border-collapse: collapse; margin-top: 14px; margin-bottom: 18px; table-layout: fixed; }
table.abs th, table.abs td { border: 1px solid #000; padding: 0 8px; height: 33px; font-size: 12pt; }
table.abs th { background: none; color: #000; text-align: center; height: 26px; font-size: 12pt; font-weight: bold; }
th.no, td.no { text-align: center; width: 7%; font-size: 12pt; padding: 0 2px; }
th.nama, td.nama { width: 30%; font-size: 12pt; padding: 0 6px; }
th.inst, td.inst { width: 37%; font-size: 12pt; padding: 0 6px; }
th.ttd { width: 26%; white-space: nowrap; text-align: center; }
td.ttd-col { width: 13%; padding: 2px 6px; font-size: 11pt; line-height: 1.15; }
td.ttd-left { text-align: left; vertical-align: top; }
td.ttd-right { text-align: right; vertical-align: bottom; }
table.ttd { width: 100%; margin-top: 18px; border-collapse: collapse; }
table.ttd td { border: 0; vertical-align: top; }
td.ttd-box { width: 250px; text-align: center; font-size: 12pt; }
div.space { height: 62px; }
</style></head><body>';

		foreach ( $pages as $p_idx => $halaman ) {
			$is_last = ( $p_idx === $total_pages - 1 );
			$html   .= '<div class="page' . ( $is_last ? ' last' : '' ) . '">';
			if ( 0 === $p_idx ) {
				$html .= KGTK_Helpers::kop_html();
				$html .= '<h1>' . esc_html( $judul ) . '</h1>';
				$html .= KGTK_Helpers::baris_info_html( $p );
				if ( ! empty( $kelas_label ) ) {
					$html .= '<p class="kelas">' . esc_html( $kelas_label ) . '</p>';
				}
			}
			$html .= '<table class="abs">';
			$html .= '<colgroup><col style="width:7%;"><col style="width:30%;"><col style="width:37%;"><col style="width:13%;"><col style="width:13%;"></colgroup>';
			$html .= '<thead><tr><th class="no">No</th><th class="nama">Nama Peserta</th><th class="inst">Asal Sekolah / Instansi</th><th class="ttd" colspan="2" style="white-space:nowrap;text-align:center;">Tanda Tangan</th></tr></thead><tbody>';
			for ( $n = 0; $n < $halaman['jumlah']; $n++ ) {
				$row       = isset( $halaman['rows'][ $n ] ) ? $halaman['rows'][ $n ] : null;
				$no_urutan = $halaman['mulai'] + $n + 1;
				$is_first  = ( 0 === $n % 2 );
				$has_pair  = ( $n + 1 < $halaman['jumlah'] );
				$html     .= '<tr><td class="no">' . $no_urutan . '</td><td class="nama">' . ( $row ? esc_html( $row->nama ) : '&nbsp;' )
					. '</td><td class="inst">' . ( $row ? esc_html( $row->sekolah ) : '&nbsp;' ) . '</td>';
				if ( $is_first ) {
					if ( $has_pair ) {
						$html .= '<td class="ttd-col ttd-left" rowspan="2">' . $no_urutan . '</td><td class="ttd-col ttd-right" rowspan="2">' . ( $no_urutan + 1 ) . '</td>';
					} else {
						$html .= '<td class="ttd-col ttd-left">' . $no_urutan . '</td><td class="ttd-col ttd-right">&nbsp;</td>';
					}
				}
				$html .= '</tr>';
			}
			$html .= '</tbody></table>';

			if ( $is_last ) {
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

		$slug_barang = sanitize_title( $barang_clean ?: 'Barang' );
		$slug_pel    = sanitize_title( $p->nama );
		$filename    = 'Tanda-Terima-' . $slug_barang . '-' . $slug_pel . '.pdf';

		self::clean_buffers();
		nocache_headers();
		$pdf->stream( $filename, array( 'Attachment' => false ) );
		exit;
	}

	public static function handle_tanda_terima_atk_pdf() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Anda tidak memiliki akses.', 'Akses ditolak', array( 'response' => 403 ) );
		}
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'kgtk_tanda_terima_atk_pdf_' . $id );

		$p = KGTK_Helpers::get_by_id( $id );
		if ( ! $p ) {
			wp_die( 'Pelatihan tidak ditemukan.', 'Tidak ditemukan', array( 'response' => 404 ) );
		}

		if ( ! self::libs_ready() ) {
			wp_die( 'Library PDF (Dompdf) belum terpasang.', 'Error Library', array( 'response' => 500 ) );
		}

		$penerima_id   = isset( $_GET['penerima_id'] ) ? absint( $_GET['penerima_id'] ) : 0;
		$penerima_nama = isset( $_GET['penerima_nama'] ) ? sanitize_text_field( wp_unslash( $_GET['penerima_nama'] ) ) : '';
		$penyerah_nama = isset( $_GET['penyerah_nama'] ) ? sanitize_text_field( wp_unslash( $_GET['penyerah_nama'] ) ) : '';
		$tanggal_custom= isset( $_GET['tanggal'] ) ? sanitize_text_field( wp_unslash( $_GET['tanggal'] ) ) : '';

		$penerima_nip      = '';
		$penerima_instansi = '';
		$penerima_peran    = 'Pengajar / Fasilitator / Pemateri';

		if ( $penerima_id > 0 ) {
			global $wpdb;
			$peserta_row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM " . KGTK_DB::table( 'peserta' ) . " WHERE id = %d AND pelatihan_id = %d",
					$penerima_id,
					$id
				)
			);
			if ( $peserta_row ) {
				$penerima_nama     = $peserta_row->nama;
				$penerima_nip      = $peserta_row->nip ?? '';
				$penerima_instansi = $peserta_row->sekolah ?? '';
				$penerima_peran    = $peserta_row->peran ?? 'Pengajar';
			}
		}

		if ( '' === $penyerah_nama ) {
			$penyerah_nama = KGTK_Helpers::nama_pj( $p );
		}

		$items = KGTK_Helpers::get_atk_items( $id );
		if ( empty( $items ) ) {
			$items = KGTK_Helpers::default_atk_items();
		}

		$tgl_teks = $tanggal_custom ?: KGTK_Helpers::format_tanggal( $p->tanggal_mulai );
		$lokasi   = $p->lokasi ?: 'Makassar';

		$judul = 'DAFTAR TANDA TERIMA ALAT TULIS KANTOR (ATK)';

		$html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' . esc_html( $judul . ' - ' . $p->nama ) . '</title><style>
@page { margin: 0.51cm 1.0cm 0.51cm 1.0cm; }
body { font-family: "Times New Roman", Times, "Times-Roman", serif; font-size: 11.5pt; color: #000; line-height: 1.35; }
.kgtk-kop-wrap { width: 100%; text-align: center; margin: 0 auto 14px; }
.kgtk-kop-tbl td { border: 0 !important; vertical-align: middle; }
.kgtk-kop-tbl td.kgtk-kop-line { width: 1px !important; max-width: 1px !important; min-width: 1px !important; background-color: #0083C4 !important; padding: 0 !important; border: 0 !important; font-size: 1px !important; line-height: 1px !important; }
h1 { text-align: center; font-size: 13.5pt; font-weight: bold; letter-spacing: 0.5px; margin: 0 0 4px; text-transform: uppercase; }
.subjudul { text-align: center; font-size: 11pt; font-weight: bold; color: #222; margin: 0 0 10px; }
table.info-tbl { width: 100%; margin: 0 0 12px; border-collapse: collapse; border: 0; font-size: 11pt; }
table.info-tbl td { padding: 2px 4px; vertical-align: top; border: 0; }
table.atk { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 16px; table-layout: fixed; }
table.atk th, table.atk td { border: 1px solid #000; padding: 6px 8px; font-size: 11pt; vertical-align: middle; }
table.atk th { background: #f0f0f0; color: #000; text-align: center; font-weight: bold; }
th.no, td.no { text-align: center; width: 7%; }
th.nama, td.nama { width: 43%; text-align: left; }
th.jumlah, td.jumlah { width: 14%; text-align: center; font-weight: bold; }
th.kuantitas, td.kuantitas { width: 16%; text-align: center; }
th.ket, td.ket { width: 20%; text-align: left; font-size: 10pt; }
table.ttd { width: 100%; margin-top: 22px; border-collapse: collapse; border: 0; }
table.ttd td { border: 0; vertical-align: top; }
td.ttd-box { width: 48%; text-align: center; font-size: 11pt; }
div.space { height: 60px; }
</style></head><body>';

		$html .= KGTK_Helpers::kop_html();
		$html .= '<h1>' . esc_html( $judul ) . '</h1>';
		$html .= '<div class="subjudul">BAGI PENGAJAR / PEMATERI / FASILITATOR PELATIHAN</div>';

		$html .= '<table class="info-tbl">';
		$html .= '<tr><td style="width:20%;font-weight:bold;">Nama Pelatihan</td><td style="width:2%;">:</td><td>' . esc_html( $p->nama ) . '</td></tr>';
		$html .= '<tr><td style="font-weight:bold;">Waktu Pelaksanaan</td><td>:</td><td>' . esc_html( KGTK_Helpers::rentang_tanggal( $p->tanggal_mulai, $p->tanggal_selesai ) ) . '</td></tr>';
		$html .= '<tr><td style="font-weight:bold;">Tempat Pelaksanaan</td><td>:</td><td>' . esc_html( $p->lokasi ?: '-' ) . '</td></tr>';
		if ( $penerima_nama ) {
			$html .= '<tr><td style="font-weight:bold;">Nama Penerima</td><td>:</td><td><strong>' . esc_html( $penerima_nama ) . '</strong>' . ( $penerima_peran ? ' (' . esc_html( $penerima_peran ) . ')' : '' ) . '</td></tr>';
			if ( $penerima_instansi ) {
				$html .= '<tr><td style="font-weight:bold;">Asal Instansi</td><td>:</td><td>' . esc_html( $penerima_instansi ) . '</td></tr>';
			}
		} else {
			$html .= '<tr><td style="font-weight:bold;">Nama Penerima</td><td>:</td><td>...........................................................................................</td></tr>';
		}
		$html .= '</table>';

		$html .= '<table class="atk">';
		$html .= '<thead><tr>';
		$html .= '<th class="no">No</th>';
		$html .= '<th class="nama">Nama ATK / Perlengkapan</th>';
		$html .= '<th class="jumlah">Jumlah</th>';
		$html .= '<th class="kuantitas">Kuantitas</th>';
		$html .= '<th class="ket">Keterangan</th>';
		$html .= '</tr></thead>';
		$html .= '<tbody>';

		$no = 1;
		foreach ( $items as $it ) {
			$html .= '<tr>';
			$html .= '<td class="no">' . (int) $no++ . '</td>';
			$html .= '<td class="nama">' . esc_html( $it['nama'] ?? '' ) . '</td>';
			$html .= '<td class="jumlah">' . esc_html( $it['jumlah'] ?? '1' ) . '</td>';
			$html .= '<td class="kuantitas">' . esc_html( $it['kuantitas'] ?? 'Buah' ) . '</td>';
			$html .= '<td class="ket">' . esc_html( $it['keterangan'] ?? '-' ) . '</td>';
			$html .= '</tr>';
		}

		$html .= '</tbody></table>';

		$html .= '<table class="ttd">';
		$html .= '<tr>';
		$html .= '<td class="ttd-box">';
		$html .= 'Yang Menyerahkan,<br><strong>Panitia Pelaksana</strong>';
		$html .= '<div class="space">&nbsp;</div>';
		$html .= '<strong>' . esc_html( $penyerah_nama ) . '</strong>';
		$html .= '</td>';
		$html .= '<td style="width:4%;border:0;">&nbsp;</td>';
		$html .= '<td class="ttd-box">';
		$html .= esc_html( $lokasi ) . ', ' . esc_html( $tgl_teks ) . '<br>Yang Menerima,<br><strong>' . esc_html( $penerima_peran ?: 'Pengajar / Fasilitator' ) . '</strong>';
		$html .= '<div class="space">&nbsp;</div>';
		if ( $penerima_nama ) {
			$html .= '<strong>' . esc_html( $penerima_nama ) . '</strong>';
			if ( $penerima_nip ) {
				$html .= '<br><span style="font-size:10pt;">NIP. ' . esc_html( $penerima_nip ) . '</span>';
			}
		} else {
			$html .= '<strong>( ............................................................ )</strong>';
		}
		$html .= '</td>';
		$html .= '</tr>';
		$html .= '</table>';

		$html .= '</body></html>';

		$opt = new Options();
		$opt->set( 'isRemoteEnabled', false );
		$opt->set( 'defaultFont', 'Times-Roman' );
		$pdf = new Dompdf( $opt );
		$pdf->loadHtml( $html, 'UTF-8' );
		$pdf->setPaper( 'A4', 'portrait' );
		$pdf->render();

		$slug_pel = sanitize_title( $p->nama );
		$filename = 'Tanda-Terima-ATK-' . $slug_pel . '.pdf';

		self::clean_buffers();
		nocache_headers();
		$pdf->stream( $filename, array( 'Attachment' => false ) );
		exit;
	}
}
