<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Model dan Pengelola Data Panitia KGTK Pelatihan.
 */
class KGTK_Panitia {

	public static function table() {
		return KGTK_DB::table( 'panitia' );
	}

	/**
	 * Ambil semua data panitia yang tersimpan, lengkap dengan jumlah pelatihan yang pernah diikuti.
	 *
	 * @param string $search Kata kunci pencarian (opsional).
	 * @return array
	 */
	public static function get_all( $search = '' ) {
		global $wpdb;
		$tpan = self::table();
		$tp   = KGTK_DB::table( 'peserta' );

		$where  = '';
		$params = array();
		$search = trim( (string) $search );

		if ( '' !== $search ) {
			$s      = '%' . $wpdb->esc_like( $search ) . '%';
			$where  = 'WHERE p.nama LIKE %s OR p.nik LIKE %s OR p.instansi LIKE %s OR p.jabatan LIKE %s OR p.hp LIKE %s';
			$params = array( $s, $s, $s, $s, $s );
		}

		$sql = "SELECT p.*,
				(
					SELECT COUNT(DISTINCT ps.pelatihan_id)
					FROM {$tp} ps
					WHERE ps.peran = 'Panitia'
					  AND (
						(p.nik != '' AND ps.nik = p.nik)
						OR (p.nik = '' AND ps.nama = p.nama)
					  )
				) AS total_pelatihan
				FROM {$tpan} p
				{$where}
				ORDER BY p.nama ASC";

		if ( ! empty( $params ) ) {
			return (array) $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
		}

		return (array) $wpdb->get_results( $sql );
	}

	/**
	 * Ambil 1 data panitia berdasarkan ID.
	 *
	 * @param int $id
	 * @return object|null
	 */
	public static function get_by_id( $id ) {
		global $wpdb;
		$t = self::table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE id = %d", (int) $id ) );
	}

	/**
	 * Ambil data panitia berdasarkan NIK.
	 *
	 * @param string $nik
	 * @return object|null
	 */
	public static function get_by_nik( $nik ) {
		$nik = preg_replace( '/\D/', '', (string) $nik );
		if ( '' === $nik ) {
			return null;
		}
		global $wpdb;
		$t = self::table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE nik = %s", $nik ) );
	}

