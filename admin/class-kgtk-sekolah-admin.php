<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Data sekolah (impor Excel/CSV) dan rekap sekolah per kabupaten/kota.
 */
class KGTK_Sekolah_Admin {

	const MAKS_UKURAN = 10485760; // 10 MB

	public static function init() {
		add_action( 'admin_post_kgtk_sekolah_upload', array( __CLASS__, 'handle_upload' ) );
		add_action( 'admin_post_kgtk_sekolah_import', array( __CLASS__, 'handle_import' ) );
		add_action( 'admin_post_kgtk_rekap_export', array( __CLASS__, 'handle_export' ) );
		add_action( 'admin_post_kgtk_rekap_guru_export', array( __CLASS__, 'handle_export_guru' ) );
	}

	/* ====================== Util ====================== */

	private static function cek_akses() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Anda tidak memiliki akses.', 'Akses ditolak', array( 'response' => 403 ) );
		}
	}

	private static function norm( $s ) {
		return preg_replace( '/[^a-z0-9]/', '', strtolower( (string) $s ) );
	}

	private static function teks( $v ) {
		if ( is_float( $v ) && floor( $v ) == $v ) { // NPSN dan angka bulat dari Excel: tanpa notasi ilmiah.
			return sprintf( '%.0f', $v );
		}
		if ( is_bool( $v ) ) {
			return $v ? '1' : '0';
		}
		return trim( (string) preg_replace( '/\s+/u', ' ', (string) $v ) );
	}

	private static function huruf( $i ) {
		$s = '';
		for ( $n = $i + 1; $n > 0; $n = intdiv( $n - 1, 26 ) ) {
			$s = chr( 65 + ( $n - 1 ) % 26 ) . $s;
		}
		return $s;
	}

	private static function dir_impor() {
		$u   = wp_upload_dir();
		$dir = trailingslashit( $u['basedir'] ) . 'kgtk-import';
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
			file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			file_put_contents( $dir . '/.htaccess', "Deny from all\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		return $dir;
	}

	private static function bersihkan_lama( $dir ) {
		foreach ( (array) glob( $dir . '/*.*' ) as $f ) {
			$nama = basename( $f );
			if ( in_array( $nama, array( 'index.php', '.htaccess' ), true ) ) {
				continue;
			}
			if ( is_file( $f ) && filemtime( $f ) < time() - HOUR_IN_SECONDS ) {
				@unlink( $f ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			}
		}
	}

	private static function kembali( $page, $args ) {
		wp_safe_redirect( KGTK_Admin::page_url( $page, $args ) );
		exit;
	}

	/**
	 * Baca baris dari berkas (xlsx/xls/csv, sheet pertama). Setiap sel menjadi teks bersih.
	 * $maks > 0 membatasi jumlah baris. Mengembalikan array atau false.
	 */
	public static function baca_baris( $path, $maks = 0 ) {
		$ext  = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
		$rows = array();

		if ( 'csv' === $ext ) {
			$h = fopen( $path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			if ( ! $h ) {
				return false;
			}
			$first = (string) fgets( $h );
			rewind( $h );
			$delim = substr_count( $first, ';' ) > substr_count( $first, ',' ) ? ';' : ',';
			while ( false !== ( $r = fgetcsv( $h, 0, $delim ) ) ) {
				if ( array( null ) === $r ) {
					continue;
				}
				$rows[] = array_map( array( __CLASS__, 'teks' ), $r );
				if ( $maks && count( $rows ) >= $maks ) {
					break;
				}
			}
			fclose( $h ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			if ( $rows && isset( $rows[0][0] ) ) {
				$rows[0][0] = preg_replace( '/^\xEF\xBB\xBF/', '', $rows[0][0] );
			}
			return $rows;
		}

		if ( ! class_exists( '\PhpOffice\PhpSpreadsheet\IOFactory' ) ) {
			return false;
		}
		try {
			$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile( $path );
			$reader->setReadDataOnly( true );
			$ss  = $reader->load( $path );
			$arr = $ss->getSheet( 0 )->toArray( null, false, false, false );
			foreach ( $arr as $r ) {
				$baris = array_map( array( __CLASS__, 'teks' ), $r );
				if ( '' === implode( '', $baris ) ) {
					continue;
				}
				$rows[] = $baris;
				if ( $maks && count( $rows ) >= $maks ) {
					break;
				}
			}
			$ss->disconnectWorksheets();
			return $rows;
		} catch ( \Throwable $e ) {
			return false;
		}
	}

	private static function sinonim() {
		return array(
			'kabkota' => array( 'kabupatenkota', 'kabkota', 'kotakabupaten', 'kabupaten', 'kota', 'wilayah' ),
			'nama'    => array( 'namasekolah', 'namasatuanpendidikan', 'satuanpendidikan', 'sekolah', 'nama' ),
			'npsn'    => array( 'npsn' ),
			'jenjang' => array( 'jenjangpendidikan', 'bentukpendidikan', 'jenjang', 'bentuk', 'jenis' ),
		);
	}

	/** Baris judul kolom: baris (dari 15 baris pertama) yang paling banyak cocok dengan nama kolom yang dikenal. */
	public static function deteksi_header( $rows ) {
		$kata  = array();
		foreach ( self::sinonim() as $list ) {
			$kata = array_merge( $kata, $list );
		}
		$best  = 0;
		$skor  = -1;
		$batas = min( 15, count( $rows ) );
		for ( $i = 0; $i < $batas; $i++ ) {
			$n = 0;
			foreach ( $rows[ $i ] as $c ) {
				if ( in_array( self::norm( $c ), $kata, true ) ) {
					$n++;
				}
			}
			if ( $n > $skor ) {
				$skor = $n;
				$best = $i;
			}
		}
		return $best;
	}

	/** Tebak kolom untuk tiap isian berdasarkan nama kolom. Mengembalikan indeks kolom atau -1. */
	public static function tebak_kolom( $header ) {
		$normh = array_map( array( __CLASS__, 'norm' ), $header );
		$pakai = array();
		$out   = array();
		foreach ( self::sinonim() as $f => $list ) {
			$idx = -1;
			foreach ( $list as $w ) {
				$k = array_search( $w, $normh, true );
				if ( false !== $k && ! isset( $pakai[ $k ] ) ) {
					$idx = $k;
					break;
				}
			}
			if ( $idx < 0 ) {
				foreach ( $list as $w ) {
					foreach ( $normh as $k => $h ) {
						if ( '' !== $h && false !== strpos( $h, $w ) && ! isset( $pakai[ $k ] ) ) {
							$idx = $k;
							break 2;
						}
					}
				}
			}
			if ( $idx >= 0 ) {
				$pakai[ $idx ] = true;
			}
			$out[ $f ] = $idx;
		}
		return $out;
	}

	private static function potong( $s, $n ) {
		return function_exists( 'mb_substr' ) ? mb_substr( $s, 0, $n, 'UTF-8' ) : substr( $s, 0, $n );
	}

	/* ====================== Halaman Data Sekolah ====================== */

	public static function page_data() {
		self::cek_akses();
		$step = isset( $_GET['step'] ) ? sanitize_key( wp_unslash( $_GET['step'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		echo '<div class="wrap kgtk-wrap kgtk-ui">';
		if ( 'map' === $step ) {
			self::view_map();
		} else {
			self::view_overview();
		}
		echo '</div>';
	}

	private static function pesan_error( $kode ) {
		$p = array(
			'upload'     => 'Berkas gagal diunggah. Coba lagi.',
			'ext'        => 'Format berkas harus .xlsx, .xls, atau .csv.',
			'ukuran'     => 'Ukuran berkas maksimal 10 MB.',
			'lib'        => 'Library Excel belum terpasang. Jalankan composer install di folder plugin, atau simpan data sebagai .csv lalu unggah.',
			'simpan'     => 'Berkas tidak bisa disimpan di server (periksa izin folder uploads).',
			'baca'       => 'Berkas tidak bisa dibaca atau kosong.',
			'kedaluwarsa' => 'Sesi impor sudah kedaluwarsa. Unggah ulang berkasnya.',
			'kolom'      => 'Kolom Kabupaten/Kota dan Nama sekolah wajib dipilih dan tidak boleh memakai kolom yang sama.',
		);
		return isset( $p[ $kode ] ) ? $p[ $kode ] : 'Terjadi kesalahan.';
	}

	private static function view_overview() {
		global $wpdb;
		$t    = KGTK_DB::table( 'sekolah' );
		$rows = $wpdb->get_results( "SELECT kabkota, COUNT(*) AS jumlah FROM {$t} GROUP BY kabkota ORDER BY kabkota" );
		$tot  = 0;
		foreach ( (array) $rows as $r ) {
			$tot += (int) $r->jumlah;
		}

		KGTK_Admin::head( 'Data Sekolah', 'Daftar sekolah untuk pilihan kabupaten/kota dan sekolah di form pendaftaran.' );
		KGTK_Admin::notice_libs();

		// phpcs:disable WordPress.Security.NonceVerification
		if ( isset( $_GET['hasil'] ) ) {
			echo '<div class="kgtk-ok">Impor selesai: ' . (int) ( $_GET['baru'] ?? 0 ) . ' sekolah baru, ' . (int) ( $_GET['ubah'] ?? 0 ) . ' diperbarui, ' . (int) ( $_GET['lewat'] ?? 0 ) . ' dilewati (kosong atau duplikat).</div>';
		}
		if ( isset( $_GET['err'] ) ) {
			echo '<div class="kgtk-alert">' . esc_html( self::pesan_error( sanitize_key( wp_unslash( $_GET['err'] ) ) ) ) . '</div>';
		}
		// phpcs:enable

		echo '<section class="kgtk-card kgtk-form"><h2 class="kgtk-h2">Sekolah tersimpan: ' . (int) $tot . '</h2>';
		if ( $rows ) {
			echo '<div class="kgtk-scroll"><table class="kgtk-table kgtk-table--ringkas"><thead><tr><th>Kabupaten/Kota</th><th class="c-no">Sekolah</th></tr></thead><tbody>';
			foreach ( $rows as $r ) {
				echo '<tr><td>' . esc_html( $r->kabkota ) . '</td><td class="c-no">' . (int) $r->jumlah . '</td></tr>';
			}
			echo '</tbody></table></div>';
		} else {
			echo '<p class="kgtk-muted">Belum ada data. Selama kosong, form pendaftaran tetap memakai isian bebas untuk asal sekolah. Unggah berkas Excel di bawah untuk mengaktifkan pilihan kabupaten/kota dan sekolah.</p>';
		}
		echo '</section>';

		echo '<section class="kgtk-card kgtk-form" style="margin-top:16px"><h2 class="kgtk-h2">Impor dari Excel / CSV</h2>';
		echo '<p class="kgtk-muted">Unggah berkas berisi sekolah (sheet pertama). Satu baris per sekolah dengan kolom judul di baris atas. Minimal ada kolom kabupaten/kota dan nama sekolah; NPSN dan jenjang opsional. Langkah berikutnya: Anda mencocokkan kolom. Mengimpor ulang aman: sekolah yang sama diperbarui, bukan digandakan, dan data lama tidak dihapus.</p>';
		echo '<form method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="kgtk_sekolah_upload">';
		wp_nonce_field( 'kgtk_sekolah_upload' );
		echo '<label class="kgtk-field"><span>Berkas (.xlsx, .xls, .csv, maksimal 10 MB)</span><input type="file" class="kgtk-input" name="berkas" accept=".xlsx,.xls,.csv" required></label>';
		echo '<div class="kgtk-form-actions"><button type="submit" class="kgtk-btn">Unggah dan lanjut</button></div></form></section>';
	}

	public static function handle_upload() {
		self::cek_akses();
		check_admin_referer( 'kgtk_sekolah_upload' );

		$f = isset( $_FILES['berkas'] ) ? $_FILES['berkas'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! $f || ! isset( $f['error'] ) || UPLOAD_ERR_OK !== (int) $f['error'] || empty( $f['tmp_name'] ) ) {
			self::kembali( 'kgtk-sekolah', array( 'err' => 'upload' ) );
		}
		$nama = sanitize_file_name( wp_unslash( (string) $f['name'] ) );
		$ext  = strtolower( pathinfo( $nama, PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, array( 'xlsx', 'xls', 'csv' ), true ) ) {
			self::kembali( 'kgtk-sekolah', array( 'err' => 'ext' ) );
		}
		if ( (int) $f['size'] > self::MAKS_UKURAN ) {
			self::kembali( 'kgtk-sekolah', array( 'err' => 'ukuran' ) );
		}
		if ( 'csv' !== $ext && ! class_exists( '\PhpOffice\PhpSpreadsheet\IOFactory' ) ) {
			self::kembali( 'kgtk-sekolah', array( 'err' => 'lib' ) );
		}

		$dir = self::dir_impor();
		self::bersihkan_lama( $dir );
		$dest = $dir . '/' . wp_generate_password( 20, false ) . '.' . $ext;
		if ( ! move_uploaded_file( $f['tmp_name'], $dest ) ) {
			self::kembali( 'kgtk-sekolah', array( 'err' => 'simpan' ) );
		}

		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			wp_raise_memory_limit( 'admin' );
		}
		$rows = self::baca_baris( $dest, 20 );
		if ( ! $rows ) {
			@unlink( $dest ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			self::kembali( 'kgtk-sekolah', array( 'err' => 'baca' ) );
		}

		$token = wp_generate_password( 16, false );
		set_transient(
			'kgtk_imp_' . $token,
			array(
				'path'   => $dest,
				'nama'   => $nama,
				'user'   => get_current_user_id(),
				'header' => self::deteksi_header( $rows ),
			),
			HOUR_IN_SECONDS
		);
		self::kembali( 'kgtk-sekolah', array( 'step' => 'map', 't' => $token ) );
	}

	private static function ambil_sesi( $token ) {
		$token = preg_replace( '/[^A-Za-z0-9]/', '', (string) $token );
		$d     = $token ? get_transient( 'kgtk_imp_' . $token ) : false;
		if ( ! is_array( $d ) || (int) $d['user'] !== get_current_user_id() || ! is_file( $d['path'] ) ) {
			return false;
		}
		$d['token'] = $token;
		return $d;
	}

	private static function view_map() {
		$d = self::ambil_sesi( isset( $_GET['t'] ) ? wp_unslash( $_GET['t'] ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput
		KGTK_Admin::head( 'Cocokkan kolom', 'Tentukan kolom mana yang berisi kabupaten/kota dan nama sekolah.', '<a class="kgtk-btn kgtk-btn--ghost" href="' . esc_url( admin_url( 'admin.php?page=kgtk-sekolah' ) ) . '">Batal</a>' );
		if ( ! $d ) {
			echo '<div class="kgtk-alert">' . esc_html( self::pesan_error( 'kedaluwarsa' ) ) . '</div>';
			return;
		}
		$rows = self::baca_baris( $d['path'], (int) $d['header'] + 7 );
		if ( ! $rows || ! isset( $rows[ $d['header'] ] ) ) {
			echo '<div class="kgtk-alert">' . esc_html( self::pesan_error( 'baca' ) ) . '</div>';
			return;
		}
		$header = $rows[ $d['header'] ];
		$tebak  = self::tebak_kolom( $header );
		$data   = array_slice( $rows, (int) $d['header'] + 1, 5 );

		$opsi = function ( $dipilih, $wajib ) use ( $header ) {
			$h = $wajib ? '' : '<option value="-1">- tidak dipakai -</option>';
			foreach ( $header as $i => $nm ) {
				$h .= '<option value="' . (int) $i . '"' . selected( $dipilih, $i, false ) . '>' . esc_html( self::huruf( $i ) . ': ' . ( '' !== $nm ? $nm : '(kosong)' ) ) . '</option>';
			}
			return $h;
		};

		echo '<form class="kgtk-card kgtk-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="kgtk_sekolah_import"><input type="hidden" name="t" value="' . esc_attr( $d['token'] ) . '">';
		wp_nonce_field( 'kgtk_sekolah_import' );
		echo '<p class="kgtk-muted">Berkas: <strong>' . esc_html( $d['nama'] ) . '</strong>. Baris judul terdeteksi di baris ' . ( (int) $d['header'] + 1 ) . '. Periksa pilihan di bawah, lalu klik Impor.</p>';
		echo '<div class="kgtk-row2">';
		echo '<label class="kgtk-field"><span>Kolom Kabupaten/Kota *</span><select class="kgtk-input" name="kol_kabkota">' . $opsi( $tebak['kabkota'], true ) . '</select></label>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<label class="kgtk-field"><span>Kolom Nama sekolah *</span><select class="kgtk-input" name="kol_nama">' . $opsi( $tebak['nama'], true ) . '</select></label>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<label class="kgtk-field"><span>Kolom NPSN (opsional)</span><select class="kgtk-input" name="kol_npsn">' . $opsi( $tebak['npsn'], false ) . '</select></label>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<label class="kgtk-field"><span>Kolom Jenjang (opsional)</span><select class="kgtk-input" name="kol_jenjang">' . $opsi( $tebak['jenjang'], false ) . '</select></label>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '</div>';

		echo '<h3 class="kgtk-h3">Contoh isi berkas</h3><div class="kgtk-scroll"><table class="kgtk-table kgtk-table--ringkas"><thead><tr>';
		foreach ( $header as $i => $nm ) {
			echo '<th>' . esc_html( self::huruf( $i ) . ': ' . $nm ) . '</th>';
		}
		echo '</tr></thead><tbody>';
		foreach ( $data as $r ) {
			echo '<tr>';
			foreach ( $header as $i => $nm ) {
				echo '<td>' . esc_html( isset( $r[ $i ] ) ? $r[ $i ] : '' ) . '</td>';
			}
			echo '</tr>';
		}
		echo '</tbody></table></div>';
		echo '<div class="kgtk-form-actions"><button type="submit" class="kgtk-btn">Impor sekarang</button></div></form>';
	}

	/**
	 * Inti impor: simpan baris ke tabel sekolah tanpa menggandakan (NPSN, atau kabupaten/kota + nama).
	 * $awal = indeks baris data pertama; $ik/$in/$ip/$ij = indeks kolom (ip/ij boleh -1).
	 * Mengembalikan array( 'baru' => n, 'ubah' => n, 'lewat' => n ).
	 */
	public static function impor( $rows, $awal, $ik, $in, $ip, $ij ) {
		global $wpdb;
		$t = KGTK_DB::table( 'sekolah' );

		// Data yang sudah ada, untuk mencegah duplikat (NPSN, atau kabupaten/kota + nama).
		$ada_npsn = array();
		$ada_key  = array();
		foreach ( (array) $wpdb->get_results( "SELECT id, kabkota, nama, npsn, jenjang FROM {$t}", ARRAY_A ) as $r ) {
			$r['id'] = (int) $r['id'];
			$key     = strtolower( $r['kabkota'] . '|' . $r['nama'] );
			if ( '' !== $r['npsn'] ) {
				$ada_npsn[ $r['npsn'] ] = $r;
			}
			$ada_key[ $key ] = $r;
		}

		$baru   = 0;
		$ubah   = 0;
		$lewat  = 0;
		$antre  = array();
		$simpan = function () use ( &$antre, $t, $wpdb ) {
			if ( $antre ) {
				$wpdb->query( "INSERT INTO {$t} (kabkota, nama, npsn, jenjang) VALUES " . implode( ',', $antre ) ); // phpcs:ignore WordPress.DB.PreparedSQL
				$antre = array();
			}
		};

		for ( $i = $awal, $n = count( $rows ); $i < $n; $i++ ) {
			$r     = $rows[ $i ];
			$kab   = self::potong( isset( $r[ $ik ] ) ? $r[ $ik ] : '', 80 );
			$nama  = self::potong( isset( $r[ $in ] ) ? $r[ $in ] : '', 200 );
			$npsn  = self::potong( $ip >= 0 && isset( $r[ $ip ] ) ? $r[ $ip ] : '', 20 );
			$jenj  = self::potong( $ij >= 0 && isset( $r[ $ij ] ) ? $r[ $ij ] : '', 40 );
			if ( '' === $kab || '' === $nama ) {
				$lewat++;
				continue;
			}
			$key = strtolower( $kab . '|' . $nama );
			$ex  = null;
			if ( '' !== $npsn && isset( $ada_npsn[ $npsn ] ) ) {
				$ex = $ada_npsn[ $npsn ];
			} elseif ( isset( $ada_key[ $key ] ) ) {
				$ex = $ada_key[ $key ];
			}

			if ( $ex ) {
				if ( 0 === $ex['id'] ) { // duplikat di dalam berkas yang sama (belum tersimpan)
					$lewat++;
					continue;
				}
				$set = array();
				if ( $ex['kabkota'] !== $kab ) {
					$set['kabkota'] = $kab;
				}
				if ( $ex['nama'] !== $nama ) {
					$set['nama'] = $nama;
				}
				if ( '' !== $npsn && $ex['npsn'] !== $npsn ) {
					$set['npsn'] = $npsn;
				}
				if ( '' !== $jenj && $ex['jenjang'] !== $jenj ) {
					$set['jenjang'] = $jenj;
				}
				if ( $set ) {
					$wpdb->update( $t, $set, array( 'id' => $ex['id'] ) );
					$ubah++;
				} else {
					$lewat++;
				}
				continue;
			}

			$antre[] = $wpdb->prepare( '(%s, %s, %s, %s)', $kab, $nama, $npsn, $jenj );
			$baru++;
			$rec = array( 'id' => 0, 'kabkota' => $kab, 'nama' => $nama, 'npsn' => $npsn, 'jenjang' => $jenj );
			if ( '' !== $npsn ) {
				$ada_npsn[ $npsn ] = $rec;
			}
			$ada_key[ $key ] = $rec;
			if ( count( $antre ) >= 200 ) {
				$simpan();
			}
		}
		$simpan();

		return array( 'baru' => $baru, 'ubah' => $ubah, 'lewat' => $lewat );
	}

	public static function handle_import() {
		global $wpdb;
		self::cek_akses();
		check_admin_referer( 'kgtk_sekolah_import' );

		$d = self::ambil_sesi( isset( $_POST['t'] ) ? wp_unslash( $_POST['t'] ) : '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! $d ) {
			self::kembali( 'kgtk-sekolah', array( 'err' => 'kedaluwarsa' ) );
		}
		$ik = isset( $_POST['kol_kabkota'] ) ? (int) $_POST['kol_kabkota'] : -1;
		$in = isset( $_POST['kol_nama'] ) ? (int) $_POST['kol_nama'] : -1;
		$ip = isset( $_POST['kol_npsn'] ) ? (int) $_POST['kol_npsn'] : -1;
		$ij = isset( $_POST['kol_jenjang'] ) ? (int) $_POST['kol_jenjang'] : -1;
		if ( $ik < 0 || $in < 0 || $ik === $in ) {
			self::kembali( 'kgtk-sekolah', array( 'step' => 'map', 't' => $d['token'], 'err' => 'kolom' ) );
		}

		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			wp_raise_memory_limit( 'admin' );
		}
		@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		$rows = self::baca_baris( $d['path'] );
		if ( ! $rows ) {
			self::kembali( 'kgtk-sekolah', array( 'err' => 'baca' ) );
		}

		$h = self::impor( $rows, (int) $d['header'] + 1, $ik, $in, $ip, $ij );
		$baru  = $h['baru'];
		$ubah  = $h['ubah'];
		$lewat = $h['lewat'];

		@unlink( $d['path'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		delete_transient( 'kgtk_imp_' . $d['token'] );
		self::kembali( 'kgtk-sekolah', array( 'hasil' => 1, 'baru' => $baru, 'ubah' => $ubah, 'lewat' => $lewat ) );
	}

	/* ====================== Rekap Sekolah ====================== */

	/** Data rekap: sekolah pada kabupaten/kota (dengan jumlah guru) dan guru dari sekolah di luar daftar. */
	public static function rekap_data( $kab, $pel_id, $tampil ) {
		global $wpdb;
		$ts   = KGTK_DB::table( 'sekolah' );
		$tp   = KGTK_DB::table( 'peserta' );
		$tpel = KGTK_DB::table( 'pelatihan' );

		$sekolah = array();
		if ( KGTK_Helpers::KABKOTA_LAIN !== $kab ) {
			$on = 'p.sekolah_id = s.id' . ( $pel_id ? $wpdb->prepare( ' AND p.pelatihan_id = %d', $pel_id ) : '' );
			$hv = 'ikut' === $tampil ? ' HAVING guru > 0' : ( 'belum' === $tampil ? ' HAVING guru = 0' : '' );
			$q  = "SELECT s.id, s.nama, s.npsn, s.jenjang, COUNT(DISTINCT p.nik) AS guru, COUNT(DISTINCT p.pelatihan_id) AS pelatihan,
				GROUP_CONCAT(DISTINCT pel.nama ORDER BY pel.nama SEPARATOR '; ') AS daftar_pelatihan
				FROM {$ts} s
				LEFT JOIN {$tp} p ON {$on}
				LEFT JOIN {$tpel} pel ON pel.id = p.pelatihan_id
				WHERE s.kabkota = %s
				GROUP BY s.id, s.nama, s.npsn, s.jenjang{$hv}
				ORDER BY s.nama";
			$sekolah = (array) $wpdb->get_results( $wpdb->prepare( $q, $kab ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		}

		$luar = array();
		if ( 'belum' !== $tampil ) {
			$w = $wpdb->prepare( 'p.kabkota = %s AND p.sekolah_id = 0', $kab );
			if ( $pel_id ) {
				$w .= $wpdb->prepare( ' AND p.pelatihan_id = %d', $pel_id );
			}
			$luar = (array) $wpdb->get_results(
				"SELECT p.sekolah AS nama, COUNT(DISTINCT p.nik) AS guru, COUNT(DISTINCT p.pelatihan_id) AS pelatihan,
				 GROUP_CONCAT(DISTINCT pel.nama ORDER BY pel.nama SEPARATOR '; ') AS daftar_pelatihan
				 FROM {$tp} p INNER JOIN {$tpel} pel ON pel.id = p.pelatihan_id
				 WHERE {$w} GROUP BY p.sekolah ORDER BY p.sekolah", // phpcs:ignore WordPress.DB.PreparedSQL
				ARRAY_A
			);
		}
		return array( 'sekolah' => $sekolah, 'luar' => $luar );
	}

	/** Parameter filter dari GET, sudah dibersihkan. */
	private static function filter_get() {
		// phpcs:disable WordPress.Security.NonceVerification
		$kab    = isset( $_GET['kab'] ) ? sanitize_text_field( wp_unslash( $_GET['kab'] ) ) : '';
		$pel    = isset( $_GET['pel'] ) ? absint( $_GET['pel'] ) : 0;
		$tampil = isset( $_GET['tampil'] ) ? sanitize_key( wp_unslash( $_GET['tampil'] ) ) : 'semua';
		// phpcs:enable
		if ( ! in_array( $tampil, array( 'semua', 'ikut', 'belum' ), true ) ) {
			$tampil = 'semua';
		}
		$ok = in_array( $kab, array_merge( KGTK_Helpers::kabkota_list(), array( KGTK_Helpers::KABKOTA_LAIN ) ), true );
		return array( $ok ? $kab : '', $pel, $tampil );
	}

	public static function page_rekap() {
		global $wpdb;
		self::cek_akses();
		list( $kab, $pel, $tampil ) = self::filter_get();
		$kabs = KGTK_Helpers::kabkota_list();
		$tpel = KGTK_DB::table( 'pelatihan' );
		$pels = $wpdb->get_results( "SELECT id, nama FROM {$tpel} ORDER BY tanggal_mulai DESC, id DESC" );

		echo '<div class="wrap kgtk-wrap kgtk-ui">';
		$act = '';
		if ( $kab ) {
			$act = '<a class="kgtk-btn" href="' . esc_url(
				wp_nonce_url(
					admin_url( 'admin-post.php?action=kgtk_rekap_export&kab=' . rawurlencode( $kab ) . '&pel=' . (int) $pel . '&tampil=' . rawurlencode( $tampil ) ),
					'kgtk_rekap_export'
				)
			) . '">Export Excel</a>';
		}
		KGTK_Admin::head( 'Rekap Sekolah', 'Sekolah dan guru yang mengikuti pelatihan, per kabupaten/kota.', $act );

		if ( ! $kabs ) {
			echo '<div class="kgtk-card kgtk-empty"><h2>Data sekolah belum diimpor</h2><p>Impor data sekolah dulu di menu Pelatihan KGTK &gt; Data Sekolah.</p><p><a class="kgtk-btn" href="' . esc_url( KGTK_Admin::page_url( 'sekolah' ) ) . '">Buka Data Sekolah</a></p></div></div>';
			return;
		}

		echo '<form method="get" class="kgtk-card kgtk-filter"><input type="hidden" name="page" value="kgtk-rekap">';
		echo '<label class="kgtk-field"><span>Kabupaten/Kota</span><select class="kgtk-input" name="kab"><option value="">- pilih -</option>';
		foreach ( array_merge( $kabs, array( KGTK_Helpers::KABKOTA_LAIN ) ) as $k ) {
			$lbl = KGTK_Helpers::KABKOTA_LAIN === $k ? 'Lainnya (sekolah di luar daftar)' : KGTK_Helpers::format_kabkota_label( $k );
			echo '<option value="' . esc_attr( $k ) . '"' . selected( $kab, $k, false ) . '>' . esc_html( $lbl ) . '</option>';
		}
		echo '</select></label>';
		echo '<label class="kgtk-field"><span>Pelatihan</span><select class="kgtk-input" name="pel"><option value="0">Semua pelatihan</option>';
		foreach ( (array) $pels as $p ) {
			echo '<option value="' . (int) $p->id . '"' . selected( $pel, (int) $p->id, false ) . '>' . esc_html( $p->nama ) . '</option>';
		}
		echo '</select></label>';
		echo '<label class="kgtk-field"><span>Tampilkan</span><select class="kgtk-input" name="tampil">';
		foreach ( array( 'semua' => 'Semua sekolah', 'ikut' => 'Sudah mengikuti', 'belum' => 'Belum mengikuti' ) as $v => $l ) {
			echo '<option value="' . esc_attr( $v ) . '"' . selected( $tampil, $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select></label><button type="submit" class="kgtk-btn">Tampilkan</button></form>';

		if ( ! $kab ) {
			echo '<p class="kgtk-muted" style="margin-top:14px">Pilih kabupaten/kota untuk melihat rekap.</p></div>';
			return;
		}

		$data = self::rekap_data( $kab, $pel, $tampil );
		$ikut = 0;
		$guru = 0;
		foreach ( $data['sekolah'] as $s ) {
			if ( (int) $s['guru'] > 0 ) {
				$ikut++;
			}
			$guru += (int) $s['guru'];
		}
		$total = count( $data['sekolah'] );

		if ( KGTK_Helpers::KABKOTA_LAIN !== $kab && 'semua' === $tampil ) {
			echo '<div class="kgtk-stats"><div class="kgtk-card"><strong>' . (int) $total . '</strong><span>Sekolah</span></div><div class="kgtk-card"><strong>' . (int) $ikut . '</strong><span>Sudah mengikuti</span></div><div class="kgtk-card"><strong>' . (int) ( $total - $ikut ) . '</strong><span>Belum mengikuti</span></div><div class="kgtk-card"><strong>' . (int) $guru . '</strong><span>Guru</span></div></div>';
		}

		if ( KGTK_Helpers::KABKOTA_LAIN !== $kab ) {
			echo '<section class="kgtk-card" style="margin-top:16px"><div class="kgtk-scroll"><table class="kgtk-table kgtk-table--ringkas"><thead><tr><th class="c-no">No</th><th>Sekolah</th><th>NPSN</th><th>Jenjang</th><th class="c-no">Guru</th><th>Status</th></tr></thead><tbody>';
			if ( ! $data['sekolah'] ) {
				echo '<tr><td colspan="6">Tidak ada sekolah untuk filter ini.</td></tr>';
			}
			foreach ( $data['sekolah'] as $i => $s ) {
				$sudah = (int) $s['guru'] > 0;
				echo '<tr><td class="c-no">' . (int) ( $i + 1 ) . '</td><td>' . esc_html( $s['nama'] );
				if ( $sudah && '' !== (string) $s['daftar_pelatihan'] ) {
					echo '<br><small class="kgtk-muted">' . esc_html( $s['daftar_pelatihan'] ) . '</small>';
				}
				echo '</td><td>' . esc_html( $s['npsn'] ) . '</td><td>' . esc_html( $s['jenjang'] ) . '</td><td class="c-no">' . (int) $s['guru'] . '</td>';
				echo '<td><span class="kgtk-badge ' . ( $sudah ? 'is-open' : 'is-full' ) . '">' . ( $sudah ? 'Sudah ikut' : 'Belum ikut' ) . '</span></td></tr>';
			}
			echo '</tbody></table></div></section>';
		}

		if ( $data['luar'] ) {
			echo '<section class="kgtk-card" style="margin-top:16px"><h2 class="kgtk-h2">Guru dari sekolah di luar daftar</h2><p class="kgtk-muted">Sekolah yang diketik sendiri oleh guru karena tidak ada di daftar' . ( KGTK_Helpers::KABKOTA_LAIN === $kab ? '' : ' (kabupaten/kota ini)' ) . '.</p><div class="kgtk-scroll"><table class="kgtk-table kgtk-table--ringkas"><thead><tr><th class="c-no">No</th><th>Sekolah (diketik)</th><th class="c-no">Guru</th><th>Pelatihan</th></tr></thead><tbody>';
			foreach ( $data['luar'] as $i => $s ) {
				echo '<tr><td class="c-no">' . (int) ( $i + 1 ) . '</td><td>' . esc_html( $s['nama'] ) . '</td><td class="c-no">' . (int) $s['guru'] . '</td><td>' . esc_html( $s['daftar_pelatihan'] ) . '</td></tr>';
			}
			echo '</tbody></table></div></section>';
		}
		echo '<p class="kgtk-muted" style="margin-top:12px">Guru yang mendaftar sebelum fitur ini ada tidak memiliki data kabupaten/kota, sehingga tidak masuk rekap.</p></div>';
	}

	public static function handle_export() {
		self::cek_akses();
		check_admin_referer( 'kgtk_rekap_export' );
		if ( ! class_exists( '\PhpOffice\PhpSpreadsheet\Spreadsheet' ) ) {
			wp_die( 'Library Excel belum terpasang. Jalankan <code>composer install --no-dev</code> di folder plugin.', 'Library belum terpasang', array( 'response' => 500 ) );
		}
		list( $kab, $pel, $tampil ) = self::filter_get();
		if ( '' === $kab ) {
			wp_die( 'Kabupaten/kota tidak valid.', 'Permintaan tidak valid', array( 'response' => 400 ) );
		}
		$data = self::rekap_data( $kab, $pel, $tampil );

		$ss = new Spreadsheet();
		$ws = $ss->getActiveSheet();
		$ws->setTitle( 'Rekap Sekolah' );
		$put = function ( $ws, $cell, $v ) {
			$ws->setCellValueExplicit( $cell, (string) $v, DataType::TYPE_STRING );
		};
		$put( $ws, 'A1', 'REKAP SEKOLAH - ' . ( KGTK_Helpers::KABKOTA_LAIN === $kab ? 'LAINNYA' : strtoupper( $kab ) ) );
		$ws->getStyle( 'A1' )->getFont()->setBold( true )->setSize( 14 );
		$ws->mergeCells( 'A1:G1' );
		$nama_pel = 'Semua pelatihan';
		if ( $pel ) {
			$p = KGTK_Helpers::get_by_id( $pel );
			if ( $p ) {
				$nama_pel = $p->nama;
			}
		}
		$put( $ws, 'A2', $nama_pel );
		$ws->mergeCells( 'A2:G2' );

		$heads = array( 'No', 'Sekolah', 'NPSN', 'Jenjang', 'Jumlah Guru', 'Status', 'Pelatihan' );
		foreach ( $heads as $i => $h ) {
			$put( $ws, chr( 65 + $i ) . '4', $h );
		}
		$ws->getStyle( 'A4:G4' )->getFont()->setBold( true );
		$ws->getStyle( 'A4:G4' )->getFill()->setFillType( 'solid' )->getStartColor()->setRGB( 'E4ECF1' );
		$r = 5;
		foreach ( $data['sekolah'] as $i => $s ) {
			$sudah = (int) $s['guru'] > 0;
			$put( $ws, 'A' . $r, $i + 1 );
			$put( $ws, 'B' . $r, $s['nama'] );
			$put( $ws, 'C' . $r, $s['npsn'] );
			$put( $ws, 'D' . $r, $s['jenjang'] );
			$ws->setCellValue( 'E' . $r, (int) $s['guru'] );
			$put( $ws, 'F' . $r, $sudah ? 'Sudah ikut' : 'Belum ikut' );
			$put( $ws, 'G' . $r, $s['daftar_pelatihan'] );
			$r++;
		}
		if ( $r > 5 ) {
			$ws->getStyle( 'A4:G' . ( $r - 1 ) )->getBorders()->getAllBorders()->setBorderStyle( Border::BORDER_THIN );
		}
		foreach ( array( 'A' => 6, 'B' => 46, 'C' => 14, 'D' => 14, 'E' => 12, 'F' => 14, 'G' => 50 ) as $c => $w ) {
			$ws->getColumnDimension( $c )->setWidth( $w );
		}

		if ( $data['luar'] ) {
			$w2 = $ss->createSheet();
			$w2->setTitle( 'Di luar daftar' );
			foreach ( array( 'No', 'Sekolah (diketik)', 'Jumlah Guru', 'Pelatihan' ) as $i => $h ) {
				$put( $w2, chr( 65 + $i ) . '1', $h );
			}
			$w2->getStyle( 'A1:D1' )->getFont()->setBold( true );
			foreach ( $data['luar'] as $i => $s ) {
				$rr = $i + 2;
				$put( $w2, 'A' . $rr, $i + 1 );
				$put( $w2, 'B' . $rr, $s['nama'] );
				$w2->setCellValue( 'C' . $rr, (int) $s['guru'] );
				$put( $w2, 'D' . $rr, $s['daftar_pelatihan'] );
			}
			foreach ( array( 'A' => 6, 'B' => 46, 'C' => 12, 'D' => 50 ) as $c => $w ) {
				$w2->getColumnDimension( $c )->setWidth( $w );
			}
		}
		$ss->setActiveSheetIndex( 0 );

		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}
		nocache_headers();
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( 'rekap-sekolah-' . strtolower( $kab ) . '.xlsx' ) . '"' );
		$writer = new Xlsx( $ss );
		$writer->save( 'php://output' );
		exit;
	}

	/* ====================== Rekap Guru ====================== */

	/** Parameter filter rekap guru dari GET, sudah dibersihkan. */
	private static function filter_guru_get() {
		// phpcs:disable WordPress.Security.NonceVerification
		$kab       = isset( $_GET['kab'] ) ? sanitize_text_field( wp_unslash( $_GET['kab'] ) ) : '';
		$pel       = isset( $_GET['pel'] ) ? absint( $_GET['pel'] ) : 0;
		$jenis_gtk = isset( $_GET['jenis_gtk'] ) ? sanitize_text_field( wp_unslash( $_GET['jenis_gtk'] ) ) : '';
		$search    = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		// phpcs:enable
		$ok_kab = in_array( $kab, array_merge( KGTK_Helpers::kabkota_list(), array( KGTK_Helpers::KABKOTA_LAIN ) ), true );
		$ok_gtk = in_array( $jenis_gtk, KGTK_Helpers::JENIS_GTK, true );
		return array(
			'kab'       => $ok_kab ? $kab : '',
			'pel'       => $pel,
			'jenis_gtk' => $ok_gtk ? $jenis_gtk : '',
			'search'    => $search,
		);
	}

	/**
	 * Mengambil data semua guru yang telah mengikuti pelatihan dan seluruh pelatihan yang diikuti.
	 */
	public static function rekap_guru_data( $filter = array() ) {
		global $wpdb;
		$tp   = KGTK_DB::table( 'peserta' );
		$tg   = KGTK_DB::table( 'guru' );
		$tpel = KGTK_DB::table( 'pelatihan' );
		$tk   = KGTK_DB::table( 'kelas' );

		$kab       = isset( $filter['kab'] ) ? $filter['kab'] : '';
		$pel_id    = isset( $filter['pel'] ) ? (int) $filter['pel'] : 0;
		$jenis_gtk = isset( $filter['jenis_gtk'] ) ? $filter['jenis_gtk'] : '';
		$search    = isset( $filter['search'] ) ? trim( (string) $filter['search'] ) : '';

		$where = 'WHERE 1=1';

		if ( $pel_id > 0 ) {
			$where .= $wpdb->prepare( ' AND p.nik IN (SELECT DISTINCT nik FROM ' . $tp . ' WHERE pelatihan_id = %d)', $pel_id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		if ( '' !== $kab ) {
			if ( KGTK_Helpers::KABKOTA_LAIN === $kab ) {
				$where .= $wpdb->prepare( ' AND (COALESCE(NULLIF(g.kabkota, \'\'), p.kabkota) = %s OR COALESCE(NULLIF(g.kabkota, \'\'), p.kabkota) = \'\')', $kab );
			} else {
				$where .= $wpdb->prepare( ' AND COALESCE(NULLIF(g.kabkota, \'\'), p.kabkota) = %s', $kab );
			}
		}

		if ( '' !== $jenis_gtk ) {
			$where .= $wpdb->prepare( ' AND COALESCE(NULLIF(g.jenis_gtk, \'\'), p.jenis_gtk) = %s', $jenis_gtk );
		}

		if ( '' !== $search ) {
			$s_like = '%' . $wpdb->esc_like( $search ) . '%';
			$where .= $wpdb->prepare(
				' AND (COALESCE(NULLIF(g.nama, \'\'), p.nama) LIKE %s OR p.nik LIKE %s OR COALESCE(NULLIF(g.nip, \'\'), p.nip) LIKE %s OR COALESCE(NULLIF(g.sekolah, \'\'), p.sekolah) LIKE %s)',
				$s_like,
				$s_like,
				$s_like,
				$s_like
			);
		}

		// 1. Ambil data guru unik yang terdaftar pada pelatihan
		$sql_guru = "SELECT
				p.nik,
				MAX(p.id) AS latest_id,
				COALESCE(NULLIF(g.nama, ''), p.nama) AS nama,
				COALESCE(NULLIF(g.sekolah, ''), p.sekolah) AS sekolah,
				COALESCE(NULLIF(g.kabkota, ''), p.kabkota) AS kabkota,
				COALESCE(NULLIF(g.jenis_gtk, ''), p.jenis_gtk) AS jenis_gtk,
				COALESCE(NULLIF(g.peran, ''), p.peran) AS peran,
				COALESCE(NULLIF(g.hp, ''), p.hp) AS hp,
				COALESCE(NULLIF(g.nip, ''), p.nip) AS nip,
				COALESCE(NULLIF(g.nuptk, ''), NULLIF(p.nuptk, ''), NULLIF(g.nisn, ''), p.nisn) AS nuptk,
				COALESCE(NULLIF(g.nisn, ''), p.nisn) AS nisn,
				COALESCE(NULLIF(g.email, ''), p.email) AS email,
				COALESCE(NULLIF(g.email_belajar, ''), p.email_belajar) AS email_belajar,
				COALESCE(NULLIF(g.simpkb, ''), NULLIF(p.simpkb, ''), NULLIF(g.email_guru, ''), p.email_guru) AS simpkb,
				COALESCE(NULLIF(g.email_guru, ''), p.email_guru) AS email_guru,
				COUNT(DISTINCT p.pelatihan_id) AS total_pelatihan
			FROM {$tp} p
			LEFT JOIN {$tg} g ON g.nik = p.nik
			{$where}
			GROUP BY p.nik
			ORDER BY nama ASC";

		$guru_list = (array) $wpdb->get_results( $sql_guru, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( empty( $guru_list ) ) {
			return array(
				'guru'              => array(),
				'total_guru'        => 0,
				'total_partisipasi' => 0,
				'multi_pelatihan'   => 0,
			);
		}

		$niks         = array_column( $guru_list, 'nik' );
		$placeholders = implode( ',', array_fill( 0, count( $niks ), '%s' ) );

		// 2. Ambil seluruh riwayat pelatihan yang telah diikuti oleh guru-guru tersebut
		$sql_riwayat = $wpdb->prepare(
			"SELECT
				p.nik,
				p.pelatihan_id,
				p.peran,
				p.jenis_gtk,
				p.created_at,
				pel.nama AS pelatihan_nama,
				pel.penyelenggara,
				pel.tanggal_mulai,
				pel.tanggal_selesai,
				pel.lokasi,
				COALESCE(k.nama, '') AS kelas_nama
			FROM {$tp} p
			INNER JOIN {$tpel} pel ON pel.id = p.pelatihan_id
			LEFT JOIN {$tk} k ON k.id = p.kelas_id
			WHERE p.nik IN ({$placeholders})
			ORDER BY pel.tanggal_mulai DESC, p.id DESC",
			$niks
		);

		$riwayat_rows = (array) $wpdb->get_results( $sql_riwayat, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$riwayat_by_nik    = array();
		$total_partisipasi = 0;
		foreach ( $riwayat_rows as $r ) {
			$riwayat_by_nik[ $r['nik'] ][] = array(
				'pelatihan_id'    => (int) $r['pelatihan_id'],
				'nama'            => $r['pelatihan_nama'],
				'penyelenggara'   => $r['penyelenggara'],
				'tanggal'         => KGTK_Helpers::rentang_tanggal( $r['tanggal_mulai'], $r['tanggal_selesai'] ),
				'lokasi'          => $r['lokasi'],
				'peran'           => $r['peran'],
				'jenis_gtk'       => $r['jenis_gtk'],
				'kelas'           => $r['kelas_nama'],
				'kelas_label'     => KGTK_Helpers::label_kelas( $r['kelas_nama'] ),
				'created_at'      => $r['created_at'],
			);
			$total_partisipasi++;
		}

		$multi_pelatihan = 0;
		foreach ( $guru_list as &$g ) {
			$g['pelatihan_list'] = isset( $riwayat_by_nik[ $g['nik'] ] ) ? $riwayat_by_nik[ $g['nik'] ] : array();
			if ( count( $g['pelatihan_list'] ) > 1 ) {
				$multi_pelatihan++;
			}
		}
		unset( $g );

		return array(
			'guru'              => $guru_list,
			'total_guru'        => count( $guru_list ),
			'total_partisipasi' => $total_partisipasi,
			'multi_pelatihan'   => $multi_pelatihan,
		);
	}

	public static function page_rekap_guru() {
		global $wpdb;
		self::cek_akses();
		$filter = self::filter_guru_get();
		$kabs   = KGTK_Helpers::kabkota_list();
		$tpel   = KGTK_DB::table( 'pelatihan' );
		$pels   = $wpdb->get_results( "SELECT id, nama FROM {$tpel} ORDER BY tanggal_mulai DESC, id DESC" );

		$data = self::rekap_guru_data( $filter );

		echo '<div class="wrap kgtk-wrap kgtk-ui">';
		$export_url = wp_nonce_url(
			admin_url(
				'admin-post.php?action=kgtk_rekap_guru_export&kab=' . rawurlencode( $filter['kab'] ) .
				'&pel=' . (int) $filter['pel'] .
				'&jenis_gtk=' . rawurlencode( $filter['jenis_gtk'] ) .
				'&s=' . rawurlencode( $filter['search'] )
			),
			'kgtk_rekap_guru_export'
		);
		$act = '<a class="kgtk-btn" href="' . esc_url( $export_url ) . '">&#128190; Export Excel</a>';
		KGTK_Admin::head( 'Rekap Guru', 'Semua guru yang telah mengikuti pelatihan dan seluruh riwayat pelatihan yang diikuti.', $act );

		// Filter Form
		echo '<form method="get" class="kgtk-card kgtk-filter kgtk-filter-rekap-guru">';
		echo '<input type="hidden" name="page" value="kgtk-rekap-guru">';

		echo '<label class="kgtk-field"><span>Pencarian</span>';
		echo '<input type="text" class="kgtk-input" name="s" value="' . esc_attr( $filter['search'] ) . '" placeholder="Ketik nama, NIK, NIP, atau sekolah...">';
		echo '</label>';

		echo '<label class="kgtk-field"><span>Kabupaten/Kota</span><select class="kgtk-input" name="kab"><option value="">Semua Kabupaten/Kota</option>';
		foreach ( array_merge( $kabs, array( KGTK_Helpers::KABKOTA_LAIN ) ) as $k ) {
			$lbl = KGTK_Helpers::KABKOTA_LAIN === $k ? 'Lainnya (di luar daftar)' : KGTK_Helpers::format_kabkota_label( $k );
			echo '<option value="' . esc_attr( $k ) . '"' . selected( $filter['kab'], $k, false ) . '>' . esc_html( $lbl ) . '</option>';
		}
		echo '</select></label>';

		echo '<label class="kgtk-field"><span>Pelatihan</span><select class="kgtk-input" name="pel"><option value="0">Semua pelatihan</option>';
		foreach ( (array) $pels as $p ) {
			echo '<option value="' . (int) $p->id . '"' . selected( $filter['pel'], (int) $p->id, false ) . '>' . esc_html( $p->nama ) . '</option>';
		}
		echo '</select></label>';

		echo '<label class="kgtk-field"><span>Jenis GTK</span><select class="kgtk-input" name="jenis_gtk"><option value="">Semua Jenis GTK</option>';
		foreach ( KGTK_Helpers::JENIS_GTK as $jg ) {
			echo '<option value="' . esc_attr( $jg ) . '"' . selected( $filter['jenis_gtk'], $jg, false ) . '>' . esc_html( $jg ) . '</option>';
		}
		echo '</select></label>';

		echo '<div style="display:flex;gap:8px;align-items:flex-end;">';
		echo '<button type="submit" class="kgtk-btn">Tampilkan</button>';
		if ( '' !== $filter['kab'] || $filter['pel'] > 0 || '' !== $filter['jenis_gtk'] || '' !== $filter['search'] ) {
			echo '<a href="' . esc_url( admin_url( 'admin.php?page=kgtk-rekap-guru' ) ) . '" class="kgtk-btn kgtk-btn--ghost" title="Reset filter">&#10005; Reset</a>';
		}
		echo '</div>';
		echo '</form>';

		// Statistik Ringkas
		echo '<div class="kgtk-stats">';
		echo '<div class="kgtk-card"><strong>' . (int) $data['total_guru'] . '</strong><span>Total Guru</span></div>';
		echo '<div class="kgtk-card"><strong>' . (int) $data['total_partisipasi'] . '</strong><span>Total Pelatihan Diikuti</span></div>';
		echo '<div class="kgtk-card"><strong>' . (int) $data['multi_pelatihan'] . '</strong><span>Guru &gt; 1 Pelatihan</span></div>';
		echo '</div>';

		// Tabel Rekap Guru
		echo '<section class="kgtk-card" style="margin-top:16px">';
		echo '<div class="kgtk-scroll">';
		echo '<table class="kgtk-table kgtk-table--ringkas kgtk-table--kelola">';
		echo '<thead><tr>';
		echo '<th class="c-no">No</th>';
		echo '<th>Guru & Kontak</th>';
		echo '<th>NIK</th>';
		echo '<th>Sekolah & Wilayah</th>';
		echo '<th>Jenis GTK / Peran</th>';
		echo '<th>Pelatihan yang Diikuti</th>';
		echo '</tr></thead><tbody>';

		if ( empty( $data['guru'] ) ) {
			echo '<tr><td colspan="6" style="text-align:center;padding:36px 14px;color:var(--muted)">';
			echo '<p style="font-size:15px;margin-bottom:4px;">&#128100; Belum ada data guru yang mengikuti pelatihan.</p>';
			if ( '' !== $filter['search'] || '' !== $filter['kab'] || $filter['pel'] > 0 || '' !== $filter['jenis_gtk'] ) {
				echo '<p style="font-size:13px;">Tidak ada hasil yang cocok dengan filter. Coba ubah kata kunci atau reset filter.</p>';
			}
			echo '</td></tr>';
		} else {
			$no = 1;
			foreach ( $data['guru'] as $g ) {
				echo '<tr>';
				echo '<td class="c-no"><span class="kgtk-row-num">' . (int) $no++ . '.</span></td>';

				// Kolom 1: Guru & Kontak
				echo '<td>';
				echo '<div class="kgtk-guru-cell">';
				echo '<strong class="kgtk-guru-nama">' . esc_html( $g['nama'] ) . '</strong>';
				if ( $g['hp'] ) {
					$wa_clean = preg_replace( '/[^0-9]/', '', (string) $g['hp'] );
					if ( 0 === strpos( $wa_clean, '0' ) ) {
						$wa_clean = '62' . substr( $wa_clean, 1 );
					} elseif ( 0 !== strpos( $wa_clean, '62' ) && '' !== $wa_clean ) {
						$wa_clean = '62' . $wa_clean;
					}
					echo '<div class="kgtk-guru-kontak">';
					echo '<a href="https://wa.me/' . esc_attr( $wa_clean ) . '" target="_blank" rel="noopener noreferrer" class="kgtk-wa-link" title="Chat WhatsApp">';
					echo '<span class="kgtk-wa-icon">&#128172;</span> ' . esc_html( $g['hp'] );
					echo '</a>';
					echo '</div>';
				}
				$email_show = $g['email_belajar'] ? $g['email_belajar'] : $g['email'];
				if ( $email_show ) {
					echo '<div class="kgtk-date-text">&#9993; ' . esc_html( $email_show ) . '</div>';
				}
				echo '</div>';
				echo '</td>';

				// Kolom 2: NIK
				echo '<td>';
				echo '<div class="kgtk-nik-cell">';
				echo '<code class="kgtk-nik-code">' . esc_html( $g['nik'] ) . '</code>';
				echo '<button type="button" class="kgtk-copy-btn" data-copy-nik="' . esc_attr( $g['nik'] ) . '" title="Salin NIK">&#128203;</button>';
				echo '</div>';
				echo '</td>';

				// Kolom 3: Asal Sekolah & Wilayah
				echo '<td>';
				echo '<div class="kgtk-sekolah-cell">';
				echo '<span class="kgtk-sekolah-nama">' . esc_html( $g['sekolah'] ? $g['sekolah'] : '-' ) . '</span>';
				if ( $g['kabkota'] ) {
					echo '<div class="kgtk-kab-tag">&#128205; ' . esc_html( $g['kabkota'] ) . '</div>';
				}
				if ( $g['nip'] ) {
					echo '<div class="kgtk-date-text">NIP: ' . esc_html( $g['nip'] ) . '</div>';
				}
				$nuptk_show = ! empty( $g['nuptk'] ) ? $g['nuptk'] : ( ! empty( $g['nisn'] ) ? $g['nisn'] : '' );
				if ( $nuptk_show ) {
					echo '<div class="kgtk-date-text">NUPTK: ' . esc_html( $nuptk_show ) . '</div>';
				}
				echo '</div>';
				echo '</td>';

				// Kolom 4: Jenis GTK / Peran
				echo '<td>';
				if ( $g['jenis_gtk'] ) {
					echo '<div style="margin-bottom:4px;"><span class="kgtk-badge is-assigned-tag">' . esc_html( $g['jenis_gtk'] ) . '</span></div>';
				}
				$peran_label = $g['peran'] ? $g['peran'] : 'Peserta';
				$peran_cls   = 'Peserta' === $peran_label ? 'is-peserta' : 'is-lain';
				echo '<span class="kgtk-badge ' . esc_attr( $peran_cls ) . '">' . esc_html( $peran_label ) . '</span>';
				echo '</td>';

				// Kolom 5: Pelatihan yang Diikuti
				echo '<td>';
				$total_pel = count( $g['pelatihan_list'] );
				$count_cls = $total_pel > 1 ? 'is-multi' : '';
				echo '<span class="kgtk-guru-pel-count ' . esc_attr( $count_cls ) . '">' . (int) $total_pel . ' Pelatihan</span>';

				if ( ! empty( $g['pelatihan_list'] ) ) {
					echo '<div class="kgtk-guru-pel-wrap">';
					foreach ( $g['pelatihan_list'] as $p_item ) {
						echo '<div class="kgtk-guru-pel-item">';
						$absen_link = KGTK_Admin::page_url( 'pelatihan', array( 'view' => 'absen', 'id' => (int) $p_item['pelatihan_id'] ) );
						echo '<a href="' . esc_url( $absen_link ) . '" class="kgtk-guru-pel-title" title="Buka lembar absen">' . esc_html( $p_item['nama'] ) . '</a>';
						echo '<div class="kgtk-guru-pel-meta">';
						if ( $p_item['tanggal'] ) {
							echo '<span>&#128197; ' . esc_html( $p_item['tanggal'] ) . '</span>';
						}
						$kl_label = $p_item['kelas_label'] ? $p_item['kelas_label'] : 'Belum Ditentukan';
						echo '<span>&#127979; ' . esc_html( $kl_label ) . '</span>';
						if ( $p_item['peran'] ) {
							$p_cls = 'Peserta' === $p_item['peran'] ? 'is-peserta' : 'is-lain';
							echo '<span class="kgtk-badge ' . esc_attr( $p_cls ) . '" style="padding:1px 6px;font-size:10px;">' . esc_html( $p_item['peran'] ) . '</span>';
						}
						echo '</div>';
						echo '</div>';
					}
					echo '</div>';
				} else {
					echo '<span class="kgtk-muted">-</span>';
				}
				echo '</td>';

				echo '</tr>';
			}
		}

		echo '</tbody></table></div></section>';
		echo '</div>';
	}

	public static function handle_export_guru() {
		self::cek_akses();
		check_admin_referer( 'kgtk_rekap_guru_export' );
		if ( ! class_exists( '\PhpOffice\PhpSpreadsheet\Spreadsheet' ) ) {
			wp_die( 'Library Excel belum terpasang. Jalankan <code>composer install --no-dev</code> di folder plugin.', 'Library belum terpasang', array( 'response' => 500 ) );
		}
		$filter = self::filter_guru_get();
		$data   = self::rekap_guru_data( $filter );

		$ss = new Spreadsheet();
		$ws = $ss->getActiveSheet();
		$ws->setTitle( 'Rekap Guru' );

		$put = function ( $ws, $cell, $v ) {
			$ws->setCellValueExplicit( $cell, (string) $v, DataType::TYPE_STRING );
		};

		// Judul
		$put( $ws, 'A1', 'REKAP GURU PELATIHAN KGTK' );
		$ws->getStyle( 'A1' )->getFont()->setBold( true )->setSize( 14 );
		$ws->mergeCells( 'A1:N1' );

		// Subjudul info filter
		$sub = array();
		if ( '' !== $filter['kab'] ) {
			$sub[] = 'Kabupaten/Kota: ' . ( KGTK_Helpers::KABKOTA_LAIN === $filter['kab'] ? 'Lainnya' : $filter['kab'] );
		}
		if ( $filter['pel'] > 0 ) {
			$p = KGTK_Helpers::get_by_id( $filter['pel'] );
			if ( $p ) {
				$sub[] = 'Pelatihan: ' . $p->nama;
			}
		}
		if ( '' !== $filter['jenis_gtk'] ) {
			$sub[] = 'Jenis GTK: ' . $filter['jenis_gtk'];
		}
		if ( '' !== $filter['search'] ) {
			$sub[] = 'Pencarian: "' . $filter['search'] . '"';
		}
		$sub_txt = empty( $sub ) ? 'Semua Wilayah & Pelatihan' : implode( ' | ', $sub );
		$put( $ws, 'A2', $sub_txt . ' (Total: ' . $data['total_guru'] . ' Guru)' );
		$ws->mergeCells( 'A2:N2' );

		// Header Tabel
		$heads = array(
			'No',
			'NIK',
			'Nama Guru',
			'NIP',
			'NUPTK',
			'Asal Sekolah',
			'Kabupaten/Kota',
			'Jenis GTK',
			'Peran Terakhir',
			'No. HP/WhatsApp',
			'Email',
			'Email belajar.id',
			'Jumlah Pelatihan',
			'Daftar Pelatihan yang Diikuti',
		);
		foreach ( $heads as $i => $h ) {
			$col = chr( 65 + $i ); // A through N
			$put( $ws, $col . '4', $h );
		}
		$ws->getStyle( 'A4:N4' )->getFont()->setBold( true );
		$ws->getStyle( 'A4:N4' )->getFill()->setFillType( 'solid' )->getStartColor()->setRGB( '2F75B5' );
		$ws->getStyle( 'A4:N4' )->getFont()->getColor()->setRGB( 'FFFFFF' );

		$r = 5;
		foreach ( $data['guru'] as $i => $g ) {
			$put( $ws, 'A' . $r, $i + 1 );
			$put( $ws, 'B' . $r, $g['nik'] );
			$put( $ws, 'C' . $r, $g['nama'] );
			$put( $ws, 'D' . $r, $g['nip'] ? $g['nip'] : '-' );
			$nuptk_exp = ! empty( $g['nuptk'] ) ? $g['nuptk'] : ( ! empty( $g['nisn'] ) ? $g['nisn'] : '' );
			$put( $ws, 'E' . $r, $nuptk_exp ? $nuptk_exp : '-' );
			$put( $ws, 'F' . $r, $g['sekolah'] );
			$put( $ws, 'G' . $r, $g['kabkota'] ? $g['kabkota'] : '-' );
			$put( $ws, 'H' . $r, $g['jenis_gtk'] ? $g['jenis_gtk'] : '-' );
			$put( $ws, 'I' . $r, $g['peran'] ? $g['peran'] : '-' );
			$put( $ws, 'J' . $r, $g['hp'] ? $g['hp'] : '-' );
			$put( $ws, 'K' . $r, $g['email'] ? $g['email'] : '-' );
			$put( $ws, 'L' . $r, $g['email_belajar'] ? $g['email_belajar'] : '-' );
			$ws->setCellValue( 'M' . $r, count( $g['pelatihan_list'] ) );

			// Daftar pelatihan format multiline
			$pel_lines = array();
			foreach ( $g['pelatihan_list'] as $idx => $p_item ) {
				$line  = ( $idx + 1 ) . '. ' . $p_item['nama'];
				$extra = array();
				if ( $p_item['tanggal'] ) {
					$extra[] = $p_item['tanggal'];
				}
				if ( $p_item['kelas_label'] ) {
					$extra[] = $p_item['kelas_label'];
				}
				if ( $p_item['peran'] ) {
					$extra[] = $p_item['peran'];
				}
				if ( ! empty( $extra ) ) {
					$line .= ' (' . implode( ', ', $extra ) . ')';
				}
				$pel_lines[] = $line;
			}
			$pel_str = implode( "\n", $pel_lines );
			$put( $ws, 'N' . $r, $pel_str );
			$ws->getStyle( 'N' . $r )->getAlignment()->setWrapText( true );

			$r++;
		}

		if ( $r > 5 ) {
			$ws->getStyle( 'A4:N' . ( $r - 1 ) )->getBorders()->getAllBorders()->setBorderStyle( Border::BORDER_THIN );
		}

		// Widths
		$widths = array(
			'A' => 6,
			'B' => 20,
			'C' => 30,
			'D' => 22,
			'E' => 14,
			'F' => 35,
			'G' => 18,
			'H' => 18,
			'I' => 14,
			'J' => 18,
			'K' => 26,
			'L' => 28,
			'M' => 16,
			'N' => 60,
		);
		foreach ( $widths as $c => $w ) {
			$ws->getColumnDimension( $c )->setWidth( $w );
		}

		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}
		nocache_headers();
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		$fn_suffix = ( '' !== $filter['kab'] ) ? '-' . strtolower( $filter['kab'] ) : '';
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( 'rekap-guru' . $fn_suffix . '.xlsx' ) . '"' );
		$writer = new Xlsx( $ss );
		$writer->save( 'php://output' );
		exit;
	}
}
