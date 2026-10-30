<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fungsi bantu yang dipakai bersama oleh REST, admin, dan export.
 */
class KGTK_Helpers {

	/** Hanya peran ini yang memakai slot kelas; peran lain tidak dibatasi. */
	const PERAN_SLOT           = 'Peserta';
	const BARIS_PER_HALAMAN    = 20;
	const BARIS_HALAMAN_AWAL   = 20;
	const BARIS_HALAMAN_LANJUT = 26;
	/** Nilai kabupaten/kota untuk guru dari luar daftar sekolah. */
	const KABKOTA_LAIN      = 'Lainnya';
	const PERAN = array( 'Fasilitator', 'Pengajar', 'Narasumber', 'Penceramah', 'Pemateri', 'Panitia', 'Peserta' );
	const JABATAN_PANITIA = array( 'Ketua Panitia', 'Sekretaris', 'Bendahara', 'Seksi Acara', 'Seksi Konsumsi', 'Seksi Perlengkapan', 'Seksi Dokumentasi', 'Anggota Panitia' );
	const JENIS_GTK = array( 'Guru', 'Tenaga Didik', 'Kepala Sekolah', 'Pengawas Sekolah' );
	const JABATAN = array( 'Guru', 'Tenaga Didik', 'Kepala Sekolah', 'Pengawas Sekolah' );
	const PENDIDIKAN = array( 'SMA/SMK Sederajat', 'D3', 'D4 / S1', 'S2', 'S3' );
	const JENIS_KELAMIN = array( 'Laki-laki', 'Perempuan' );
	const AGAMA = array( 'Islam', 'Kristen Protestan', 'Katolik', 'Hindu', 'Buddha', 'Khonghucu' );
	const PANGKAT_GOLONGAN = array(
		'Non ASN / Honorer',
		'Pengatur Muda (II/a)',
		'Pengatur Muda Tk. I (II/b)',
		'Pengatur (II/c)',
		'Pengatur Tk. I (II/d)',
		'Penata Muda (III/a)',
		'Penata Muda Tk. I (III/b)',
		'Penata (III/c)',
		'Penata Tk. I (III/d)',
		'Pembina (IV/a)',
		'Pembina Tk. I (IV/b)',
		'Pembina Utama Muda (IV/c)',
		'Pembina Utama Madya (IV/d)',
		'Pembina Utama (IV/e)',
		'PPPK',
		'Lainnya',
	);

	/* ---------- Format ---------- */

	/**
	 * Format nama lengkap beserta gelar depan dan gelar belakang bila ada.
	 * Contoh: "Dr. Axel Dhanis Gusadi, S.I.Kom"
	 *
	 * @param string|object|array $nama_or_obj Objek peserta/guru, array data, atau string nama.
	 * @param string              $gelar_depan Gelar depan (opsional).
	 * @param string              $gelar_belakang Gelar belakang (opsional).
	 * @return string
	 */
	public static function format_nama_gelar( $nama_or_obj, $gelar_depan = '', $gelar_belakang = '' ) {
		$nama = '';
		$gd   = '';
		$gb   = '';

		if ( is_object( $nama_or_obj ) ) {
			$nama = isset( $nama_or_obj->nama ) ? trim( (string) $nama_or_obj->nama ) : '';
			$gd   = isset( $nama_or_obj->gelar_depan ) ? trim( (string) $nama_or_obj->gelar_depan ) : '';
			$gb   = isset( $nama_or_obj->gelar_belakang ) ? trim( (string) $nama_or_obj->gelar_belakang ) : '';
		} elseif ( is_array( $nama_or_obj ) ) {
			$nama = isset( $nama_or_obj['nama'] ) ? trim( (string) $nama_or_obj['nama'] ) : '';
			$gd   = isset( $nama_or_obj['gelar_depan'] ) ? trim( (string) $nama_or_obj['gelar_depan'] ) : '';
			$gb   = isset( $nama_or_obj['gelar_belakang'] ) ? trim( (string) $nama_or_obj['gelar_belakang'] ) : '';
		} else {
			$nama = trim( (string) $nama_or_obj );
			$gd   = trim( (string) $gelar_depan );
			$gb   = trim( (string) $gelar_belakang );
		}

		$out = $nama;
		if ( '' !== $gd ) {
			$out = $gd . ' ' . $out;
		}
		if ( '' !== $gb ) {
			$out = $out . ', ' . $gb;
		}
		return trim( $out );
	}

	/** Nama kelas untuk ditampilkan: "Pagi" menjadi "Kelas Pagi", "Kelas A" tetap "Kelas A", kosong menjadi "Menunggu Penentuan Admin". */
	public static function label_kelas( $nama ) {
		if ( ! $nama || 'Belum Ditentukan' === $nama || 'Belum ada kelas' === $nama ) {
			return 'Menunggu Penentuan Admin';
		}
		return 0 === stripos( (string) $nama, 'kelas' ) ? $nama : 'Kelas ' . $nama;
	}

	public static function format_tanggal( $ymd ) {
		$bulan = array( '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember' );
		$t     = strtotime( $ymd . ' 00:00:00 UTC' );
		if ( ! $t ) {
			return $ymd;
		}
		return (int) gmdate( 'j', $t ) . ' ' . $bulan[ (int) gmdate( 'n', $t ) ] . ' ' . gmdate( 'Y', $t );
	}

	public static function format_tanggal_indo( $ymd ) {
		return self::format_tanggal( $ymd );
	}

	/** "12 Mei 2026" atau "12 - 14 Mei 2026" atau "30 Mei - 2 Juni 2026". */
	public static function rentang_tanggal( $mulai, $selesai ) {
		if ( ! $selesai || $selesai === $mulai ) {
			return self::format_tanggal( $mulai );
		}
		$a = explode( ' ', self::format_tanggal( $mulai ) );
		$b = explode( ' ', self::format_tanggal( $selesai ) );
		if ( $a[2] === $b[2] && $a[1] === $b[1] ) {
			return $a[0] . ' - ' . self::format_tanggal( $selesai );
		}
		if ( $a[2] === $b[2] ) {
			return $a[0] . ' ' . $a[1] . ' - ' . self::format_tanggal( $selesai );
		}
		return self::format_tanggal( $mulai ) . ' - ' . self::format_tanggal( $selesai );
	}

