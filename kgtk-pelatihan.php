<?php
/**
 * Plugin Name:       KGTK Pelatihan
 * Description:       Pendaftaran pelatihan guru dengan kode pelatihan dan NIK, daftar hadir per kelas, cetak, serta export PDF dan Excel.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            KGTK
 * License:           GPL-2.0-or-later
 * Text Domain:       kgtk-pelatihan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'KGTK_VERSION', '1.0.12' );
define( 'KGTK_FILE', __FILE__ );
define( 'KGTK_PATH', plugin_dir_path( __FILE__ ) );
define( 'KGTK_URL', plugin_dir_url( __FILE__ ) );

// Library PDF/Excel dipasang lewat Composer (lihat README.md).
if ( file_exists( KGTK_PATH . 'vendor/autoload.php' ) ) {
	require_once KGTK_PATH . 'vendor/autoload.php';
}

require_once KGTK_PATH . 'includes/class-kgtk-db.php';
require_once KGTK_PATH . 'includes/class-kgtk-helpers.php';
require_once KGTK_PATH . 'includes/class-kgtk-undangan.php';
require_once KGTK_PATH . 'includes/class-kgtk-rest.php';
require_once KGTK_PATH . 'includes/class-kgtk-export.php';
require_once KGTK_PATH . 'includes/class-kgtk-admin-portal.php';
require_once KGTK_PATH . 'admin/class-kgtk-admin.php';
require_once KGTK_PATH . 'admin/class-kgtk-sekolah-admin.php';
require_once KGTK_PATH . 'public/class-kgtk-public.php';

register_activation_hook( __FILE__, array( 'KGTK_DB', 'activate' ) );

add_action(
	'plugins_loaded',
	function () {
		KGTK_DB::maybe_upgrade();
		KGTK_Undangan::init();
		KGTK_REST::init();
		KGTK_Public::init();
		KGTK_Admin_Portal::init();
		KGTK_Export::init();
		KGTK_Sekolah_Admin::init();
		if ( is_admin() ) {
			KGTK_Admin::init();
		}
	}
);
