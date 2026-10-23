<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controller untuk REST API Admin: Buat Pelatihan & Hapus Pelatihan.
 */
class KGTK_REST_Admin_Pelatihan extends KGTK_REST_Base {

	public static function valid_date( $s ) {
		$d = DateTime::createFromFormat( 'Y-m-d', $s );
		return $d && $d->format( 'Y-m-d' ) === $s;
	}

	public static function admin_create( WP_REST_Request $req ) {
		global $wpdb;
		$in = self::params( $req );

		$nama    = self::str( $in, 'nama' );
		$penyel  = self::str( $in, 'penyelenggara' );
		$mulai   = self::str( $in, 'tanggal_mulai' );
		$selesai = self::str( $in, 'tanggal_selesai' );
		$lokasi  = self::str( $in, 'lokasi' );
		$kabkota = self::str( $in, 'kabkota' );
		$pj      = self::str( $in, 'penanggung_jawab' );
		$tipe_pj = self::str( $in, 'tipe_pj' );
		if ( ! in_array( $tipe_pj, array( 'Penanggung Jawab', 'Ketua' ), true ) ) {
			$tipe_pj = 'Penanggung Jawab';
		}
		$ket     = isset( $in['keterangan'] ) && is_scalar( $in['keterangan'] ) ? sanitize_textarea_field( (string) $in['keterangan'] ) : '';
		$kode    = KGTK_Helpers::normalize_kode( self::str( $in, 'kode' ) );

		if ( '' === $nama ) {
			return self::err( 'nama', 'Nama pelatihan wajib diisi.', 422 );
		}
		if ( self::len( $nama ) > 200 || self::len( $penyel ) > 200 ) {
			return self::err( 'nama', 'Nama pelatihan dan penyelenggara maksimal 200 karakter.', 422 );
		}
		if ( self::len( $pj ) > 150 ) {
			return self::err( 'penanggung_jawab', 'Nama penanggung jawab maksimal 150 karakter.', 422 );
		}
		if ( self::len( $lokasi ) > 255 ) {
			return self::err( 'lokasi', 'Lokasi maksimal 255 karakter.', 422 );
		}
		if ( self::len( $kabkota ) > 80 ) {
			return self::err( 'kabkota', 'Kabupaten/Kota maksimal 80 karakter.', 422 );
		}
		if ( self::len( $ket ) > 2000 ) {
			return self::err( 'keterangan', 'Keterangan maksimal 2000 karakter.', 422 );
		}
		if ( ! self::valid_date( $mulai ) ) {
			return self::err( 'tanggal_mulai', 'Tanggal mulai wajib diisi dengan benar.', 422 );
		}
		if ( '' === $selesai ) {
			$selesai = $mulai;
		}
		if ( ! self::valid_date( $selesai ) ) {
			return self::err( 'tanggal_selesai', 'Tanggal selesai tidak valid.', 422 );
		}
		if ( $selesai < $mulai ) {
			return self::err( 'tanggal_selesai', 'Tanggal selesai tidak boleh sebelum tanggal mulai.', 422 );
		}

		// Kelas: nama bebas, urutan = urutan pengisian.
		$kelas = array();
		$seen  = array();
		$raw   = isset( $in['kelas'] ) && is_array( $in['kelas'] ) ? $in['kelas'] : array();
		if ( count( $raw ) > 20 ) {
			return self::err( 'kelas', 'Maksimal 20 kelas per pelatihan.', 422 );
		}
		foreach ( $raw as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$kn   = self::str( $row, 'nama' );
			$slot = isset( $row['slot'] ) && is_numeric( $row['slot'] ) ? (int) $row['slot'] : 0;
			if ( '' === $kn || self::len( $kn ) > 30 ) {
				return self::err( 'kelas', 'Nama kelas wajib diisi (maksimal 30 karakter).', 422 );
			}
			$key = function_exists( 'mb_strtolower' ) ? mb_strtolower( $kn, 'UTF-8' ) : strtolower( $kn );
			if ( isset( $seen[ $key ] ) ) {
				return self::err( 'kelas', 'Nama kelas tidak boleh sama: ' . $kn . '.', 422 );
			}
			if ( $slot < 1 || $slot > 2000 ) {
				return self::err( 'kelas', 'Slot kelas ' . $kn . ' harus antara 1 dan 2000.', 422 );
			}
			$seen[ $key ] = true;
			$kelas[]      = array( 'nama' => $kn, 'slot' => $slot );
		}
		if ( ! $kelas ) {
			return self::err( 'kelas', 'Tambahkan minimal satu kelas.', 422 );
		}

		// Kode.
		if ( '' === $kode ) {
			$kode = KGTK_Helpers::generate_kode();
		}
		if ( ! KGTK_Helpers::valid_kode( $kode ) ) {
			return self::err( 'kode', 'Kode harus 3-20 karakter: huruf, angka, atau tanda hubung.', 422 );
		}
		if ( KGTK_Helpers::kode_exists( $kode ) ) {
			return self::err( 'kode', 'Kode sudah dipakai pelatihan lain.', 422 );
		}

		// Undangan peserta yang ditetapkan (opsional).
		$und_raw   = isset( $in['undangan'] ) && is_array( $in['undangan'] ) ? $in['undangan'] : array();
		if ( count( $und_raw ) > KGTK_Undangan::MAKS_TOTAL_UNDANGAN ) {
			return self::err( 'undangan', 'Maksimal ' . KGTK_Undangan::MAKS_TOTAL_UNDANGAN . ' peserta yang ditetapkan.', 422 );
		}
		$und_valid = array();
		$seen_und  = array( 'nik' => array(), 'email' => array(), 'hp' => array() );
		foreach ( $und_raw as $i_und => $row_und ) {
			if ( ! is_array( $row_und ) ) {
				continue;
			}
			$v_und = KGTK_Undangan::validasi_baris( $row_und, 0, 0, $seen_und );
			if ( is_wp_error( $v_und ) ) {
				return self::err( 'undangan_invalid', 'Baris peserta ditetapkan #' . ( $i_und + 1 ) . ' tidak valid: ' . $v_und->get_error_message(), 422 );
			}
			$und_valid[] = $v_und;
		}

		$tp = KGTK_DB::table( 'pelatihan' );
		$tk = KGTK_DB::table( 'kelas' );

		$wpdb->query( 'START TRANSACTION' );
		$wpdb->suppress_errors( true );
		$ok = $wpdb->insert(
			$tp,
			array(
				'nama'            => $nama,
				'penyelenggara'   => $penyel,
				'tanggal_mulai'   => $mulai,
				'tanggal_selesai' => $selesai,
				'lokasi'          => $lokasi,
				'kabkota'         => $kabkota,
				'penanggung_jawab' => $pj,
				'tipe_pj'          => $tipe_pj,
				'keterangan'      => $ket,
				'kode'            => $kode,
				'created_at'      => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
		$id = $ok ? (int) $wpdb->insert_id : 0;
		if ( $id ) {
			foreach ( $kelas as $i => $kl ) {
				$r = $wpdb->insert(
					$tk,
					array( 'pelatihan_id' => $id, 'nama' => $kl['nama'], 'slot' => $kl['slot'], 'urutan' => $i + 1 ),
					array( '%d', '%s', '%d', '%d' )
				);
				if ( ! $r ) {
					$id = 0;
					break;
				}
			}
		}

		if ( $id && $und_valid ) {
			$tu  = KGTK_DB::table( 'undangan' );
			$now = current_time( 'mysql' );
			foreach ( $und_valid as $uv ) {
				$ru = $wpdb->insert(
					$tu,
					array(
						'pelatihan_id'  => $id,
						'nama'          => $uv['nama'],
						'nik'           => $uv['nik'],
						'hp'            => $uv['hp'],
						'hp_norm'       => $uv['hp_norm'],
						'email_belajar' => $uv['email_belajar'],
						'nama_norm'     => $uv['nama_norm'],
						'peserta_id'    => 0,
						'cocok_dengan'  => '',
						'cocok_pada'    => null,
						'catatan'       => $uv['catatan'],
						'created_at'    => $now,
					),
					array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
				);
				if ( ! $ru ) {
					$id = 0;
					break;
				}
			}
		}

		// Panitia (opsional).
		$panitia_raw   = isset( $in['panitia'] ) && is_array( $in['panitia'] ) ? $in['panitia'] : array();
		$panitia_valid = array();
		foreach ( $panitia_raw as $row_p ) {
			if ( ! is_array( $row_p ) ) {
				continue;
			}
			$p_nama = self::str( $row_p, 'nama' );
			if ( '' === $p_nama ) {
				continue;
			}
			$panitia_valid[] = array(
				'nama'     => $p_nama,
				'jabatan'  => self::str( $row_p, 'jabatan' ) ?: ( self::str( $row_p, 'tugas' ) ?: 'Panitia Pelaksana' ),
				'nik'      => preg_replace( '/\D/', '', self::str( $row_p, 'nik' ) ),
				'nip'      => preg_replace( '/\D/', '', self::str( $row_p, 'nip' ) ),
				'nuptk'    => preg_replace( '/\D/', '', self::str( $row_p, 'nuptk' ) ),
				'hp'       => self::str( $row_p, 'hp' ),
				'instansi' => self::str( $row_p, 'instansi' ),
				'kabkota'  => self::str( $row_p, 'kabkota' ),
				'email'    => self::str( $row_p, 'email' ),
				'bank'     => self::str( $row_p, 'bank' ),
				'rekening' => self::str( $row_p, 'rekening' ),
				'npwp'     => self::str( $row_p, 'npwp' ),
			);
		}

		if ( $id && ! empty( $panitia_valid ) ) {
			KGTK_Panitia::tambah_ke_pelatihan( $id, $panitia_valid );
		}
		$wpdb->suppress_errors( false );

		if ( ! $id ) {
			$wpdb->query( 'ROLLBACK' );
			return self::err( 'gagal', 'Pelatihan gagal disimpan. Kode mungkin sudah dipakai atau data tidak valid.', 500 );
		}
		$wpdb->query( 'COMMIT' );

		return self::respond(
			array(
				'id'  => $id,
				'url' => KGTK_Admin::page_url( 'pelatihan', array( 'view' => 'absen', 'id' => $id ) ),
			),
			201
		);
	}

	public static function admin_update( WP_REST_Request $req ) {
		global $wpdb;
		$id = absint( $req['id'] );
		$existing = KGTK_Helpers::get_by_id( $id );
		if ( ! $existing ) {
			return self::err( 'tidak_ada', 'Pelatihan tidak ditemukan.', 404 );
		}

		$in = self::params( $req );

		$nama    = self::str( $in, 'nama' );
		$penyel  = self::str( $in, 'penyelenggara' );
		$mulai   = self::str( $in, 'tanggal_mulai' );
		$selesai = self::str( $in, 'tanggal_selesai' );
		$lokasi  = self::str( $in, 'lokasi' );
		$kabkota = self::str( $in, 'kabkota' );
		$pj      = self::str( $in, 'penanggung_jawab' );
		$tipe_pj = self::str( $in, 'tipe_pj' );
		if ( ! in_array( $tipe_pj, array( 'Penanggung Jawab', 'Ketua' ), true ) ) {
			$tipe_pj = 'Penanggung Jawab';
		}
		$ket     = isset( $in['keterangan'] ) && is_scalar( $in['keterangan'] ) ? sanitize_textarea_field( (string) $in['keterangan'] ) : '';
		$kode    = KGTK_Helpers::normalize_kode( self::str( $in, 'kode' ) );

		if ( '' === $nama ) {
			return self::err( 'nama', 'Nama pelatihan wajib diisi.', 422 );
		}
		if ( self::len( $nama ) > 200 || self::len( $penyel ) > 200 ) {
			return self::err( 'nama', 'Nama pelatihan dan penyelenggara maksimal 200 karakter.', 422 );
		}
		if ( self::len( $pj ) > 150 ) {
			return self::err( 'penanggung_jawab', 'Nama penanggung jawab maksimal 150 karakter.', 422 );
		}
		if ( self::len( $lokasi ) > 255 ) {
			return self::err( 'lokasi', 'Lokasi maksimal 255 karakter.', 422 );
		}
		if ( self::len( $kabkota ) > 80 ) {
			return self::err( 'kabkota', 'Kabupaten/Kota maksimal 80 karakter.', 422 );
		}
		if ( self::len( $ket ) > 2000 ) {
			return self::err( 'keterangan', 'Keterangan maksimal 2000 karakter.', 422 );
		}
		if ( ! self::valid_date( $mulai ) ) {
			return self::err( 'tanggal_mulai', 'Tanggal mulai wajib diisi dengan benar.', 422 );
		}
		if ( '' === $selesai ) {
			$selesai = $mulai;
		}
		if ( ! self::valid_date( $selesai ) ) {
			return self::err( 'tanggal_selesai', 'Tanggal selesai tidak valid.', 422 );
		}
		if ( $selesai < $mulai ) {
			return self::err( 'tanggal_selesai', 'Tanggal selesai tidak boleh sebelum tanggal mulai.', 422 );
		}
		if ( '' === $kode || ! preg_match( '/^[A-Za-z0-9-]{3,20}$/', $kode ) ) {
			return self::err( 'kode', 'Kode pelatihan harus 3-20 karakter: huruf, angka, tanda hubung.', 422 );
		}

		// Periksa jika kode diubah ke kode yang sudah dipakai kegiatan lain
		if ( $kode !== $existing->kode ) {
			$tp = KGTK_DB::table( 'pelatihan' );
			$ada = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$tp} WHERE kode = %s AND id != %d", $kode, $id ) );
			if ( $ada ) {
				return self::err( 'kode', 'Kode pelatihan ' . $kode . ' sudah dipakai kegiatan lain.', 422 );
			}
		}

		$tp = KGTK_DB::table( 'pelatihan' );
		$ok = $wpdb->update(
			$tp,
			array(
				'nama'             => $nama,
				'penyelenggara'    => $penyel,
				'tanggal_mulai'    => $mulai,
				'tanggal_selesai'  => $selesai,
				'lokasi'           => $lokasi,
				'kabkota'          => $kabkota,
				'penanggung_jawab' => $pj,
				'tipe_pj'          => $tipe_pj,
				'keterangan'       => $ket,
				'kode'             => $kode,
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $ok ) {
			return self::err( 'gagal', 'Gagal memperbarui pelatihan.', 500 );
		}

		return self::respond(
			array(
				'id'  => $id,
				'ok'  => true,
				'url' => KGTK_Admin::page_url( 'pelatihan', array( 'view' => 'absen', 'id' => $id ) ),
			)
		);
	}

	public static function admin_delete( WP_REST_Request $req ) {
		global $wpdb;
		$id = absint( $req['id'] );
		if ( ! KGTK_Helpers::get_by_id( $id ) ) {
			return self::err( 'tidak_ada', 'Pelatihan tidak ditemukan.', 404 );
		}
		$wpdb->query( 'START TRANSACTION' );
		$a = $wpdb->delete( KGTK_DB::table( 'peserta' ), array( 'pelatihan_id' => $id ), array( '%d' ) );
		$b = $wpdb->delete( KGTK_DB::table( 'kelas' ), array( 'pelatihan_id' => $id ), array( '%d' ) );
		$c = $wpdb->delete( KGTK_DB::table( 'pelatihan' ), array( 'id' => $id ), array( '%d' ) );
		$d = $wpdb->delete( KGTK_DB::table( 'undangan' ), array( 'pelatihan_id' => $id ), array( '%d' ) );
		if ( false === $a || false === $b || false === $c || false === $d ) {
			$wpdb->query( 'ROLLBACK' );
			return self::err( 'gagal', 'Pelatihan gagal dihapus.', 500 );
		}
		$wpdb->query( 'COMMIT' );
		return self::respond( array( 'deleted' => true ) );
	}

	public static function admin_get_atk( WP_REST_Request $req ) {
		$id = absint( $req['id'] );
		$p  = KGTK_Helpers::get_by_id( $id );
		if ( ! $p ) {
			return self::err( 'tidak_ada', 'Pelatihan tidak ditemukan.', 404 );
		}
		$items = KGTK_Helpers::get_atk_items( $id );
		return self::respond(
			array(
				'pelatihan_id' => $id,
				'items'        => $items,
			)
		);
	}

	public static function admin_save_atk( WP_REST_Request $req ) {
		$id = absint( $req['id'] );
		$p  = KGTK_Helpers::get_by_id( $id );
		if ( ! $p ) {
			return self::err( 'tidak_ada', 'Pelatihan tidak ditemukan.', 404 );
		}
		$in    = self::params( $req );
		$raw   = isset( $in['items'] ) && is_array( $in['items'] ) ? $in['items'] : array();
		$saved = KGTK_Helpers::save_atk_items( $id, $raw );
		return self::respond(
			array(
				'ok'           => true,
				'pelatihan_id' => $id,
				'items'        => $saved,
				'pesan'        => 'Daftar ATK berhasil disimpan.',
			)
		);
	}
}
