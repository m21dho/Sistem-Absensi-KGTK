<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageMargins;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Logika dan manajemen data Peserta Ditetapkan (KGTK Undangan).
 */
class KGTK_Undangan {

	const MAKS_IMPOR_BARIS    = 2000;
	const MAKS_TOTAL_UNDANGAN = 5000;

	public static function init() {
		add_action( 'admin_post_kgtk_undangan_export', array( __CLASS__, 'handle_export' ) );
	}

	/* ====================== Normalisasi & Helper ====================== */

	/**
	 * Normalisasi nomor HP: ambil hanya digit, ubah awalan 62 menjadi 0.
	 */
	public static function norm_hp( $s ) {
		$s = preg_replace( '/[^0-9]/', '', (string) $s );
		if ( '62' === substr( $s, 0, 2 ) ) {
			$s = '0' . substr( $s, 2 );
		}
		return $s;
	}

	/**
	 * Normalisasi Email: huruf kecil dan trim.
	 */
	public static function norm_email( $s ) {
		return strtolower( trim( (string) $s ) );
	}

	/**
	 * Normalisasi Nama: huruf kecil, spasi ganda dirapikan menjadi spasi tunggal.
	 */
	public static function norm_nama( $s ) {
		$s = trim( (string) $s );
		$s = preg_replace( '/\s+/u', ' ', $s );
		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $s, 'UTF-8' ) : strtolower( $s );
	}

	/**
	 * Samarkan NIK sebagian untuk tampilan layar (mis. 3201************).
	 */
	public static function mask_nik( $nik ) {
		$nik = trim( (string) $nik );
		if ( '' === $nik ) {
			return '-';
		}
		if ( strlen( $nik ) === 16 ) {
			return substr( $nik, 0, 4 ) . '************';
		}
		return substr( $nik, 0, 4 ) . str_repeat( '*', max( 0, strlen( $nik ) - 4 ) );
	}

	/**
	 * Netralkan formula injection Excel: jika diawali =, +, -, @, tambahkan tanda petik tunggal.
	 */
	public static function safe_excel_string( $s ) {
		$s = (string) $s;
		if ( '' !== $s && in_array( $s[0], array( '=', '+', '-', '@' ), true ) ) {
			return "'" . $s;
		}
		return $s;
	}

	/* ====================== Validasi ====================== */

	/**
	 * Validasi satu baris data undangan.
	 *
	 * Aturan:
	 * 1. Minimal satu dari nama, nik, hp, email_belajar terisi; baris yang semuanya kosong ditolak.
	 * 2. Bila NIK diisi harus 16 digit angka.
	 * 3. Bila HP diisi harus lolos validasi regex HP pendaftaran.
	 * 4. Bila email diisi harus berformat valid dan berakhiran belajar.id.
	 * 5. Deteksi duplikat NIK, email_belajar, atau hp_norm pada pelatihan yang sama.
	 *
	 * @param array $row Array input.
	 * @param int   $pelatihan_id ID pelatihan.
	 * @param int   $exclude_id ID undangan yang diabaikan saat cek duplikat (mis. saat edit).
	 * @param array $batch_seen Peta duplikat dalam batch saat ini (referensi).
	 * @return array|WP_Error Data tersanitasi atau objek WP_Error.
	 */
	public static function validasi_baris( $row, $pelatihan_id = 0, $exclude_id = 0, &$batch_seen = null ) {
		$nama    = isset( $row['nama'] ) ? sanitize_text_field( trim( (string) $row['nama'] ) ) : '';
		$nik     = isset( $row['nik'] ) ? preg_replace( '/\D/', '', trim( (string) $row['nik'] ) ) : '';
		$hp      = isset( $row['hp'] ) ? sanitize_text_field( trim( (string) $row['hp'] ) ) : '';
		$email   = isset( $row['email_belajar'] ) ? self::norm_email( $row['email_belajar'] ) : '';
		$catatan = isset( $row['catatan'] ) ? sanitize_text_field( trim( (string) $row['catatan'] ) ) : '';

		// 1. Minimal SATU dari nama, nik, hp, email_belajar terisi.
		if ( '' === $nama && '' === $nik && '' === $hp && '' === $email ) {
			return new WP_Error( 'kgtk_wajib_satu', 'Minimal satu dari Nama, NIK, No. HP, atau Email belajar.id wajib diisi.' );
		}

		// Batas panjang nama
		if ( function_exists( 'mb_strlen' ) ? mb_strlen( $nama, 'UTF-8' ) > 100 : strlen( $nama ) > 100 ) {
			return new WP_Error( 'kgtk_nama', 'Nama maksimal 100 karakter.' );
		}

		// 2. Validasi NIK bila diisi
		if ( '' !== $nik && ! preg_match( '/^\d{16}$/', $nik ) ) {
			return new WP_Error( 'kgtk_nik', 'NIK harus 16 digit angka.' );
		}

		// 3. Validasi HP bila diisi
		if ( '' !== $hp && ! preg_match( '/^\+?[0-9][0-9 \-]{6,18}[0-9]$/', $hp ) ) {
			return new WP_Error( 'kgtk_hp', 'Nomor HP tidak valid.' );
		}

		// 4. Validasi Email belajar.id bila diisi
		if ( '' !== $email ) {
			$len = function_exists( 'mb_strlen' ) ? mb_strlen( $email, 'UTF-8' ) : strlen( $email );
			if ( ! is_email( $email ) || $len > 100 || ! preg_match( '/@([a-z0-9-]+\.)*belajar\.id$/', $email ) ) {
				return new WP_Error( 'kgtk_email_belajar', 'Email belajar.id harus berformat valid dan berakhiran belajar.id.' );
			}
		}

		// Batas panjang catatan
		if ( function_exists( 'mb_strlen' ) ? mb_strlen( $catatan, 'UTF-8' ) > 200 : strlen( $catatan ) > 200 ) {
			return new WP_Error( 'kgtk_catatan', 'Catatan maksimal 200 karakter.' );
		}

		$hp_norm   = '' !== $hp ? self::norm_hp( $hp ) : '';
		$nama_norm = '' !== $nama ? self::norm_nama( $nama ) : '';

		// 5. Cek duplikasi di DB untuk pelatihan yang sama
		if ( $pelatihan_id > 0 ) {
			global $wpdb;
			$tu = KGTK_DB::table( 'undangan' );

			if ( '' !== $nik ) {
				$ada_nik = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT id FROM {$tu} WHERE pelatihan_id = %d AND nik = %s AND id != %d",
						$pelatihan_id,
						$nik,
						$exclude_id
					)
				);
				if ( $ada_nik ) {
					return new WP_Error( 'kgtk_duplikat_nik', 'NIK sudah terdaftar dalam daftar peserta ditetapkan pelatihan ini.' );
				}
			}

			if ( '' !== $email ) {
				$ada_email = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT id FROM {$tu} WHERE pelatihan_id = %d AND email_belajar = %s AND id != %d",
						$pelatihan_id,
						$email,
						$exclude_id
					)
				);
				if ( $ada_email ) {
					return new WP_Error( 'kgtk_duplikat_email', 'Email belajar.id sudah terdaftar dalam daftar peserta ditetapkan pelatihan ini.' );
				}
			}

			if ( '' !== $hp_norm ) {
				$ada_hp = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT id FROM {$tu} WHERE pelatihan_id = %d AND hp_norm = %s AND id != %d",
						$pelatihan_id,
						$hp_norm,
						$exclude_id
					)
				);
				if ( $ada_hp ) {
					return new WP_Error( 'kgtk_duplikat_hp', 'Nomor HP sudah terdaftar dalam daftar peserta ditetapkan pelatihan ini.' );
				}
			}
		}

		// 6. Cek duplikasi di batch saat ini (jika disediakan)
		if ( is_array( $batch_seen ) ) {
			if ( '' !== $nik ) {
				if ( isset( $batch_seen['nik'][ $nik ] ) ) {
					return new WP_Error( 'kgtk_duplikat_nik', 'NIK duplikat dalam daftar yang dikirimkan.' );
				}
				$batch_seen['nik'][ $nik ] = true;
			}
			if ( '' !== $email ) {
				if ( isset( $batch_seen['email'][ $email ] ) ) {
					return new WP_Error( 'kgtk_duplikat_email', 'Email belajar.id duplikat dalam daftar yang dikirimkan.' );
				}
				$batch_seen['email'][ $email ] = true;
			}
			if ( '' !== $hp_norm ) {
				if ( isset( $batch_seen['hp'][ $hp_norm ] ) ) {
					return new WP_Error( 'kgtk_duplikat_hp', 'Nomor HP duplikat dalam daftar yang dikirimkan.' );
				}
				$batch_seen['hp'][ $hp_norm ] = true;
			}
		}

		return array(
			'nama'          => $nama,
			'nama_norm'     => $nama_norm,
			'nik'           => $nik,
			'hp'            => $hp,
			'hp_norm'       => $hp_norm,
			'email_belajar' => $email,
			'catatan'       => $catatan,
		);
	}

	/* ====================== Query & Data ====================== */

	public static function get_by_id( $id ) {
		global $wpdb;
		$tu = KGTK_DB::table( 'undangan' );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tu} WHERE id = %d", $id ) );
		if ( $row && (int) $row->peserta_id > 0 && ( empty( $row->nama ) || empty( $row->nik ) || empty( $row->hp ) || empty( $row->email_belajar ) ) ) {
			$tp = KGTK_DB::table( 'peserta' );
			$p  = $wpdb->get_row( $wpdb->prepare( "SELECT nama, nik, hp, email_belajar FROM {$tp} WHERE id = %d", $row->peserta_id ) );
			if ( $p ) {
				$bf = array();
				if ( empty( $row->nama ) && ! empty( $p->nama ) ) {
					$row->nama      = $p->nama;
					$row->nama_norm = self::norm_nama( $p->nama );
					$bf['nama']      = $row->nama;
					$bf['nama_norm'] = $row->nama_norm;
				}
				if ( empty( $row->nik ) && ! empty( $p->nik ) ) {
					$row->nik  = $p->nik;
					$bf['nik'] = $row->nik;
				}
				if ( empty( $row->hp ) && ! empty( $p->hp ) ) {
					$row->hp      = $p->hp;
					$row->hp_norm = self::norm_hp( $p->hp );
					$bf['hp']      = $row->hp;
					$bf['hp_norm'] = $row->hp_norm;
				}
				if ( empty( $row->email_belajar ) && ! empty( $p->email_belajar ) ) {
					$row->email_belajar  = $p->email_belajar;
					$bf['email_belajar'] = $row->email_belajar;
				}
				if ( ! empty( $bf ) ) {
					$wpdb->update( $tu, $bf, array( 'id' => (int) $row->id ) );
				}
			}
		}
		return $row;
	}

	/**
	 * Daftar peserta ditetapkan untuk satu pelatihan, dengan filter dan pencarian.
	 */
	public static function get_list( $pelatihan_id, $filter = 'all', $search = '' ) {
		global $wpdb;
		$tu = KGTK_DB::table( 'undangan' );
		$tp = KGTK_DB::table( 'peserta' );
		$tk = KGTK_DB::table( 'kelas' );

		$where = $wpdb->prepare( 'WHERE u.pelatihan_id = %d', $pelatihan_id );

		// Filter status: 'all', 'sudah', 'belum', 'perlu'
		if ( 'sudah' === $filter ) {
			$where .= " AND u.peserta_id > 0 AND u.cocok_dengan <> 'nama'";
		} elseif ( 'belum' === $filter ) {
			$where .= ' AND u.peserta_id = 0';
		} elseif ( 'perlu' === $filter ) {
			$where .= " AND u.peserta_id > 0 AND u.cocok_dengan = 'nama'";
		}

		if ( '' !== trim( $search ) ) {
			$like = '%' . $wpdb->esc_like( trim( $search ) ) . '%';
			$where .= $wpdb->prepare(
				' AND (u.nama LIKE %s OR u.nik LIKE %s OR u.hp LIKE %s OR u.email_belajar LIKE %s OR ps.nama LIKE %s OR ps.nik LIKE %s OR ps.hp LIKE %s OR ps.email_belajar LIKE %s)',
				$like,
				$like,
				$like,
				$like,
				$like,
				$like,
				$like,
				$like
			);
		}

		$sql = "SELECT u.*,
				       ps.nama AS peserta_nama,
				       ps.gelar_depan AS peserta_gelar_depan,
				       ps.gelar_belakang AS peserta_gelar_belakang,
				       ps.nik AS peserta_nik,
				       ps.tempat_lahir AS peserta_tempat_lahir,
				       ps.tanggal_lahir AS peserta_tanggal_lahir,
				       ps.jenis_kelamin AS peserta_jenis_kelamin,
				       ps.alamat AS peserta_alamat,
				       ps.alamat_instansi AS peserta_alamat_instansi,
				       ps.pangkat_golongan AS peserta_pangkat_golongan,
				       ps.agama AS peserta_agama,
				       ps.pendidikan AS peserta_pendidikan,
				       ps.bank AS peserta_bank,
				       ps.rekening AS peserta_rekening,
				       ps.npwp AS peserta_npwp,
				       ps.pengalaman_jabatan AS peserta_pengalaman_jabatan,
				       ps.hp AS peserta_hp,
				       ps.email_belajar AS peserta_email_belajar,
				       ps.email AS peserta_email,
				       ps.sekolah AS peserta_sekolah,
				       ps.kabkota AS peserta_kabkota,
				       ps.nuptk AS peserta_nuptk,
				       ps.nip AS peserta_nip,
				       ps.simpkb AS peserta_simpkb,
				       ps.peran AS peserta_peran,
				       ps.jenis_gtk AS peserta_jenis_gtk,
				       ps.created_at AS peserta_daftar_at,
				       COALESCE(k.nama, '') AS peserta_kelas_nama
				FROM {$tu} u
				LEFT JOIN {$tp} ps ON ps.id = u.peserta_id
				LEFT JOIN {$tk} k ON k.id = ps.kelas_id
				{$where}
				ORDER BY u.id DESC";

		$rows = $wpdb->get_results( $sql );
		$out  = array();

		foreach ( (array) $rows as $r ) {
			$is_matched  = ( (int) $r->peserta_id > 0 );
			$is_perlu    = ( $is_matched && 'nama' === $r->cocok_dengan );
			$status      = $is_perlu ? 'perlu' : ( $is_matched ? 'sudah' : 'belum' );
			$status_lbl  = $is_perlu ? 'Perlu konfirmasi' : ( $is_matched ? 'Sudah mendaftar' : 'Belum mendaftar' );

			$nama          = (string) $r->nama;
			$nik           = (string) $r->nik;
			$hp            = (string) $r->hp;
			$email_belajar = (string) $r->email_belajar;

			// Jika peserta sudah mendaftar, ambil data dari peserta yang mendaftar untuk mengisi kolom yang kosong
			if ( $is_matched ) {
				$backfill = array();
				if ( '' === trim( $nama ) && ! empty( $r->peserta_nama ) ) {
					$nama                  = (string) $r->peserta_nama;
					$backfill['nama']      = $nama;
					$backfill['nama_norm'] = self::norm_nama( $nama );
				}
				if ( '' === trim( $nik ) && ! empty( $r->peserta_nik ) ) {
					$nik             = (string) $r->peserta_nik;
					$backfill['nik'] = $nik;
				}
				if ( '' === trim( $hp ) && ! empty( $r->peserta_hp ) ) {
					$hp                  = (string) $r->peserta_hp;
					$backfill['hp']      = $hp;
					$backfill['hp_norm'] = self::norm_hp( $hp );
				}
				if ( '' === trim( $email_belajar ) && ! empty( $r->peserta_email_belajar ) ) {
					$email_belajar             = (string) $r->peserta_email_belajar;
					$backfill['email_belajar'] = $email_belajar;
				}
				if ( ! empty( $backfill ) ) {
					$wpdb->update( $tu, $backfill, array( 'id' => (int) $r->id ) );
				}
			}

			$out[] = array(
				'id'                    => (int) $r->id,
				'pelatihan_id'          => (int) $r->pelatihan_id,
				'nama'                  => $nama,
				'nik'                   => $nik,
				'nik_masked'            => self::mask_nik( $nik ),
				'hp'                    => $hp,
				'hp_norm'               => self::norm_hp( $hp ),
				'email_belajar'         => $email_belajar,
				'catatan'               => $r->catatan,
				'peserta_id'            => (int) $r->peserta_id,
				'cocok_dengan'          => $r->cocok_dengan,
				'cocok_pada'            => $r->cocok_pada,
				'status'                => $status,
				'status_label'          => $status_lbl,
				'peserta_nama'          => (string) $r->peserta_nama,
				'peserta_nik'           => (string) $r->peserta_nik,
				'peserta_hp'            => (string) $r->peserta_hp,
				'peserta_email_belajar' => (string) $r->peserta_email_belajar,
				'peserta_email'         => (string) $r->peserta_email,
				'peserta_sekolah'       => (string) $r->peserta_sekolah,
				'peserta_kabkota'       => (string) $r->peserta_kabkota,
				'peserta_nuptk'         => (string) $r->peserta_nuptk,
				'peserta_nip'           => (string) $r->peserta_nip,
				'peserta_simpkb'        => (string) $r->peserta_simpkb,
				'peserta_peran'         => (string) $r->peserta_peran,
				'peserta_kelas'         => (string) $r->peserta_kelas_nama,
				'peserta_kelas_lbl'     => KGTK_Helpers::label_kelas( (string) $r->peserta_kelas_nama ),
				'peserta_daftar_at'     => (string) $r->peserta_daftar_at,
				'created_at'            => $r->created_at,
			);
		}

		return $out;
	}

	/**
	 * Ringkasan statistik peserta ditetapkan satu pelatihan.
	 */
	public static function get_stats( $pelatihan_id ) {
		global $wpdb;
		$tu = KGTK_DB::table( 'undangan' );

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					COUNT(*) AS total,
					SUM(CASE WHEN peserta_id > 0 AND cocok_dengan <> 'nama' THEN 1 ELSE 0 END) AS sudah,
					SUM(CASE WHEN peserta_id = 0 THEN 1 ELSE 0 END) AS belum,
					SUM(CASE WHEN peserta_id > 0 AND cocok_dengan = 'nama' THEN 1 ELSE 0 END) AS perlu
				 FROM {$tu}
				 WHERE pelatihan_id = %d",
				$pelatihan_id
			)
		);

		$total = $row ? (int) $row->total : 0;
		$sudah = $row ? (int) $row->sudah : 0;
		$belum = $row ? (int) $row->belum : 0;
		$perlu = $row ? (int) $row->perlu : 0;

		return array(
			'total'  => $total,
			'sudah'  => $sudah,
			'belum'  => $belum,
			'perlu'  => $perlu,
			'persen' => $total > 0 ? (int) round( ( $sudah / $total ) * 100 ) : 0,
		);
	}

	/**
	 * Statistik semua pelatihan untuk kartu pada halaman daftar.
	 */
	public static function all_undangan_stats() {
		global $wpdb;
		$tu = KGTK_DB::table( 'undangan' );

		$rows = $wpdb->get_results(
			"SELECT pelatihan_id,
			        COUNT(*) AS total,
			        SUM(CASE WHEN peserta_id > 0 AND cocok_dengan <> 'nama' THEN 1 ELSE 0 END) AS sudah,
			        SUM(CASE WHEN peserta_id = 0 THEN 1 ELSE 0 END) AS belum,
			        SUM(CASE WHEN peserta_id > 0 AND cocok_dengan = 'nama' THEN 1 ELSE 0 END) AS perlu
			 FROM {$tu}
			 GROUP BY pelatihan_id"
		);

		$map = array();
		foreach ( (array) $rows as $r ) {
			$tot   = (int) $r->total;
			$sudah = (int) $r->sudah;
			$map[ (int) $r->pelatihan_id ] = array(
				'total'  => $tot,
				'sudah'  => $sudah,
				'belum'  => (int) $r->belum,
				'perlu'  => (int) $r->perlu,
				'persen' => $tot > 0 ? (int) round( ( $sudah / $tot ) * 100 ) : 0,
			);
		}
		return $map;
	}

	/**
	 * Daftar peserta terdaftar dalam pelatihan yang belum terhubung ke baris ditetapkan manapun.
	 */
	public static function get_unlinked_peserta( $pelatihan_id ) {
		global $wpdb;
		$tp = KGTK_DB::table( 'peserta' );
		$tu = KGTK_DB::table( 'undangan' );
		$tk = KGTK_DB::table( 'kelas' );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ps.id, ps.nama, ps.nik, ps.hp, ps.email_belajar, ps.sekolah, ps.kabkota, ps.peran,
				        COALESCE(k.nama, '') AS kelas_nama
				 FROM {$tp} ps
				 LEFT JOIN {$tk} k ON k.id = ps.kelas_id
				 WHERE ps.pelatihan_id = %d
				   AND ps.id NOT IN (SELECT u.peserta_id FROM {$tu} u WHERE u.pelatihan_id = %d AND u.peserta_id > 0)
				 ORDER BY ps.nama ASC",
				$pelatihan_id,
				$pelatihan_id
			)
		);

		$out = array();
		foreach ( (array) $rows as $r ) {
			$out[] = array(
				'id'            => (int) $r->id,
				'nama'          => $r->nama,
				'nik'           => $r->nik,
				'nik_masked'    => self::mask_nik( $r->nik ),
				'hp'            => $r->hp,
				'email_belajar' => $r->email_belajar,
				'sekolah'       => $r->sekolah,
				'kabkota'       => $r->kabkota,
				'peran'         => $r->peran,
				'kelas_nama'    => $r->kelas_nama,
				'kelas_label'   => KGTK_Helpers::label_kelas( $r->kelas_nama ),
			);
		}
		return $out;
	}

	/**
	 * Peta ID peserta yang sudah terhubung ke baris ditetapkan dalam pelatihan ini.
	 * Digunakan untuk menampilkan badge "Ditetapkan" pada tabel peserta.
	 */
	public static function linked_peserta_map( $pelatihan_id ) {
		global $wpdb;
		$tu = KGTK_DB::table( 'undangan' );
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT peserta_id FROM {$tu} WHERE pelatihan_id = %d AND peserta_id > 0",
				$pelatihan_id
			)
		);
		$map = array();
		foreach ( (array) $ids as $id ) {
			$map[ (int) $id ] = true;
		}
		return $map;
	}

	/* ====================== Operasi CRUD ====================== */

	/**
	 * Tambah satu baris peserta ditetapkan oleh admin.
	 */
	public static function tambah_baris( $pelatihan_id, $data ) {
		global $wpdb;
		$pelatihan_id = absint( $pelatihan_id );

		// Cek batas total undangan
		$tu    = KGTK_DB::table( 'undangan' );
		$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tu} WHERE pelatihan_id = %d", $pelatihan_id ) );
		if ( $count >= self::MAKS_TOTAL_UNDANGAN ) {
			return new WP_Error( 'kgtk_maks_undangan', 'Maksimal ' . self::MAKS_TOTAL_UNDANGAN . ' peserta ditetapkan per pelatihan.' );
		}

		$val = self::validasi_baris( $data, $pelatihan_id );
		if ( is_wp_error( $val ) ) {
			return $val;
		}

		$now = current_time( 'mysql' );
		$ok  = $wpdb->insert(
			$tu,
			array(
				'pelatihan_id'  => $pelatihan_id,
				'nama'          => $val['nama'],
				'nik'           => $val['nik'],
				'hp'            => $val['hp'],
				'hp_norm'       => $val['hp_norm'],
				'email_belajar' => $val['email_belajar'],
				'nama_norm'     => $val['nama_norm'],
				'peserta_id'    => 0,
				'cocok_dengan'  => '',
				'cocok_pada'    => null,
				'catatan'       => $val['catatan'],
				'created_at'    => $now,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
		);

		if ( ! $ok ) {
			return new WP_Error( 'kgtk_db', 'Gagal menyimpan data ke database.' );
		}

		$id = (int) $wpdb->insert_id;

		// Pencocokan retroaktif terhadap peserta yang mungkin sudah mendaftar lebih dulu
		self::cocokkan_undangan( $id );

		return self::get_by_id( $id );
	}

	/**
	 * Ubah satu baris peserta ditetapkan oleh admin.
	 */
	public static function ubah_baris( $id, $data ) {
		global $wpdb;
		$id  = absint( $id );
		$row = self::get_by_id( $id );
		if ( ! $row ) {
			return new WP_Error( 'kgtk_tidak_ada', 'Data peserta ditetapkan tidak ditemukan.' );
		}

		$val = self::validasi_baris( $data, (int) $row->pelatihan_id, $id );
		if ( is_wp_error( $val ) ) {
			return $val;
		}

		$tu = KGTK_DB::table( 'undangan' );
		$up = $wpdb->update(
			$tu,
			array(
				'nama'          => $val['nama'],
				'nik'           => $val['nik'],
				'hp'            => $val['hp'],
				'hp_norm'       => $val['hp_norm'],
				'email_belajar' => $val['email_belajar'],
				'nama_norm'     => $val['nama_norm'],
				'catatan'       => $val['catatan'],
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $up ) {
			return new WP_Error( 'kgtk_db', 'Gagal memperbarui data di database.' );
		}

		// Jika belum terhubung, coba pencocokan ulang
		if ( (int) $row->peserta_id === 0 ) {
			self::cocokkan_undangan( $id );
		}

		return self::get_by_id( $id );
	}

	/**
	 * Hapus satu baris peserta ditetapkan oleh admin.
	 */
	public static function hapus_baris( $id ) {
		global $wpdb;
		$id = absint( $id );
		$tu = KGTK_DB::table( 'undangan' );
		return (bool) $wpdb->delete( $tu, array( 'id' => $id ), array( '%d' ) );
	}

	/* ====================== Pencocokan Otomatis & Hubungan ====================== */

	/**
	 * Pencocokan otomatis saat peserta mendaftar (pendaftaran publik atau tambah admin).
	 *
	 * Urutan prioritas hanya terhadap baris ditetapkan yang belum terhubung (peserta_id = 0):
	 * a. NIK sama persis.
	 * b. email_belajar sama (setelah norm_email).
	 * c. HP sama (setelah norm_hp; nilai kosong tidak pernah dicocokkan).
	 * d. Nama sama persis setelah dinormalkan HANYA bila tepat satu kandidat;
	 *    hasilnya berstatus "Perlu konfirmasi" (cocok_dengan = 'nama').
	 *
	 * @param int $peserta_id ID peserta.
	 * @param int $pelatihan_id ID pelatihan (opsional, dicari otomatis jika 0).
	 * @return bool Berhasil dicocokkan atau tidak.
	 */
	public static function cocokkan( $peserta_id, $pelatihan_id = 0 ) {
		return self::cocokkan_peserta( $peserta_id, $pelatihan_id );
	}

	public static function tambah( $pelatihan_id, $data ) {
		$res = self::tambah_baris( $pelatihan_id, $data );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		return is_object( $res ) ? (int) $res->id : $res;
	}

	public static function ubah( $id, $data ) {
		$res = self::ubah_baris( $id, $data );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		return is_object( $res ) ? (int) $res->id : $res;
	}

	public static function hapus( $id ) {
		return self::hapus_baris( $id );
	}

	public static function lepas( $undangan_id ) {
		return self::lepas_hubungan( $undangan_id );
	}

	/**
	 * Tautkan baris undangan ke peserta dan otomatis isi data yang masih kosong
	 * mengambil data dari peserta yang sudah mendaftar.
	 */
	public static function tautkan_peserta( $undangan_id, $peserta_id, $cocok_dengan ) {
		global $wpdb;
		$undangan_id = absint( $undangan_id );
		$peserta_id  = absint( $peserta_id );
		if ( ! $undangan_id || ! $peserta_id ) {
			return false;
		}

		$tu = KGTK_DB::table( 'undangan' );
		$tp = KGTK_DB::table( 'peserta' );

		$u = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tu} WHERE id = %d", $undangan_id ) );
		$p = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tp} WHERE id = %d", $peserta_id ) );
		if ( ! $u || ! $p ) {
			return false;
		}

		$now  = current_time( 'mysql' );
		$data = array(
			'peserta_id'   => $peserta_id,
			'cocok_dengan' => $cocok_dengan,
			'cocok_pada'   => $now,
		);
		$fmt  = array( '%d', '%s', '%s' );

		// Jika data undangan masih kosong, isi dari peserta yang mendaftar
		if ( '' === trim( (string) $u->nama ) && ! empty( $p->nama ) ) {
			$data['nama']      = $p->nama;
			$data['nama_norm'] = self::norm_nama( $p->nama );
			$fmt[]             = '%s';
			$fmt[]             = '%s';
		}
		if ( '' === trim( (string) $u->nik ) && ! empty( $p->nik ) ) {
			$data['nik'] = $p->nik;
			$fmt[]       = '%s';
		}
		if ( '' === trim( (string) $u->hp ) && ! empty( $p->hp ) ) {
			$data['hp']      = $p->hp;
			$data['hp_norm'] = self::norm_hp( $p->hp );
			$fmt[]           = '%s';
			$fmt[]           = '%s';
		}
		if ( '' === trim( (string) $u->email_belajar ) && ! empty( $p->email_belajar ) ) {
			$data['email_belajar'] = $p->email_belajar;
			$fmt[]                 = '%s';
		}

		return (bool) $wpdb->update(
			$tu,
			$data,
			array( 'id' => $undangan_id ),
			$fmt,
			array( '%d' )
		);
	}

	public static function cocokkan_peserta( $peserta_id, $pelatihan_id = 0 ) {
		global $wpdb;
		$peserta_id   = absint( $peserta_id );
		$pelatihan_id = absint( $pelatihan_id );

		if ( ! $peserta_id ) {
			return false;
		}

		$tp = KGTK_DB::table( 'peserta' );
		$tu = KGTK_DB::table( 'undangan' );

		if ( ! $pelatihan_id ) {
			$pelatihan_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT pelatihan_id FROM {$tp} WHERE id = %d", $peserta_id ) );
		}

		if ( ! $pelatihan_id ) {
			return false;
		}

		// Cek apakah peserta sudah terhubung ke baris lain di pelatihan ini
		$sudah = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$tu} WHERE pelatihan_id = %d AND peserta_id = %d",
				$pelatihan_id,
				$peserta_id
			)
		);
		if ( $sudah ) {
			return true;
		}

		// Ambil data peserta
		$peserta = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, nik, nama, hp, email_belajar FROM {$tp} WHERE id = %d AND pelatihan_id = %d",
				$peserta_id,
				$pelatihan_id
			)
		);
		if ( ! $peserta ) {
			return false;
		}

		$p_nik   = trim( (string) $peserta->nik );
		$p_email = self::norm_email( $peserta->email_belajar );
		$p_hp    = self::norm_hp( $peserta->hp );
		$p_nama  = self::norm_nama( $peserta->nama );
		$now     = current_time( 'mysql' );

		// Prioritas A: NIK sama persis
		if ( '' !== $p_nik ) {
			$matched_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$tu} WHERE pelatihan_id = %d AND peserta_id = 0 AND nik = %s LIMIT 1",
					$pelatihan_id,
					$p_nik
				)
			);
			if ( $matched_id > 0 ) {
				return self::tautkan_peserta( $matched_id, $peserta_id, 'nik' );
			}
		}

		// Prioritas B: email_belajar sama
		if ( '' !== $p_email ) {
			$matched_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$tu} WHERE pelatihan_id = %d AND peserta_id = 0 AND email_belajar = %s LIMIT 1",
					$pelatihan_id,
					$p_email
				)
			);
			if ( $matched_id > 0 ) {
				return self::tautkan_peserta( $matched_id, $peserta_id, 'email' );
			}
		}

		// Prioritas C: HP sama (hp_norm)
		if ( '' !== $p_hp ) {
			$matched_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$tu} WHERE pelatihan_id = %d AND peserta_id = 0 AND hp_norm = %s LIMIT 1",
					$pelatihan_id,
					$p_hp
				)
			);
			if ( $matched_id > 0 ) {
				return self::tautkan_peserta( $matched_id, $peserta_id, 'hp' );
			}
		}

		// Prioritas D: Nama sama persis HANYA bila tepat satu kandidat
		if ( '' !== $p_nama ) {
			// Cek jumlah kandidat di undangan belum cocok
			$cand_undangan = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT id FROM {$tu} WHERE pelatihan_id = %d AND peserta_id = 0 AND nama_norm = %s",
					$pelatihan_id,
					$p_nama
				)
			);

			// Cek jumlah kandidat di peserta belum cocok
			$cand_peserta_count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$tp}
					 WHERE pelatihan_id = %d
					   AND id NOT IN (SELECT u.peserta_id FROM {$tu} u WHERE u.pelatihan_id = %d AND u.peserta_id > 0)
					   AND LOWER(TRIM(nama)) = %s",
					$pelatihan_id,
					$pelatihan_id,
					$p_nama
				)
			);

			if ( 1 === count( $cand_undangan ) && 1 === $cand_peserta_count ) {
				$matched_id = (int) $cand_undangan[0];
				return self::tautkan_peserta( $matched_id, $peserta_id, 'nama' );
			}
		}

		return false;
	}

	/**
	 * Pencocokan retroaktif untuk satu baris undangan terhadap peserta yang sudah mendaftar.
	 */
	public static function cocokkan_undangan( $undangan_id ) {
		global $wpdb;
		$undangan_id = absint( $undangan_id );
		$tu          = KGTK_DB::table( 'undangan' );
		$tp          = KGTK_DB::table( 'peserta' );

		$u = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tu} WHERE id = %d", $undangan_id ) );
		if ( ! $u || (int) $u->peserta_id > 0 ) {
			return false;
		}

		$pelatihan_id = (int) $u->pelatihan_id;
		$now          = current_time( 'mysql' );

		// Prioritas A: NIK
		if ( '' !== $u->nik ) {
			$p_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$tp}
					 WHERE pelatihan_id = %d
					   AND nik = %s
					   AND id NOT IN (SELECT peserta_id FROM {$tu} WHERE pelatihan_id = %d AND peserta_id > 0)
					 LIMIT 1",
					$pelatihan_id,
					$u->nik,
					$pelatihan_id
				)
			);
			if ( $p_id > 0 ) {
				return self::tautkan_peserta( $undangan_id, $p_id, 'nik' );
			}
		}

		// Prioritas B: email_belajar
		if ( '' !== $u->email_belajar ) {
			$p_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$tp}
					 WHERE pelatihan_id = %d
					   AND email_belajar = %s
					   AND id NOT IN (SELECT peserta_id FROM {$tu} WHERE pelatihan_id = %d AND peserta_id > 0)
					 LIMIT 1",
					$pelatihan_id,
					$u->email_belajar,
					$pelatihan_id
				)
			);
			if ( $p_id > 0 ) {
				return self::tautkan_peserta( $undangan_id, $p_id, 'email' );
			}
		}

		// Prioritas C: HP (hp_norm)
		if ( '' !== $u->hp_norm ) {
			$unlinked_peserta = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, hp FROM {$tp}
					 WHERE pelatihan_id = %d
					   AND id NOT IN (SELECT peserta_id FROM {$tu} WHERE pelatihan_id = %d AND peserta_id > 0)",
					$pelatihan_id,
					$pelatihan_id
				)
			);
			$matched_pids = array();
			foreach ( (array) $unlinked_peserta as $p_row ) {
				if ( self::norm_hp( $p_row->hp ) === $u->hp_norm ) {
					$matched_pids[] = (int) $p_row->id;
				}
			}
			if ( 1 === count( $matched_pids ) ) {
				return self::tautkan_peserta( $undangan_id, $matched_pids[0], 'hp' );
			}
		}

		// Prioritas D: Nama sama persis (tepat satu kandidat)
		if ( '' !== $u->nama_norm ) {
			// Cek unlinked undangan dengan nama ini
			$u_cand_count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$tu} WHERE pelatihan_id = %d AND peserta_id = 0 AND nama_norm = %s",
					$pelatihan_id,
					$u->nama_norm
				)
			);

			// Cek unlinked peserta dengan nama ini
			$p_candidates = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT id FROM {$tp}
					 WHERE pelatihan_id = %d
					   AND id NOT IN (SELECT peserta_id FROM {$tu} WHERE pelatihan_id = %d AND peserta_id > 0)
					   AND LOWER(TRIM(nama)) = %s",
					$pelatihan_id,
					$pelatihan_id,
					$u->nama_norm
				)
			);

			if ( 1 === $u_cand_count && 1 === count( $p_candidates ) ) {
				return self::tautkan_peserta( $undangan_id, (int) $p_candidates[0], 'nama' );
			}
		}

		return false;
	}

	/**
	 * Sinkronkan ulang seluruh baris belum cocok pada pelatihan tertentu.
	 *
	 * Tahapan dilakukan berurutan:
	 * 1. Seluruh NIK
	 * 2. Seluruh Email
	 * 3. Seluruh HP
	 * 4. Seluruh Nama (hanya kandidat tunggal)
	 */
	public static function sinkronkan_pelatihan( $pelatihan_id ) {
		global $wpdb;
		$pelatihan_id = absint( $pelatihan_id );
		$tu           = KGTK_DB::table( 'undangan' );
		$tp           = KGTK_DB::table( 'peserta' );
		$now          = current_time( 'mysql' );
		$matched      = 0;

		// Tahap 1: NIK
		$sql_nik = "SELECT u.id AS u_id, ps.id AS p_id
		            FROM {$tu} u
		            INNER JOIN {$tp} ps ON ps.pelatihan_id = u.pelatihan_id AND ps.nik = u.nik
		            WHERE u.pelatihan_id = %d AND u.peserta_id = 0 AND u.nik <> ''
		              AND ps.id NOT IN (SELECT u2.peserta_id FROM {$tu} u2 WHERE u2.pelatihan_id = %d AND u2.peserta_id > 0)";
		$nik_matches = $wpdb->get_results( $wpdb->prepare( $sql_nik, $pelatihan_id, $pelatihan_id ) );
		foreach ( (array) $nik_matches as $m ) {
			if ( self::tautkan_peserta( (int) $m->u_id, (int) $m->p_id, 'nik' ) ) {
				$matched++;
			}
		}

		// Tahap 2: Email
		$sql_email = "SELECT u.id AS u_id, ps.id AS p_id
		              FROM {$tu} u
		              INNER JOIN {$tp} ps ON ps.pelatihan_id = u.pelatihan_id AND ps.email_belajar = u.email_belajar
		              WHERE u.pelatihan_id = %d AND u.peserta_id = 0 AND u.email_belajar <> ''
		                AND ps.id NOT IN (SELECT u2.peserta_id FROM {$tu} u2 WHERE u2.pelatihan_id = %d AND u2.peserta_id > 0)";
		$email_matches = $wpdb->get_results( $wpdb->prepare( $sql_email, $pelatihan_id, $pelatihan_id ) );
		foreach ( (array) $email_matches as $m ) {
			if ( self::tautkan_peserta( (int) $m->u_id, (int) $m->p_id, 'email' ) ) {
				$matched++;
			}
		}

		// Tahap 3: HP
		$unlinked_undangan = $wpdb->get_results(
			$wpdb->prepare( "SELECT id, hp_norm FROM {$tu} WHERE pelatihan_id = %d AND peserta_id = 0 AND hp_norm <> ''", $pelatihan_id )
		);
		$unlinked_peserta  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, hp FROM {$tp}
				 WHERE pelatihan_id = %d
				   AND id NOT IN (SELECT u.peserta_id FROM {$tu} u WHERE u.pelatihan_id = %d AND u.peserta_id > 0)",
				$pelatihan_id,
				$pelatihan_id
			)
		);
		$p_by_hp = array();
		foreach ( (array) $unlinked_peserta as $p_row ) {
			$norm = self::norm_hp( $p_row->hp );
			if ( '' !== $norm ) {
				$p_by_hp[ $norm ][] = (int) $p_row->id;
			}
		}
		foreach ( (array) $unlinked_undangan as $u_row ) {
			if ( isset( $p_by_hp[ $u_row->hp_norm ] ) && 1 === count( $p_by_hp[ $u_row->hp_norm ] ) ) {
				$pid = $p_by_hp[ $u_row->hp_norm ][0];
				if ( self::tautkan_peserta( (int) $u_row->id, $pid, 'hp' ) ) {
					$matched++;
					unset( $p_by_hp[ $u_row->hp_norm ] );
				}
			}
		}

		// Tahap 4: Nama
		$unlinked_und_nama = $wpdb->get_results(
			$wpdb->prepare( "SELECT id, nama_norm FROM {$tu} WHERE pelatihan_id = %d AND peserta_id = 0 AND nama_norm <> ''", $pelatihan_id )
		);
		$unlinked_pes_nama = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, nama FROM {$tp}
				 WHERE pelatihan_id = %d
				   AND id NOT IN (SELECT u.peserta_id FROM {$tu} u WHERE u.pelatihan_id = %d AND u.peserta_id > 0)",
				$pelatihan_id,
				$pelatihan_id
			)
		);
		$p_by_nama = array();
		foreach ( (array) $unlinked_pes_nama as $p_row ) {
			$n_norm = self::norm_nama( $p_row->nama );
			if ( '' !== $n_norm ) {
				$p_by_nama[ $n_norm ][] = (int) $p_row->id;
			}
		}
		$u_by_nama = array();
		foreach ( (array) $unlinked_und_nama as $u_row ) {
			$u_by_nama[ $u_row->nama_norm ][] = (int) $u_row->id;
		}

		foreach ( $u_by_nama as $n_norm => $u_ids ) {
			if ( 1 === count( $u_ids ) && isset( $p_by_nama[ $n_norm ] ) && 1 === count( $p_by_nama[ $n_norm ] ) ) {
				$uid = $u_ids[0];
				$pid = $p_by_nama[ $n_norm ][0];
				if ( self::tautkan_peserta( $uid, $pid, 'nama' ) ) {
					$matched++;
					unset( $p_by_nama[ $n_norm ] );
				}
			}
		}

		// Backfill baris yang sudah terhubung tapi data di undangan masih kosong
		$matched_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT u.id AS u_id, u.nama AS u_nama, u.nik AS u_nik, u.hp AS u_hp, u.email_belajar AS u_email,
				        ps.nama AS p_nama, ps.nik AS p_nik, ps.hp AS p_hp, ps.email_belajar AS p_email
				 FROM {$tu} u
				 INNER JOIN {$tp} ps ON ps.id = u.peserta_id
				 WHERE u.pelatihan_id = %d AND u.peserta_id > 0
				   AND (u.nama = '' OR u.nik = '' OR u.hp = '' OR u.email_belajar = '')",
				$pelatihan_id
			)
		);
		foreach ( (array) $matched_rows as $mr ) {
			$bf = array();
			if ( empty( $mr->u_nama ) && ! empty( $mr->p_nama ) ) {
				$bf['nama']      = $mr->p_nama;
				$bf['nama_norm'] = self::norm_nama( $mr->p_nama );
			}
			if ( empty( $mr->u_nik ) && ! empty( $mr->p_nik ) ) {
				$bf['nik'] = $mr->p_nik;
			}
			if ( empty( $mr->u_hp ) && ! empty( $mr->p_hp ) ) {
				$bf['hp']      = $mr->p_hp;
				$bf['hp_norm'] = self::norm_hp( $mr->p_hp );
			}
			if ( empty( $mr->u_email ) && ! empty( $mr->p_email ) ) {
				$bf['email_belajar'] = $mr->p_email;
			}
			if ( ! empty( $bf ) ) {
				$wpdb->update( $tu, $bf, array( 'id' => (int) $mr->u_id ) );
			}
		}

		return $matched;
	}

	/**
	 * Hubungkan baris undangan ke peserta secara manual oleh admin.
	 */
	public static function hubungkan_manual( $undangan_id, $peserta_id ) {
		global $wpdb;
		$undangan_id = absint( $undangan_id );
		$peserta_id  = absint( $peserta_id );

		$u = self::get_by_id( $undangan_id );
		if ( ! $u ) {
			return new WP_Error( 'kgtk_tidak_ada', 'Data peserta ditetapkan tidak ditemukan.' );
		}

		$tp = KGTK_DB::table( 'peserta' );
		$p  = $wpdb->get_row( $wpdb->prepare( "SELECT id, pelatihan_id FROM {$tp} WHERE id = %d", $peserta_id ) );
		if ( ! $p || (int) $p->pelatihan_id !== (int) $u->pelatihan_id ) {
			return new WP_Error( 'kgtk_peserta_invalid', 'Peserta tidak valid untuk pelatihan ini.' );
		}

		$tu = KGTK_DB::table( 'undangan' );
		// Pastikan peserta belum terhubung ke baris undangan lain
		$ada = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$tu} WHERE pelatihan_id = %d AND peserta_id = %d AND id != %d",
				$u->pelatihan_id,
				$peserta_id,
				$undangan_id
			)
		);
		if ( $ada ) {
			return new WP_Error( 'kgtk_peserta_sudah_terhubung', 'Peserta ini sudah terhubung ke baris peserta ditetapkan lainnya.' );
		}

		$ok = self::tautkan_peserta( $undangan_id, $peserta_id, 'manual' );
		if ( ! $ok ) {
			return new WP_Error( 'kgtk_gagal', 'Gagal menghubungkan peserta.' );
		}

		return self::get_by_id( $undangan_id );
	}

	/**
	 * Lepas hubungan antara baris undangan dan peserta.
	 */
	public static function lepas_hubungan( $undangan_id ) {
		global $wpdb;
		$undangan_id = absint( $undangan_id );
		$tu          = KGTK_DB::table( 'undangan' );

		$wpdb->update(
			$tu,
			array( 'peserta_id' => 0, 'cocok_dengan' => '', 'cocok_pada' => null ),
			array( 'id' => $undangan_id ),
			array( '%d', '%s', '%s' ),
			array( '%d' )
		);

		return self::get_by_id( $undangan_id );
	}

	/**
	 * Konfirmasi pencocokan nama oleh admin (mengubah status dari 'Perlu konfirmasi' menjadi 'Sudah mendaftar').
	 */
	public static function konfirmasi( $undangan_id ) {
		global $wpdb;
		$undangan_id = absint( $undangan_id );
		$u           = self::get_by_id( $undangan_id );
		if ( ! $u || (int) $u->peserta_id === 0 ) {
			return new WP_Error( 'kgtk_invalid', 'Baris ini belum terhubung ke peserta.' );
		}

		$tu = KGTK_DB::table( 'undangan' );
		$wpdb->update(
			$tu,
			array( 'cocok_dengan' => 'manual' ),
			array( 'id' => $undangan_id ),
			array( '%s' ),
			array( '%d' )
		);

		return self::get_by_id( $undangan_id );
	}

	/**
	 * Lepas hubungan bila peserta dihapus dari pelatihan.
	 */
	public static function lepas_peserta( $peserta_id ) {
		global $wpdb;
		$peserta_id = absint( $peserta_id );
		if ( ! $peserta_id ) {
			return;
		}
		$tu = KGTK_DB::table( 'undangan' );
		$wpdb->update(
			$tu,
			array( 'peserta_id' => 0, 'cocok_dengan' => '', 'cocok_pada' => null ),
			array( 'peserta_id' => $peserta_id ),
			array( '%d', '%s', '%s' ),
			array( '%d' )
		);
	}

	/* ====================== Impor Excel/CSV ====================== */

	/**
	 * Sinonim kolom untuk tebak otomatis kolom impor undangan.
	 */
	public static function sinonim_kolom() {
		return array(
			'nama'          => array( 'nama', 'namapeserta', 'namalengkap', 'namaguru', 'peserta' ),
			'nik'           => array( 'nik', 'nomorindukkependudukan', 'indukkependudukan', 'nonik', 'nomornik', 'noktp', 'ktp', 'nomorinduk' ),
			'hp'            => array( 'hp', 'nohp', 'nomorhp', 'telepon', 'notelepon', 'wa', 'nowa', 'nomorwa', 'whatsapp', 'handphone', 'ponsel', 'nohandphone', 'kontak', 'nohpwhatsapp', 'hpwhatsapp' ),
			'email_belajar' => array( 'emailbelajar', 'emailbelajarid', 'belajarid', 'akunbelajar', 'akunbelajarid', 'email', 'surel' ),
		);
	}

	/**
	 * Deteksi baris judul kolom untuk data undangan.
	 */
	public static function deteksi_header( $rows ) {
		$kata = array();
		foreach ( self::sinonim_kolom() as $list ) {
			$kata = array_merge( $kata, $list );
		}
		$kata[] = 'no';
		$kata[] = 'nomor';

		$best  = 0;
		$skor  = -1;
		$batas = min( 15, count( $rows ) );
		for ( $i = 0; $i < $batas; $i++ ) {
			$n = 0;
			if ( is_array( $rows[ $i ] ) ) {
				foreach ( $rows[ $i ] as $c ) {
					$norm = preg_replace( '/[^a-z0-9]/', '', strtolower( (string) $c ) );
					if ( '' !== $norm && in_array( $norm, $kata, true ) ) {
						$n++;
					}
				}
			}
			if ( $n > $skor ) {
				$skor = $n;
				$best = $i;
			}
		}
		return $best;
	}

	/**
	 * Tebak kolom dari header berkas impor undangan.
	 */
	public static function tebak_kolom( $header ) {
		$normh = array();
		foreach ( (array) $header as $h ) {
			$normh[] = preg_replace( '/[^a-z0-9]/', '', strtolower( (string) $h ) );
		}

		$pakai = array();
		$out   = array();
		foreach ( self::sinonim_kolom() as $field => $synonyms ) {
			$idx = -1;
			foreach ( $synonyms as $w ) {
				foreach ( $normh as $i => $hval ) {
					if ( ! isset( $pakai[ $i ] ) && $hval === $w ) {
						$idx = $i;
						break 2;
					}
				}
			}
			if ( -1 === $idx ) {
				foreach ( $synonyms as $w ) {
					foreach ( $normh as $i => $hval ) {
						if ( ! isset( $pakai[ $i ] ) && '' !== $w && false !== strpos( $hval, $w ) ) {
							$idx = $i;
							break 2;
						}
					}
				}
			}
			if ( $idx > -1 ) {
				$pakai[ $idx ] = true;
			}
			$out[ $field ] = $idx;
		}
		return $out;
	}

	/**
	 * Proses impor baris berkas Excel/CSV ke kgtk_undangan.
	 */
	public static function proses_impor( $pelatihan_id, $file_path, $map ) {
		$pelatihan_id = absint( $pelatihan_id );

		if ( ! file_exists( $file_path ) ) {
			return new WP_Error( 'kgtk_file', 'Berkas impor tidak ditemukan.' );
		}

		$rows = KGTK_Sekolah_Admin::baca_baris( $file_path, self::MAKS_IMPOR_BARIS + 10 );
		if ( ! is_array( $rows ) || count( $rows ) < 2 ) {
			@unlink( $file_path );
			return new WP_Error( 'kgtk_kosong', 'Berkas tidak memiliki baris data yang cukup.' );
		}

		// Deteksi header
		$hdr_idx = self::deteksi_header( $rows );
		$data_rows = array_slice( $rows, $hdr_idx + 1 );

		if ( empty( $data_rows ) ) {
			@unlink( $file_path );
			return new WP_Error( 'kgtk_kosong', 'Tidak ada data setelah baris judul kolom.' );
		}

		// Cek total batas undangan pelatihan
		global $wpdb;
		$tu          = KGTK_DB::table( 'undangan' );
		$count_awal  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tu} WHERE pelatihan_id = %d", $pelatihan_id ) );
		$sisa_kuota  = max( 0, self::MAKS_TOTAL_UNDANGAN - $count_awal );

		if ( $sisa_kuota <= 0 ) {
			@unlink( $file_path );
			return new WP_Error( 'kgtk_kuota_penuh', 'Pelatihan ini sudah mencapai batas maksimum ' . self::MAKS_TOTAL_UNDANGAN . ' peserta ditetapkan.' );
		}

		$idx_nama  = isset( $map['nama'] ) ? (int) $map['nama'] : -1;
		$idx_nik   = isset( $map['nik'] ) ? (int) $map['nik'] : -1;
		$idx_hp    = isset( $map['hp'] ) ? (int) $map['hp'] : -1;
		$idx_email = isset( $map['email_belajar'] ) ? (int) $map['email_belajar'] : -1;

		if ( $idx_nama < 0 && $idx_nik < 0 && $idx_hp < 0 && $idx_email < 0 ) {
			@unlink( $file_path );
			return new WP_Error( 'kgtk_map', 'Minimal satu kolom harus dipetakan.' );
		}

		$batch_seen = array( 'nik' => array(), 'email' => array(), 'hp' => array() );
		$imported   = 0;
		$skipped    = 0;
		$reasons    = array();
		$now        = current_time( 'mysql' );

		foreach ( $data_rows as $row_no => $cells ) {
			if ( $imported >= $sisa_kuota ) {
				$skipped++;
				$reasons[] = 'Baris ' . ( $row_no + $hdr_idx + 2 ) . ': Dilewati karena kuota maksimum tercapai.';
				continue;
			}

			$r_data = array(
				'nama'          => $idx_nama >= 0 && isset( $cells[ $idx_nama ] ) ? $cells[ $idx_nama ] : '',
				'nik'           => $idx_nik >= 0 && isset( $cells[ $idx_nik ] ) ? $cells[ $idx_nik ] : '',
				'hp'            => $idx_hp >= 0 && isset( $cells[ $idx_hp ] ) ? $cells[ $idx_hp ] : '',
				'email_belajar' => $idx_email >= 0 && isset( $cells[ $idx_email ] ) ? $cells[ $idx_email ] : '',
				'catatan'       => 'Impor berkas',
			);

			// Baris yang semuanya kosong dilewati
			if ( '' === trim( (string) $r_data['nama'] ) && '' === trim( (string) $r_data['nik'] ) && '' === trim( (string) $r_data['hp'] ) && '' === trim( (string) $r_data['email_belajar'] ) ) {
				$skipped++;
				$reasons[] = 'Baris ' . ( $row_no + $hdr_idx + 2 ) . ': Dilewati karena tidak memiliki data.';
				continue;
			}

			$val = self::validasi_baris( $r_data, $pelatihan_id, 0, $batch_seen );
			if ( is_wp_error( $val ) ) {
				$skipped++;
				$reasons[] = 'Baris ' . ( $row_no + $hdr_idx + 2 ) . ': ' . $val->get_error_message();
				continue;
			}

			$ins = $wpdb->insert(
				$tu,
				array(
					'pelatihan_id'  => $pelatihan_id,
					'nama'          => $val['nama'],
					'nik'           => $val['nik'],
					'hp'            => $val['hp'],
					'hp_norm'       => $val['hp_norm'],
					'email_belajar' => $val['email_belajar'],
					'nama_norm'     => $val['nama_norm'],
					'peserta_id'    => 0,
					'cocok_dengan'  => '',
					'cocok_pada'    => null,
					'catatan'       => $val['catatan'],
					'created_at'    => $now,
				),
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
			);

			if ( $ins ) {
				$imported++;
			} else {
				$skipped++;
				$reasons[] = 'Baris ' . ( $row_no + $hdr_idx + 2 ) . ': Gagal disimpan ke database.';
			}
		}

		// Hapus berkas sementara
		@unlink( $file_path );

		// Jalankan pencocokan massal untuk baris yang baru saja diimpor
		if ( $imported > 0 ) {
			self::sinkronkan_pelatihan( $pelatihan_id );
		}

		return array(
			'imported'   => $imported,
			'skipped'    => $skipped,
			'total_rows' => $imported + $skipped,
			'reasons'    => $reasons,
		);
	}

	/* ====================== Export Excel ====================== */

	/**
	 * Handler admin-post untuk export Excel peserta ditetapkan.
	 */
	public static function handle_export() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Anda tidak memiliki akses.', 'Akses ditolak', array( 'response' => 403 ) );
		}
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		check_admin_referer( 'kgtk_undangan_export_' . $id );

		$filter = isset( $_GET['filter'] ) ? sanitize_key( wp_unslash( $_GET['filter'] ) ) : 'all';
		self::export_excel( $id, $filter );
	}

	/**
	 * Generate dan stream berkas Excel status peserta ditetapkan.
	 */
	public static function export_excel( $pelatihan_id, $filter = 'all' ) {
		$pelatihan_id = absint( $pelatihan_id );
		$p            = KGTK_Helpers::get_by_id( $pelatihan_id );
		if ( ! $p ) {
			wp_die( 'Pelatihan tidak ditemukan.', 'Tidak ditemukan', array( 'response' => 404 ) );
		}

		if ( ! class_exists( '\PhpOffice\PhpSpreadsheet\Spreadsheet' ) ) {
			wp_die( 'Library Excel belum terpasang. Jalankan composer install di folder plugin.', 'Library belum terpasang', array( 'response' => 500 ) );
		}

		$rows = self::get_list( $pelatihan_id, $filter );

		$ss = new Spreadsheet();
		$ss->getProperties()
			->setCreator( 'KGTK Pelatihan' )
			->setTitle( 'Peserta Ditetapkan - ' . $p->nama );

		$ws = $ss->getActiveSheet();
		$ws->setTitle( 'Peserta Ditetapkan' );

		// Judul
		$ws->setCellValueExplicit( 'A1', 'DAFTAR PESERTA DITETAPKAN', DataType::TYPE_STRING );
		$ws->setCellValueExplicit( 'A2', self::safe_excel_string( $p->nama ), DataType::TYPE_STRING );
		$ws->setCellValueExplicit( 'A3', self::safe_excel_string( KGTK_Helpers::rentang_tanggal( $p->tanggal_mulai, $p->tanggal_selesai ) . ( $p->lokasi ? ' | ' . $p->lokasi : '' ) ), DataType::TYPE_STRING );

		$ws->mergeCells( 'A1:K1' );
		$ws->mergeCells( 'A2:K2' );
		$ws->mergeCells( 'A3:K3' );

		$ws->getStyle( 'A1:A2' )->getFont()->setBold( true )->setSize( 14 );
		$ws->getStyle( 'A3' )->getFont()->setSize( 11 );
		$ws->getStyle( 'A1:A3' )->getAlignment()->setHorizontal( Alignment::HORIZONTAL_CENTER );

		// Header kolom
		$headers = array(
			'A' => 'No',
			'B' => 'Nama',
			'C' => 'NIK',
			'D' => 'No. HP',
			'E' => 'Email Belajar.id',
			'F' => 'Status Pendaftaran',
			'G' => 'Dicocokkan Lewat',
			'H' => 'Kelas Peserta',
			'I' => 'Peran',
			'J' => 'Waktu Mendaftar',
			'K' => 'Catatan',
		);

		$hr = 5;
		foreach ( $headers as $col => $title ) {
			$ws->setCellValueExplicit( $col . $hr, $title, DataType::TYPE_STRING );
		}

		$ws->getStyle( 'A5:K5' )->getFont()->setBold( true )->setColor( new \PhpOffice\PhpSpreadsheet\Style\Color( 'FFFFFFFF' ) );
		$ws->getStyle( 'A5:K5' )->getFill()->setFillType( \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID )->getStartColor()->setARGB( 'FF1E3A8A' ); // Navy
		$ws->getStyle( 'A5:K5' )->getAlignment()->setHorizontal( Alignment::HORIZONTAL_CENTER )->setVertical( Alignment::VERTICAL_CENTER );
		$ws->getRowDimension( $hr )->setRowHeight( 26 );

		$r = 6;
		$no = 1;
		foreach ( $rows as $row ) {
			$cocok_lbl = '-';
			if ( 'nik' === $row['cocok_dengan'] ) {
				$cocok_lbl = 'NIK';
			} elseif ( 'email' === $row['cocok_dengan'] ) {
				$cocok_lbl = 'Email';
			} elseif ( 'hp' === $row['cocok_dengan'] ) {
				$cocok_lbl = 'No. HP';
			} elseif ( 'nama' === $row['cocok_dengan'] ) {
				$cocok_lbl = 'Nama (Perlu Konfirmasi)';
			} elseif ( 'manual' === $row['cocok_dengan'] ) {
				$cocok_lbl = 'Manual';
			}

			$ws->setCellValueExplicit( 'A' . $r, (string) $no++, DataType::TYPE_STRING );
			$ws->setCellValueExplicit( 'B' . $r, self::safe_excel_string( $row['nama'] ), DataType::TYPE_STRING );
			$ws->setCellValueExplicit( 'C' . $r, self::safe_excel_string( $row['nik'] ), DataType::TYPE_STRING );
			$ws->setCellValueExplicit( 'D' . $r, self::safe_excel_string( $row['hp'] ), DataType::TYPE_STRING );
			$ws->setCellValueExplicit( 'E' . $r, self::safe_excel_string( $row['email_belajar'] ), DataType::TYPE_STRING );
			$ws->setCellValueExplicit( 'F' . $r, self::safe_excel_string( $row['status_label'] ), DataType::TYPE_STRING );
			$ws->setCellValueExplicit( 'G' . $r, self::safe_excel_string( $cocok_lbl ), DataType::TYPE_STRING );
			$ws->setCellValueExplicit( 'H' . $r, self::safe_excel_string( $row['peserta_kelas_lbl'] ? $row['peserta_kelas_lbl'] : '-' ), DataType::TYPE_STRING );
			$ws->setCellValueExplicit( 'I' . $r, self::safe_excel_string( $row['peserta_peran'] ? $row['peserta_peran'] : '-' ), DataType::TYPE_STRING );
			$ws->setCellValueExplicit( 'J' . $r, self::safe_excel_string( $row['peserta_daftar_at'] ? $row['peserta_daftar_at'] : '-' ), DataType::TYPE_STRING );
			$ws->setCellValueExplicit( 'K' . $r, self::safe_excel_string( $row['catatan'] ), DataType::TYPE_STRING );

			$ws->getStyle( 'A' . $r )->getAlignment()->setHorizontal( Alignment::HORIZONTAL_CENTER );
			$ws->getStyle( 'C' . $r . ':D' . $r )->getAlignment()->setHorizontal( Alignment::HORIZONTAL_LEFT );
			$ws->getStyle( 'F' . $r . ':J' . $r )->getAlignment()->setHorizontal( Alignment::HORIZONTAL_CENTER );

			$ws->getRowDimension( $r )->setRowHeight( 22 );
			$r++;
		}

		$last_r = max( 6, $r - 1 );
		$ws->getStyle( 'A5:K' . $last_r )->getBorders()->getAllBorders()->setBorderStyle( Border::BORDER_THIN );
		$ws->getStyle( 'A5:K' . $last_r )->getFont()->setSize( 11 );

		// Auto-fit kolom
		foreach ( range( 'A', 'K' ) as $col ) {
			$ws->getColumnDimension( $col )->setAutoSize( true );
		}

		$ws->getPageSetup()->setPaperSize( PageSetup::PAPERSIZE_A4 );
		$ws->getPageSetup()->setOrientation( PageSetup::ORIENTATION_LANDSCAPE );

		$safe_name = preg_replace( '/[^A-Za-z0-9_-]/', '_', $p->nama );
		$filename  = 'Peserta_Ditetapkan_' . $safe_name . '_' . gmdate( 'Ymd_His' ) . '.xlsx';

		while ( ob_get_level() ) {
			ob_end_clean();
		}

		nocache_headers();
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		$writer = new Xlsx( $ss );
		$writer->save( 'php://output' );
		$ss->disconnectWorksheets();
		exit;
	}
}
