<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controller untuk REST API Admin: Pengelolaan peserta, assign kelas, dan lookup data guru.
 */
class KGTK_REST_Admin_Peserta extends KGTK_REST_Base {

	/** Admin mendaftarkan peserta (mis. guru yang kesulitan memakai teknologi). */
	public static function admin_tambah_peserta( WP_REST_Request $req ) {
		$p = KGTK_Helpers::get_by_id( absint( $req['id'] ) );
		if ( ! $p ) {
			return self::err( 'tidak_ada', 'Pelatihan tidak ditemukan.', 404 );
		}
		$in = self::params( $req );
		$d  = KGTK_REST_Public::validasi_peserta( $in );
		if ( is_wp_error( $d ) ) {
			return $d;
		}
		$kelas_id = isset( $in['kelas_id'] ) ? absint( $in['kelas_id'] ) : 0;
		$res      = KGTK_REST_Public::proses_daftar( $p, $d, $kelas_id, false, false, true );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		return self::respond(
			array(
				'hasil' => $res['hasil'],
				'kelas' => self::kelas_payload( KGTK_Helpers::kelas_stats( $p->id ) ),
			),
			201
		);
	}

	/** Biodata tersimpan berdasarkan NIK atau NIP (untuk mengisi otomatis form admin). */
	public static function admin_guru( WP_REST_Request $req ) {
		global $wpdb;
		$nip = preg_replace( '/\D/', '', sanitize_text_field( (string) $req->get_param( 'nip' ) ) );
		$nik = sanitize_text_field( (string) $req->get_param( 'nik' ) );

		if ( '' !== $nip && preg_match( '/^\d{18}$/', $nip ) ) {
			$g   = KGTK_DB::table( 'guru' );
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT nama, gelar_depan, gelar_belakang, nik, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, alamat_instansi, pangkat_golongan, agama, pendidikan, bank, rekening, npwp, pengalaman_jabatan, sekolah, kabkota, sekolah_id, peran, jenis_gtk, hp, nisn, nuptk, nip, email, email_belajar, email_guru, simpkb FROM {$g} WHERE nip = %s LIMIT 1", $nip ), ARRAY_A );
			if ( ! $row ) {
				$tpan    = KGTK_DB::table( 'panitia' );
				$pan_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tpan} WHERE nip = %s LIMIT 1", $nip ), ARRAY_A );
				if ( $pan_row ) {
					$row = array(
						'nama'      => $pan_row['nama'],
						'nip'       => $pan_row['nip'],
						'nik'       => $pan_row['nik'],
						'sekolah'   => $pan_row['instansi'],
						'kabkota'   => $pan_row['kabkota'],
						'hp'        => $pan_row['hp'],
						'email'     => $pan_row['email'],
						'peran'     => 'Panitia',
						'jenis_gtk' => $pan_row['jabatan'],
					);
				}
			}
			if ( $row ) {
				$row['jabatan'] = ! empty( $row['jenis_gtk'] ) ? $row['jenis_gtk'] : 'Panitia Pelaksana';
				return self::respond( array( 'guru' => $row ) );
			}
		}