	public static function format_rentang_tanggal( $mulai, $selesai ) {
		return self::rentang_tanggal( $mulai, $selesai );
	}

	/**
	 * Rentang tanggal spesifik sebuah kelas bila diisi, atau fallback ke tanggal umum pelatihan.
	 *
	 * @param object      $p Objek pelatihan.
	 * @param object|null $k Objek kelas (opsional).
	 * @return string
	 */
	public static function rentang_tanggal_kelas( $p, $k = null ) {
		if ( is_object( $k ) && ! empty( $k->tanggal_mulai ) && '0000-00-00' !== $k->tanggal_mulai ) {
			$kselesai = ( ! empty( $k->tanggal_selesai ) && '0000-00-00' !== $k->tanggal_selesai ) ? $k->tanggal_selesai : $k->tanggal_mulai;
			return self::rentang_tanggal( $k->tanggal_mulai, $kselesai );
		}
		if ( is_object( $p ) ) {
			return self::rentang_tanggal( $p->tanggal_mulai, $p->tanggal_selesai );
		}
		return '';
	}

	/**
	 * Daftar hari pelaksanaan spesifik kelas bila diisi, atau fallback ke daftar hari pelatihan induk.
	 *
	 * @param object      $p Objek pelatihan.
	 * @param object|null $k Objek kelas (opsional).
	 * @return array
	 */
	public static function daftar_hari_kelas( $p, $k = null ) {
		if ( is_object( $k ) && ! empty( $k->tanggal_mulai ) && '0000-00-00' !== $k->tanggal_mulai ) {
			$kselesai = ( ! empty( $k->tanggal_selesai ) && '0000-00-00' !== $k->tanggal_selesai ) ? $k->tanggal_selesai : $k->tanggal_mulai;
			return self::daftar_hari_pelatihan( $k->tanggal_mulai, $kselesai );
		}
		if ( is_object( $p ) ) {
			return self::daftar_hari_pelatihan( $p->tanggal_mulai, $p->tanggal_selesai );
		}
		return array();
	}

