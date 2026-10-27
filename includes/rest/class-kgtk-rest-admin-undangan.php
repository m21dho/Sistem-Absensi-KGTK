<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controller untuk REST API Admin: Pengelolaan data peserta yang ditetapkan (undangan), upload, import, dan sinkronisasi.
 */
class KGTK_REST_Admin_Undangan extends KGTK_REST_Base {

	public static function admin_undangan_list( WP_REST_Request $req ) {
		$pelatihan_id = absint( $req['id'] );
		$p            = KGTK_Helpers::get_by_id( $pelatihan_id );
		if ( ! $p ) {
			return self::err( 'tidak_ada', 'Pelatihan tidak ditemukan.', 404 );
		}

		$filter = sanitize_key( (string) $req->get_param( 'filter' ) );
		if ( ! in_array( $filter, array( 'all', 'sudah', 'belum', 'perlu' ), true ) ) {
			$filter = 'all';
		}
		$search = sanitize_text_field( (string) $req->get_param( 'search' ) );

		return self::respond(
			array(
				'undangan' => KGTK_Undangan::get_list( $pelatihan_id, $filter, $search ),
				'stats'    => KGTK_Undangan::get_stats( $pelatihan_id ),
			)
		);
	}

	public static function admin_tambah_undangan( WP_REST_Request $req ) {
		$pelatihan_id = absint( $req['id'] );
		$p            = KGTK_Helpers::get_by_id( $pelatihan_id );
		if ( ! $p ) {
			return self::err( 'tidak_ada', 'Pelatihan tidak ditemukan.', 404 );
		}

		$in  = self::params( $req );
		$res = KGTK_Undangan::tambah_baris( $pelatihan_id, $in );
		if ( is_wp_error( $res ) ) {
			return $res;
		}

		return self::respond(
			array(
				'item'  => $res,
				'stats' => KGTK_Undangan::get_stats( $pelatihan_id ),
			),
			201
		);
	}

	public static function admin_ubah_undangan( WP_REST_Request $req ) {
		$id  = absint( $req['id'] );
		$in  = self::params( $req );
		$res = KGTK_Undangan::ubah_baris( $id, $in );
		if ( is_wp_error( $res ) ) {
			return $res;
		}

		return self::respond(
			array(
				'item'  => $res,
				'stats' => KGTK_Undangan::get_stats( (int) $res->pelatihan_id ),
			)
		);
	}

	public static function admin_hapus_undangan( WP_REST_Request $req ) {
		$id  = absint( $req['id'] );
		$row = KGTK_Undangan::get_by_id( $id );
		if ( ! $row ) {
			return self::err( 'tidak_ada', 'Data tidak ditemukan.', 404 );
		}

		$pel_id = (int) $row->pelatihan_id;
		$ok     = KGTK_Undangan::hapus_baris( $id );
		if ( ! $ok ) {
			return self::err( 'gagal', 'Gagal menghapus data.', 500 );
		}

		return self::respond(
			array(
				'deleted' => true,
				'stats'   => KGTK_Undangan::get_stats( $pel_id ),
			)
		);
	}

	public static function admin_hubungkan_undangan( WP_REST_Request $req ) {
		$id         = absint( $req['id'] );
		$in         = self::params( $req );
		$peserta_id = isset( $in['peserta_id'] ) ? absint( $in['peserta_id'] ) : 0;
		if ( ! $peserta_id ) {
			return self::err( 'peserta_id', 'Pilih peserta yang valid.', 422 );
		}

		$res = KGTK_Undangan::hubungkan_manual( $id, $peserta_id );
		if ( is_wp_error( $res ) ) {
			return $res;
		}

		return self::respond(
			array(
				'item'  => $res,
				'stats' => KGTK_Undangan::get_stats( (int) $res->pelatihan_id ),
			)
		);
	}

	public static function admin_lepas_undangan( WP_REST_Request $req ) {
		$id  = absint( $req['id'] );
		$res = KGTK_Undangan::lepas_hubungan( $id );
		if ( is_wp_error( $res ) ) {
			return $res;
		}

		return self::respond(
			array(
				'item'  => $res,
				'stats' => KGTK_Undangan::get_stats( (int) $res->pelatihan_id ),
			)
		);
	}

	public static function admin_konfirmasi_undangan( WP_REST_Request $req ) {
		$id  = absint( $req['id'] );
		$res = KGTK_Undangan::konfirmasi( $id );
		if ( is_wp_error( $res ) ) {
			return $res;
		}

		return self::respond(
			array(
				'item'  => $res,
				'stats' => KGTK_Undangan::get_stats( (int) $res->pelatihan_id ),
			)
		);
	}

	public static function admin_sinkron_undangan( WP_REST_Request $req ) {
		$pelatihan_id = absint( $req['id'] );
		$p            = KGTK_Helpers::get_by_id( $pelatihan_id );
		if ( ! $p ) {
			return self::err( 'tidak_ada', 'Pelatihan tidak ditemukan.', 404 );
		}

		$matched = KGTK_Undangan::sinkronkan_pelatihan( $pelatihan_id );

		return self::respond(
			array(
				'matched' => (int) $matched,
				'stats'   => KGTK_Undangan::get_stats( $pelatihan_id ),
			)
		);
	}

