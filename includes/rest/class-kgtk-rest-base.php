<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base Controller untuk REST API KGTK.
 * Menyediakan konstanta namespace, helper sanitasi, payload, rate limit, dan permission callback.
 */
abstract class KGTK_REST_Base {

	const NS = 'kgtk/v1';

	/* ====================== Permission Callbacks ====================== */

	public static function public_permission( $req = null ) {
		return true;
	}

	public static function admin_permission( WP_REST_Request $req ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'kgtk_forbidden', 'Anda tidak memiliki akses.', array( 'status' => rest_authorization_required_code() ) );
		}
		if ( ! wp_verify_nonce( (string) $req->get_header( 'X-WP-Nonce' ), 'wp_rest' ) ) {
			return new WP_Error( 'kgtk_nonce', 'Sesi tidak valid. Muat ulang halaman lalu coba lagi.', array( 'status' => 403 ) );
		}
		return true;
	}

	/* ====================== Response & Error Helpers ====================== */

	public static function err( $code, $msg, $status = 400 ) {
		return new WP_Error( 'kgtk_' . $code, $msg, array( 'status' => $status, 'kode' => $code ) );
	}

	public static function respond( $data, $status = 200 ) {
		$res = new WP_REST_Response( $data, $status );
		$res->header( 'Cache-Control', 'no-store' );
		return $res;
	}

	/** Rate limit sederhana per IP memakai transient (jendela tetap sejak hit pertama). */
	public static function rate_limit( $bucket, $max, $window = 600 ) {
		$max = (int) apply_filters( 'kgtk_rate_limit_max', $max, $bucket );
		$key = 'kgtk_rl_' . md5( $bucket . '|' . KGTK_Helpers::client_ip() );
		$d   = get_transient( $key );
		if ( ! is_array( $d ) || $d['exp'] <= time() ) {
			$d = array( 'n' => 0, 'exp' => time() + $window );
		}
		if ( $d['n'] >= $max ) {
			return self::err( 'rate', 'Terlalu banyak permintaan. Coba lagi beberapa menit lagi.', 429 );
		}
		$d['n']++;
		set_transient( $key, $d, max( 1, $d['exp'] - time() ) );
		return true;
	}

	/* ====================== Parameter & Sanitasi Helpers ====================== */

	public static function params( WP_REST_Request $req ) {
		$j = $req->get_json_params();
		return is_array( $j ) ? $j : (array) $req->get_params();
	}

	public static function str( $arr, $key ) {
		return isset( $arr[ $key ] ) && is_scalar( $arr[ $key ] ) ? sanitize_text_field( (string) $arr[ $key ] ) : '';
	}

	public static function mail( $arr, $key ) {
		return isset( $arr[ $key ] ) && is_scalar( $arr[ $key ] ) ? strtolower( trim( sanitize_text_field( (string) $arr[ $key ] ) ) ) : '';
	}

	public static function len( $s ) {
		return function_exists( 'mb_strlen' ) ? mb_strlen( $s, 'UTF-8' ) : strlen( $s );
	}

	/* ====================== Payload Formatters ====================== */

	public static function kelas_payload( $kelas, $p = null ) {
		$out        = array();
		$general_dl = ( is_object( $p ) && ! empty( $p->deadline_pendaftaran ) && '0000-00-00 00:00:00' !== $p->deadline_pendaftaran )
			? (string) $p->deadline_pendaftaran
			: '';
		foreach ( $kelas as $k ) {
			$has_own_dl = ! empty( $k->deadline_pendaftaran ) && '0000-00-00 00:00:00' !== $k->deadline_pendaftaran;
			$eff_dl     = $has_own_dl ? (string) $k->deadline_pendaftaran : $general_dl;
			$out[]      = array(
				'id'                   => $k->id,
				'nama'                 => $k->nama,
				'slot'                 => $k->slot,
				'terisi'               => $k->terisi,
				'sisa'                 => $k->sisa,
				'label'                => $k->label,
				'tanggal_mulai'        => isset( $k->tanggal_mulai ) ? $k->tanggal_mulai : '',
				'tanggal_selesai'      => isset( $k->tanggal_selesai ) ? $k->tanggal_selesai : '',
				'tanggal'              => isset( $k->tanggal_label ) ? $k->tanggal_label : '',
				'deadline_pendaftaran' => $has_own_dl ? (string) $k->deadline_pendaftaran : '',
				'deadline_formatted'   => $has_own_dl ? KGTK_Helpers::format_deadline( $k->deadline_pendaftaran ) : '',
				'is_deadline_passed'   => '' !== $eff_dl ? KGTK_Helpers::is_deadline_passed( $eff_dl ) : false,
			);
		}
		return $out;
	}

	public static function pelatihan_payload( $p, $kelas ) {
		$t = KGTK_Helpers::totals( $kelas );
		return array(
			'nama'                 => $p->nama,
			'penyelenggara'        => $p->penyelenggara,
			'tanggal'              => KGTK_Helpers::rentang_tanggal( $p->tanggal_mulai, $p->tanggal_selesai ),
			'lokasi'               => $p->lokasi,
			'pelatihan_kabkota'    => isset( $p->kabkota ) ? $p->kabkota : '',
			'keterangan'           => (string) $p->keterangan,
			'kode'                 => $p->kode,
			'kelas'                => self::kelas_payload( $kelas, $p ),
			'kabkota'              => KGTK_Helpers::kabkota_list(),
			'penuh'                => $t['penuh'],
			'deadline_pendaftaran' => ! empty( $p->deadline_pendaftaran ) ? $p->deadline_pendaftaran : null,
			'deadline_formatted'   => ! empty( $p->deadline_pendaftaran ) ? KGTK_Helpers::format_deadline( $p->deadline_pendaftaran ) : '',
			'is_deadline_passed'   => KGTK_Helpers::is_deadline_passed( $p, $kelas ),
		);
	}

	public static function hasil( $p, $nama, $sekolah, $peran, $kelas_nama, $slot, $nomor, $jenis_gtk = '', $kelas_tgl_mulai = '', $kelas_tgl_selesai = '' ) {
		$tgl_m = ( ! empty( $kelas_tgl_mulai ) && '0000-00-00' !== $kelas_tgl_mulai ) ? $kelas_tgl_mulai : $p->tanggal_mulai;
		$tgl_s = ( ! empty( $kelas_tgl_selesai ) && '0000-00-00' !== $kelas_tgl_selesai ) ? $kelas_tgl_selesai : ( ( ! empty( $kelas_tgl_mulai ) && '0000-00-00' !== $kelas_tgl_mulai ) ? $kelas_tgl_mulai : $p->tanggal_selesai );
		return array(
			'nama'        => $nama,
			'sekolah'     => $sekolah,
			'peran'       => $peran,
			'jenis_gtk'   => $jenis_gtk,
			'pelatihan'   => $p->nama,
			'kelas'       => $kelas_nama,
			'kelas_label' => KGTK_Helpers::label_kelas( $kelas_nama ),
			'tanggal'     => KGTK_Helpers::rentang_tanggal( $tgl_m, $tgl_s ),
			'lokasi'      => $p->lokasi,
			'kabkota'     => isset( $p->kabkota ) ? $p->kabkota : '',
			'nomor'       => (int) $nomor,
			'slot'        => (int) $slot,
		);
	}

	/* ====================== Database Lookup Helpers ====================== */

	public static function find_pelatihan( $raw_kode ) {
		$kode = KGTK_Helpers::normalize_kode( $raw_kode );
		if ( ! KGTK_Helpers::valid_kode( $kode ) ) {
			return null;
		}
		return KGTK_Helpers::get_by_kode( $kode );
	}

	/** Data pendaftaran NIK pada pelatihan (atau null). */
	public static function find_registrasi( $pelatihan_id, $nik ) {
		global $wpdb;
		$ps = KGTK_DB::table( 'peserta' );
		$k  = KGTK_DB::table( 'kelas' );
		$r  = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT ps.id, ps.kelas_id, ps.nama, ps.sekolah, ps.peran, ps.jenis_gtk, COALESCE(k.nama, '') AS kelas_nama, COALESCE(k.slot, 0) AS slot,
				        k.tanggal_mulai AS kelas_tanggal_mulai, k.tanggal_selesai AS kelas_tanggal_selesai
				 FROM {$ps} ps LEFT JOIN {$k} k ON k.id = ps.kelas_id
				 WHERE ps.pelatihan_id = %d AND ps.nik = %s",
				$pelatihan_id,
				$nik
			)
		);
		if ( ! $r ) {
			return null;
		}
		if ( (int) $r->kelas_id > 0 ) {
			$r->nomor = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$ps} WHERE kelas_id = %d AND peran = %s AND id <= %d", $r->kelas_id, $r->peran, $r->id )
			);
		} else {
			$r->nomor = 0;
		}
		return $r;
	}
}
