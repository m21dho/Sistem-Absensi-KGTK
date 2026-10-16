<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fungsi bantu yang dipakai bersama oleh REST, admin, dan export.
 */
class KGTK_Helpers {

	/** Hanya peran ini yang memakai slot kelas; peran lain tidak dibatasi. */
	const PERAN_SLOT        = 'Peserta';
	const BARIS_PER_HALAMAN = 15;
	/** Nilai kabupaten/kota untuk guru dari luar daftar sekolah. */
	const KABKOTA_LAIN      = 'Lainnya';
	const PERAN = array( 'Fasilitator', 'Pengajar', 'Narasumber', 'Penceramah', 'Pemateri', 'Peserta' );
	const JENIS_GTK = array( 'Guru', 'Tenaga Didik', 'Kepala Sekolah', 'Pengawas Sekolah' );
	const JABATAN = array( 'Guru', 'Tenaga Didik', 'Kepala Sekolah', 'Pengawas Sekolah' );
	const PENDIDIKAN = array( 'SMA/SMK Sederajat', 'D3', 'D4 / S1', 'S2', 'S3' );
	const JENIS_KELAMIN = array( 'Laki-laki', 'Perempuan' );

	/* ---------- Format ---------- */

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
				"SELECT ps.*, COALESCE(k.nama, '') AS kelas_nama, COALESCE(k.slot, 0) AS kelas_slot
				 FROM {$tp} ps
				 LEFT JOIN {$tk} k ON k.id = ps.kelas_id
				 WHERE ps.pelatihan_id = %d
				 ORDER BY ps.created_at DESC, ps.id DESC",
				$pelatihan_id
			)
		);
	}

	/** Hitung peserta yang belum ditentukan kelasnya (kelas_id = 0). */
	public static function unassigned_count( $pelatihan_id ) {
		global $wpdb;
		$tp = KGTK_DB::table( 'peserta' );
		return (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$tp} WHERE pelatihan_id = %d AND kelas_id = 0", $pelatihan_id )
		);
	}

	/** Peta pelatihan_id => daftar kelas (id, nama, slot, terisi). */
	public static function all_kelas_stats( $only_id = 0 ) {
		global $wpdb;
		$k = KGTK_DB::table( 'kelas' );
		$p = KGTK_DB::table( 'peserta' );

		$where = $only_id ? $wpdb->prepare( 'WHERE k.pelatihan_id = %d', $only_id ) : '';
		$rows  = $wpdb->get_results(
			"SELECT k.id, k.pelatihan_id, k.nama, k.slot, k.urutan, SUM(CASE WHEN p.peran = 'Peserta' THEN 1 ELSE 0 END) AS terisi, SUM(CASE WHEN p.peran <> 'Peserta' THEN 1 ELSE 0 END) AS lain
			 FROM {$k} k LEFT JOIN {$p} p ON p.kelas_id = k.id
			 {$where}
			 GROUP BY k.id, k.pelatihan_id, k.nama, k.slot, k.urutan
			 ORDER BY k.pelatihan_id, k.urutan, k.id"
		);

		$map = array();
		foreach ( (array) $rows as $r ) {
			$r->id           = (int) $r->id;
			$r->pelatihan_id = (int) $r->pelatihan_id;
			$r->slot         = (int) $r->slot;
			$r->terisi       = (int) $r->terisi;
			$r->lain         = (int) $r->lain;
			$r->sisa         = max( 0, $r->slot - $r->terisi );
			$r->label        = self::label_kelas( $r->nama );
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
	 * Bagi baris absen menjadi halaman berisi maksimal 15 baris. Jumlah baris total = $total
	 * (slot kelas), atau jumlah peserta bila lebih banyak. Baris yang belum terisi dibiarkan kosong.
	 * Tiap halaman: array( 'rows' => peserta, 'mulai' => indeks baris pertama (0), 'jumlah' => baris di halaman ini ).
	 */
	public static function halaman( $rows, $total = 0 ) {
		$rows  = array_values( (array) $rows );
		$total = max( 1, (int) $total, count( $rows ) );
		$per   = self::BARIS_PER_HALAMAN;
		$pages = array();
		for ( $mulai = 0; $mulai < $total; $mulai += $per ) {
			$pages[] = array(
				'rows'   => array_slice( $rows, $mulai, $per ),
				'mulai'  => $mulai,
				'jumlah' => min( $per, $total - $mulai ),
			);
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
	public static function baris_info_array( $p, $tanggal_khusus = '' ) {
		$arr = array();
		if ( ! empty( $p->nama ) ) {
			$arr[] = (string) $p->nama;
		}
		$tanggal_khusus = trim( (string) $tanggal_khusus );
		$is_custom_day  = ( '' !== $tanggal_khusus && 'semua' !== strtolower( $tanggal_khusus ) && 'all' !== strtolower( $tanggal_khusus ) && 'all_days' !== strtolower( $tanggal_khusus ) && 'range' !== strtolower( $tanggal_khusus ) );
		if ( $is_custom_day ) {
			$arr[] = self::format_hari_tanggal( $tanggal_khusus );
		} else {
			$tgl = self::rentang_tanggal( $p->tanggal_mulai, $p->tanggal_selesai );
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
	public static function baris_info( $p, $tanggal_khusus = '' ) {
		return implode( ', ', self::baris_info_array( $p, $tanggal_khusus ) );
	}

	/** Output HTML 3 baris info kegiatan terpisah di tengah halaman. */
	public static function baris_info_html( $p, $tanggal_khusus = '' ) {
		$tgl_default    = self::rentang_tanggal( $p->tanggal_mulai, $p->tanggal_selesai );
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

	/** Jumlah baris lembar: Peserta = slot kelas; peran lain = jumlah yang terdaftar. */
	public static function baris_lembar( $peran, $list, $k ) {
		return self::PERAN_SLOT === $peran ? max( 1, (int) $k->slot ) : max( 1, count( $list ) );
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
				// sediakan 15 baris kosong (1 halaman penuh) agar dapat ditandatangani manual di lokasi.
				$jumlah_baris = self::baris_lembar( (string) $peran, $list, $k );
				if ( $is_filtered && self::PERAN_SLOT !== $peran && empty( $list ) ) {
					$jumlah_baris = self::BARIS_PER_HALAMAN;
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
			$wpdb->prepare( "SELECT kelas_id, nik, nama, sekolah, peran FROM {$p} WHERE pelatihan_id = %d ORDER BY id ASC", $pelatihan_id )
		);
		$by = array();
		foreach ( (array) $rows as $r ) {
			$by[ (int) $r->kelas_id ][] = $r;
		}
		foreach ( $kelas as $k ) {
			$k->peserta = isset( $by[ $k->id ] ) ? $by[ $k->id ] : array();
		}
		return array(
			'pelatihan' => $pel,
			'kelas'     => $kelas,
		);
	}

	/** Kop surat Kemendikdasmen untuk tampilan web dan PDF export. */
	public static function kop_html() {
		$logo_path = defined( 'KGTK_PATH' ) ? KGTK_PATH . 'assets/images/logo-kemendikdasmen.svg' : '';
		$logo_src  = '';
		if ( $logo_path && file_exists( $logo_path ) ) {
			$logo_src = 'data:image/svg+xml;base64,' . base64_encode( (string) file_get_contents( $logo_path ) );
		} elseif ( defined( 'KGTK_URL' ) ) {
			$logo_src = KGTK_URL . 'assets/images/logo-kemendikdasmen.svg';
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
		$html .= '<td style="width:2.5px;background-color:#0083C4;padding:0;border:0;font-size:1px;">&nbsp;</td>';
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
