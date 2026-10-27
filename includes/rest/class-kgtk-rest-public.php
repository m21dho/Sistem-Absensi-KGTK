<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controller untuk REST API Publik: Info pelatihan, pilihan sekolah, cek NIK, verifikasi, pendaftaran, dan riwayat biodata.
 */
class KGTK_REST_Public extends KGTK_REST_Base {

	/* ====================== Endpoint Publik ====================== */

	public static function public_info( WP_REST_Request $req ) {
		$rl = self::rate_limit( 'info', 120 );
		if ( is_wp_error( $rl ) ) {
			return $rl;
		}
		$p = self::find_pelatihan( $req->get_param( 'kode' ) );
		if ( ! $p ) {
			return self::err( 'kode_tidak_ada', 'Kode pelatihan tidak ditemukan. Periksa kembali kodenya.', 404 );
		}
		return self::respond( self::pelatihan_payload( $p, KGTK_Helpers::kelas_stats( $p->id ) ) );
	}

	/** Daftar sekolah pada satu kabupaten/kota (untuk pilihan di form). */
	public static function public_sekolah( WP_REST_Request $req ) {
		global $wpdb;
		$rl = self::rate_limit( 'sekolah', 120 );
		if ( is_wp_error( $rl ) ) {
			return $rl;
		}
		$kab = sanitize_text_field( (string) $req->get_param( 'kabkota' ) );
		if ( ! in_array( $kab, KGTK_Helpers::kabkota_list(), true ) ) {
			return self::err( 'kabkota', 'Kabupaten/kota tidak valid.', 422 );
		}
		$t    = KGTK_DB::table( 'sekolah' );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, nama, npsn, jenjang FROM {$t} WHERE kabkota = %s ORDER BY nama", $kab ) );
		$out  = array();
		foreach ( (array) $rows as $r ) {
			$out[] = array( 'id' => (int) $r->id, 'nama' => $r->nama, 'npsn' => $r->npsn, 'jenjang' => $r->jenjang );
		}
		return self::respond( array( 'sekolah' => $out ) );
	}

	/* ====================== Verifikasi Kredensial Guru ====================== */

	/** Normalisasi HP: buang karakter selain angka, ubah awalan 62 menjadi 0. */
	public static function norm_hp( $s ) {
		$s = preg_replace( '/[^0-9]/', '', (string) $s );
		if ( '62' === substr( $s, 0, 2 ) ) {
			$s = '0' . substr( $s, 2 );
		}
		return $s;
	}

	/** Normalisasi Email: huruf kecil dan trim. */
	public static function norm_email( $s ) {
		return strtolower( trim( (string) $s ) );
	}

	/** Pembatasan percobaan verifikasi: maks 5 per IP+NIK, maks 20 per IP per 15 menit. Keys disimpan berbentuk md5. */
	public static function check_verif_rate_limit( $nik ) {
		$ip     = KGTK_Helpers::client_ip();
		$key_in = 'kgtk_vrl_in_' . md5( $ip . '|' . $nik );
		$key_ip = 'kgtk_vrl_ip_' . md5( $ip );

		$d_in = get_transient( $key_in );
		$d_ip = get_transient( $key_ip );

		$max_in = (int) apply_filters( 'kgtk_verif_rate_limit_max_nik', 5 );
		$max_ip = (int) apply_filters( 'kgtk_verif_rate_limit_max_ip', 20 );

		if ( ( is_array( $d_in ) && isset( $d_in['n'] ) && $d_in['n'] >= $max_in ) ||
		     ( is_array( $d_ip ) && isset( $d_ip['n'] ) && $d_ip['n'] >= $max_ip ) ) {
			return self::err( 'rate', 'Terlalu banyak percobaan verifikasi yang gagal. Coba lagi 15 menit lagi.', 429 );
		}
		return true;
	}

	/** Catat percobaan verifikasi gagal pada transient (15 menit = 900 detik). */
	public static function record_verif_failure( $nik ) {
		$ip     = KGTK_Helpers::client_ip();
		$key_in = 'kgtk_vrl_in_' . md5( $ip . '|' . $nik );
		$key_ip = 'kgtk_vrl_ip_' . md5( $ip );
		$window = 900;

		foreach ( array( $key_in, $key_ip ) as $k ) {
			$d = get_transient( $k );
			if ( ! is_array( $d ) || ! isset( $d['exp'] ) || $d['exp'] <= time() ) {
				$d = array( 'n' => 0, 'exp' => time() + $window );
			}
			$d['n']++;
			set_transient( $k, $d, max( 1, $d['exp'] - time() ) );
		}
	}

