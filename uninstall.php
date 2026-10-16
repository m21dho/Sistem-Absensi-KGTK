<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Data hanya dihapus jika opsi di menu Pelatihan KGTK > Pengaturan dicentang.
if ( ! (int) get_option( 'kgtk_hapus_saat_uninstall', 0 ) ) {
	return;
}

global $wpdb;
foreach ( array( 'undangan', 'peserta', 'kelas', 'pelatihan', 'guru' ) as $t ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}kgtk_{$t}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

$page = (int) get_option( 'kgtk_page_id' );
if ( $page ) {
	wp_delete_post( $page, true );
}
delete_option( 'kgtk_page_id' );
delete_option( 'kgtk_db_version' );
delete_option( 'kgtk_hapus_saat_uninstall' );
delete_option( 'kgtk_lebar' );