	/**
	 * Sanitasi nilai deadline pendaftaran dari input form atau API.
	 * Format yang disimpan di database: 'Y-m-d H:i:s' atau null jika tidak ditentukan.
	 *
	 * @param string|null $raw
	 * @return string|null
	 */
	public static function sanitize_deadline( $raw ) {
		if ( empty( $raw ) || ! is_scalar( $raw ) ) {
			return null;
		}
		$raw = trim( (string) $raw );
		if ( '' === $raw ) {
			return null;
		}
		// Dukung format HTML datetime-local (mis. '2026-10-30T23:59')
		$raw = str_replace( 'T', ' ', $raw );
		// Jika hanya tanggal YYYY-MM-DD
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw ) ) {
			$raw .= ' 23:59:59';
		} elseif ( preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $raw ) ) {
			$raw .= ':00';
		}

		$d = DateTime::createFromFormat( 'Y-m-d H:i:s', $raw );
		if ( $d && $d->format( 'Y-m-d H:i:s' ) === $raw ) {
			return $raw;
		}
		return null;
	}

	/**
	 * Format tanggal & waktu deadline dalam bahasa Indonesia yang ramah dibaca.
	 * Mis. "30 Oktober 2026 pukul 23:59 WIB"
	 *
	 * @param string|null $dt
	 * @return string
	 */
	public static function format_deadline( $dt ) {
		if ( empty( $dt ) || ! is_string( $dt ) ) {
			return '';
		}
		$dt = trim( $dt );
		if ( '' === $dt || '0000-00-00 00:00:00' === $dt ) {
			return '';
		}
		$ts = strtotime( $dt );
		if ( ! $ts ) {
			return '';
		}
		$tgl = self::format_tanggal( date( 'Y-m-d', $ts ) );
		$jam = date( 'H:i', $ts );
		return $tgl . " pukul {$jam} WIB";
	}

	/**
	 * Periksa apakah deadline pendaftaran pelatihan sudah terlewati.
	 * Jika kelas memiliki batas pendaftaran spesifik, pendaftaran tetap dibuka selama
	 * minimal masih ada 1 kelas yang batas pendaftarannya belum terlewati.
	 *
	 * @param object|array|string|null $pelatihan_or_dt Objek pelatihan, array, atau string datetime.
	 * @param array|null               $kelas           Daftar kelas dari kelas_stats (opsional).
	 * @return bool
	 */
	public static function is_deadline_passed( $pelatihan_or_dt, $kelas = null ) {
		$now = current_time( 'mysql' );

		if ( is_object( $pelatihan_or_dt ) && ! empty( $pelatihan_or_dt->id ) ) {
			if ( null === $kelas ) {
				$kelas = self::kelas_stats( (int) $pelatihan_or_dt->id );
			}
			if ( ! empty( $kelas ) && is_array( $kelas ) ) {
				$has_class_dl = false;
				foreach ( $kelas as $k ) {
					if ( ! empty( $k->deadline_pendaftaran ) && '0000-00-00 00:00:00' !== $k->deadline_pendaftaran ) {
						$has_class_dl = true;
						break;
					}
				}
				if ( $has_class_dl ) {
					$general_dl = ! empty( $pelatihan_or_dt->deadline_pendaftaran ) && '0000-00-00 00:00:00' !== $pelatihan_or_dt->deadline_pendaftaran
						? trim( (string) $pelatihan_or_dt->deadline_pendaftaran )
						: '';
					foreach ( $kelas as $k ) {
						$k_dl = ( ! empty( $k->deadline_pendaftaran ) && '0000-00-00 00:00:00' !== $k->deadline_pendaftaran )
							? trim( (string) $k->deadline_pendaftaran )
							: $general_dl;
						// Jika kelas ini belum melewati batas pendaftaran (atau tidak punya batas), maka masih buka.
						if ( '' === $k_dl || $now <= $k_dl ) {
							return false;
						}
					}
					return true;
				}
			}
		}

		$dt = '';
		if ( is_object( $pelatihan_or_dt ) ) {
			$dt = ! empty( $pelatihan_or_dt->deadline_pendaftaran ) ? (string) $pelatihan_or_dt->deadline_pendaftaran : '';
		} elseif ( is_array( $pelatihan_or_dt ) ) {
			$dt = ! empty( $pelatihan_or_dt['deadline_pendaftaran'] ) ? (string) $pelatihan_or_dt['deadline_pendaftaran'] : '';
		} else {
			$dt = (string) $pelatihan_or_dt;
		}
		$dt = trim( $dt );
		if ( '' === $dt || '0000-00-00 00:00:00' === $dt ) {
			return false;
		}
		return $now > $dt;
	}

	/**
	 * Ambil teks deadline efektif untuk pesan penutupan pendaftaran.
	 *
	 * @param object     $p     Objek pelatihan.
	 * @param array|null $kelas Daftar kelas (opsional).
	 * @return string
	 */
	public static function effective_deadline_text( $p, $kelas = null ) {
		if ( is_object( $p ) && ! empty( $p->deadline_pendaftaran ) && '0000-00-00 00:00:00' !== $p->deadline_pendaftaran ) {
			if ( null === $kelas && ! empty( $p->id ) ) {
				$kelas = self::kelas_stats( (int) $p->id );
			}
			$latest = (string) $p->deadline_pendaftaran;
			foreach ( (array) $kelas as $k ) {
				if ( ! empty( $k->deadline_pendaftaran ) && $k->deadline_pendaftaran > $latest ) {
					$latest = (string) $k->deadline_pendaftaran;
				}
			}
			return self::format_deadline( $latest );
		}
		if ( is_object( $p ) ) {
			if ( null === $kelas && ! empty( $p->id ) ) {
				$kelas = self::kelas_stats( (int) $p->id );
			}
			$latest = '';
			foreach ( (array) $kelas as $k ) {
				if ( ! empty( $k->deadline_pendaftaran ) && '0000-00-00 00:00:00' !== $k->deadline_pendaftaran ) {
					if ( '' === $latest || $k->deadline_pendaftaran > $latest ) {
						$latest = (string) $k->deadline_pendaftaran;
					}
				}
			}
			if ( '' !== $latest ) {
				return self::format_deadline( $latest );
			}
		}
		return 'batas waktu yang ditentukan';
	}

	/**
	 * Daftar Kabupaten/Kota resmi di Kepulauan Riau untuk dropdown lokasi pelatihan.
	 *
	 * @return array
	 */
	public static function kabkota_pelatihan_list() {
		$default = array(
			'Kota Tanjungpinang',
			'Kota Batam',
			'Kabupaten Bintan',
			'Kabupaten Karimun',
			'Kabupaten Natuna',
			'Kabupaten Lingga',
			'Kabupaten Kepulauan Anambas',
			'Luar Kepulauan Riau / Lainnya',
		);
		$db_list = self::kabkota_list();
		if ( ! empty( $db_list ) ) {
			foreach ( $db_list as $k ) {
				$label = self::format_kabkota_label( $k );
				if ( ! in_array( $label, $default, true ) && 'Lainnya' !== $label && '' !== $label ) {
					$default[] = $label;
				}
			}
		}
		return $default;
	}

	/**
	 * Tentukan nama Kabupaten/Kota tempat kegiatan pelatihan untuk tanggal tanda tangan dokumen.
	 *
	 * @param object|array|string $kabkota_or_pel Objek pelatihan, array, atau string kabkota.
	 * @param string              $lokasi         Lokasi pelatihan jika argumen pertama berupa string kabkota.
	 * @return string Nama Kabupaten/Kota tempat kegiatan (mis. "Tanjungpinang", "Batam", "Bintan").
	 */
	public static function kabkota_tempat_kegiatan( $kabkota_or_pel, $lokasi = '' ) {
		$kab = '';
		$lok = '';
		if ( is_object( $kabkota_or_pel ) ) {
			$kab = ! empty( $kabkota_or_pel->kabkota ) ? (string) $kabkota_or_pel->kabkota : '';
			$lok = ! empty( $kabkota_or_pel->lokasi ) ? (string) $kabkota_or_pel->lokasi : '';
		} elseif ( is_array( $kabkota_or_pel ) ) {
			$kab = ! empty( $kabkota_or_pel['kabkota'] ) ? (string) $kabkota_or_pel['kabkota'] : '';
			$lok = ! empty( $kabkota_or_pel['lokasi'] ) ? (string) $kabkota_or_pel['lokasi'] : '';
		} else {
			$kab = (string) $kabkota_or_pel;
			$lok = (string) $lokasi;
		}

		$target = '' !== trim( $kab ) ? trim( $kab ) : trim( $lok );
		if ( '' === $target ) {
			return 'Bintan';
		}

		$low = strtolower( $target );

		// Jika daring/online dan tidak ada kabkota pelatihan eksplisit, default ke Bintan
		if ( '' === trim( $kab ) && ( false !== strpos( $low, 'zoom' ) || false !== strpos( $low, 'daring' ) || false !== strpos( $low, 'online' ) || false !== strpos( $low, 'lms' ) ) ) {
			return 'Bintan';
		}

		// Deteksi kabupaten/kota di Kepri atau umum
		if ( false !== strpos( $low, 'tanjungpinang' ) || false !== strpos( $low, 'tanjung pinang' ) ) {
			return 'Tanjungpinang';
		}
		if ( false !== strpos( $low, 'batam' ) ) {
			return 'Batam';
		}
		if ( false !== strpos( $low, 'bintan' ) ) {
			return 'Bintan';
		}
		if ( false !== strpos( $low, 'karimun' ) ) {
			return 'Karimun';
		}
		if ( false !== strpos( $low, 'natuna' ) ) {
			return 'Natuna';
		}
		if ( false !== strpos( $low, 'lingga' ) ) {
			return 'Lingga';
		}
		if ( false !== strpos( $low, 'anambas' ) ) {
			return 'Kepulauan Anambas';
		}

		// Jika berupa teks lokasi lain, bersihkan prefix "Kota ", "Kab. ", "Kabupaten " jika ada
		$clean = preg_replace( '/^(kota|kabupaten|kab\.)\s+/i', '', $target );
		return ! empty( $clean ) ? $clean : $target;
	}

	/**
	 * Nama hari dalam bahasa Indonesia berdasarkan tanggal (Y-m-d).
	 */
	public static function hari_indonesia( $ymd ) {
		$nama_hari = array(
			'Sunday'    => 'Minggu',
			'Monday'    => 'Senin',
			'Tuesday'   => 'Selasa',
			'Wednesday' => 'Rabu',
			'Thursday'  => 'Kamis',
			'Friday'    => 'Jumat',
			'Saturday'  => 'Sabtu',
		);
		$t = strtotime( $ymd . ' 00:00:00 UTC' );
		if ( ! $t ) {
			return '';
		}
		$day_en = gmdate( 'l', $t );
		return isset( $nama_hari[ $day_en ] ) ? $nama_hari[ $day_en ] : $day_en;
	}

	/**
	 * Format lengkap Hari, Tanggal Bulan Tahun (contoh: "Kamis, 15 Oktober 2026").
	 */
	public static function format_hari_tanggal( $ymd ) {
		$hari = self::hari_indonesia( $ymd );
		$tgl  = self::format_tanggal( $ymd );
		if ( '' !== $hari && '' !== $tgl ) {
			return $hari . ', ' . $tgl;
		}
		return '' !== $tgl ? $tgl : $ymd;
	}

	/**
	 * Mengambil daftar hari (tanggal dan nama hari) dalam lingkup pelaksanaan pelatihan.
	 * Menghasilkan array: hari ke-1, hari ke-2, dst.
	 */
	public static function daftar_hari_pelatihan( $mulai, $selesai ) {
		$list = array();
		if ( empty( $mulai ) ) {
			return $list;
		}
		$t_start = strtotime( $mulai . ' 00:00:00 UTC' );
		if ( ! $t_start ) {
			return $list;
		}
		$t_end = ! empty( $selesai ) ? strtotime( $selesai . ' 00:00:00 UTC' ) : $t_start;
		if ( ! $t_end || $t_end < $t_start ) {
			$t_end = $t_start;
		}

		$cur      = $t_start;
		$index    = 1;
		$max_days = 90; // batas aman maksimal hari pelatihan
		while ( $cur <= $t_end && $index <= $max_days ) {
			$ymd           = gmdate( 'Y-m-d', $cur );
			$hari          = self::hari_indonesia( $ymd );
			$tgl           = self::format_tanggal( $ymd );
			$label_lengkap = $hari . ', ' . $tgl;

			$list[] = array(
				'ymd'           => $ymd,
				'hari'          => $hari,
				'tanggal'       => $tgl,
				'label_lengkap' => $label_lengkap,
				'label_hari_ke' => 'Hari ke-' . $index . ' : ' . $label_lengkap,
				'hari_ke'       => $index,
			);

			$cur = strtotime( '+1 day', $cur );
			$index++;
		}
		return $list;
	}

	/* ---------- Data sekolah ---------- */

	/** Daftar kabupaten/kota yang ada di data sekolah (kosong bila data belum diimpor). */
	public static function kabkota_list() {
		static $cache = null;
		if ( null === $cache ) {
			global $wpdb;
			$t     = KGTK_DB::table( 'sekolah' );
			$cache = array_map( 'strval', (array) $wpdb->get_col( "SELECT DISTINCT kabkota FROM {$t} ORDER BY kabkota" ) );
		}
		return $cache;
	}

	/**
	 * Format nama tampilan Kabupaten/Kota untuk dropdown tanpa mengubah value aslinya.
	 */
	public static function format_kabkota_label( $raw ) {
		$s = trim( (string) $raw );
		if ( '' === $s || self::KABKOTA_LAIN === $s ) {
			return $s;
		}
		$lower = strtolower( $s );
		if ( in_array( $lower, array( 'pulau lingga', 'lingga', 'kab. pulau lingga', 'kabupaten pulau lingga', 'kab. lingga', 'kabupaten lingga' ), true ) ) {
			return 'Kabupaten Lingga';
		}
		if ( 'batam' === $lower || 'kota batam' === $lower ) {
			return 'Kota Batam';
		}
		if ( in_array( $lower, array( 'tanjungpinang', 'kota tanjungpinang', 'tanjung pinang', 'kota tanjung pinang' ), true ) ) {
			return 'Kota Tanjungpinang';
		}
		if ( preg_match( '/^kabupaten\s+/i', $s ) ) {
			return $s;
		}
		if ( preg_match( '/^kab\.\s*/i', $s ) ) {
			return 'Kabupaten ' . preg_replace( '/^kab\.\s*/i', '', $s );
		}
		if ( preg_match( '/^kota\s+/i', $s ) ) {
			return $s;
		}
		return 'Kabupaten ' . $s;
	}

	/* ---------- Kode & link ---------- */

	public static function normalize_kode( $raw ) {
		return strtoupper( trim( (string) $raw ) );
	}

	public static function valid_kode( $kode ) {
		return (bool) preg_match( '/^[A-Z0-9-]{3,20}$/', $kode );
	}

	public static function kode_exists( $kode ) {
		global $wpdb;
		$t = KGTK_DB::table( 'pelatihan' );
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t} WHERE kode = %s", $kode ) );
	}

	public static function generate_kode() {
		$abc = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
		for ( $i = 0; $i < 20; $i++ ) {
			$s = '';
			for ( $j = 0; $j < 4; $j++ ) {
				$s .= $abc[ wp_rand( 0, strlen( $abc ) - 1 ) ];
			}
			$kode = 'KGTK-' . $s;
			if ( ! self::kode_exists( $kode ) ) {
				return $kode;
			}
		}
		return 'KGTK-' . strtoupper( wp_generate_password( 6, false ) );
	}

	public static function share_url( $kode ) {
		$page = (int) get_option( 'kgtk_page_id' );
		$base = ( $page && 'publish' === get_post_status( $page ) ) ? get_permalink( $page ) : home_url( '/' );
		return add_query_arg( 'kode', rawurlencode( $kode ), $base );
	}

	public static function public_url() {
		$page = (int) get_option( 'kgtk_page_id' );
		return ( $page && 'publish' === get_post_status( $page ) ) ? get_permalink( $page ) : home_url( '/' );
	}

	public static function client_ip() {
		// Sengaja tidak mempercayai X-Forwarded-For (mudah dipalsukan).
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
	}

	/* ---------- Query ---------- */

	public static function get_by_kode( $kode ) {
		global $wpdb;
		$t = KGTK_DB::table( 'pelatihan' );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE kode = %s", $kode ) );
	}

	public static function get_by_id( $id ) {
		global $wpdb;
		$t = KGTK_DB::table( 'pelatihan' );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE id = %d", $id ) );
	}

	/** Kelas satu pelatihan beserta jumlah terisi. */
	public static function kelas_stats( $pelatihan_id ) {
		$all = self::all_kelas_stats( (int) $pelatihan_id );
		return isset( $all[ (int) $pelatihan_id ] ) ? $all[ (int) $pelatihan_id ] : array();
	}

	/** Ambil semua peserta dalam satu pelatihan beserta nama kelas (atau kosong bila belum ditempatkan). */
	public static function peserta_pelatihan( $pelatihan_id ) {
		global $wpdb;
		$tp = KGTK_DB::table( 'peserta' );
		$tk = KGTK_DB::table( 'kelas' );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ps.*, COALESCE(k.nama, '') AS kelas_nama, COALESCE(k.slot, 0) AS kelas_slot,
				        k.tanggal_mulai AS kelas_tanggal_mulai, k.tanggal_selesai AS kelas_tanggal_selesai
				 FROM {$tp} ps
				 LEFT JOIN {$tk} k ON k.id = ps.kelas_id
				 WHERE ps.pelatihan_id = %d AND ps.peran != 'Panitia'
				 ORDER BY ps.created_at DESC, ps.id DESC",
				$pelatihan_id
			)
		);
	}

	/** Hitung peserta yang belum ditentukan kelasnya (kelas_id = 0, tidak termasuk Panitia). */
	public static function unassigned_count( $pelatihan_id ) {
		global $wpdb;
		$tp = KGTK_DB::table( 'peserta' );
		return (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$tp} WHERE pelatihan_id = %d AND kelas_id = 0 AND peran != 'Panitia'", $pelatihan_id )
		);
	}

	/** Peta pelatihan_id => daftar kelas (id, nama, slot, tanggal_mulai, tanggal_selesai, deadline_pendaftaran, terisi). */
	public static function all_kelas_stats( $only_id = 0 ) {
		global $wpdb;
		$k = KGTK_DB::table( 'kelas' );
		$p = KGTK_DB::table( 'peserta' );

		$where = $only_id ? $wpdb->prepare( 'WHERE k.pelatihan_id = %d', $only_id ) : '';
		$rows  = $wpdb->get_results(
			"SELECT k.id, k.pelatihan_id, k.nama, k.slot, k.tanggal_mulai, k.tanggal_selesai, k.deadline_pendaftaran, k.urutan,
			        SUM(CASE WHEN p.peran = 'Peserta' THEN 1 ELSE 0 END) AS terisi,
			        SUM(CASE WHEN p.peran <> 'Peserta' AND p.peran <> 'Panitia' AND p.peran IS NOT NULL THEN 1 ELSE 0 END) AS lain
			 FROM {$k} k LEFT JOIN {$p} p ON p.kelas_id = k.id AND p.peran != 'Panitia'
			 {$where}
			 GROUP BY k.id, k.pelatihan_id, k.nama, k.slot, k.tanggal_mulai, k.tanggal_selesai, k.deadline_pendaftaran, k.urutan
			 ORDER BY k.pelatihan_id, k.urutan, k.id"
		);

		$now = current_time( 'mysql' );
		$map = array();
		foreach ( (array) $rows as $r ) {
			$r->id                   = (int) $r->id;
			$r->pelatihan_id         = (int) $r->pelatihan_id;
			$r->slot                 = (int) $r->slot;
			$r->terisi               = (int) $r->terisi;
			$r->lain                 = (int) $r->lain;
			$r->sisa                 = max( 0, $r->slot - $r->terisi );
			$r->label                = self::label_kelas( $r->nama );
			$r->tanggal_mulai        = ( ! empty( $r->tanggal_mulai ) && '0000-00-00' !== $r->tanggal_mulai ) ? $r->tanggal_mulai : null;
			$r->tanggal_selesai      = ( ! empty( $r->tanggal_selesai ) && '0000-00-00' !== $r->tanggal_selesai ) ? $r->tanggal_selesai : $r->tanggal_mulai;
			$r->tanggal_label        = $r->tanggal_mulai ? self::rentang_tanggal( $r->tanggal_mulai, $r->tanggal_selesai ) : '';
			$r->deadline_pendaftaran = ( ! empty( $r->deadline_pendaftaran ) && '0000-00-00 00:00:00' !== $r->deadline_pendaftaran ) ? $r->deadline_pendaftaran : null;
			$r->deadline_formatted   = $r->deadline_pendaftaran ? self::format_deadline( $r->deadline_pendaftaran ) : '';
			$r->is_deadline_passed   = $r->deadline_pendaftaran ? ( $now > $r->deadline_pendaftaran ) : false;
			$map[ $r->pelatihan_id ][] = $r;
		}
		return $map;
	}

	/** Total slot, terisi, dan status dari daftar kelas. */
	public static function totals( $kelas ) {
		$slot   = 0;
		$terisi = 0;
		$lain   = 0;
		foreach ( $kelas as $k ) {
			$slot   += $k->slot;
			$terisi += $k->terisi;
			$lain   += $k->lain;
		}
		return array(
			'slot'   => $slot,
			'terisi' => $terisi,
			'lain'   => $lain,
			'sisa'   => max( 0, $slot - $terisi ),
			'persen' => $slot ? (int) round( $terisi / $slot * 100 ) : 0,
			'penuh'  => $slot > 0 && $terisi >= $slot,
		);
	}

	/**
	 * Kelompokkan peserta menurut peran. Urutan mengikuti KGTK_Helpers::PERAN;
	 * peran lain (mis. data lama "Guru") ditaruh di belakang, urut abjad.
	 */
	public static function group_peran( $rows ) {
		$by = array();
		foreach ( (array) $rows as $r ) {
			$by[ (string) $r->peran ][] = $r;
		}
		$out = array();
		foreach ( self::PERAN as $p ) {
			if ( isset( $by[ $p ] ) ) {
				$out[ $p ] = $by[ $p ];
				unset( $by[ $p ] );
			}
		}
		ksort( $by );
		foreach ( $by as $p => $list ) {
			$out[ $p ] = $list;
		}
		return $out;
	}

	/**
	 * Bagi baris absen menjadi halaman. Halaman pertama memuat hingga 20 baris (karena ada kop dan info kegiatan),
	 * sedangkan halaman lanjutan memuat hingga 25 baris sehingga memenuhkan kertas terlebih dahulu sebelum pindah halaman.
	 * Tiap halaman: array( 'rows' => peserta, 'mulai' => indeks baris pertama (0), 'jumlah' => baris di halaman ini ).
	 */
	public static function halaman( $rows, $total = 0 ) {
		$rows  = array_values( (array) $rows );
		$total = max( 1, (int) $total, count( $rows ) );
		$pages = array();
		$mulai = 0;
		$p_idx = 0;

		while ( $mulai < $total ) {
			$per = ( 0 === $p_idx ) ? self::BARIS_HALAMAN_AWAL : self::BARIS_HALAMAN_LANJUT;
			$pages[] = array(
				'rows'   => array_slice( $rows, $mulai, $per ),
				'mulai'  => $mulai,
				'jumlah' => min( $per, $total - $mulai ),
			);
			$mulai += $per;
			$p_idx++;
		}
		return $pages;
	}

	/** "DAFTAR HADIR FASILITATOR" (tanpa peran: "DAFTAR HADIR"). */
	public static function judul_absen( $peran ) {
		$peran = trim( (string) $peran );
		if ( '' === $peran ) {
			return 'DAFTAR HADIR';
		}
		return 'DAFTAR HADIR ' . ( function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $peran, 'UTF-8' ) : strtoupper( $peran ) );
	}

	/** Array 3 baris info kegiatan: Kegiatan, Tanggal kegiatan, Tempat kegiatan. */
	public static function baris_info_array( $p, $tanggal_khusus = '', $k = null ) {
		$arr = array();
		if ( ! empty( $p->nama ) ) {
			$arr[] = (string) $p->nama;
		}
		$tanggal_khusus = trim( (string) $tanggal_khusus );
		$is_custom_day  = ( '' !== $tanggal_khusus && 'semua' !== strtolower( $tanggal_khusus ) && 'all' !== strtolower( $tanggal_khusus ) && 'all_days' !== strtolower( $tanggal_khusus ) && 'range' !== strtolower( $tanggal_khusus ) );
		if ( $is_custom_day ) {
			$arr[] = self::format_hari_tanggal( $tanggal_khusus );
		} else {
			$tgl = self::rentang_tanggal_kelas( $p, $k );
			if ( '' !== $tgl ) {
				$arr[] = $tgl;
			}
		}
		if ( ! empty( $p->lokasi ) ) {
			$arr[] = (string) $p->lokasi;
		}
		return $arr;
	}

	/** Baris kegiatan gabungan (satu baris dipisah koma). */
	public static function baris_info( $p, $tanggal_khusus = '', $k = null ) {
		return implode( ', ', self::baris_info_array( $p, $tanggal_khusus, $k ) );
	}

	/** Output HTML 3 baris info kegiatan terpisah di tengah halaman. */
	public static function baris_info_html( $p, $tanggal_khusus = '', $k = null ) {
		$tgl_default    = self::rentang_tanggal_kelas( $p, $k );
		$tanggal_khusus = trim( (string) $tanggal_khusus );
		$is_custom_day  = ( '' !== $tanggal_khusus && 'semua' !== strtolower( $tanggal_khusus ) && 'all' !== strtolower( $tanggal_khusus ) && 'all_days' !== strtolower( $tanggal_khusus ) && 'range' !== strtolower( $tanggal_khusus ) );
		$tgl_display    = $is_custom_day ? self::format_hari_tanggal( $tanggal_khusus ) : $tgl_default;

		$html = '<div class="kgtk-doc-info" style="text-align:center;margin:6px 0 12px;font-size:12pt;line-height:1.4;">';
		if ( ! empty( $p->nama ) ) {
			$html .= '<div class="kgtk-doc-nama">' . esc_html( $p->nama ) . '</div>';
		}
		if ( '' !== $tgl_display ) {
			$html .= '<div class="kgtk-doc-tanggal" data-tgl-default="' . esc_attr( $tgl_default ) . '">' . esc_html( $tgl_display ) . '</div>';
		}
		if ( ! empty( $p->lokasi ) ) {
			$html .= '<div class="kgtk-doc-lokasi">' . esc_html( $p->lokasi ) . '</div>';
		}
		$html .= '</div>';
		return $html;
	}

	/** Nama penanggung jawab untuk blok tanda tangan; titik-titik bila kosong (diisi tangan). */
	public static function nama_pj( $p ) {
		$n = trim( (string) $p->penanggung_jawab );
		return '' !== $n ? $n : '........................................';
	}

	/** Label jabatan untuk blok tanda tangan: "(Penanggung Jawab)," atau "(Ketua),". */
	public static function label_pj( $p ) {
		$tipe = ! empty( $p->tipe_pj ) ? trim( (string) $p->tipe_pj ) : 'Penanggung Jawab';
		return '(' . $tipe . '),';
	}

	/**
	 * Kelompok lembar absen satu kelas: "Peserta" selalu ada dan paling depan (bisa kosong,
	 * barisnya sebanyak slot); peran lain hanya muncul bila ada yang terdaftar.
	 */
	public static function kelompok_absen( $peserta ) {
		$g   = self::group_peran( $peserta );
		$out = array( self::PERAN_SLOT => isset( $g[ self::PERAN_SLOT ] ) ? $g[ self::PERAN_SLOT ] : array() );
		unset( $g[ self::PERAN_SLOT ] );
		foreach ( $g as $peran => $list ) {
			$out[ $peran ] = $list;
		}
		return $out;
	}

	/** Jumlah baris lembar: Peserta = slot kelas (minimal 1 halaman penuh); peran lain = jumlah yang terdaftar. */
	public static function baris_lembar( $peran, $list, $k ) {
		return self::PERAN_SLOT === $peran ? max( self::BARIS_HALAMAN_AWAL, (int) $k->slot, count( $list ) ) : max( 1, count( $list ) );
	}

	/** Lembar absen untuk export: satu lembar per kelas per peran (lihat kelompok_absen). */
	public static function absen_sheets( $data, $peran_filter = '' ) {
		$sheets       = array();
		$peran_filter = trim( (string) $peran_filter );
		$is_filtered  = ( '' !== $peran_filter && 'all' !== strtolower( $peran_filter ) && 'semua' !== strtolower( $peran_filter ) );

		foreach ( $data['kelas'] as $k ) {
			$kelompok = self::kelompok_absen( $k->peserta );

			// Jika peran spesifik difilter, pastikan peran tersebut ada di kelompok lembar kelas ini
			// (meskipun belum ada peserta yang terdaftar, buatkan 1 lembar kosong agar bisa dicetak)
			if ( $is_filtered ) {
				$found = false;
				foreach ( $kelompok as $p_key => $list ) {
					if ( strtolower( (string) $p_key ) === strtolower( $peran_filter ) ) {
						$found = true;
						break;
					}
				}
				if ( ! $found ) {
					$label_peran = ucwords( strtolower( $peran_filter ) );
					$kelompok[ $label_peran ] = array();
				}
			}

			foreach ( $kelompok as $peran => $list ) {
				if ( $is_filtered && strtolower( (string) $peran ) !== strtolower( $peran_filter ) ) {
					continue;
				}

				// Untuk peran yang bukan 'Peserta' tapi dipilih khusus dan datanya kosong,
				// sediakan baris kosong (1 halaman penuh) agar dapat ditandatangani manual di lokasi.
				$jumlah_baris = self::baris_lembar( (string) $peran, $list, $k );
				if ( $is_filtered && self::PERAN_SLOT !== $peran && empty( $list ) ) {
					$jumlah_baris = self::BARIS_HALAMAN_AWAL;
				}

				$sheets[] = array(
					'kelas'        => $k,
					'peran'        => (string) $peran,
					'rows'         => $list,
					'jumlah'       => $jumlah_baris,
					'tampil_kelas' => count( $data['kelas'] ) > 1,
				);
			}
		}

		// Lembar Hadir Panitia Pelatihan (berlaku tingkat kegiatan/pelatihan, tidak terikat per kelas)
		global $wpdb;
		$panitia_rows = isset( $data['panitia'] ) ? $data['panitia'] : array();
		if ( empty( $panitia_rows ) && isset( $data['pelatihan']->id ) ) {
			$tp_pan = KGTK_DB::table( 'peserta' );
			$panitia_rows = $wpdb->get_results(
				$wpdb->prepare( "SELECT id, nik, nama, sekolah, jenis_gtk AS jabatan, hp, nip, nuptk, npwp FROM {$tp_pan} WHERE pelatihan_id = %d AND peran = 'Panitia' ORDER BY id ASC", (int) $data['pelatihan']->id )
			);
		}

		if ( ! empty( $panitia_rows ) || ( $is_filtered && 'panitia' === strtolower( $peran_filter ) ) ) {
			if ( ! $is_filtered || 'panitia' === strtolower( $peran_filter ) ) {
				$dummy_k = (object) array(
					'id'    => 0,
					'nama'  => 'Panitia Pelaksana',
					'label' => 'Panitia Pelaksana',
					'slot'  => 0,
				);
				$sheets[] = array(
					'kelas'        => $dummy_k,
					'peran'        => 'Panitia',
					'rows'         => $panitia_rows,
					'jumlah'       => ! empty( $panitia_rows ) ? max( 1, count( $panitia_rows ) ) : self::BARIS_HALAMAN_AWAL,
					'tampil_kelas' => false,
				);
			}
		}

		return $sheets;
	}

	/** Data lembar absen: pelatihan + kelas, masing-masing dengan array peserta (urut daftar). */
	public static function absen_data( $pelatihan_id ) {
		global $wpdb;
		$pel = self::get_by_id( $pelatihan_id );
		if ( ! $pel ) {
			return null;
		}
		$kelas = self::kelas_stats( $pelatihan_id );
		$p     = KGTK_DB::table( 'peserta' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare( "SELECT kelas_id, nik, nama, sekolah, peran FROM {$p} WHERE pelatihan_id = %d AND peran != 'Panitia' ORDER BY id ASC", $pelatihan_id )
		);
		$by = array();
		foreach ( (array) $rows as $r ) {
			$by[ (int) $r->kelas_id ][] = $r;
		}
		foreach ( $kelas as $k ) {
			$k->peserta = isset( $by[ $k->id ] ) ? $by[ $k->id ] : array();
		}

		$panitia = $wpdb->get_results(
			$wpdb->prepare( "SELECT id, nik, nama, sekolah, jenis_gtk AS jabatan, hp, nip, nuptk, npwp FROM {$p} WHERE pelatihan_id = %d AND peran = 'Panitia' ORDER BY id ASC", $pelatihan_id )
		);

		return array(
			'pelatihan' => $pel,
			'kelas'     => $kelas,
			'panitia'   => $panitia,
		);
	}

	/** Daftar default perlengkapan ATK pengajar / fasilitator. */
	public static function default_atk_items() {
		return array(
			array( 'nama' => 'Spidol Whiteboard Marker', 'jumlah' => '3', 'kuantitas' => 'Buah', 'keterangan' => 'Hitam & Biru' ),
			array( 'nama' => 'Penghapus Whiteboard', 'jumlah' => '1', 'kuantitas' => 'Buah', 'keterangan' => 'Standar' ),
			array( 'nama' => 'Kertas Plano', 'jumlah' => '10', 'kuantitas' => 'Lembar', 'keterangan' => 'Putih' ),
			array( 'nama' => 'Sticky Notes / Post-it', 'jumlah' => '2', 'kuantitas' => 'Pad', 'keterangan' => 'Warna-warni' ),
			array( 'nama' => 'Ballpoint / Pulpen', 'jumlah' => '1', 'kuantitas' => 'Kotak', 'keterangan' => 'Hitam' ),
		);
	}

	/** Ambil daftar ATK untuk pelatihan tertentu. */
	public static function get_atk_items( $pelatihan_id ) {
		$items = get_option( 'kgtk_atk_items_' . (int) $pelatihan_id, null );
		if ( null === $items || ! is_array( $items ) ) {
			return self::default_atk_items();
		}
		return $items;
	}

	/** Simpan daftar ATK untuk pelatihan tertentu. */
	public static function save_atk_items( $pelatihan_id, $items ) {
		if ( ! is_array( $items ) ) {
			$items = array();
		}
		$clean = array();
		foreach ( $items as $it ) {
			$nama = sanitize_text_field( $it['nama'] ?? '' );
			if ( '' === $nama ) {
				continue;
			}
			$clean[] = array(
				'nama'       => $nama,
				'jumlah'     => sanitize_text_field( (string) ( $it['jumlah'] ?? '1' ) ),
				'kuantitas'  => sanitize_text_field( (string) ( $it['kuantitas'] ?? 'Buah' ) ),
				'keterangan' => sanitize_text_field( (string) ( $it['keterangan'] ?? '' ) ),
			);
		}
		update_option( 'kgtk_atk_items_' . (int) $pelatihan_id, $clean, false );
		return $clean;
	}

	/** Kop surat Kemendikdasmen untuk tampilan web dan PDF export. */
	public static function kop_html() {
		$logo_path = defined( 'KGTK_PATH' ) ? KGTK_PATH . 'assets/images/kemendikdasmen.png' : '';
		$logo_src  = '';
		if ( $logo_path && file_exists( $logo_path ) ) {
			$logo_src = 'data:image/png;base64,' . base64_encode( (string) file_get_contents( $logo_path ) );
		} elseif ( defined( 'KGTK_URL' ) ) {
			$logo_src = KGTK_URL . 'assets/images/kemendikdasmen.png';
		}

		$header_path = defined( 'KGTK_PATH' ) ? KGTK_PATH . 'assets/images/header-kemendikdasmen.png' : '';
		$header_src  = '';
		if ( $header_path && file_exists( $header_path ) ) {
			$header_src = 'data:image/png;base64,' . base64_encode( (string) file_get_contents( $header_path ) );
		} elseif ( defined( 'KGTK_URL' ) ) {
			$header_src = KGTK_URL . 'assets/images/header-kemendikdasmen.png';
		}

		$html  = '<div class="kgtk-kop-wrap" style="width:100%;text-align:center;margin:0 auto 16px;">';
		$html .= '<table class="kgtk-kop-tbl" align="center" style="width:100%;max-width:760px;margin:0 auto;border-collapse:collapse;border:0;">';
		$html .= '<tr>';
		$html .= '<td style="width:49%;vertical-align:middle;padding:0 14px 0 0;border:0;text-align:right;">';
		if ( $logo_src ) {
			$html .= '<img src="' . esc_attr( $logo_src ) . '" style="max-height:74px;width:auto;display:inline-block;vertical-align:middle;" alt="Kemendikdasmen" />';
		}
		$html .= '</td>';
		$html .= '<td class="kgtk-kop-line" style="width:1px;max-width:1px;min-width:1px;background-color:#0083C4;padding:0;border:0;font-size:1px;line-height:1px;">&nbsp;</td>';
		$html .= '<td style="width:49%;vertical-align:middle;padding:0 0 0 16px;border:0;text-align:left;">';
		if ( $header_src ) {
			$html .= '<img src="' . esc_attr( $header_src ) . '" style="max-height:80px;width:auto;display:inline-block;vertical-align:middle;" alt="Kementerian Pendidikan Dasar dan Menengah" />';
		}
		$html .= '</td>';
		$html .= '</tr>';
		$html .= '</table>';
		$html .= '</div>';

		return $html;
	}
}