	/**
	 * Verifikasi kredensial guru (tipe: "hp" atau "email").
	 * Hanya mencocokkan input normalized dengan kgtk_guru (bukan kgtk_peserta).
	 * Memakai hash_equals() dan menjaga timing konsisten walau NIK tidak ditemukan.
	 */
	public static function verify_guru_credentials( $nik, $tipe, $nilai ) {
		global $wpdb;

		if ( ! preg_match( '/^\d{16}$/', $nik ) || ! in_array( $tipe, array( 'hp', 'email' ), true ) || '' === trim( (string) $nilai ) ) {
			return false;
		}

		if ( 'hp' === $tipe ) {
			$target = self::norm_hp( $nilai );
		} else {
			$target = self::norm_email( $nilai );
		}

		// Bila hasil normalisasi nilai yang dikirim kosong, verifikasi harus gagal.
		if ( '' === $target ) {
			return false;
		}

		$tg       = KGTK_DB::table( 'guru' );
		$guru_row = $wpdb->get_row( $wpdb->prepare( "SELECT nama, gelar_depan, gelar_belakang, nik, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, alamat_instansi, pangkat_golongan, agama, pendidikan, bank, rekening, npwp, pengalaman_jabatan, sekolah, kabkota, sekolah_id, peran, jenis_gtk, hp, nisn, nuptk, nip, email, email_belajar, email_guru, simpkb FROM {$tg} WHERE nik = %s", $nik ), ARRAY_A );
		if ( $guru_row ) {
			$guru_row['jabatan'] = $guru_row['jenis_gtk'];
			if ( empty( $guru_row['nuptk'] ) && ! empty( $guru_row['nisn'] ) ) {
				$guru_row['nuptk'] = $guru_row['nisn'];
			}
			if ( empty( $guru_row['simpkb'] ) && ! empty( $guru_row['email_guru'] ) ) {
				$guru_row['simpkb'] = $guru_row['email_guru'];
			}
		}

		if ( ! $guru_row ) {
			if ( 'hp' === $tipe ) {
				hash_equals( '089999999999', $target );
			} else {
				hash_equals( 'dummy@belajar.id', $target );
			}
			return false;
		}

		if ( 'hp' === $tipe ) {
			$stored = self::norm_hp( $guru_row['hp'] );
		} else {
			$stored = self::norm_email( $guru_row['email_belajar'] );
		}

		// Bila nilai tersimpan kosong, verifikasi harus gagal. Jangan cocokkan string kosong dengan string kosong.
		if ( '' === $stored ) {
			if ( 'hp' === $tipe ) {
				hash_equals( '089999999999', $target );
			} else {
				hash_equals( 'dummy@belajar.id', $target );
			}
			return false;
		}

		if ( hash_equals( $stored, $target ) ) {
			return $guru_row;
		}

		return false;
	}

	public static function public_cek_nik( WP_REST_Request $req ) {
		global $wpdb;
		$rl = self::rate_limit( 'cek', 60 );
		if ( is_wp_error( $rl ) ) {
			return $rl;
		}
		$in = self::params( $req );
		$p  = self::find_pelatihan( self::str( $in, 'kode' ) );
		if ( ! $p ) {
			return self::err( 'kode_tidak_ada', 'Kode pelatihan tidak ditemukan.', 404 );
		}
		$id_type = self::str( $in, 'id_type' );
		$nip     = preg_replace( '/\D/', '', self::str( $in, 'nip' ) );
		$nik     = self::str( $in, 'nik' );

		// Jika pengguna memilih/memasukkan NIP 18 digit (khusus Panitia / ASN)
		if ( 'nip' === $id_type || ( empty( $nik ) && 18 === strlen( $nip ) ) ) {
			if ( ! preg_match( '/^\d{18}$/', $nip ) ) {
				return self::err( 'nip', 'NIP harus 18 digit angka.', 422 );
			}

			$kelas = KGTK_Helpers::kelas_stats( $p->id );
			$pel   = self::pelatihan_payload( $p, $kelas );

			// Cek apakah sudah terdaftar di pelatihan ini berdasarkan NIP
			$tp  = KGTK_DB::table( 'peserta' );
			$reg = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tp} WHERE pelatihan_id = %d AND nip = %s", $p->id, $nip ) );
			if ( $reg ) {
				return self::respond(
					array(
						'status'    => 'terdaftar',
						'pelatihan' => $pel,
						'hasil'     => self::hasil( $p, $reg->nama, $reg->sekolah, $reg->peran, '', 0, 0, $reg->jenis_gtk ),
					)
				);
			}

			// Ambil data profil dari kgtk_guru atau kgtk_panitia jika ada
			$tg       = KGTK_DB::table( 'guru' );
			$guru_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tg} WHERE nip = %s LIMIT 1", $nip ), ARRAY_A );
			if ( ! $guru_row ) {
				$tpan    = KGTK_DB::table( 'panitia' );
				$pan_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tpan} WHERE nip = %s LIMIT 1", $nip ), ARRAY_A );
				if ( $pan_row ) {
					$guru_row = array(
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

			return self::respond(
				array(
					'status'    => 'belum',
					'pelatihan' => $pel,
					'guru'      => $guru_row,
					'nip'       => $nip,
					'id_type'   => 'nip',
				)
			);
		}

		if ( ! preg_match( '/^\d{16}$/', $nik ) ) {
			return self::err( 'nik', 'NIK harus 16 digit angka.', 422 );
		}

		$kelas = KGTK_Helpers::kelas_stats( $p->id );
		$pel   = self::pelatihan_payload( $p, $kelas );

		$reg = self::find_registrasi( $p->id, $nik );
		if ( $reg ) {
			return self::respond(
				array(
					'status'    => 'terdaftar',
					'pelatihan' => $pel,
					'hasil'     => self::hasil( $p, $reg->nama, $reg->sekolah, $reg->peran, $reg->kelas_nama, KGTK_Helpers::PERAN_SLOT === $reg->peran ? (int) $reg->slot : 0, $reg->nomor, $reg->jenis_gtk ),
				)
			);
		}

		$tipe  = self::str( $in, 'tipe' );
		$nilai = self::str( $in, 'nilai' );

		if ( '' !== $tipe || '' !== $nilai ) {
			$v_rl = self::check_verif_rate_limit( $nik );
			if ( is_wp_error( $v_rl ) ) {
				return $v_rl;
			}
			$guru = self::verify_guru_credentials( $nik, $tipe, $nilai );
			if ( ! $guru ) {
				self::record_verif_failure( $nik );
				return self::err( 'verifikasi_gagal', 'Data tidak cocok. Periksa NIK dan nomor HP atau email belajar.id yang Anda daftarkan.', 422 );
			}
			return self::respond(
				array(
					'status'    => 'ok',
					'pelatihan' => $pel,
					'guru'      => $guru,
				)
			);
		}

		return self::respond(
			array(
				'status'    => 'ok',
				'pelatihan' => $pel,
				'guru'      => null,
			)
		);
	}

