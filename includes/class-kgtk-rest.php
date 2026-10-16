<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once KGTK_PATH . 'includes/rest/class-kgtk-rest-base.php';
require_once KGTK_PATH . 'includes/rest/class-kgtk-rest-public.php';
require_once KGTK_PATH . 'includes/rest/class-kgtk-rest-admin-pelatihan.php';
require_once KGTK_PATH . 'includes/rest/class-kgtk-rest-admin-peserta.php';
require_once KGTK_PATH . 'includes/rest/class-kgtk-rest-admin-undangan.php';

/**
 * Main Router untuk REST API namespace kgtk/v1.
 * Mendaftarkan seluruh route dan mendelegasikannya ke sub-controller modular:
 * - KGTK_REST_Public
 * - KGTK_REST_Admin_Pelatihan
 * - KGTK_REST_Admin_Peserta
 * - KGTK_REST_Admin_Undangan
 */
class KGTK_REST extends KGTK_REST_Base {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function routes() {
		$pub   = array( 'KGTK_REST_Base', 'public_permission' );
		$admin = array( 'KGTK_REST_Base', 'admin_permission' );

		/* ------------------ Endpoint Publik ------------------ */

		register_rest_route(
			self::NS,
			'/public/pelatihan',
			array(
				'methods'             => 'GET',
				'callback'            => array( 'KGTK_REST_Public', 'public_info' ),
				'permission_callback' => $pub,
			)
		);
		register_rest_route(
			self::NS,
			'/public/sekolah',
			array(
				'methods'             => 'GET',
				'callback'            => array( 'KGTK_REST_Public', 'public_sekolah' ),
				'permission_callback' => $pub,
			)
		);
		register_rest_route(
			self::NS,
			'/public/cek-nik',
			array(
				'methods'             => 'POST',
				'callback'            => array( 'KGTK_REST_Public', 'public_cek_nik' ),
				'permission_callback' => $pub,
			)
		);
		register_rest_route(
			self::NS,
			'/public/daftar',
			array(
				'methods'             => 'POST',
				'callback'            => array( 'KGTK_REST_Public', 'public_daftar' ),
				'permission_callback' => $pub,
			)
		);
		register_rest_route(
			self::NS,
			'/public/biodata-saya',
			array(
				'methods'             => 'POST',
				'callback'            => array( 'KGTK_REST_Public', 'public_biodata_saya' ),
				'permission_callback' => $pub,
			)
		);

		/* ------------------ Endpoint Admin: Pelatihan ------------------ */

		register_rest_route(
			self::NS,
			'/admin/pelatihan',
			array(
				'methods'             => 'POST',
				'callback'            => array( 'KGTK_REST_Admin_Pelatihan', 'admin_create' ),
				'permission_callback' => $admin,
			)
		);
		register_rest_route(
			self::NS,
			'/admin/pelatihan/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( 'KGTK_REST_Admin_Pelatihan', 'admin_delete' ),
				'permission_callback' => $admin,
			)
		);

		/* ------------------ Endpoint Admin: Peserta ------------------ */

