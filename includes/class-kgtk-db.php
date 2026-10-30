<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manajemen skema database, migrasi tabel, dan penyediaan halaman pendaftaran.
 */
class KGTK_DB {

	const VERSION         = '20';
	const OPTION_VERSION  = 'kgtk_db_version';
	const OPTION_PAGE_ID  = 'kgtk_page_id';
	const DEFAULT_TIPE_PJ = 'Penanggung Jawab';
	const ROLE_PANITIA    = 'Panitia';

	/**
	 * Mengembalikan nama tabel lengkap dengan prefix WordPress.
	 *
	 * @param string $name Nama pendek tabel (tanpa prefix kgtk_).
	 * @return string
	 */
	public static function table( $name ) {
		global $wpdb;
		return $wpdb->prefix . 'kgtk_' . sanitize_key( $name );
	}

	/**
	 * Hook aktivasi plugin.
	 */
	public static function activate() {
		self::install();
		self::ensure_page();
	}

	/**
	 * Menjalankan instalasi/migrasi skema apabila versi DB belum mutakhir.
	 */
	public static function maybe_upgrade() {
		if ( get_option( self::OPTION_VERSION ) === self::VERSION ) {
			return;
		}
		self::install();
	}

	/**
	 * Orkestrasi pembuatan tabel, migrasi kolom, dan normalisasi data.
	 */
	public static function install() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		foreach ( self::get_table_schemas() as $schema_sql ) {
			dbDelta( $schema_sql );
		}

		self::migrate_pelatihan_columns();
		self::migrate_kelas_columns();
		self::migrate_biodata_tables();
		self::normalize_legacy_data();

