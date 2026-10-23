<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcode [kgtk_daftar] untuk halaman pendaftaran peserta.
 */
class KGTK_Public {

	public static function init() {
		add_shortcode( 'kgtk_daftar', array( __CLASS__, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_enqueue' ) );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
		add_action( 'wp_head', array( __CLASS__, 'viewport_meta' ), 0 );
	}

	/** Lebar halaman pendaftaran: 'penuh' (layar penuh, default) atau 'tema' (ikuti kolom konten tema). */
	public static function lebar() {
		return 'tema' === get_option( 'kgtk_lebar', 'penuh' ) ? 'tema' : 'penuh';
	}

	/**
	 * Pastikan meta viewport terpasang agar peramban mobile langsung merender tampilan mobile (width=device-width)
	 * dan tidak mengecilkan (zoom-out) halaman ke skala desktop 980px bawaan.
	 */
	public static function viewport_meta() {
		global $post;
		$perlu = false;
		if ( is_singular() && $post instanceof WP_Post && has_shortcode( $post->post_content, 'kgtk_daftar' ) ) {
			$perlu = true;
		} elseif ( ! empty( $GLOBALS['kgtk_has_shortcode'] ) ) {
			$perlu = true;
		}
		if ( $perlu ) {
			echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">' . "\n";
		}
	}

	/** Tambahkan class pada <body> halaman pendaftaran/verifikasi agar CSS bisa menyembunyikan judul tema dan mencegah geser horizontal. */
	public static function body_class( $classes ) {
		global $post;
		if ( 'penuh' === self::lebar() && is_singular() && $post instanceof WP_Post && has_shortcode( $post->post_content, 'kgtk_daftar' ) ) {
			$classes[] = 'kgtk-penuh';
		}
		return $classes;
	}

	public static function maybe_enqueue() {
		global $post;
		if ( is_singular() && $post instanceof WP_Post && has_shortcode( $post->post_content, 'kgtk_daftar' ) ) {
			self::enqueue();
		}
	}

	private static function enqueue() {
		$ver_common_css = file_exists( KGTK_PATH . 'assets/css/common.css' ) ? filemtime( KGTK_PATH . 'assets/css/common.css' ) : KGTK_VERSION;
		$ver_public_css = file_exists( KGTK_PATH . 'assets/css/public.css' ) ? filemtime( KGTK_PATH . 'assets/css/public.css' ) : KGTK_VERSION;
		$ver_sekolah_js = file_exists( KGTK_PATH . 'assets/js/sekolah.js' ) ? filemtime( KGTK_PATH . 'assets/js/sekolah.js' ) : KGTK_VERSION;
		$ver_public_js  = file_exists( KGTK_PATH . 'assets/js/public.js' ) ? filemtime( KGTK_PATH . 'assets/js/public.js' ) : KGTK_VERSION;
		wp_enqueue_style( 'kgtk-common', KGTK_URL . 'assets/css/common.css', array(), $ver_common_css );
		wp_enqueue_style( 'kgtk-public', KGTK_URL . 'assets/css/public.css', array( 'kgtk-common' ), $ver_public_css );
		wp_enqueue_script( 'kgtk-sekolah', KGTK_URL . 'assets/js/sekolah.js', array(), $ver_sekolah_js, true );
		wp_enqueue_script( 'kgtk-public', KGTK_URL . 'assets/js/public.js', array( 'kgtk-sekolah' ), $ver_public_js, true );
		wp_localize_script(
			'kgtk-public',
			'KGTK_PUB',
			array(
				'root'      => esc_url_raw( rest_url( 'kgtk/v1/' ) ),
				'logoUrl'   => esc_url_raw( KGTK_URL . 'assets/images/logo-kemendikdasmen.svg' ),
				'headerUrl' => esc_url_raw( KGTK_URL . 'assets/images/header-kemendikdasmen.png' ),
				'waHelp'          => esc_attr( get_option( 'kgtk_wa_help', '08136798163' ) ),
			)
		);
	}

	public static function shortcode() {
		$GLOBALS['kgtk_has_shortcode'] = true;
		self::enqueue(); // Cadangan bila tema/page builder tidak menjalankan has_shortcode().
		$kelas = 'kgtk-ui kgtk-app' . ( 'penuh' === self::lebar() ? ' kgtk-penuh alignfull' : '' );
		return '<div id="kgtk-app" class="' . esc_attr( $kelas ) . '"><noscript>Halaman pendaftaran memerlukan JavaScript. Aktifkan JavaScript di peramban Anda.</noscript></div>';
	}
}
