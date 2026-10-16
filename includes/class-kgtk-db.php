<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pembuatan tabel dan halaman pendaftaran.
 */
class KGTK_DB {

	const VERSION = '11';

	public static function table( $name ) {
		global $wpdb;
		return $wpdb->prefix . 'kgtk_' . $name;
	}

	public static function activate() {
		self::install();
		self::ensure_page();
	}

	public static function maybe_upgrade() {
		if ( get_option( 'kgtk_db_version' ) !== self::VERSION ) {
			self::install();
		}
	}

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$c = $wpdb->get_charset_collate();
		$p = $wpdb->prefix;

		$sql   = array();
		$sql[] = "CREATE TABLE {$p}kgtk_pelatihan (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  nama varchar(200) NOT NULL,
  penyelenggara varchar(200) NOT NULL DEFAULT '',
  tanggal_mulai date NOT NULL,
  tanggal_selesai date NOT NULL,
  lokasi varchar(255) NOT NULL DEFAULT '',
  penanggung_jawab varchar(150) NOT NULL DEFAULT '',
  tipe_pj varchar(50) NOT NULL DEFAULT 'Penanggung Jawab',
  keterangan text,
  kode varchar(20) NOT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY kode (kode)
) ENGINE=InnoDB $c;";

		$sql[] = "CREATE TABLE {$p}kgtk_kelas (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  pelatihan_id bigint(20) unsigned NOT NULL,
  nama varchar(20) NOT NULL,
  slot int(10) unsigned NOT NULL,
  urutan int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY  (id),
  UNIQUE KEY pelatihan_nama (pelatihan_id,nama)
) ENGINE=InnoDB $c;";

		$sql[] = "CREATE TABLE {$p}kgtk_peserta (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  pelatihan_id bigint(20) unsigned NOT NULL,
  kelas_id bigint(20) unsigned NOT NULL,
  nik char(16) NOT NULL,
  nama varchar(100) NOT NULL,
  tempat_lahir varchar(80) NOT NULL DEFAULT '',
  tanggal_lahir varchar(30) NOT NULL DEFAULT '',
  jenis_kelamin varchar(20) NOT NULL DEFAULT '',
  alamat text NULL,
  pendidikan varchar(50) NOT NULL DEFAULT '',
  bank varchar(60) NOT NULL DEFAULT '',
  rekening varchar(50) NOT NULL DEFAULT '',
  npwp varchar(30) NOT NULL DEFAULT '',
  pengalaman_jabatan varchar(255) NOT NULL DEFAULT '',
  sekolah varchar(200) NOT NULL,
  kabkota varchar(80) NOT NULL DEFAULT '',
  sekolah_id bigint(20) unsigned NOT NULL DEFAULT 0,
  peran varchar(20) NOT NULL,
  jenis_gtk varchar(50) NOT NULL DEFAULT '',
  hp varchar(20) NOT NULL,
  nisn varchar(20) NOT NULL DEFAULT '',
  nuptk varchar(20) NOT NULL DEFAULT '',
  nip varchar(20) NOT NULL DEFAULT '',
  email varchar(100) NOT NULL DEFAULT '',
  email_belajar varchar(100) NOT NULL DEFAULT '',
  email_guru varchar(100) NOT NULL DEFAULT '',
  simpkb varchar(100) NOT NULL DEFAULT '',
  terverifikasi tinyint(1) NOT NULL DEFAULT 1,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY pelatihan_nik (pelatihan_id,nik),
  KEY kelas_id (kelas_id),
  KEY sekolah_id (sekolah_id),
  KEY nik (nik)
) ENGINE=InnoDB $c;";

		$sql[] = "CREATE TABLE {$p}kgtk_guru (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  nik char(16) NOT NULL,
  nama varchar(100) NOT NULL,
  tempat_lahir varchar(80) NOT NULL DEFAULT '',
  tanggal_lahir varchar(30) NOT NULL DEFAULT '',
  jenis_kelamin varchar(20) NOT NULL DEFAULT '',
  alamat text NULL,
  pendidikan varchar(50) NOT NULL DEFAULT '',
  bank varchar(60) NOT NULL DEFAULT '',
  rekening varchar(50) NOT NULL DEFAULT '',
  npwp varchar(30) NOT NULL DEFAULT '',
  pengalaman_jabatan varchar(255) NOT NULL DEFAULT '',
  sekolah varchar(200) NOT NULL,
  kabkota varchar(80) NOT NULL DEFAULT '',
  sekolah_id bigint(20) unsigned NOT NULL DEFAULT 0,
  peran varchar(20) NOT NULL,
  jenis_gtk varchar(50) NOT NULL DEFAULT '',
  hp varchar(20) NOT NULL,
  nisn varchar(20) NOT NULL DEFAULT '',
  nuptk varchar(20) NOT NULL DEFAULT '',
  nip varchar(20) NOT NULL DEFAULT '',
  email varchar(100) NOT NULL DEFAULT '',
  email_belajar varchar(100) NOT NULL DEFAULT '',
  email_guru varchar(100) NOT NULL DEFAULT '',
  simpkb varchar(100) NOT NULL DEFAULT '',
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY nik (nik)
) ENGINE=InnoDB $c;";

		$sql[] = "CREATE TABLE {$p}kgtk_sekolah (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  kabkota varchar(80) NOT NULL,
  nama varchar(200) NOT NULL,
  npsn varchar(20) NOT NULL DEFAULT '',
  jenjang varchar(40) NOT NULL DEFAULT '',
  PRIMARY KEY  (id),
  KEY kabkota (kabkota),
  KEY npsn (npsn)
) ENGINE=InnoDB $c;";

		$sql[] = "CREATE TABLE {$p}kgtk_undangan (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  pelatihan_id bigint(20) unsigned NOT NULL,
  nama varchar(100) NOT NULL DEFAULT '',
  nik char(16) NOT NULL DEFAULT '',
  hp varchar(20) NOT NULL DEFAULT '',
  hp_norm varchar(20) NOT NULL DEFAULT '',
  email_belajar varchar(100) NOT NULL DEFAULT '',
  nama_norm varchar(100) NOT NULL DEFAULT '',
  peserta_id bigint(20) unsigned NOT NULL DEFAULT 0,
  cocok_dengan varchar(10) NOT NULL DEFAULT '',
  cocok_pada datetime DEFAULT NULL,
  catatan varchar(200) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY pelatihan_id (pelatihan_id),
  KEY nik (nik),
  KEY hp_norm (hp_norm),
  KEY email_belajar (email_belajar),
  KEY peserta_id (peserta_id)
) ENGINE=InnoDB $c;";

		foreach ( $sql as $q ) {
			dbDelta( $q );
		}

		// Kolom jenis_gtk.
		$col_peserta = $wpdb->get_results( "SHOW COLUMNS FROM {$p}kgtk_peserta LIKE 'jenis_gtk'" );
		if ( empty( $col_peserta ) ) {
			$wpdb->query( "ALTER TABLE {$p}kgtk_peserta ADD jenis_gtk varchar(50) NOT NULL DEFAULT '' AFTER peran" );
		}
		$col_guru = $wpdb->get_results( "SHOW COLUMNS FROM {$p}kgtk_guru LIKE 'jenis_gtk'" );
		if ( empty( $col_guru ) ) {
			$wpdb->query( "ALTER TABLE {$p}kgtk_guru ADD jenis_gtk varchar(50) NOT NULL DEFAULT '' AFTER peran" );
		}

		// Kolom tipe_pj pada kgtk_pelatihan (Penanggung Jawab / Ketua).
		$col_tipe_pj = $wpdb->get_results( "SHOW COLUMNS FROM {$p}kgtk_pelatihan LIKE 'tipe_pj'" );
		if ( empty( $col_tipe_pj ) ) {
			$wpdb->query( "ALTER TABLE {$p}kgtk_pelatihan ADD tipe_pj varchar(50) NOT NULL DEFAULT 'Penanggung Jawab' AFTER penanggung_jawab" );
		}

		// Kolom nuptk dan simpkb pada kgtk_peserta dan kgtk_guru.
		$col_nuptk_p = $wpdb->get_results( "SHOW COLUMNS FROM {$p}kgtk_peserta LIKE 'nuptk'" );
		if ( empty( $col_nuptk_p ) ) {
			$wpdb->query( "ALTER TABLE {$p}kgtk_peserta ADD nuptk varchar(20) NOT NULL DEFAULT '' AFTER nisn" );
			$wpdb->query( "UPDATE {$p}kgtk_peserta SET nuptk = nisn WHERE nuptk = '' AND nisn != ''" );
		}
		$col_simpkb_p = $wpdb->get_results( "SHOW COLUMNS FROM {$p}kgtk_peserta LIKE 'simpkb'" );
		if ( empty( $col_simpkb_p ) ) {
			$wpdb->query( "ALTER TABLE {$p}kgtk_peserta ADD simpkb varchar(100) NOT NULL DEFAULT '' AFTER email_guru" );
			$wpdb->query( "UPDATE {$p}kgtk_peserta SET simpkb = email_guru WHERE simpkb = '' AND email_guru != ''" );
		}
		$col_nuptk_g = $wpdb->get_results( "SHOW COLUMNS FROM {$p}kgtk_guru LIKE 'nuptk'" );
		if ( empty( $col_nuptk_g ) ) {
			$wpdb->query( "ALTER TABLE {$p}kgtk_guru ADD nuptk varchar(20) NOT NULL DEFAULT '' AFTER nisn" );
			$wpdb->query( "UPDATE {$p}kgtk_guru SET nuptk = nisn WHERE nuptk = '' AND nisn != ''" );
		}
		$col_simpkb_g = $wpdb->get_results( "SHOW COLUMNS FROM {$p}kgtk_guru LIKE 'simpkb'" );
		if ( empty( $col_simpkb_g ) ) {
			$wpdb->query( "ALTER TABLE {$p}kgtk_guru ADD simpkb varchar(100) NOT NULL DEFAULT '' AFTER email_guru" );
			$wpdb->query( "UPDATE {$p}kgtk_guru SET simpkb = email_guru WHERE simpkb = '' AND email_guru != ''" );
		}

		// Pastikan kolom terverifikasi ada pada kgtk_peserta bila tabel sudah ada sebelumnya.
		$col_terverifikasi = $wpdb->get_results( "SHOW COLUMNS FROM {$p}kgtk_peserta LIKE 'terverifikasi'" );
		if ( empty( $col_terverifikasi ) ) {
			$wpdb->query( "ALTER TABLE {$p}kgtk_peserta ADD terverifikasi tinyint(1) NOT NULL DEFAULT 1 AFTER simpkb" );
		}

		// Kolom biodata baru: tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, pendidikan, bank, rekening, npwp, pengalaman_jabatan.
		$new_cols = array(
			'tempat_lahir'       => "varchar(80) NOT NULL DEFAULT ''",
			'tanggal_lahir'      => "varchar(30) NOT NULL DEFAULT ''",
			'jenis_kelamin'      => "varchar(20) NOT NULL DEFAULT ''",
			'alamat'             => 'text NULL',
			'pendidikan'         => "varchar(50) NOT NULL DEFAULT ''",
			'bank'               => "varchar(60) NOT NULL DEFAULT ''",
			'rekening'           => "varchar(50) NOT NULL DEFAULT ''",
			'npwp'               => "varchar(30) NOT NULL DEFAULT ''",
			'pengalaman_jabatan' => "varchar(255) NOT NULL DEFAULT ''",
		);

		foreach ( array( 'peserta', 'guru' ) as $tbl ) {
			$tname = $p . 'kgtk_' . $tbl;
			foreach ( $new_cols as $col => $def ) {
				$chk = $wpdb->get_results( "SHOW COLUMNS FROM {$tname} LIKE '{$col}'" );
				if ( empty( $chk ) ) {
					$wpdb->query( "ALTER TABLE {$tname} ADD {$col} {$def}" );
				}
			}
		}

		update_option( 'kgtk_db_version', self::VERSION );
	}

	/**
	 * Buat halaman pendaftaran berisi [kgtk_daftar] jika belum ada.
	 */
	public static function ensure_page() {
		$id = (int) get_option( 'kgtk_page_id' );
		if ( $id && 'publish' === get_post_status( $id ) ) {
			return;
		}
		$new = wp_insert_post(
			array(
				'post_title'   => 'Pendaftaran Pelatihan',
				'post_name'    => 'pendaftaran-pelatihan',
				'post_content' => '[kgtk_daftar]',
				'post_status'  => 'publish',
				'post_type'    => 'page',
			)
		);
		if ( $new && ! is_wp_error( $new ) ) {
			update_option( 'kgtk_page_id', (int) $new );
		}
	}
}