		if ( ! preg_match( '/^\d{16}$/', $nik ) ) {
			return self::err( 'nik', 'NIK harus 16 digit angka.', 422 );
		}
		$g   = KGTK_DB::table( 'guru' );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT nama, gelar_depan, gelar_belakang, nik, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, alamat_instansi, pangkat_golongan, agama, pendidikan, bank, rekening, npwp, pengalaman_jabatan, sekolah, kabkota, sekolah_id, peran, jenis_gtk, hp, nisn, nuptk, nip, email, email_belajar, email_guru, simpkb FROM {$g} WHERE nik = %s", $nik ), ARRAY_A );
		if ( $row ) {
			$row['jabatan'] = $row['jenis_gtk'];
			if ( empty( $row['nuptk'] ) && ! empty( $row['nisn'] ) ) {
				$row['nuptk'] = $row['nisn'];
			}
			if ( empty( $row['simpkb'] ) && ! empty( $row['email_guru'] ) ) {
				$row['simpkb'] = $row['email_guru'];
			}
		}
		return self::respond( array( 'guru' => $row ? $row : null ) );
	}

	/** Admin assign / ubah kelas peserta tunggal. */
	public static function admin_assign_kelas( WP_REST_Request $req ) {
		global $wpdb;
		$id = absint( $req['id'] );
		$tp = KGTK_DB::table( 'peserta' );
		$tk = KGTK_DB::table( 'kelas' );

		$peserta = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tp} WHERE id = %d", $id ) );
		if ( ! $peserta ) {
			return self::err( 'tidak_ada', 'Peserta tidak ditemukan.', 404 );
		}

		$in       = self::params( $req );
		$kelas_id = isset( $in['kelas_id'] ) ? absint( $in['kelas_id'] ) : 0;

		$kelas_nama = '';
		if ( $kelas_id > 0 ) {
			$k = $wpdb->get_row( $wpdb->prepare( "SELECT id, nama, slot FROM {$tk} WHERE id = %d AND pelatihan_id = %d", $kelas_id, $peserta->pelatihan_id ) );
			if ( ! $k ) {
				return self::err( 'kelas_invalid', 'Kelas tidak valid untuk pelatihan ini.', 422 );
			}
			$kelas_nama = $k->nama;
		}

		$updated = $wpdb->update(
			$tp,
			array( 'kelas_id' => $kelas_id ),
			array( 'id' => $id ),
			array( '%d' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return self::err( 'gagal', 'Gagal memperbarui kelas peserta.', 500 );
		}

		return self::respond(
			array(
				'peserta_id'  => $id,
				'kelas_id'    => $kelas_id,
				'kelas_nama'  => $kelas_nama,
				'kelas_label' => KGTK_Helpers::label_kelas( $kelas_nama ),
				'stats'       => self::kelas_payload( KGTK_Helpers::kelas_stats( $peserta->pelatihan_id ) ),
				'unassigned'  => KGTK_Helpers::unassigned_count( $peserta->pelatihan_id ),
			)
		);
	}

	/** Admin assign kelas masal (banyak peserta sekaligus). */
	public static function admin_bulk_assign_kelas( WP_REST_Request $req ) {
		global $wpdb;
		$pelatihan_id = absint( $req['id'] );
		$p = KGTK_Helpers::get_by_id( $pelatihan_id );
		if ( ! $p ) {
			return self::err( 'tidak_ada', 'Pelatihan tidak ditemukan.', 404 );
		}

		$in          = self::params( $req );
		$peserta_ids = isset( $in['peserta_ids'] ) && is_array( $in['peserta_ids'] ) ? array_map( 'absint', $in['peserta_ids'] ) : array();
		$kelas_id    = isset( $in['kelas_id'] ) ? absint( $in['kelas_id'] ) : 0;

		if ( empty( $peserta_ids ) ) {
			return self::err( 'peserta_kosong', 'Pilih minimal satu peserta.', 422 );
		}

		$tk = KGTK_DB::table( 'kelas' );
		if ( $kelas_id > 0 ) {
			$k = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$tk} WHERE id = %d AND pelatihan_id = %d", $kelas_id, $pelatihan_id ) );
			if ( ! $k ) {
				return self::err( 'kelas_invalid', 'Kelas tidak valid untuk pelatihan ini.', 422 );
			}
		}

		$tp = KGTK_DB::table( 'peserta' );
		$placeholders = implode( ',', array_fill( 0, count( $peserta_ids ), '%d' ) );
		$query = $wpdb->prepare(
			"UPDATE {$tp} SET kelas_id = %d WHERE pelatihan_id = %d AND id IN ({$placeholders})",
			array_merge( array( $kelas_id, $pelatihan_id ), $peserta_ids )
		);

		$wpdb->query( $query );

		return self::respond(
			array(
				'updated_count' => count( $peserta_ids ),
				'kelas_id'      => $kelas_id,
				'stats'         => self::kelas_payload( KGTK_Helpers::kelas_stats( $pelatihan_id ) ),
				'unassigned'    => KGTK_Helpers::unassigned_count( $pelatihan_id ),
			)
		);
	}

	/** Admin menghapus peserta dari pelatihan. */
	public static function admin_delete_peserta( WP_REST_Request $req ) {
		global $wpdb;
		$id = absint( $req['id'] );
		$tp = KGTK_DB::table( 'peserta' );

		$peserta = $wpdb->get_row( $wpdb->prepare( "SELECT pelatihan_id FROM {$tp} WHERE id = %d", $id ) );
		if ( ! $peserta ) {
			return self::err( 'tidak_ada', 'Peserta tidak ditemukan.', 404 );
		}

		$wpdb->delete( $tp, array( 'id' => $id ), array( '%d' ) );
		KGTK_Undangan::lepas_peserta( $id );

		return self::respond(
			array(
				'deleted'    => true,
				'stats'      => self::kelas_payload( KGTK_Helpers::kelas_stats( $peserta->pelatihan_id ) ),
				'unassigned' => KGTK_Helpers::unassigned_count( $peserta->pelatihan_id ),
			)
		);
	}

	/** Admin melihat daftar peserta lengkap untuk pelatihan. */
	public static function admin_peserta_list( WP_REST_Request $req ) {
		$pelatihan_id = absint( $req['id'] );
		$p = KGTK_Helpers::get_by_id( $pelatihan_id );
		if ( ! $p ) {
			return self::err( 'tidak_ada', 'Pelatihan tidak ditemukan.', 404 );
		}

		$list       = KGTK_Helpers::peserta_pelatihan( $pelatihan_id );
		$linked_map = KGTK_Undangan::linked_peserta_map( $pelatihan_id );
		$out        = array();
		foreach ( (array) $list as $row ) {
			$out[] = array(
				'id'          => (int) $row->id,
				'nik'         => $row->nik,
				'nama'        => $row->nama,
				'sekolah'     => $row->sekolah,
				'kabkota'     => $row->kabkota,
				'peran'       => $row->peran,
				'jenis_gtk'   => $row->jenis_gtk,
				'hp'          => $row->hp,
				'email'       => $row->email,
				'kelas_id'    => (int) $row->kelas_id,
				'kelas_nama'  => $row->kelas_nama,
				'kelas_label' => KGTK_Helpers::label_kelas( $row->kelas_nama ),
				'created_at'  => $row->created_at,
				'ditetapkan'  => ! empty( $linked_map[ (int) $row->id ] ),
			);
		}

		return self::respond(
			array(
				'peserta'    => $out,
				'kelas'      => self::kelas_payload( KGTK_Helpers::kelas_stats( $pelatihan_id ) ),
				'unassigned' => KGTK_Helpers::unassigned_count( $pelatihan_id ),
			)
		);
	}
}