	/**
	 * Simpan atau perbarui data master panitia.
	 *
	 * @param array $data
	 * @return int|WP_Error ID panitia yang tersimpan atau error.
	 */
	public static function save( array $data ) {
		global $wpdb;
		$t = self::table();

		$nama = isset( $data['nama'] ) ? sanitize_text_field( wp_unslash( $data['nama'] ) ) : '';
		$nama = trim( $nama );
		if ( '' === $nama ) {
			return new WP_Error( 'nama_empty', 'Nama panitia wajib diisi.' );
		}

		$gelar_depan    = isset( $data['gelar_depan'] ) ? trim( sanitize_text_field( wp_unslash( $data['gelar_depan'] ) ) ) : '';
		$gelar_belakang = isset( $data['gelar_belakang'] ) ? trim( sanitize_text_field( wp_unslash( $data['gelar_belakang'] ) ) ) : '';
		if ( '' !== $gelar_depan || '' !== $gelar_belakang ) {
			$nama = KGTK_Helpers::format_nama_gelar( $nama, $gelar_depan, $gelar_belakang );
		}

		$nik = isset( $data['nik'] ) ? preg_replace( '/\D/', '', (string) $data['nik'] ) : '';
		if ( strlen( $nik ) > 16 ) {
			$nik = substr( $nik, 0, 16 );
		}

		$jabatan  = isset( $data['jabatan'] ) && '' !== trim( $data['jabatan'] ) ? trim( sanitize_text_field( wp_unslash( $data['jabatan'] ) ) ) : ( isset( $data['tugas'] ) && '' !== trim( $data['tugas'] ) ? trim( sanitize_text_field( wp_unslash( $data['tugas'] ) ) ) : ( isset( $data['jenis_gtk'] ) && '' !== trim( $data['jenis_gtk'] ) ? trim( sanitize_text_field( wp_unslash( $data['jenis_gtk'] ) ) ) : 'Panitia Pelaksana' ) );
		$nip      = isset( $data['nip'] ) ? preg_replace( '/\D/', '', (string) $data['nip'] ) : '';
		$nuptk    = isset( $data['nuptk'] ) ? preg_replace( '/\D/', '', (string) $data['nuptk'] ) : '';
		$instansi = isset( $data['instansi'] ) && '' !== trim( $data['instansi'] ) ? trim( sanitize_text_field( wp_unslash( $data['instansi'] ) ) ) : ( isset( $data['sekolah'] ) ? trim( sanitize_text_field( wp_unslash( $data['sekolah'] ) ) ) : '' );
		$kabkota  = isset( $data['kabkota'] ) ? sanitize_text_field( wp_unslash( $data['kabkota'] ) ) : '';
		$hp       = isset( $data['hp'] ) ? sanitize_text_field( wp_unslash( $data['hp'] ) ) : '';
		$email    = isset( $data['email'] ) ? sanitize_email( wp_unslash( $data['email'] ) ) : '';
		$bank     = isset( $data['bank'] ) ? sanitize_text_field( wp_unslash( $data['bank'] ) ) : '';
		$rekening = isset( $data['rekening'] ) ? sanitize_text_field( wp_unslash( $data['rekening'] ) ) : '';
		$npwp     = isset( $data['npwp'] ) ? sanitize_text_field( wp_unslash( $data['npwp'] ) ) : '';
		$catatan  = isset( $data['catatan'] ) ? sanitize_textarea_field( wp_unslash( $data['catatan'] ) ) : '';

		// Jika NIK pseudo (dimulai dengan 99...), jangan simpan sebagai NIK resmi di master
		$stored_nik = $nik;
		if ( 0 === strpos( $nik, '99' ) && 16 === strlen( $nik ) ) {
			$stored_nik = '';
		}

		$now = current_time( 'mysql' );
		$id  = isset( $data['id'] ) ? absint( $data['id'] ) : 0;

		$payload = array(
			'nama'       => $nama,
			'jabatan'    => $jabatan,
			'nik'        => $stored_nik,
			'nip'        => $nip,
			'nuptk'      => $nuptk,
			'instansi'   => $instansi,
			'kabkota'    => $kabkota,
			'hp'         => $hp,
			'email'      => $email,
			'bank'       => $bank,
			'rekening'   => $rekening,
			'npwp'       => $npwp,
			'catatan'    => $catatan,
			'updated_at' => $now,
		);

		// Jika ada ID spesifik, update
		if ( $id > 0 ) {
			$wpdb->update( $t, $payload, array( 'id' => $id ) );
			return $id;
		}

		// Jika NIK valid (bukan pseudo 99...) dan sudah ada di tabel panitia, update berdasarkan NIK
		if ( '' !== $stored_nik ) {
			$existing = self::get_by_nik( $stored_nik );
			if ( $existing ) {
				$wpdb->update( $t, $payload, array( 'id' => $existing->id ) );
				return (int) $existing->id;
			}
		} elseif ( '' !== $nip ) {
			// Cari berdasarkan NIP bila NIK tidak ada
			$existing_nip = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$t} WHERE nip = %s LIMIT 1", $nip ) );
			if ( $existing_nip ) {
				$wpdb->update( $t, $payload, array( 'id' => $existing_nip->id ) );
				return (int) $existing_nip->id;
			}
		} else {
			// Cari berdasarkan nama yang sama jika NIK dan NIP tidak ada / pseudo
			$existing_by_name = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$t} WHERE LOWER(TRIM(nama)) = LOWER(TRIM(%s)) LIMIT 1", $nama ) );
			if ( $existing_by_name ) {
				$wpdb->update( $t, $payload, array( 'id' => $existing_by_name->id ) );
				return (int) $existing_by_name->id;
			}
		}

		// Insert baru
		$payload['created_at'] = $now;
		$ok = $wpdb->insert( $t, $payload );
		if ( false === $ok ) {
			return new WP_Error( 'db_error', 'Gagal menyimpan data panitia ke database.' );
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Hapus panitia dari tabel master.
	 *
	 * @param int $id
	 * @return bool
	 */
	public static function delete( $id ) {
		global $wpdb;
		$t = self::table();
		return false !== $wpdb->delete( $t, array( 'id' => (int) $id ), array( '%d' ) );
	}

	/**
	 * Tambahkan daftar panitia ke pelatihan saat pelatihan dibuat.
	 * Panitia tidak terikat pada kelas tertentu (kelas_id = 0) dan tidak memakan kuota slot peserta.
	 *
	 * @param int   $pelatihan_id ID pelatihan yang baru dibuat.
	 * @param array $panitia_rows Daftar panitia dari form.
	 * @return int Jumlah panitia yang berhasil ditambahkan.
	 */
	public static function tambah_ke_pelatihan( $pelatihan_id, array $panitia_rows ) {
		global $wpdb;
		$pelatihan_id = absint( $pelatihan_id );
		if ( ! $pelatihan_id || empty( $panitia_rows ) ) {
			return 0;
		}

		$tp = KGTK_DB::table( 'peserta' );
		$tg = KGTK_DB::table( 'guru' );

		$now     = current_time( 'mysql' );
		$success = 0;

		foreach ( $panitia_rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$nama = isset( $row['nama'] ) ? trim( sanitize_text_field( wp_unslash( $row['nama'] ) ) ) : '';
			if ( '' === $nama ) {
				continue;
			}

			// Simpan / update data panitia ke tabel master kgtk_panitia
			$saved_id = self::save( $row );
			$pan_id   = is_wp_error( $saved_id ) ? 0 : $saved_id;

			$jabatan  = isset( $row['jabatan'] ) && '' !== trim( $row['jabatan'] ) ? trim( sanitize_text_field( $row['jabatan'] ) ) : ( isset( $row['tugas'] ) && '' !== trim( $row['tugas'] ) ? trim( sanitize_text_field( $row['tugas'] ) ) : 'Panitia Pelaksana' );
			$nik      = isset( $row['nik'] ) ? preg_replace( '/\D/', '', (string) $row['nik'] ) : '';
			$hp       = isset( $row['hp'] ) ? sanitize_text_field( wp_unslash( $row['hp'] ) ) : '';
			$instansi = isset( $row['instansi'] ) ? sanitize_text_field( wp_unslash( $row['instansi'] ) ) : '';
			$kabkota  = isset( $row['kabkota'] ) ? sanitize_text_field( wp_unslash( $row['kabkota'] ) ) : '';
			$nip      = isset( $row['nip'] ) ? preg_replace( '/\D/', '', (string) $row['nip'] ) : '';
			$nuptk    = isset( $row['nuptk'] ) ? preg_replace( '/\D/', '', (string) $row['nuptk'] ) : '';
			$email    = isset( $row['email'] ) ? sanitize_email( wp_unslash( $row['email'] ) ) : '';
			$bank     = isset( $row['bank'] ) ? sanitize_text_field( wp_unslash( $row['bank'] ) ) : '';
			$rekening = isset( $row['rekening'] ) ? sanitize_text_field( wp_unslash( $row['rekening'] ) ) : '';
			$npwp     = isset( $row['npwp'] ) ? sanitize_text_field( wp_unslash( $row['npwp'] ) ) : '';

			// NIK wajib 16 digit dan unik per pelatihan.
			// Jika kosong, buat NIK pseudo 16 digit yang unik agar tidak terjadi tabrakan UNIQUE KEY (pelatihan_id, nik).
			if ( 16 !== strlen( $nik ) ) {
				$seed = $pelatihan_id . '_' . $nama . '_' . $success . '_' . ( $pan_id ?: microtime( true ) );
				$nik  = '99' . substr( preg_replace( '/\D/', '', md5( $seed ) ) . '00000000000000', 0, 14 );
			}

			// Cek apakah NIK ini sudah ada di pelatihan
			$exists = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$tp} WHERE pelatihan_id = %d AND nik = %s", $pelatihan_id, $nik )
			);
			if ( $exists > 0 ) {
				continue;
			}

			// Insert ke kgtk_peserta sebagai peran 'Panitia' (kelas_id = 0, tidak terikat ke kelas peserta)
			$peserta_payload = array(
				'pelatihan_id'       => $pelatihan_id,
				'kelas_id'           => 0,
				'nik'                => $nik,
				'nama'               => $nama,
				'tempat_lahir'       => '-',
				'tanggal_lahir'      => '-',
				'jenis_kelamin'      => '-',
				'alamat'             => '-',
				'pendidikan'         => '-',
				'bank'               => $bank ?: '-',
				'rekening'           => $rekening ?: '-',
				'npwp'               => $npwp ?: '-',
				'pengalaman_jabatan' => '-',
				'sekolah'            => $instansi ?: 'Panitia Pelaksana',
				'kabkota'            => $kabkota ?: 'Lainnya',
				'sekolah_id'         => 0,
				'peran'              => 'Panitia',
				'jenis_gtk'          => $jabatan,
				'hp'                 => $hp ?: '-',
				'nisn'               => '',
				'nuptk'              => $nuptk ?: '-',
				'nip'                => $nip ?: '-',
				'email'              => $email ?: '-',
				'email_belajar'      => '',
				'email_guru'         => '',
				'simpkb'             => '',
				'terverifikasi'      => 1,
				'created_at'         => $now,
			);

			$ins = $wpdb->insert( $tp, $peserta_payload );
			if ( false !== $ins ) {
				$success++;

				// Sync ke kgtk_guru (hanya jika NIK bukan pseudo)
				if ( 0 !== strpos( $nik, '99' ) ) {
					$wpdb->query(
						$wpdb->prepare(
							"INSERT INTO {$tg} (nik, nama, sekolah, kabkota, peran, jenis_gtk, hp, nip, nuptk, email, bank, rekening, npwp, updated_at)
							 VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
							 ON DUPLICATE KEY UPDATE
							   nama = VALUES(nama),
							   sekolah = VALUES(sekolah),
							   peran = VALUES(peran),
							   jenis_gtk = VALUES(jenis_gtk),
							   hp = VALUES(hp),
							   nip = VALUES(nip),
							   nuptk = VALUES(nuptk),
							   email = VALUES(email),
							   updated_at = VALUES(updated_at)",
							$nik,
							$nama,
							$instansi ?: 'Panitia Pelaksana',
							$kabkota ?: 'Lainnya',
							'Panitia',
							$jabatan,
							$hp,
							$nip,
							$nuptk,
							$email,
							$bank,
							$rekening,
							$npwp,
							$now
						)
					);
				}
			}
		}

		return $success;
	}

	/**
	 * Ambil daftar panitia yang terdaftar pada pelatihan tertentu.
	 *
	 * @param int $pelatihan_id
	 * @return array
	 */
	public static function get_by_pelatihan( $pelatihan_id ) {
		global $wpdb;
		$tp = KGTK_DB::table( 'peserta' );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, nik, nama, sekolah, kabkota, jenis_gtk AS jabatan, hp, nip, nuptk, npwp, created_at
				 FROM {$tp}
				 WHERE pelatihan_id = %d AND peran = 'Panitia'
				 ORDER BY id ASC",
				(int) $pelatihan_id
			)
		);
	}

	/**
	 * Hapus panitia dari pelatihan tertentu.
	 *
	 * @param int $peserta_id ID di tabel peserta.
	 * @param int $pelatihan_id ID pelatihan (opsional).
	 * @return bool
	 */
	public static function hapus_dari_pelatihan( $peserta_id, $pelatihan_id = 0 ) {
		global $wpdb;
		$tp = KGTK_DB::table( 'peserta' );
		$where = array( 'id' => (int) $peserta_id, 'peran' => 'Panitia' );
		if ( $pelatihan_id > 0 ) {
			$where['pelatihan_id'] = (int) $pelatihan_id;
		}
		return false !== $wpdb->delete( $tp, $where, array( '%d', '%s' ) );
	}
}