	public static function public_biodata_saya( WP_REST_Request $req ) {
		$in    = self::params( $req );
		$nik   = self::str( $in, 'nik' );
		$tipe  = self::str( $in, 'tipe' );
		$nilai = self::str( $in, 'nilai' );

		$err_msg = 'Data tidak cocok. Periksa NIK dan nomor HP atau email belajar.id yang Anda daftarkan.';

		if ( ! preg_match( '/^\d{16}$/', $nik ) ) {
			return self::err( 'verifikasi_gagal', $err_msg, 422 );
		}

		$rl = self::check_verif_rate_limit( $nik );
		if ( is_wp_error( $rl ) ) {
			return $rl;
		}

		$bio = self::verify_guru_credentials( $nik, $tipe, $nilai );
		if ( ! $bio ) {
			self::record_verif_failure( $nik );
			return self::err( 'verifikasi_gagal', $err_msg, 422 );
		}

		global $wpdb;
		$tp   = KGTK_DB::table( 'peserta' );
		$tpel = KGTK_DB::table( 'pelatihan' );
		$tk   = KGTK_DB::table( 'kelas' );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ps.id AS peserta_id, ps.pelatihan_id, ps.kelas_id, ps.peran, COALESCE(k.nama, '') AS kelas_nama, COALESCE(k.slot, 0) AS slot,
				        pl.nama AS pelatihan_nama, pl.penyelenggara, pl.tanggal_mulai, pl.tanggal_selesai, pl.lokasi, pl.kabkota AS pelatihan_kabkota
				 FROM {$tp} ps
				 INNER JOIN {$tpel} pl ON pl.id = ps.pelatihan_id
				 LEFT JOIN {$tk} k ON k.id = ps.kelas_id
				 WHERE ps.nik = %s AND ps.terverifikasi = 1
				 ORDER BY pl.tanggal_mulai DESC, ps.id DESC",
				$nik
			)
		);

		$riwayat = array();
		foreach ( (array) $rows as $r ) {
			$nomor = ( (int) $r->kelas_id > 0 ) ? (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$tp} WHERE kelas_id = %d AND peran = %s AND id <= %d", $r->kelas_id, $r->peran, $r->peserta_id )
			) : 0;

			$riwayat[] = array(
				'pelatihan_id'  => (int) $r->pelatihan_id,
				'nama'          => $r->pelatihan_nama,
				'penyelenggara' => $r->penyelenggara,
				'tanggal'       => KGTK_Helpers::rentang_tanggal( $r->tanggal_mulai, $r->tanggal_selesai ),
				'lokasi'        => $r->lokasi,
				'kabkota'       => isset( $r->pelatihan_kabkota ) ? $r->pelatihan_kabkota : '',
				'kelas'         => $r->kelas_nama,
				'kelas_label'   => KGTK_Helpers::label_kelas( $r->kelas_nama ),
				'peran'         => $r->peran,
				'nomor'         => $nomor,
				'slot'          => KGTK_Helpers::PERAN_SLOT === $r->peran ? (int) $r->slot : 0,
			);
		}

		return self::respond(
			array(
				'biodata' => $bio,
				'riwayat' => $riwayat,
			)
		);
	}

	/**
	 * Peserta memperbarui data biodata dirinya sendiri dari halaman Biodata & Riwayat Saya.
	 */
	public static function public_update_biodata_saya( WP_REST_Request $req ) {
		$in    = self::params( $req );
		$nik   = self::str( $in, 'nik' );
		$tipe  = self::str( $in, 'verif_tipe' );
		$nilai = self::str( $in, 'verif_nilai' );

		$err_msg = 'Verifikasi sesi tidak cocok. Silakan periksa kembali identitas Anda.';

		if ( ! preg_match( '/^\d{16}$/', $nik ) ) {
			return self::err( 'verifikasi_gagal', $err_msg, 422 );
		}

		$rl = self::check_verif_rate_limit( $nik );
		if ( is_wp_error( $rl ) ) {
			return $rl;
		}

		$bio = self::verify_guru_credentials( $nik, $tipe, $nilai );
		if ( ! $bio ) {
			self::record_verif_failure( $nik );
			return self::err( 'verifikasi_gagal', $err_msg, 422 );
		}

		// NIK tidak boleh diubah dari input
		$in['nik'] = $nik;
		if ( empty( $in['peran'] ) ) {
			$in['peran'] = ! empty( $bio['peran'] ) ? $bio['peran'] : 'Peserta';
		}
		if ( empty( $in['jenis_gtk'] ) && ! empty( $in['jabatan'] ) ) {
			$in['jenis_gtk'] = $in['jabatan'];
		}

		$d = self::validasi_peserta( $in );
		if ( is_wp_error( $d ) ) {
			return $d;
		}

		global $wpdb;
		$tg = KGTK_DB::table( 'guru' );
		$tp = KGTK_DB::table( 'peserta' );

		$update_guru = array(
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
			'updated_at'         => current_time( 'mysql' ),
		);

		// Simpan perubahan ke kgtk_guru (Master Data)
		$wpdb->update( $tg, $update_guru, array( 'nik' => $nik ) );

		// Update juga pada pendaftaran peserta yang bersangkutan di kgtk_peserta
		$update_peserta = array(
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
			'jenis_gtk'          => $d['jenis_gtk'],
			'hp'                 => $d['hp'],
			'nisn'               => $d['nisn'],
			'nuptk'              => $d['nuptk'],
			'nip'                => $d['nip'],
			'email'              => $d['email'],
			'email_belajar'      => $d['email_belajar'],
			'email_guru'         => $d['email_guru'],
			'simpkb'             => $d['simpkb'],
		);
		$wpdb->update( $tp, $update_peserta, array( 'nik' => $nik ) );

		$bio_baru = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tg} WHERE nik = %s LIMIT 1", $nik ), ARRAY_A );
		if ( ! empty( $bio_baru ) ) {
			$bio_baru['jabatan'] = ! empty( $bio_baru['jenis_gtk'] ) ? $bio_baru['jenis_gtk'] : '';
		}

		return self::respond(
			array(
				'success' => true,
				'message' => 'Biodata Anda berhasil diperbarui.',
				'biodata' => $bio_baru ? $bio_baru : $update_guru,
			)
		);
	}

	/* ====================== Validasi & Proses Pendaftaran ====================== */

	/**
	 * Tentukan kabupaten/kota dan sekolah. Bila data sekolah sudah diimpor: kabupaten/kota wajib dipilih;
	 * sekolah dipilih dari daftar (nama diambil dari database), atau diketik bila tidak ada dalam daftar
	 * atau guru dari luar daftar ("Lainnya"). Bila belum diimpor: isian bebas seperti semula.
	 */
	public static function resolve_sekolah( $d ) {
		global $wpdb;
		$manual = $d['sekolah'];
		$tulis  = function () use ( $manual ) {
			if ( '' === $manual || self::len( $manual ) > 200 ) {
				return self::err( 'sekolah', 'Asal sekolah/instansi wajib diisi (maksimal 200 karakter).', 422 );
			}
			return true;
		};

		$daftar = KGTK_Helpers::kabkota_list();
		if ( ! $daftar ) {
			$ok = $tulis();
			return true === $ok ? array( 'kabkota' => '', 'sekolah_id' => 0, 'sekolah' => $manual ) : $ok;
		}

		$kab = $d['kabkota'];
		if ( '' === $kab ) {
			return self::err( 'kabkota', 'Pilih kabupaten/kota.', 422 );
		}
		if ( KGTK_Helpers::KABKOTA_LAIN === $kab ) {
			$ok = $tulis();
			return true === $ok ? array( 'kabkota' => $kab, 'sekolah_id' => 0, 'sekolah' => $manual ) : $ok;
		}
		if ( ! in_array( $kab, $daftar, true ) ) {
			return self::err( 'kabkota', 'Kabupaten/kota tidak valid.', 422 );
		}
		if ( $d['sekolah_id'] > 0 ) {
			$t   = KGTK_DB::table( 'sekolah' );
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT id, nama FROM {$t} WHERE id = %d AND kabkota = %s", $d['sekolah_id'], $kab ) );
			if ( ! $row ) {
				return self::err( 'sekolah', 'Sekolah/instansi tidak ditemukan pada kabupaten/kota yang dipilih.', 422 );
			}
			return array( 'kabkota' => $kab, 'sekolah_id' => (int) $row->id, 'sekolah' => $row->nama );
		}
		$ok = $tulis(); // sekolah tidak ada dalam daftar: diketik sendiri.
		return true === $ok ? array( 'kabkota' => $kab, 'sekolah_id' => 0, 'sekolah' => $manual ) : $ok;
	}

	/** Validasi dan sanitasi biodata peserta. Mengembalikan array data atau WP_Error. */
	public static function validasi_peserta( $in ) {
		$d = array(
			'nik'                => self::str( $in, 'nik' ),
			'nama'               => self::str( $in, 'nama' ),
			'gelar_depan'        => self::str( $in, 'gelar_depan' ),
			'gelar_belakang'     => self::str( $in, 'gelar_belakang' ),
			'tempat_lahir'       => self::str( $in, 'tempat_lahir' ),
			'tanggal_lahir'      => self::str( $in, 'tanggal_lahir' ),
			'jenis_kelamin'      => self::str( $in, 'jenis_kelamin' ),
			'alamat'             => isset( $in['alamat'] ) && is_scalar( $in['alamat'] ) ? sanitize_textarea_field( (string) $in['alamat'] ) : '',
			'alamat_instansi'    => isset( $in['alamat_instansi'] ) && is_scalar( $in['alamat_instansi'] ) ? sanitize_textarea_field( (string) $in['alamat_instansi'] ) : '',
			'pangkat_golongan'   => self::str( $in, 'pangkat_golongan' ),
			'agama'              => self::str( $in, 'agama' ),
			'pendidikan'         => self::str( $in, 'pendidikan' ),
			'bank'               => self::str( $in, 'bank' ),
			'rekening'           => self::str( $in, 'rekening' ),
			'npwp'               => self::str( $in, 'npwp' ),
			'pengalaman_jabatan' => isset( $in['pengalaman_jabatan'] ) && is_scalar( $in['pengalaman_jabatan'] ) ? sanitize_textarea_field( (string) $in['pengalaman_jabatan'] ) : '',
			'sekolah'            => self::str( $in, 'sekolah' ),
			'kabkota'            => self::str( $in, 'kabkota' ),
			'sekolah_id'         => isset( $in['sekolah_id'] ) ? absint( $in['sekolah_id'] ) : 0,
			'peran'              => self::str( $in, 'peran' ),
			'jenis_gtk'          => ! empty( $in['jabatan'] ) ? self::str( $in, 'jabatan' ) : self::str( $in, 'jenis_gtk' ),
			'hp'                 => self::str( $in, 'hp' ),
			'nisn'               => ! empty( $in['nuptk'] ) ? self::str( $in, 'nuptk' ) : self::str( $in, 'nisn' ),
			'nuptk'              => ! empty( $in['nuptk'] ) ? self::str( $in, 'nuptk' ) : self::str( $in, 'nisn' ),
			'nip'                => preg_replace( '/\D/', '', self::str( $in, 'nip' ) ),
			'email'              => self::mail( $in, 'email' ),
			'email_belajar'      => self::mail( $in, 'email_belajar' ),
			'email_guru'         => ! empty( $in['simpkb'] ) ? self::str( $in, 'simpkb' ) : self::str( $in, 'email_guru' ),
			'simpkb'             => ! empty( $in['simpkb'] ) ? self::str( $in, 'simpkb' ) : self::str( $in, 'email_guru' ),
			'setuju_kirim'       => ! empty( $in['setuju_kirim'] ) ? 1 : 0,
		);

		$is_panitia = ( 'Panitia' === $d['peran'] );
		$id_type    = isset( $in['panitia_id_type'] ) ? sanitize_text_field( $in['panitia_id_type'] ) : ( isset( $in['id_type'] ) ? sanitize_text_field( $in['id_type'] ) : '' );
		$pakai_nip  = $is_panitia && ( 'nip' === $id_type || ( empty( $d['nik'] ) && ! empty( $d['nip'] ) ) );

		if ( $pakai_nip ) {
			if ( ! preg_match( '/^\d{18}$/', $d['nip'] ) ) {
				return self::err( 'nip', 'NIP panitia harus 18 digit angka.', 422 );
			}
			// Jika NIK belum diisi atau bukan 16 digit, buat pseudo NIK 16 digit unik agar integritas DB terpenuhi
			if ( ! preg_match( '/^\d{16}$/', $d['nik'] ) ) {
				$seed     = $d['nip'] . '_' . $d['nama'];
				$d['nik'] = '99' . substr( preg_replace( '/\D/', '', md5( $seed ) ) . '00000000000000', 0, 14 );
			}
		} else {
			if ( ! preg_match( '/^\d{16}$/', $d['nik'] ) ) {
				return self::err( 'nik', 'NIK harus 16 digit angka.', 422 );
			}
		}
		if ( '' === $d['nama'] || self::len( $d['nama'] ) > 100 ) {
			return self::err( 'nama', 'Nama lengkap wajib diisi (maksimal 100 karakter).', 422 );
		}
		if ( '' !== $d['gelar_depan'] && self::len( $d['gelar_depan'] ) > 30 ) {
			return self::err( 'gelar_depan', 'Gelar depan maksimal 30 karakter.', 422 );
		}
		if ( '' !== $d['gelar_belakang'] && self::len( $d['gelar_belakang'] ) > 50 ) {
			return self::err( 'gelar_belakang', 'Gelar belakang maksimal 50 karakter.', 422 );
		}
		if ( '' === $d['tempat_lahir'] || self::len( $d['tempat_lahir'] ) > 80 ) {
			return self::err( 'tempat_lahir', 'Tempat lahir wajib diisi (maksimal 80 karakter).', 422 );
		}
		if ( '' === $d['tanggal_lahir'] ) {
			return self::err( 'tanggal_lahir', 'Tanggal lahir wajib diisi.', 422 );
		}
		if ( ! in_array( $d['jenis_kelamin'], KGTK_Helpers::JENIS_KELAMIN, true ) ) {
			return self::err( 'jenis_kelamin', 'Pilih jenis kelamin: ' . implode( ', ', KGTK_Helpers::JENIS_KELAMIN ) . '.', 422 );
		}
		if ( '' === $d['alamat'] || self::len( $d['alamat'] ) > 500 ) {
			return self::err( 'alamat', 'Alamat rumah / tempat tinggal wajib diisi (maksimal 500 karakter).', 422 );
		}
		if ( '' === $d['alamat_instansi'] || self::len( $d['alamat_instansi'] ) > 500 ) {
			return self::err( 'alamat_instansi', 'Alamat instansi wajib diisi (maksimal 500 karakter).', 422 );
		}
		if ( '' !== $d['agama'] && ! in_array( $d['agama'], KGTK_Helpers::AGAMA, true ) ) {
			return self::err( 'agama', 'Pilih agama yang valid.', 422 );
		}
		if ( '' !== $d['pangkat_golongan'] && self::len( $d['pangkat_golongan'] ) > 80 ) {
			return self::err( 'pangkat_golongan', 'Pangkat/Golongan maksimal 80 karakter.', 422 );
		}
		if ( 'S1' === $d['pendidikan'] || 'D4' === $d['pendidikan'] ) {
			$d['pendidikan'] = 'D4 / S1';
		} elseif ( 'SMA' === $d['pendidikan'] || 'SMK' === $d['pendidikan'] ) {
			$d['pendidikan'] = 'SMA/SMK Sederajat';
		}
		if ( '' === $d['pendidikan'] || ! in_array( $d['pendidikan'], KGTK_Helpers::PENDIDIKAN, true ) ) {
			return self::err( 'pendidikan', 'Pilih pendidikan terakhir.', 422 );
		}
		$sek = self::resolve_sekolah( $d );
		if ( is_wp_error( $sek ) ) {
			return $sek;
		}
		$d['kabkota']    = $sek['kabkota'];
		$d['sekolah_id'] = $sek['sekolah_id'];
		$d['sekolah']    = $sek['sekolah'];
		if ( ! in_array( $d['peran'], KGTK_Helpers::PERAN, true ) ) {
			return self::err( 'peran', 'Pilih peran yang tersedia: ' . implode( ', ', KGTK_Helpers::PERAN ) . '.', 422 );
		}

		if ( 'Panitia' === $d['peran'] ) {
			// Bank dan rekening untuk Panitia bersifat opsional
			$d['bank']               = '' !== $d['bank'] ? $d['bank'] : '-';
			$d['rekening']           = '' !== $d['rekening'] ? $d['rekening'] : '-';
			$d['pengalaman_jabatan'] = '-';
			$d['email_belajar']      = '';
			$d['nuptk']              = '';
			$d['simpkb']             = '';
			$d['npwp']               = '';

			if ( '' === $d['jenis_gtk'] ) {
				$d['jenis_gtk'] = 'Panitia Pelaksana';
			}
		} else {
			// Untuk yang lainnya (Peserta, Narasumber, Pengajar, Fasilitator, Penceramah, Pemateri): tetap sama
			if ( '' === $d['bank'] || self::len( $d['bank'] ) > 60 ) {
				return self::err( 'bank', 'Nama bank wajib diisi (maksimal 60 karakter).', 422 );
			}
			if ( '' === $d['rekening'] || ! preg_match( '/^[0-9\- ]{5,30}$/', $d['rekening'] ) ) {
				return self::err( 'rekening', 'Nomor rekening wajib diisi (hanya angka/tanda hubung, 5-30 digit).', 422 );
			}
			if ( '' !== $d['npwp'] && self::len( $d['npwp'] ) > 30 ) {
				return self::err( 'npwp', 'NPWP maksimal 30 karakter (kosongkan jika tidak ada).', 422 );
			}
			if ( '' === $d['pengalaman_jabatan'] || self::len( $d['pengalaman_jabatan'] ) > 255 ) {
				return self::err( 'pengalaman_jabatan', 'Pengalaman jabatan wajib diisi (maksimal 255 karakter).', 422 );
			}

			if ( 'Peserta' === $d['peran'] ) {
				if ( ! in_array( $d['jenis_gtk'], KGTK_Helpers::JABATAN, true ) && ! in_array( $d['jenis_gtk'], KGTK_Helpers::JENIS_GTK, true ) ) {
					return self::err( 'jenis_gtk', 'Pilih Jabatan: ' . implode( ', ', KGTK_Helpers::JABATAN ) . '.', 422 );
				}
				$b = $d['email_belajar'];
				if ( ! is_email( $b ) || self::len( $b ) > 100 || ! preg_match( '/@([a-z0-9-]+\.)*belajar\.id$/', $b ) ) {
					return self::err( 'email_belajar', 'Email belajar.id wajib diisi dan harus berakhiran belajar.id untuk peran Peserta.', 422 );
				}
			} else {
				// Non-peserta non-panitia (Narasumber, Pengajar, Fasilitator, dll.)
				if ( '' === $d['jenis_gtk'] ) {
					$d['jenis_gtk'] = $d['peran'];
				}
				$b = $d['email_belajar'];
				if ( '' !== $b && ( ! is_email( $b ) || self::len( $b ) > 100 ) ) {
					return self::err( 'email_belajar', 'Format email belajar.id tidak valid (kosongkan jika tidak ada).', 422 );
				}
			}

			// NUPTK opsional (boleh kosong). Jika diisi, harus berupa angka maksimal 16 digit.
			if ( '' !== $d['nuptk'] && ! preg_match( '/^\d{1,16}$/', $d['nuptk'] ) ) {
				return self::err( 'nuptk', 'NUPTK harus berupa angka (maksimal 16 digit, kosongkan jika belum ada).', 422 );
			}
			// SIMPKB opsional, maksimal 100 karakter.
			if ( '' !== $d['simpkb'] && self::len( $d['simpkb'] ) > 100 ) {
				return self::err( 'simpkb', 'SIMPKB maksimal 100 karakter (kosongkan jika tidak ada).', 422 );
			}
		}
		if ( '' !== $d['nip'] && ! preg_match( '/^\d{18}$/', $d['nip'] ) ) {
			return self::err( 'nip', 'NIP harus 18 digit angka (kosongkan jika tidak ada).', 422 );
		}
		if ( ! preg_match( '/^\+?[0-9][0-9 \-]{6,18}[0-9]$/', $d['hp'] ) ) {
			return self::err( 'hp', 'Nomor HP/WhatsApp tidak valid.', 422 );
		}
		if ( ! is_email( $d['email'] ) || self::len( $d['email'] ) > 100 ) {
			return self::err( 'email', 'Email wajib diisi dengan format yang benar.', 422 );
		}
		return $d;
	}

	/**
	 * Daftarkan peserta dalam satu transaksi (dipakai pendaftaran publik dan input admin).
	 * $kelas_id = 0: otomatis ke kelas pertama (urutan teratas) yang masih punya slot.
	 * $ignore_slot = true: abaikan slot penuh (untuk pendaftaran publik — admin assign kelas nanti).
	 * $is_verified = true: pendaftar publik telah lolos verifikasi kredensial kgtk_guru.
	 * $is_admin = true: dipanggil oleh admin terpercaya.
	 * Mengembalikan array( 'hasil' => ... ) atau WP_Error.
	 */
	public static function proses_daftar( $p, $d, $kelas_id = 0, $ignore_slot = false, $is_verified = false, $is_admin = false ) {
		global $wpdb;

		$tpel = KGTK_DB::table( 'pelatihan' );
		$tk   = KGTK_DB::table( 'kelas' );
		$tp   = KGTK_DB::table( 'peserta' );
		$tg   = KGTK_DB::table( 'guru' );
		$now  = current_time( 'mysql' );

		$wpdb->query( 'START TRANSACTION' );

		/*
		 * Kunci baris pelatihan lebih dulu: semua pendaftar pelatihan yang sama antre di sini.
		 * Pembacaan pertama sesudah kunci (hitungan terisi per kelas) selalu terbaru,
		 * sehingga dua pendaftar bersamaan tidak bisa melewati slot.
		 */
		$lock = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$tpel} WHERE id = %d FOR UPDATE", $p->id ) );
		if ( ! $lock ) {
			$wpdb->query( 'ROLLBACK' );
			return self::err( 'kode_tidak_ada', 'Pelatihan tidak ditemukan.', 404 );
		}

		// Slot hanya dihitung untuk peran Peserta; peran lain tidak dibatasi.
		$pakai_slot = ( KGTK_Helpers::PERAN_SLOT === $d['peran'] );

		$daftar = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT k.id, k.nama, k.slot, (SELECT COUNT(*) FROM {$tp} ps WHERE ps.kelas_id = k.id AND ps.peran = %s) AS terisi
				 FROM {$tk} k WHERE k.pelatihan_id = %d ORDER BY k.urutan, k.id",
				KGTK_Helpers::PERAN_SLOT,
				$p->id
			)
		);

		$k = null;
		if ( $kelas_id ) {
			foreach ( (array) $daftar as $row ) {
				if ( (int) $row->id === (int) $kelas_id ) {
					$k = $row;
					break;
				}
			}
			if ( ! $k ) {
				$wpdb->query( 'ROLLBACK' );
				return self::err( 'kelas', 'Kelas tidak valid untuk pelatihan ini.', 422 );
			}
			if ( ! $ignore_slot && $pakai_slot && (int) $k->terisi >= (int) $k->slot ) {
				$wpdb->query( 'ROLLBACK' );
				return self::err( 'kelas_penuh', KGTK_Helpers::label_kelas( $k->nama ) . ' sudah penuh. Pilih kelas lain atau "Otomatis".', 409 );
			}
		} elseif ( ! $ignore_slot ) {
			// Otomatis (hanya jika admin memilih kelas Otomatis saat input manual).
			foreach ( (array) $daftar as $row ) {
				if ( ! $pakai_slot || (int) $row->terisi < (int) $row->slot ) {
					$k = $row;
					break;
				}
			}
			if ( ! $k && $pakai_slot ) {
				$wpdb->query( 'ROLLBACK' );
				return self::err( 'kelas_penuh', 'Maaf, semua slot Peserta pada pelatihan ini sudah penuh.', 409 );
			}
			if ( ! $k && $daftar ) {
				$k = $daftar[0]; // fallback: kelas pertama yang ada.
			}
		}
		// Pendaftaran publik ($ignore_slot = true): guru hanya mendaftar pelatihan, $k = null (kelas_id = 0).
		// Penempatan kelas akan ditentukan oleh admin melalui panel admin.

		$ada = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$tp} WHERE pelatihan_id = %d AND nik = %s", $p->id, $d['nik'] ) );
		if ( $ada ) {
			$wpdb->query( 'ROLLBACK' );
			return self::err( 'sudah_terdaftar', 'NIK ini sudah terdaftar pada pelatihan ini.', 409 );
		}

		// Cek apakah data guru sudah ada sebelumnya untuk NIK ini.
		$has_guru = (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$tg} WHERE nik = %s", $d['nik'] ) );

		// A. Baris kgtk_guru hanya boleh dibuat atau diperbarui bila salah satu benar:
		// (1) belum ada baris kgtk_guru untuk NIK itu (pendaftar pertama);
		// (2) permintaan menyertakan verifikasi yang valid terhadap baris kgtk_guru yang ada;
		// (3) aksi admin (admin_tambah_peserta), karena admin dipercaya.
		// Selain itu baris kgtk_guru TIDAK diubah.
		$terverifikasi = ( ! $has_guru || $is_verified || $is_admin ) ? 1 : 0;
		$update_guru   = ( ! $has_guru || $is_verified || $is_admin );

		$wpdb->suppress_errors( true );
		$ok_guru = true;
		if ( $update_guru ) {
			$res_guru = $wpdb->query(
				$wpdb->prepare(
					"INSERT INTO {$tg} (nik, nama, gelar_depan, gelar_belakang, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, alamat_instansi, pangkat_golongan, agama, pendidikan, bank, rekening, npwp, pengalaman_jabatan, sekolah, kabkota, sekolah_id, peran, jenis_gtk, hp, nisn, nuptk, nip, email, email_belajar, email_guru, simpkb, updated_at)
					 VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
					 ON DUPLICATE KEY UPDATE nama = VALUES(nama), gelar_depan = VALUES(gelar_depan), gelar_belakang = VALUES(gelar_belakang), tempat_lahir = VALUES(tempat_lahir), tanggal_lahir = VALUES(tanggal_lahir), jenis_kelamin = VALUES(jenis_kelamin),
					 alamat = VALUES(alamat), alamat_instansi = VALUES(alamat_instansi), pangkat_golongan = VALUES(pangkat_golongan), agama = VALUES(agama), pendidikan = VALUES(pendidikan), bank = VALUES(bank), rekening = VALUES(rekening), npwp = VALUES(npwp), pengalaman_jabatan = VALUES(pengalaman_jabatan),
					 sekolah = VALUES(sekolah), kabkota = VALUES(kabkota), sekolah_id = VALUES(sekolah_id),
					 peran = VALUES(peran), jenis_gtk = VALUES(jenis_gtk), hp = VALUES(hp), nisn = VALUES(nisn), nuptk = VALUES(nuptk), nip = VALUES(nip), email = VALUES(email),
					 email_belajar = VALUES(email_belajar), email_guru = VALUES(email_guru), simpkb = VALUES(simpkb), updated_at = VALUES(updated_at)",
					$d['nik'],
					$d['nama'],
					$d['gelar_depan'],
					$d['gelar_belakang'],
					$d['tempat_lahir'],
					$d['tanggal_lahir'],
					$d['jenis_kelamin'],
					$d['alamat'],
					$d['alamat_instansi'],
					$d['pangkat_golongan'],
					$d['agama'],
					$d['pendidikan'],
					$d['bank'],
					$d['rekening'],
					$d['npwp'],
					$d['pengalaman_jabatan'],
					$d['sekolah'],
					$d['kabkota'],
					$d['sekolah_id'],
					$d['peran'],
					$d['jenis_gtk'],
					$d['hp'],
					$d['nisn'],
					$d['nuptk'],
					$d['nip'],
					$d['email'],
					$d['email_belajar'],
					$d['email_guru'],
					$d['simpkb'],
					$now
				)
			);
			$ok_guru = ( false !== $res_guru );
		}

		$ok_ins = false;
		if ( $ok_guru ) {
			$ok_ins = $wpdb->insert(
				$tp,
				array(
					'pelatihan_id'       => $p->id,
					'kelas_id'           => $k ? (int) $k->id : 0,
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
					'setuju_kirim'       => ! empty( $d['setuju_kirim'] ) ? 1 : 0,
					'setuju_pada'        => ! empty( $d['setuju_kirim'] ) ? $now : null,
					'terverifikasi'      => $terverifikasi,
					'created_at'         => $now,
				),
				array(
					'%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
					'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
					'%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
					'%s', '%s', '%d', '%s', '%d', '%s'
				)
			);
		}
		$wpdb->suppress_errors( false );

		if ( ! $ok_ins ) {
			$wpdb->query( 'ROLLBACK' );
			if ( ! empty( $wpdb->last_error ) ) {
				error_log( '[KGTK REST] Insert Peserta Error: ' . $wpdb->last_error );
			}
			// Kemungkinan pendaftaran ganda bersamaan (UNIQUE KEY pelatihan_id + nik).
			if ( self::find_registrasi( $p->id, $d['nik'] ) ) {
				return self::err( 'sudah_terdaftar', 'NIK ini sudah terdaftar pada pelatihan ini.', 409 );
			}
			return self::err( 'gagal', 'Pendaftaran gagal diproses. Silakan coba lagi.' . ( defined( 'WP_DEBUG' ) && WP_DEBUG && ! empty( $wpdb->last_error ) ? ' (' . $wpdb->last_error . ')' : '' ), 500 );
		}

		// Nomor urut = urutan dalam daftar hadir kelas itu untuk peran yang sama (jika sudah masuk kelas).
		$urut = 0;
		if ( $k ) {
			$urut = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tp} WHERE kelas_id = %d AND peran = %s", $k->id, $d['peran'] ) );
		}

		$new_peserta_id = $ok_ins ? (int) $wpdb->insert_id : 0;

		$wpdb->query( 'COMMIT' );

		if ( $new_peserta_id ) {
			try {
				KGTK_Undangan::cocokkan_peserta( $new_peserta_id, (int) $p->id );
			} catch ( \Throwable $e ) {
				error_log( 'Gagal mencocokkan peserta: ' . $e->getMessage() );
			}

			// Masukkan data panitia ke dalam Master Data Panitia secara otomatis
			if ( 'Panitia' === $d['peran'] && class_exists( 'KGTK_Panitia' ) ) {
				try {
					KGTK_Panitia::save( array(
						'nama'     => $d['nama'],
						'nik'      => $d['nik'],
						'nip'      => $d['nip'] ?? '',
						'jabatan'  => ! empty( $d['jenis_gtk'] ) ? $d['jenis_gtk'] : 'Panitia Pelaksana',
						'instansi' => $d['sekolah'] ?? '',
						'kabkota'  => $d['kabkota'] ?? '',
						'hp'       => $d['hp'] ?? '',
						'email'    => $d['email'] ?? '',
						'bank'     => ( '-' !== $d['bank'] ) ? $d['bank'] : '',
						'rekening' => ( '-' !== $d['rekening'] ) ? $d['rekening'] : '',
					) );
				} catch ( \Throwable $e ) {
					error_log( 'Gagal menyimpan master panitia: ' . $e->getMessage() );
				}
			}
		}

		return array(
			'hasil' => self::hasil(
				$p,
				$d['nama'],
				$d['sekolah'],
				$d['peran'],
				$k ? $k->nama : '',
				( $k && $pakai_slot ) ? (int) $k->slot : 0,
				$urut,
				$d['jenis_gtk']
			),
		);
	}

	public static function public_daftar( WP_REST_Request $req ) {
		$rl = self::rate_limit( 'daftar', 30 );
		if ( is_wp_error( $rl ) ) {
			return $rl;
		}
		$in = self::params( $req );

		$p = self::find_pelatihan( self::str( $in, 'kode' ) );
		if ( ! $p ) {
			return self::err( 'kode_tidak_ada', 'Kode pelatihan tidak ditemukan.', 404 );
		}
		$d = self::validasi_peserta( $in );
		if ( is_wp_error( $d ) ) {
			return $d;
		}

		$tipe        = self::str( $in, 'tipe' );
		$nilai       = self::str( $in, 'nilai' );
		$is_verified = false;

		if ( '' !== $tipe || '' !== $nilai ) {
			$v_rl = self::check_verif_rate_limit( $d['nik'] );
			if ( is_wp_error( $v_rl ) ) {
				return $v_rl;
			}
			$guru = self::verify_guru_credentials( $d['nik'], $tipe, $nilai );
			if ( ! $guru ) {
				self::record_verif_failure( $d['nik'] );
				return self::err( 'verifikasi_gagal', 'Data tidak cocok. Periksa NIK dan nomor HP atau email belajar.id yang Anda daftarkan.', 422 );
			}
			$is_verified = true;
		}

		// Pendaftaran publik: masuk ke kelas pertama secara otomatis.
		// Admin akan menentukan kelas akhir lewat panel admin.
		$res = self::proses_daftar( $p, $d, 0, true, $is_verified, false );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		return self::respond( array( 'hasil' => $res['hasil'] ), 201 );
	}
}
