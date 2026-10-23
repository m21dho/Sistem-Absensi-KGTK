<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controller untuk REST API Admin: Kelola Data Panitia.
 */
class KGTK_REST_Admin_Panitia extends KGTK_REST_Base {

	/**
	 * GET /kgtk/v1/admin/panitia
	 * Ambil daftar semua panitia tersimpan.
	 */
	public static function admin_panitia_list( WP_REST_Request $req ) {
		$s = isset( $req['s'] ) ? sanitize_text_field( wp_unslash( $req['s'] ) ) : '';
		$list = KGTK_Panitia::get_all( $s );
		return self::respond( array( 'panitia' => $list ) );
	}

	/**
	 * POST /kgtk/v1/admin/panitia
	 * Tambah data panitia baru.
	 */
	public static function admin_tambah_panitia( WP_REST_Request $req ) {
		$in   = self::params( $req );
		$nama = self::str( $in, 'nama' );
		if ( '' === $nama ) {
			return self::err( 'nama', 'Nama panitia wajib diisi.', 422 );
		}

		$id = KGTK_Panitia::save( $in );
		if ( is_wp_error( $id ) ) {
			return self::err( 'gagal', $id->get_error_message(), 500 );
		}

		$item = KGTK_Panitia::get_by_id( $id );
		return self::respond(
			array(
				'id'   => $id,
				'item' => $item,
			),
			201
		);
	}

	/**
	 * PUT /kgtk/v1/admin/panitia/(?P<id>\d+)
	 * Perbarui data panitia.
	 */
	public static function admin_ubah_panitia( WP_REST_Request $req ) {
		$id = absint( $req['id'] );
		$existing = KGTK_Panitia::get_by_id( $id );
		if ( ! $existing ) {
			return self::err( 'tidak_ada', 'Data panitia tidak ditemukan.', 404 );
		}

		$in       = self::params( $req );
		$in['id'] = $id;

		$res = KGTK_Panitia::save( $in );
		if ( is_wp_error( $res ) ) {
			return self::err( 'gagal', $res->get_error_message(), 500 );
		}

		$item = KGTK_Panitia::get_by_id( $id );
		return self::respond(
			array(
				'id'   => $id,
				'item' => $item,
			)
		);
	}

	/**
	 * DELETE /kgtk/v1/admin/panitia/(?P<id>\d+)
	 * Hapus panitia dari daftar master.
	 */
	public static function admin_hapus_panitia( WP_REST_Request $req ) {
		$id = absint( $req['id'] );
		$existing = KGTK_Panitia::get_by_id( $id );
		if ( ! $existing ) {
			return self::err( 'tidak_ada', 'Data panitia tidak ditemukan.', 404 );
		}

		$ok = KGTK_Panitia::delete( $id );
		if ( ! $ok ) {
			return self::err( 'gagal', 'Gagal menghapus panitia.', 500 );
		}

		return self::respond( array( 'ok' => true ) );
	}

	/**
	 * POST /kgtk/v1/admin/pelatihan/(?P<id>\d+)/panitia
	 * Tambah panitia ke pelatihan tertentu.
	 */
	public static function admin_tambah_panitia_ke_pelatihan( WP_REST_Request $req ) {
		$pelatihan_id = absint( $req['id'] );
		$pelatihan    = KGTK_Helpers::get_by_id( $pelatihan_id );
		if ( ! $pelatihan ) {
			return self::err( 'tidak_ada', 'Pelatihan tidak ditemukan.', 404 );
		}

		$in           = self::params( $req );
		$panitia_list = array();
		if ( isset( $in['panitia'] ) && is_array( $in['panitia'] ) ) {
			$panitia_list = $in['panitia'];
		} else {
			$panitia_list = array( $in );
		}

		$added = KGTK_Panitia::tambah_ke_pelatihan( $pelatihan_id, $panitia_list );
		if ( ! $added ) {
			return self::err( 'gagal', 'Gagal menambahkan panitia ke pelatihan atau nama masih kosong.', 422 );
		}

		$updated_list = KGTK_Panitia::get_by_pelatihan( $pelatihan_id );
		return self::respond(
			array(
				'ok'      => true,
				'added'   => $added,
				'panitia' => $updated_list,
			),
			201
		);
	}

	/**
	 * DELETE /kgtk/v1/admin/pelatihan/(?P<id>\d+)/panitia/(?P<peserta_id>\d+)
	 * Hapus panitia dari pelatihan tertentu.
	 */
	public static function admin_hapus_panitia_dari_pelatihan( WP_REST_Request $req ) {
		$pelatihan_id = absint( $req['id'] );
		$peserta_id   = absint( $req['peserta_id'] );

		$ok = KGTK_Panitia::hapus_dari_pelatihan( $peserta_id, $pelatihan_id );
		if ( ! $ok ) {
			return self::err( 'gagal', 'Gagal menghapus panitia dari pelatihan.', 500 );
		}

		return self::respond( array( 'ok' => true ) );
	}
}
