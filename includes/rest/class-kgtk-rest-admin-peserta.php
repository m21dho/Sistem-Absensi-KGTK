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
		if ( empty( $in['pengalaman_jabatan'] ) ) {
			$in['pengalaman_jabatan'] = '-';
		}
		$d  = KGTK_REST_Public::validasi_peserta( $in, true, $p );
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

	/** Admin assign kelas masal (banyak peserta atau panitia sekaligus). */
	public static function admin_bulk_assign_kelas( WP_REST_Request $req ) {
		global $wpdb;
		$in           = self::params( $req );
		$pelatihan_id = isset( $req['id'] ) ? absint( $req['id'] ) : ( isset( $in['pelatihan_id'] ) ? absint( $in['pelatihan_id'] ) : 0 );
		$peserta_ids  = isset( $in['peserta_ids'] ) && is_array( $in['peserta_ids'] ) ? array_map( 'absint', $in['peserta_ids'] ) : array();
		$kelas_id     = isset( $in['kelas_id'] ) ? absint( $in['kelas_id'] ) : 0;

		if ( empty( $peserta_ids ) ) {
			return self::err( 'peserta_kosong', 'Pilih minimal satu peserta atau panitia.', 422 );
		}

		$tp = KGTK_DB::table( 'peserta' );
		if ( ! $pelatihan_id && ! empty( $peserta_ids ) ) {
			$first_id     = (int) $peserta_ids[0];
			$pelatihan_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT pelatihan_id FROM {$tp} WHERE id = %d", $first_id ) );
		}

		$p = $pelatihan_id ? KGTK_Helpers::get_by_id( $pelatihan_id ) : null;
		if ( ! $p ) {
			return self::err( 'tidak_ada', 'Pelatihan tidak ditemukan.', 404 );
		}

		$tk = KGTK_DB::table( 'kelas' );
		if ( $kelas_id > 0 ) {
			$k = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$tk} WHERE id = %d AND pelatihan_id = %d", $kelas_id, $pelatihan_id ) );
			if ( ! $k ) {
				return self::err( 'kelas_invalid', 'Kelas tidak valid untuk pelatihan ini.', 422 );
			}
		}

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

	/** Admin mengubah peran satu peserta (mis. dari Pengajar menjadi Peserta). */
	public static function admin_ubah_peran( WP_REST_Request $req ) {
		global $wpdb;
		$id    = absint( $req['id'] );
		$in    = self::params( $req );
		$peran = isset( $in['peran'] ) ? sanitize_text_field( $in['peran'] ) : '';

		if ( ! in_array( $peran, KGTK_Helpers::PERAN, true ) ) {
			return self::err( 'peran_tidak_valid', 'Peran yang dipilih tidak valid: ' . implode( ', ', KGTK_Helpers::PERAN ), 422 );
		}

		$tp      = KGTK_DB::table( 'peserta' );
		$peserta = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tp} WHERE id = %d", $id ) );
		if ( ! $peserta ) {
			return self::err( 'tidak_ada', 'Peserta tidak ditemukan.', 404 );
		}

		$update = array( 'peran' => $peran );
		if ( 'Peserta' === $peran ) {
			if ( empty( $peserta->jenis_gtk ) || ( ! in_array( $peserta->jenis_gtk, KGTK_Helpers::JABATAN, true ) && ! in_array( $peserta->jenis_gtk, KGTK_Helpers::JENIS_GTK, true ) ) ) {
				$update['jenis_gtk'] = 'Guru';
			}
		}

		$wpdb->update( $tp, $update, array( 'id' => $id ) );

		// Sinkronkan ke master data kgtk_guru
		$tg = KGTK_DB::table( 'guru' );
		$wpdb->update( $tg, $update, array( 'nik' => $peserta->nik ) );

		return self::respond(
			array(
				'peserta_id' => $id,
				'peran'      => $peran,
				'pesan'      => 'Peran berhasil diperbarui menjadi ' . $peran . '.',
			)
		);
	}

	/** Admin mengubah peran banyak peserta sekaligus (bulk peran). */
	public static function admin_bulk_ubah_peran( WP_REST_Request $req ) {
		global $wpdb;
		$pelatihan_id = absint( $req['id'] );
		$in           = self::params( $req );
		$peran        = isset( $in['peran'] ) ? sanitize_text_field( $in['peran'] ) : '';
		$peserta_ids  = isset( $in['peserta_ids'] ) && is_array( $in['peserta_ids'] ) ? array_map( 'absint', $in['peserta_ids'] ) : array();
		$peserta_ids  = array_filter( $peserta_ids );

		if ( empty( $peserta_ids ) ) {
			return self::err( 'kosong', 'Tidak ada peserta yang dipilih.', 422 );
		}

		if ( ! in_array( $peran, KGTK_Helpers::PERAN, true ) ) {
			return self::err( 'peran_tidak_valid', 'Peran yang dipilih tidak valid: ' . implode( ', ', KGTK_Helpers::PERAN ), 422 );
		}

		$tp           = KGTK_DB::table( 'peserta' );
		$placeholders = implode( ',', array_fill( 0, count( $peserta_ids ), '%d' ) );

		$query = $wpdb->prepare(
			"UPDATE {$tp} SET peran = %s WHERE pelatihan_id = %d AND id IN ({$placeholders})",
			array_merge( array( $peran, $pelatihan_id ), $peserta_ids )
		);
		$updated = $wpdb->query( $query );

		// Jika peran diubah menjadi Peserta, pastikan jenis_gtk valid
		if ( 'Peserta' === $peran ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$tp} SET jenis_gtk = 'Guru' WHERE pelatihan_id = %d AND id IN ({$placeholders}) AND (jenis_gtk IS NULL OR jenis_gtk = '' OR jenis_gtk NOT IN ('Guru', 'Tenaga Didik', 'Kepala Sekolah', 'Pengawas Sekolah'))",
					array_merge( array( $pelatihan_id ), $peserta_ids )
				)
			);
		}

		// Update juga ke kgtk_guru
		$niks = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT nik FROM {$tp} WHERE pelatihan_id = %d AND id IN ({$placeholders})",
				array_merge( array( $pelatihan_id ), $peserta_ids )
			)
		);
		if ( ! empty( $niks ) ) {
			$tg = KGTK_DB::table( 'guru' );
			$nik_placeholders = implode( ',', array_fill( 0, count( $niks ), '%s' ) );
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$tg} SET peran = %s WHERE nik IN ({$nik_placeholders})",
					array_merge( array( $peran ), $niks )
				)
			);
		}

		return self::respond(
			array(
				'updated_count' => count( $peserta_ids ),
				'peran'         => $peran,
				'pesan'         => 'Berhasil memperbarui peran ' . count( $peserta_ids ) . ' guru menjadi ' . $peran . '.',
			)
		);
	}

	/** Admin melihat detail satu peserta. */
	public static function admin_get_peserta( WP_REST_Request $req ) {
		global $wpdb;
		$id = absint( $req['id'] );
		$tp = KGTK_DB::table( 'peserta' );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tp} WHERE id = %d", $id ), ARRAY_A );
		if ( ! $row ) {
			return self::err( 'tidak_ada', 'Peserta tidak ditemukan.', 404 );
		}
		return self::respond( array( 'peserta' => $row ) );
	}

	/** Admin memperbarui data peserta. */
	public static function admin_update_peserta( WP_REST_Request $req ) {
		global $wpdb;
		$id = absint( $req['id'] );
		$tp = KGTK_DB::table( 'peserta' );
		$peserta = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tp} WHERE id = %d", $id ) );
		if ( ! $peserta ) {
			return self::err( 'tidak_ada', 'Peserta tidak ditemukan.', 404 );
		}

		$in = self::params( $req );

		// Fallback cerdas untuk field opsional agar tidak memblokir edit admin
		if ( empty( $in['pengalaman_jabatan'] ) ) {
			$in['pengalaman_jabatan'] = ! empty( $peserta->pengalaman_jabatan ) ? $peserta->pengalaman_jabatan : '-';
		}
		if ( empty( $in['alamat_instansi'] ) ) {
			$in['alamat_instansi'] = ! empty( $peserta->alamat_instansi ) ? $peserta->alamat_instansi : ( ! empty( $in['alamat'] ) ? $in['alamat'] : '-' );
		}
		if ( empty( $in['pendidikan'] ) ) {
			$in['pendidikan'] = ! empty( $peserta->pendidikan ) ? $peserta->pendidikan : 'D4 / S1';
		}
		if ( empty( $in['bank'] ) ) {
			$in['bank'] = ! empty( $peserta->bank ) ? $peserta->bank : '-';
		}
		if ( empty( $in['rekening'] ) ) {
			$in['rekening'] = ! empty( $peserta->rekening ) ? $peserta->rekening : '-';
		}

		$target_peran = isset( $in['peran'] ) ? sanitize_text_field( $in['peran'] ) : ( ! empty( $peserta->peran ) ? $peserta->peran : 'Peserta' );
		if ( 'Peserta' === $target_peran ) {
			if ( empty( $in['jenis_gtk'] ) || ( ! in_array( $in['jenis_gtk'], KGTK_Helpers::JABATAN, true ) && ! in_array( $in['jenis_gtk'], KGTK_Helpers::JENIS_GTK, true ) ) ) {
				$in['jenis_gtk'] = ( ! empty( $peserta->jenis_gtk ) && ( in_array( $peserta->jenis_gtk, KGTK_Helpers::JABATAN, true ) || in_array( $peserta->jenis_gtk, KGTK_Helpers::JENIS_GTK, true ) ) ) ? $peserta->jenis_gtk : 'Guru';
			}
		}

		$pelatihan_obj = self::find_pelatihan( $peserta->pelatihan_id );
		$d             = KGTK_REST_Public::validasi_peserta( $in, true, $pelatihan_obj );
		if ( is_wp_error( $d ) ) {
			return $d;
		}

		// Jika NIK diubah, pastikan tidak duplikat di pelatihan ini
		if ( $d['nik'] !== $peserta->nik ) {
			$ada = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$tp} WHERE pelatihan_id = %d AND nik = %s AND id != %d",
					$peserta->pelatihan_id,
					$d['nik'],
					$id
				)
			);
			if ( $ada ) {
				return self::err( 'duplikat_nik', 'NIK ini sudah terdaftar pada pelatihan ini.', 422 );
			}
		}

		$kelas_id = isset( $in['kelas_id'] ) ? absint( $in['kelas_id'] ) : (int) $peserta->kelas_id;

		$update_data = array(
			'nik'                => $d['nik'],
			'nama'               => $d['nama'],
			'gelar_depan'        => $d['gelar_depan'],
			'gelar_belakang'     => $d['gelar_belakang'],
			'tempat_lahir'       => $d['tempat_lahir'],
			'tanggal_lahir'      => $d['tanggal_lahir'],
			'jenis_kelamin'      => $d['jenis_kelamin'],
			'alamat'             => $d['alamat'],
			'alamat_instansi'    => $d['alamat_instansi'],
			'pangkat_golongan'   => $d['pangkat_golongan'],
			'agama'              => $d['agama'],
			'pendidikan'         => $d['pendidikan'],
			'bank'               => $d['bank'],
			'rekening'           => $d['rekening'],
			'npwp'               => $d['npwp'],
			'pengalaman_jabatan' => $d['pengalaman_jabatan'],
			'sekolah'            => $d['sekolah'],
			'kabkota'            => $d['kabkota'],
			'sekolah_id'         => $d['sekolah_id'],
			'peran'              => $d['peran'],
			'jenis_gtk'          => $d['jenis_gtk'],
			'hp'                 => $d['hp'],
			'nisn'               => $d['nisn'],
			'nuptk'              => $d['nuptk'],
			'nip'                => $d['nip'],
			'email'              => $d['email'],
			'email_belajar'      => $d['email_belajar'],
			'email_guru'         => $d['email_guru'],
			'simpkb'             => $d['simpkb'],
			'kelas_id'           => $kelas_id,
		);

		$wpdb->update( $tp, $update_data, array( 'id' => $id ) );

		// Update juga ke kgtk_guru (master data)
		$tg = KGTK_DB::table( 'guru' );
		$guru_ada = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$tg} WHERE nik = %s", $d['nik'] ) );
		$guru_data = $update_data;
		unset( $guru_data['kelas_id'] );
		$guru_data['updated_at'] = current_time( 'mysql' );
		if ( $guru_ada ) {
			$wpdb->update( $tg, $guru_data, array( 'id' => $guru_ada ) );
		} else {
			$wpdb->insert( $tg, $guru_data );
		}

		// Sinkronkan ke data undangan jika terhubung
		KGTK_Undangan::cocokkan_peserta( $id, (int) $peserta->pelatihan_id );

		return self::respond(
			array(
				'peserta_id' => $id,
				'stats'      => self::kelas_payload( KGTK_Helpers::kelas_stats( $peserta->pelatihan_id ) ),
				'unassigned' => KGTK_Helpers::unassigned_count( $peserta->pelatihan_id ),
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

	/** Admin melihat daftar guru di master data yang belum/tidak terdaftar di pelatihan ini. */
	public static function admin_list_guru_master( WP_REST_Request $req ) {
		global $wpdb;
		$pelatihan_id = absint( $req['id'] );
		$tg           = KGTK_DB::table( 'guru' );
		$tp           = KGTK_DB::table( 'peserta' );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT g.id, g.nik, g.nama, g.gelar_depan, g.gelar_belakang, g.sekolah, g.kabkota, g.hp, g.peran, g.jenis_gtk, g.updated_at
				 FROM {$tg} g
				 LEFT JOIN {$tp} p ON p.nik = g.nik AND p.pelatihan_id = %d
				 WHERE p.id IS NULL
				 ORDER BY g.updated_at DESC, g.id DESC
				 LIMIT 100",
				$pelatihan_id
			)
		);

		$list = array();
		foreach ( (array) $rows as $r ) {
			$nama_tampil = KGTK_Helpers::format_nama_gelar( $r->nama, $r->gelar_depan, $r->gelar_belakang );
			$list[] = array(
				'id'             => (int) $r->id,
				'nik'            => $r->nik,
				'nama'           => $r->nama,
				'nama_tampil'    => $nama_tampil,
				'gelar_depan'    => $r->gelar_depan,
				'gelar_belakang' => $r->gelar_belakang,
				'sekolah'        => $r->sekolah,
				'kabkota'        => $r->kabkota,
				'hp'             => $r->hp,
				'peran'          => ! empty( $r->peran ) ? $r->peran : 'Peserta',
				'jenis_gtk'      => $r->jenis_gtk,
				'updated_at'     => $r->updated_at,
			);
		}

		return self::respond( array( 'guru' => $list ) );
	}

	/** Admin memulihkan / mendaftarkan kembali guru dari master data ke pelatihan ini. */
	public static function admin_pulihkan_guru( WP_REST_Request $req ) {
		global $wpdb;
		$pelatihan_id = absint( $req['id'] );
		$in           = self::params( $req );
		$nik          = isset( $in['nik'] ) ? sanitize_text_field( $in['nik'] ) : '';

		if ( ! preg_match( '/^\d{16}$/', $nik ) ) {
			return self::err( 'nik_invalid', 'NIK tidak valid (harus 16 digit).', 422 );
		}

		$p = KGTK_Helpers::get_by_id( $pelatihan_id );
		if ( ! $p ) {
			return self::err( 'tidak_ada', 'Pelatihan tidak ditemukan.', 404 );
		}

		$tp = KGTK_DB::table( 'peserta' );
		$tg = KGTK_DB::table( 'guru' );

		// Cek apakah sudah terdaftar
		$ada = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$tp} WHERE pelatihan_id = %d AND nik = %s", $pelatihan_id, $nik ) );
		if ( $ada ) {
			return self::err( 'sudah_ada', 'Guru dengan NIK ini sudah terdaftar pada pelatihan ini.', 422 );
		}

		// Ambil data lengkap dari master data guru
		$guru = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tg} WHERE nik = %s LIMIT 1", $nik ), ARRAY_A );
		if ( ! $guru ) {
			return self::err( 'guru_tidak_ada', 'Data guru tidak ditemukan di Master Data.', 404 );
		}

		$now = current_time( 'mysql' );
		$insert_data = array(
			'pelatihan_id'       => $pelatihan_id,
			'kelas_id'           => 0, // Unassigned
			'nik'                => $guru['nik'],
			'nama'               => $guru['nama'],
			'gelar_depan'        => $guru['gelar_depan'],
			'gelar_belakang'     => $guru['gelar_belakang'],
			'tempat_lahir'       => $guru['tempat_lahir'],
			'tanggal_lahir'      => $guru['tanggal_lahir'],
			'jenis_kelamin'      => $guru['jenis_kelamin'],
			'alamat'             => $guru['alamat'],
			'alamat_instansi'    => $guru['alamat_instansi'],
			'pangkat_golongan'   => $guru['pangkat_golongan'],
			'agama'              => $guru['agama'],
			'pendidikan'         => $guru['pendidikan'],
			'bank'               => $guru['bank'],
			'rekening'           => $guru['rekening'],
			'npwp'               => $guru['npwp'],
			'pengalaman_jabatan' => $guru['pengalaman_jabatan'],
			'sekolah'            => $guru['sekolah'],
			'kabkota'            => $guru['kabkota'],
			'sekolah_id'         => (int) $guru['sekolah_id'],
			'peran'              => ! empty( $guru['peran'] ) ? $guru['peran'] : 'Peserta',
			'jenis_gtk'          => $guru['jenis_gtk'],
			'hp'                 => $guru['hp'],
			'nisn'               => $guru['nisn'],
			'nuptk'              => $guru['nuptk'],
			'nip'                => $guru['nip'],
			'email'              => $guru['email'],
			'email_belajar'      => $guru['email_belajar'],
			'email_guru'         => $guru['email_guru'],
			'simpkb'             => $guru['simpkb'],
			'setuju_kirim'       => 1,
			'terverifikasi'      => 1,
			'created_at'         => $now,
		);

		$wpdb->insert( $tp, $insert_data );
		$new_id = (int) $wpdb->insert_id;

		if ( ! $new_id ) {
			return self::err( 'gagal_simpan', 'Gagal mendaftarkan kembali peserta ke pelatihan.', 500 );
		}

		// Hubungkan kembali jika ada di data undangan
		try {
			KGTK_Undangan::cocokkan_peserta( $new_id, $pelatihan_id );
		} catch ( \Throwable $e ) {}

		$nama_tampil = KGTK_Helpers::format_nama_gelar( $guru['nama'], $guru['gelar_depan'], $guru['gelar_belakang'] );

		return self::respond(
			array(
				'peserta_id' => $new_id,
				'nama'       => $nama_tampil,
				'pesan'      => 'Guru ' . $nama_tampil . ' berhasil didaftarkan kembali ke pelatihan ini!',
			)
		);
	}
}