		register_rest_route(
			self::NS,
			'/admin/pelatihan/(?P<id>\d+)/peserta',
			array(
				'methods'             => 'POST',
				'callback'            => array( 'KGTK_REST_Admin_Peserta', 'admin_tambah_peserta' ),
				'permission_callback' => $admin,
			)
		);
		register_rest_route(
			self::NS,
			'/admin/peserta/(?P<id>\d+)/kelas',
			array(
				'methods'             => 'PATCH',
				'callback'            => array( 'KGTK_REST_Admin_Peserta', 'admin_assign_kelas' ),
				'permission_callback' => $admin,
			)
		);
		register_rest_route(
			self::NS,
			'/admin/peserta/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( 'KGTK_REST_Admin_Peserta', 'admin_delete_peserta' ),
				'permission_callback' => $admin,
			)
		);
		register_rest_route(
			self::NS,
			'/admin/pelatihan/(?P<id>\d+)/bulk-kelas',
			array(
				'methods'             => 'POST',
				'callback'            => array( 'KGTK_REST_Admin_Peserta', 'admin_bulk_assign_kelas' ),
				'permission_callback' => $admin,
			)
		);
		register_rest_route(
			self::NS,
			'/admin/pelatihan/(?P<id>\d+)/peserta-list',
			array(
				'methods'             => 'GET',
				'callback'            => array( 'KGTK_REST_Admin_Peserta', 'admin_peserta_list' ),
				'permission_callback' => $admin,
			)
		);
		register_rest_route(
			self::NS,
			'/admin/guru',
			array(
				'methods'             => 'GET',
				'callback'            => array( 'KGTK_REST_Admin_Peserta', 'admin_guru' ),
				'permission_callback' => $admin,
			)
		);
		register_rest_route(
			self::NS,
			'/admin/pelatihan/(?P<id>\d+)/peserta-unlinked',
			array(
				'methods'             => 'GET',
				'callback'            => array( 'KGTK_REST_Admin_Undangan', 'admin_peserta_unlinked' ),
				'permission_callback' => $admin,
			)
		);

		/* ------------------ Endpoint Admin: Peserta Ditetapkan (Undangan) ------------------ */

		register_rest_route(
			self::NS,
			'/admin/pelatihan/(?P<id>\d+)/undangan',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( 'KGTK_REST_Admin_Undangan', 'admin_undangan_list' ),
					'permission_callback' => $admin,
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( 'KGTK_REST_Admin_Undangan', 'admin_tambah_undangan' ),
					'permission_callback' => $admin,
				),
			)
		);
		register_rest_route(
			self::NS,
			'/admin/undangan/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'PUT',
					'callback'            => array( 'KGTK_REST_Admin_Undangan', 'admin_ubah_undangan' ),
					'permission_callback' => $admin,
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( 'KGTK_REST_Admin_Undangan', 'admin_hapus_undangan' ),
					'permission_callback' => $admin,
				),
			)
		);
		register_rest_route(
			self::NS,
			'/admin/undangan/(?P<id>\d+)/hubungkan',
			array(
				'methods'             => 'POST',
				'callback'            => array( 'KGTK_REST_Admin_Undangan', 'admin_hubungkan_undangan' ),
				'permission_callback' => $admin,
			)
		);
		register_rest_route(
			self::NS,
			'/admin/undangan/(?P<id>\d+)/lepas',
			array(
				'methods'             => 'POST',
				'callback'            => array( 'KGTK_REST_Admin_Undangan', 'admin_lepas_undangan' ),
				'permission_callback' => $admin,
			)
		);
		register_rest_route(
			self::NS,
			'/admin/undangan/(?P<id>\d+)/konfirmasi',
			array(
				'methods'             => 'POST',
				'callback'            => array( 'KGTK_REST_Admin_Undangan', 'admin_konfirmasi_undangan' ),
				'permission_callback' => $admin,
			)
		);
		register_rest_route(
			self::NS,
			'/admin/pelatihan/(?P<id>\d+)/undangan/sinkron',
			array(
				'methods'             => 'POST',
				'callback'            => array( 'KGTK_REST_Admin_Undangan', 'admin_sinkron_undangan' ),
				'permission_callback' => $admin,
			)
		);
		register_rest_route(
			self::NS,
			'/admin/pelatihan/(?P<id>\d+)/undangan/upload',
			array(
				'methods'             => 'POST',
				'callback'            => array( 'KGTK_REST_Admin_Undangan', 'admin_upload_undangan' ),
				'permission_callback' => $admin,
			)
		);
		register_rest_route(
			self::NS,
			'/admin/pelatihan/(?P<id>\d+)/undangan/import',
			array(
				'methods'             => 'POST',
				'callback'            => array( 'KGTK_REST_Admin_Undangan', 'admin_import_undangan' ),
				'permission_callback' => $admin,
			)
		);
	}

	/* ====================== Backward Compatibility Proxies ====================== */

	public static function public_info( WP_REST_Request $req ) { return KGTK_REST_Public::public_info( $req ); }
	public static function public_sekolah( WP_REST_Request $req ) { return KGTK_REST_Public::public_sekolah( $req ); }
	public static function public_cek_nik( WP_REST_Request $req ) { return KGTK_REST_Public::public_cek_nik( $req ); }
	public static function public_biodata_saya( WP_REST_Request $req ) { return KGTK_REST_Public::public_biodata_saya( $req ); }
	public static function public_daftar( WP_REST_Request $req ) { return KGTK_REST_Public::public_daftar( $req ); }

	public static function admin_create( WP_REST_Request $req ) { return KGTK_REST_Admin_Pelatihan::admin_create( $req ); }
	public static function admin_delete( WP_REST_Request $req ) { return KGTK_REST_Admin_Pelatihan::admin_delete( $req ); }

	public static function admin_tambah_peserta( WP_REST_Request $req ) { return KGTK_REST_Admin_Peserta::admin_tambah_peserta( $req ); }
	public static function admin_guru( WP_REST_Request $req ) { return KGTK_REST_Admin_Peserta::admin_guru( $req ); }
	public static function admin_assign_kelas( WP_REST_Request $req ) { return KGTK_REST_Admin_Peserta::admin_assign_kelas( $req ); }
	public static function admin_bulk_assign_kelas( WP_REST_Request $req ) { return KGTK_REST_Admin_Peserta::admin_bulk_assign_kelas( $req ); }
	public static function admin_delete_peserta( WP_REST_Request $req ) { return KGTK_REST_Admin_Peserta::admin_delete_peserta( $req ); }
	public static function admin_peserta_list( WP_REST_Request $req ) { return KGTK_REST_Admin_Peserta::admin_peserta_list( $req ); }

	public static function admin_undangan_list( WP_REST_Request $req ) { return KGTK_REST_Admin_Undangan::admin_undangan_list( $req ); }
	public static function admin_tambah_undangan( WP_REST_Request $req ) { return KGTK_REST_Admin_Undangan::admin_tambah_undangan( $req ); }
	public static function admin_ubah_undangan( WP_REST_Request $req ) { return KGTK_REST_Admin_Undangan::admin_ubah_undangan( $req ); }
	public static function admin_hapus_undangan( WP_REST_Request $req ) { return KGTK_REST_Admin_Undangan::admin_hapus_undangan( $req ); }
	public static function admin_hubungkan_undangan( WP_REST_Request $req ) { return KGTK_REST_Admin_Undangan::admin_hubungkan_undangan( $req ); }
	public static function admin_lepas_undangan( WP_REST_Request $req ) { return KGTK_REST_Admin_Undangan::admin_lepas_undangan( $req ); }
	public static function admin_konfirmasi_undangan( WP_REST_Request $req ) { return KGTK_REST_Admin_Undangan::admin_konfirmasi_undangan( $req ); }
	public static function admin_sinkron_undangan( WP_REST_Request $req ) { return KGTK_REST_Admin_Undangan::admin_sinkron_undangan( $req ); }
	public static function admin_upload_undangan( WP_REST_Request $req ) { return KGTK_REST_Admin_Undangan::admin_upload_undangan( $req ); }
	public static function admin_import_undangan( WP_REST_Request $req ) { return KGTK_REST_Admin_Undangan::admin_import_undangan( $req ); }
	public static function admin_peserta_unlinked( WP_REST_Request $req ) { return KGTK_REST_Admin_Undangan::admin_peserta_unlinked( $req ); }
}