		update_option( self::OPTION_VERSION, self::VERSION );
	}

	/**
	 * Definisi skema DDL untuk seluruh tabel plugin (kompatibel penuh dengan format dbDelta).
	 *
	 * @return array<int, string>
	 */
	private static function get_table_schemas() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$p               = $wpdb->prefix;
		$biodata_cols    = self::get_shared_biodata_columns_sql();

		return array(
			"CREATE TABLE {$p}kgtk_pelatihan (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  nama varchar(200) NOT NULL,
  penyelenggara varchar(200) NOT NULL DEFAULT '',
  tanggal_mulai date NOT NULL,
  tanggal_selesai date NOT NULL,
  deadline_pendaftaran datetime DEFAULT NULL,
  lokasi varchar(255) NOT NULL DEFAULT '',
  kabkota varchar(80) NOT NULL DEFAULT '',
  penanggung_jawab varchar(150) NOT NULL DEFAULT '',
  tipe_pj varchar(50) NOT NULL DEFAULT 'Penanggung Jawab',
  keterangan text,
  kode varchar(20) NOT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY kode (kode)
) ENGINE=InnoDB {$charset_collate};",

			"CREATE TABLE {$p}kgtk_kelas (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  pelatihan_id bigint(20) unsigned NOT NULL,
  nama varchar(30) NOT NULL,
  slot int(10) unsigned NOT NULL,
  tanggal_mulai date DEFAULT NULL,
  tanggal_selesai date DEFAULT NULL,
  deadline_pendaftaran datetime DEFAULT NULL,
  urutan int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY  (id),
  UNIQUE KEY pelatihan_nama (pelatihan_id,nama)
) ENGINE=InnoDB {$charset_collate};",

			"CREATE TABLE {$p}kgtk_peserta (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  pelatihan_id bigint(20) unsigned NOT NULL,
  kelas_id bigint(20) unsigned NOT NULL,
{$biodata_cols}
  terverifikasi tinyint(1) NOT NULL DEFAULT 1,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY pelatihan_nik (pelatihan_id,nik),
  KEY kelas_id (kelas_id),
  KEY sekolah_id (sekolah_id),
  KEY nik (nik)
) ENGINE=InnoDB {$charset_collate};",

			"CREATE TABLE {$p}kgtk_guru (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
{$biodata_cols}
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY nik (nik)
) ENGINE=InnoDB {$charset_collate};",

			"CREATE TABLE {$p}kgtk_sekolah (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  kabkota varchar(80) NOT NULL,
  nama varchar(200) NOT NULL,
  npsn varchar(20) NOT NULL DEFAULT '',
  jenjang varchar(40) NOT NULL DEFAULT '',
  PRIMARY KEY  (id),
  KEY kabkota (kabkota),
  KEY npsn (npsn)
) ENGINE=InnoDB {$charset_collate};",

			"CREATE TABLE {$p}kgtk_undangan (
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
) ENGINE=InnoDB {$charset_collate};",

			"CREATE TABLE {$p}kgtk_panitia (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  nama varchar(100) NOT NULL,
  jabatan varchar(100) NOT NULL DEFAULT 'Panitia Pelaksana',
  nik char(16) NOT NULL DEFAULT '',
  nip varchar(20) NOT NULL DEFAULT '',
  nuptk varchar(20) NOT NULL DEFAULT '',
  instansi varchar(200) NOT NULL DEFAULT '',
  kabkota varchar(80) NOT NULL DEFAULT '',
  hp varchar(20) NOT NULL DEFAULT '',
  email varchar(100) NOT NULL DEFAULT '',
  bank varchar(60) NOT NULL DEFAULT '',
  rekening varchar(50) NOT NULL DEFAULT '',
  npwp varchar(30) NOT NULL DEFAULT '',
  catatan text NULL,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY nik (nik),
  KEY nama (nama)
) ENGINE=InnoDB {$charset_collate};",
		);
	}

	/**
	 * Definisi kolom biodata yang dipakai bersama oleh tabel peserta dan guru (DRY).
	 *
	 * @return string
	 */
	private static function get_shared_biodata_columns_sql() {
		return "  nik char(16) NOT NULL,
  nama varchar(100) NOT NULL,
  gelar_depan varchar(30) NOT NULL DEFAULT '',
  gelar_belakang varchar(50) NOT NULL DEFAULT '',
  tempat_lahir varchar(80) NOT NULL DEFAULT '',
  tanggal_lahir varchar(30) NOT NULL DEFAULT '',
  jenis_kelamin varchar(20) NOT NULL DEFAULT '',
  alamat text NULL,
  alamat_instansi text NULL,
  pangkat_golongan varchar(80) NOT NULL DEFAULT '',
  agama varchar(30) NOT NULL DEFAULT '',
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
  simpkb varchar(100) NOT NULL DEFAULT '',";
	}

	/**
	 * Mengambil daftar nama kolom yang ada pada tabel dalam 1 query (menghindari N+1 SHOW COLUMNS).
	 *
	 * @param string $table_name Nama tabel penuh.
	 * @return array<string, int> Hash map nama_kolom => index untuk pencarian O(1).
	 */
	private static function get_existing_columns( $table_name ) {
		global $wpdb;
		$columns = $wpdb->get_col( "SHOW COLUMNS FROM {$table_name}", 0 ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! is_array( $columns ) ) {
			return array();
		}
		return array_flip( $columns );
	}

	/**
	 * Menambahkan kolom yang belum ada pada sebuah tabel berdasarkan spesifikasi deklaratif.
	 *
	 * @param string                $table_name Nama tabel penuh.
	 * @param array<string, string> $columns_spec Pasangan nama_kolom => definisi SQL.
	 * @param array<string, int>    $existing_cols Hash map kolom yang sudah ada.
	 * @return array<string, bool> Daftar kolom yang baru saja ditambahkan.
	 */
	private static function add_missing_columns( $table_name, array $columns_spec, array $existing_cols ) {
		global $wpdb;
		$added = array();

		foreach ( $columns_spec as $col => $definition ) {
			if ( isset( $existing_cols[ $col ] ) ) {
				continue;
			}
			$wpdb->query( "ALTER TABLE {$table_name} ADD {$col} {$definition}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$added[ $col ] = true;
		}

		return $added;
	}

	/**
	 * Migrasi kolom tambahan pada tabel kgtk_pelatihan.
	 */
	private static function migrate_pelatihan_columns() {
		$table         = self::table( 'pelatihan' );
		$existing_cols = self::get_existing_columns( $table );

		$spec = array(
			'tipe_pj'              => "varchar(50) NOT NULL DEFAULT 'Penanggung Jawab' AFTER penanggung_jawab",
			'deadline_pendaftaran' => 'datetime DEFAULT NULL AFTER tanggal_selesai',
			'kabkota'              => "varchar(80) NOT NULL DEFAULT '' AFTER lokasi",
		);

		self::add_missing_columns( $table, $spec, $existing_cols );
	}

	/**
	 * Migrasi kolom jadwal per kelas pada tabel kgtk_kelas.
	 */
	private static function migrate_kelas_columns() {
		global $wpdb;
		$table         = self::table( 'kelas' );
		$existing_cols = self::get_existing_columns( $table );

		$spec = array(
			'tanggal_mulai'        => 'date DEFAULT NULL AFTER slot',
			'tanggal_selesai'      => 'date DEFAULT NULL AFTER tanggal_mulai',
			'deadline_pendaftaran' => 'datetime DEFAULT NULL AFTER tanggal_selesai',
		);

		self::add_missing_columns( $table, $spec, $existing_cols );
		$wpdb->query( "ALTER TABLE {$table} MODIFY COLUMN nama varchar(30) NOT NULL" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Migrasi kolom biodata pada tabel kgtk_peserta dan kgtk_guru.
	 */
	private static function migrate_biodata_tables() {
		global $wpdb;

		$shared_spec = array(
			'jenis_gtk'          => "varchar(50) NOT NULL DEFAULT '' AFTER peran",
			'nuptk'              => "varchar(20) NOT NULL DEFAULT '' AFTER nisn",
			'simpkb'             => "varchar(100) NOT NULL DEFAULT '' AFTER email_guru",
			'gelar_depan'        => "varchar(30) NOT NULL DEFAULT ''",
			'gelar_belakang'     => "varchar(50) NOT NULL DEFAULT ''",
			'tempat_lahir'       => "varchar(80) NOT NULL DEFAULT ''",
			'tanggal_lahir'      => "varchar(30) NOT NULL DEFAULT ''",
			'jenis_kelamin'      => "varchar(20) NOT NULL DEFAULT ''",
			'alamat'             => 'text NULL',
			'alamat_instansi'    => 'text NULL',
			'pangkat_golongan'   => "varchar(80) NOT NULL DEFAULT ''",
			'agama'              => "varchar(30) NOT NULL DEFAULT ''",
			'pendidikan'         => "varchar(50) NOT NULL DEFAULT ''",
			'bank'               => "varchar(60) NOT NULL DEFAULT ''",
			'rekening'           => "varchar(50) NOT NULL DEFAULT ''",
			'npwp'               => "varchar(30) NOT NULL DEFAULT ''",
			'pengalaman_jabatan' => "varchar(255) NOT NULL DEFAULT ''",
		);

		foreach ( array( 'peserta', 'guru' ) as $short_name ) {
			$table         = self::table( $short_name );
			$existing_cols = self::get_existing_columns( $table );
			$spec          = $shared_spec;

			if ( 'peserta' === $short_name ) {
				$spec['terverifikasi'] = 'tinyint(1) NOT NULL DEFAULT 1 AFTER simpkb';
			}

			$added = self::add_missing_columns( $table, $spec, $existing_cols );

			// Backfill kolom legacy bila kolom baru saja ditambahkan
			if ( isset( $added['nuptk'] ) ) {
				$wpdb->query( "UPDATE {$table} SET nuptk = nisn WHERE nuptk = '' AND nisn != ''" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			}
			if ( isset( $added['simpkb'] ) ) {
				$wpdb->query( "UPDATE {$table} SET simpkb = email_guru WHERE simpkb = '' AND email_guru != ''" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			}
		}
	}

	/**
	 * Normalisasi integritas data (memastikan Panitia tidak mengurangi slot kelas peserta).
	 */
	private static function normalize_legacy_data() {
		global $wpdb;
		$table_peserta = self::table( 'peserta' );

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table_peserta} SET kelas_id = 0 WHERE peran = %s AND kelas_id != 0", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				self::ROLE_PANITIA
			)
		);
	}

	/**
	 * Buat halaman pendaftaran berisi shortcode [kgtk_daftar] jika belum tersedia.
	 */
	public static function ensure_page() {
		$page_id = (int) get_option( self::OPTION_PAGE_ID );
		if ( $page_id > 0 && 'publish' === get_post_status( $page_id ) ) {
			return;
		}

		$new_page_id = wp_insert_post(
			array(
				'post_title'   => 'Pendaftaran Pelatihan',
				'post_name'    => 'pendaftaran-pelatihan',
				'post_content' => '[kgtk_daftar]',
				'post_status'  => 'publish',
				'post_type'    => 'page',
			)
		);

		if ( ! $new_page_id || is_wp_error( $new_page_id ) ) {
			return;
		}

		update_option( self::OPTION_PAGE_ID, (int) $new_page_id );
	}
}