	public static function admin_upload_undangan( WP_REST_Request $req ) {
		$pelatihan_id = absint( $req['id'] );
		$p            = KGTK_Helpers::get_by_id( $pelatihan_id );
		if ( ! $p ) {
			return self::err( 'tidak_ada', 'Pelatihan tidak ditemukan.', 404 );
		}

		$f = isset( $_FILES['berkas'] ) ? $_FILES['berkas'] : ( isset( $_FILES['file'] ) ? $_FILES['file'] : null ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! $f || ! isset( $f['error'] ) || UPLOAD_ERR_OK !== (int) $f['error'] || empty( $f['tmp_name'] ) ) {
			return self::err( 'upload', 'Berkas gagal diunggah. Pastikan memilih berkas yang benar.', 400 );
		}

		$nama = sanitize_file_name( wp_unslash( (string) $f['name'] ) );
		$ext  = strtolower( pathinfo( $nama, PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, array( 'xlsx', 'xls', 'csv' ), true ) ) {
			return self::err( 'ext', 'Format berkas harus .xlsx, .xls, atau .csv.', 422 );
		}
		if ( (int) $f['size'] > KGTK_Sekolah_Admin::MAKS_UKURAN ) {
			return self::err( 'ukuran', 'Ukuran berkas maksimal 10 MB.', 422 );
		}
		if ( 'csv' !== $ext && ! class_exists( '\PhpOffice\PhpSpreadsheet\IOFactory' ) ) {
			return self::err( 'lib', 'Library Excel belum terpasang di server.', 500 );
		}

		$dir = KGTK_Sekolah_Admin::dir_impor();
		KGTK_Sekolah_Admin::bersihkan_lama( $dir );

		$token = 'undangan_' . wp_generate_password( 16, false ) . '.' . $ext;
		$dest  = trailingslashit( $dir ) . $token;

		if ( ! move_uploaded_file( $f['tmp_name'], $dest ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
			return self::err( 'simpan', 'Berkas gagal disimpan sementara di server.', 500 );
		}

		$rows = KGTK_Sekolah_Admin::baca_baris( $dest, 20 );
		if ( ! is_array( $rows ) || count( $rows ) < 2 ) {
			@unlink( $dest ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			return self::err( 'baca', 'Berkas tidak bisa dibaca atau tidak memiliki baris data.', 422 );
		}

		$hdr_idx = KGTK_Undangan::deteksi_header( $rows );
		$header  = isset( $rows[ $hdr_idx ] ) ? $rows[ $hdr_idx ] : array();
		$sample  = array_slice( $rows, $hdr_idx + 1, 5 );
		$guessed = KGTK_Undangan::tebak_kolom( $header );

		return self::respond(
			array(
				'token'       => $token,
				'session_key' => $token,
				'header'      => $header,
				'headers'     => $header,
				'sample'      => $sample,
				'preview'     => $sample,
				'guessed'     => $guessed,
				'detected'    => $guessed,
			)
		);
	}

	public static function admin_import_undangan( WP_REST_Request $req ) {
		$pelatihan_id = absint( $req['id'] );
		$p            = KGTK_Helpers::get_by_id( $pelatihan_id );
		if ( ! $p ) {
			return self::err( 'tidak_ada', 'Pelatihan tidak ditemukan.', 404 );
		}

		$in    = self::params( $req );
		$token = sanitize_file_name( (string) ( $in['token'] ?? ( $in['session_key'] ?? '' ) ) );
		$map   = is_array( $in['map'] ?? null ) ? $in['map'] : ( is_array( $in['mapping'] ?? null ) ? $in['mapping'] : array() );

		$dir  = KGTK_Sekolah_Admin::dir_impor();
		$path = trailingslashit( $dir ) . $token;

		if ( ! file_exists( $path ) || ! is_file( $path ) ) {
			return self::err( 'kedaluwarsa', 'Sesi berkas impor sudah kedaluwarsa. Silakan unggah ulang.', 404 );
		}

		$res = KGTK_Undangan::proses_impor( $pelatihan_id, $path, $map );
		if ( is_wp_error( $res ) ) {
			return $res;
		}

		$res['total_rows'] = isset( $res['total_rows'] ) ? $res['total_rows'] : ( $res['imported'] + $res['skipped'] );
		$res['stats']      = KGTK_Undangan::get_stats( $pelatihan_id );
		return self::respond( $res );
	}

	public static function admin_peserta_unlinked( WP_REST_Request $req ) {
		$pelatihan_id = absint( $req['id'] );
		$p            = KGTK_Helpers::get_by_id( $pelatihan_id );
		if ( ! $p ) {
			return self::err( 'tidak_ada', 'Pelatihan tidak ditemukan.', 404 );
		}

		return self::respond(
			array(
				'peserta' => KGTK_Undangan::get_unlinked_peserta( $pelatihan_id ),
			)
		);
	}
}
