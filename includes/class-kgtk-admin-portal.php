<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Portal Admin Mandiri (Standalone Frontend Admin) untuk KGTK Pelatihan.
 *
 * Mengizinkan administrator mengelola seluruh data pelatihan, peserta,
 * absensi, rekap, dan pengaturan langsung dari URL frontend (/admin-pelatihan/)
 * tanpa harus membuka dashboard WordPress (/wp-admin/).
 */
class KGTK_Admin_Portal {

	/**
	 * Pesan error login bila terjadi kesalahan pada proses POST login.
	 *
	 * @var string
	 */
	private static $login_error = '';

	/**
	 * Pesan sukses atau notifikasi (mis. setelah logout).
	 *
	 * @var string
	 */
	private static $login_notice = '';

	/**
	 * Inisialisasi hook portal admin mandiri.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_rewrites' ) );
		add_filter( 'query_vars', array( __CLASS__, 'register_query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'handle_portal_request' ), 1 );

		// AJAX handler untuk login mandiri
		add_action( 'wp_ajax_nopriv_kgtk_portal_login', array( __CLASS__, 'handle_ajax_login' ) );
		add_action( 'wp_ajax_kgtk_portal_login', array( __CLASS__, 'handle_ajax_login' ) );

		// Shortcode bila user ingin menaruh portal di page tertentu
		add_shortcode( 'kgtk_admin_portal', array( __CLASS__, 'render_shortcode' ) );

		// Matikan admin bar WordPress secara dinamis saat di dalam portal mandiri
		add_filter(
			'show_admin_bar',
			function ( $show ) {
				return self::is_portal_request() ? false : $show;
			}
		);
	}

	/**
	 * Daftarkan rewrite rule untuk URL cantik /admin-pelatihan/.
	 */
	public static function register_rewrites() {
		add_rewrite_tag( '%kgtk_admin_portal%', '([^&]+)' );
		add_rewrite_rule( '^admin-pelatihan/?$', 'index.php?kgtk_admin_portal=1', 'top' );
	}

	/**
	 * Daftarkan query var kgtk_admin_portal ke WordPress.
	 *
	 * @param array $vars Daftar query vars.
	 * @return array
	 */
	public static function register_query_vars( $vars ) {
		$vars[] = 'kgtk_admin_portal';
		return $vars;
	}

	/**
	 * Deteksi apakah permintaan saat ini menuju ke Portal Admin mandiri.
	 *
	 * @return bool
	 */
	public static function is_portal_request() {
		// 1. Cek query parameter langsung (?kgtk_admin_portal=1 atau ?kgtk_portal=admin)
		if ( isset( $_GET['kgtk_admin_portal'] ) || ( isset( $_GET['kgtk_portal'] ) && 'admin' === $_GET['kgtk_portal'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return true;
		}

		// 2. Cek URI path langsung untuk memastikan tetap bekerja meski rewrite belum di-flush
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$path = trim( (string) wp_parse_url( $uri, PHP_URL_PATH ), '/' );
		if ( 'admin-pelatihan' === $path || 0 === strpos( $path, 'admin-pelatihan/' ) ) {
			return true;
		}

		// 3. Cek query var resmi WordPress hanya jika $wp_query sudah diinisialisasi
		global $wp_query;
		if ( isset( $wp_query ) && is_object( $wp_query ) && (int) get_query_var( 'kgtk_admin_portal' ) === 1 ) {
			return true;
		}

		// 4. Jika sedang berada di admin-post.php (misalnya saat upload atau export), cek referer
		if ( is_admin() && isset( $_SERVER['SCRIPT_NAME'] ) && false !== strpos( $_SERVER['SCRIPT_NAME'], 'admin-post.php' ) ) {
			$ref = wp_get_raw_referer();
			if ( $ref && ( false !== strpos( $ref, 'admin-pelatihan' ) || false !== strpos( $ref, 'kgtk_admin_portal=1' ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Buat URL halaman di dalam Portal Admin mandiri.
	 *
	 * @param string $page   Slug halaman (mis. 'pelatihan', 'buat', 'rekap-guru', 'rekap', 'sekolah', 'pengaturan').
	 * @param array  $params Query parameter tambahan (mis. array('view' => 'absen', 'id' => 12)).
	 * @return string
	 */
	public static function portal_url( $page = 'pelatihan', $params = array() ) {
		$using_permalinks = (bool) get_option( 'permalink_structure' );
		$base = $using_permalinks ? home_url( '/admin-pelatihan/' ) : add_query_arg( 'kgtk_admin_portal', '1', home_url( '/' ) );

		$slug = ( '' === $page || 'pelatihan' === $page || 'kgtk-pelatihan' === $page )
			? ''
			: ( 0 === strpos( $page, 'kgtk-' ) ? $page : 'kgtk-' . $page );

		$args = array();
		if ( ! empty( $slug ) ) {
			$args['page'] = $slug;
		}
		if ( ! empty( $params ) && is_array( $params ) ) {
			$args = array_merge( $args, $params );
		}

		return ! empty( $args ) ? add_query_arg( $args, $base ) : $base;
	}

	/**
	 * Intersep permintaan template_redirect untuk memproses portal.
	 */
	public static function handle_portal_request() {
		if ( ! self::is_portal_request() ) {
			return;
		}

		// 1. Tangani Aksi Logout
		if ( isset( $_GET['kgtk_action'] ) && 'portal_logout' === $_GET['kgtk_action'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
			if ( wp_verify_nonce( $nonce, 'kgtk_portal_logout' ) ) {
				wp_logout();
				wp_safe_redirect( add_query_arg( 'msg', 'logged_out', self::portal_url() ) );
				exit;
			}
		}

		// 2. Tangani Notifikasi dari GET
		if ( isset( $_GET['msg'] ) && 'logged_out' === $_GET['msg'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			self::$login_notice = 'Anda telah berhasil keluar dari Portal Admin.';
		}

		// 3. Tangani Form Login Tradisional (Fallback tanpa AJAX)
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['_kgtk_portal_login_submit'] ) ) {
			$nonce = isset( $_POST['_kgtk_portal_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_kgtk_portal_nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'kgtk_portal_login_action' ) ) {
				self::$login_error = 'Sesi keamanan kedaluwarsa. Silakan muat ulang halaman.';
			} else {
				$username = isset( $_POST['log'] ) ? sanitize_text_field( wp_unslash( $_POST['log'] ) ) : '';
				$password = isset( $_POST['pwd'] ) ? (string) $_POST['pwd'] : '';
				$remember = ! empty( $_POST['remember'] );

				$user = wp_signon(
					array(
						'user_login'    => $username,
						'user_password' => $password,
						'remember'      => $remember,
					),
					is_ssl()
				);

				if ( is_wp_error( $user ) ) {
					self::$login_error = 'Nama pengguna atau kata sandi tidak cocok. Silakan coba lagi.';
				} elseif ( ! user_can( $user, 'manage_options' ) ) {
					wp_logout();
					self::$login_error = 'Akun Anda berhasil masuk tetapi tidak memiliki hak akses Administrator (manage_options).';
				} else {
					$redirect = isset( $_POST['redirect_to'] ) && '' !== $_POST['redirect_to']
						? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) )
						: self::portal_url();
					wp_safe_redirect( $redirect );
					exit;
				}
			}
		}

		// 4. Verifikasi Autentikasi Pengguna
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			self::render_login_page();
			exit;
		}

		// 5. Pengguna adalah Administrator terautentikasi -> Render Dashboard Portal
		self::render_portal_dashboard();
		exit;
	}

	/**
	 * AJAX Handler untuk proses login yang responsif tanpa reload halaman.
	 */
	public static function handle_ajax_login() {
		check_ajax_referer( 'kgtk_portal_login_action', 'nonce' );

		$username = isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : '';
		$password = isset( $_POST['password'] ) ? (string) $_POST['password'] : '';
		$remember = ! empty( $_POST['remember'] );

		if ( '' === $username || '' === $password ) {
			wp_send_json_error( array( 'message' => 'Silakan isi nama pengguna dan kata sandi.' ) );
		}

		$user = wp_signon(
			array(
				'user_login'    => $username,
				'user_password' => $password,
				'remember'      => $remember,
			),
			is_ssl()
		);

		if ( is_wp_error( $user ) ) {
			wp_send_json_error( array( 'message' => 'Nama pengguna atau kata sandi salah. Silakan coba lagi.' ) );
		}

		if ( ! user_can( $user, 'manage_options' ) ) {
			wp_logout();
			wp_send_json_error( array( 'message' => 'Akun Anda tidak memiliki izin Administrator (manage_options).' ) );
		}

		$redirect = isset( $_POST['redirect_to'] ) && '' !== $_POST['redirect_to']
			? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) )
			: self::portal_url();

		wp_send_json_success(
			array(
				'message'  => 'Login berhasil! Mengalihkan ke dashboard...',
				'redirect' => $redirect,
			)
		);
	}

	/**
	 * Render halaman login khusus portal mandiri dengan desain modern Kemendikdasmen.
	 */
	public static function render_login_page() {
		$logo_url    = KGTK_URL . 'assets/images/logo-kemendikdasmen.svg';
		$current_url = self::portal_url();
		$nonce       = wp_create_nonce( 'kgtk_portal_login_action' );
		$ajax_url    = admin_url( 'admin-ajax.php' );
		$home_url    = home_url( '/' );
		$site_title  = get_bloginfo( 'name' );
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>Masuk Portal Admin Pelatihan - <?php echo esc_html( $site_title ); ?></title>
			<link rel="preconnect" href="https://fonts.googleapis.com">
			<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
			<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
			<link rel="stylesheet" href="<?php echo esc_url( KGTK_URL . 'assets/css/common.css?v=' . KGTK_VERSION ); ?>">
			<link rel="stylesheet" href="<?php echo esc_url( KGTK_URL . 'assets/css/admin.css?v=' . KGTK_VERSION ); ?>">
		</head>
		<body class="kgtk-portal-body">
			<div class="kgtk-portal-login-screen">
				<!-- Decorative Glowing Ambient -->
				<div class="kgtk-login-orb kgtk-login-orb--1" aria-hidden="true"></div>
				<div class="kgtk-login-orb kgtk-login-orb--2" aria-hidden="true"></div>

				<div class="kgtk-login-card">
					<div class="kgtk-login-brand">
						<img src="<?php echo esc_url( $logo_url ); ?>" alt="Kemendikdasmen" class="kgtk-login-logo">
						<h1 class="kgtk-login-title">Portal Admin Pelatihan</h1>
						<p class="kgtk-login-subtitle">Kementerian Pendidikan Dasar dan Menengah</p>
					</div>

					<div id="kgtk-login-alert" class="kgtk-login-alert <?php echo self::$login_error ? 'is-active' : ''; ?>">
						<?php echo esc_html( self::$login_error ); ?>
					</div>

					<?php if ( self::$login_notice ) : ?>
						<div class="kgtk-login-notice is-active">
							<?php echo esc_html( self::$login_notice ); ?>
						</div>
					<?php endif; ?>

					<form id="kgtk-portal-login-form" method="post" action="<?php echo esc_url( $current_url ); ?>" class="kgtk-login-form" novalidate>
						<input type="hidden" name="_kgtk_portal_login_submit" value="1">
						<input type="hidden" name="_kgtk_portal_nonce" value="<?php echo esc_attr( $nonce ); ?>">
						<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $current_url ); ?>">

						<div class="kgtk-login-group">
							<label for="kgtk-login-user" class="kgtk-login-label">Nama Pengguna / Email</label>
							<div class="kgtk-login-input-wrap">
								<span class="kgtk-login-input-icon" aria-hidden="true">
									<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
								</span>
								<input type="text" id="kgtk-login-user" name="log" class="kgtk-login-input" placeholder="Masukkan nama pengguna / email" required autofocus autocomplete="username">
							</div>
						</div>

						<div class="kgtk-login-group">
							<div class="kgtk-login-label-row">
								<label for="kgtk-login-pwd" class="kgtk-login-label">Kata Sandi</label>
							</div>
							<div class="kgtk-login-input-wrap">
								<span class="kgtk-login-input-icon" aria-hidden="true">
									<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
								</span>
								<input type="password" id="kgtk-login-pwd" name="pwd" class="kgtk-login-input" placeholder="Masukkan kata sandi" required autocomplete="current-password">
								<button type="button" class="kgtk-pwd-toggle" aria-label="Tampilkan kata sandi" title="Tampilkan kata sandi">
									<svg class="kgtk-icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
									<svg class="kgtk-icon-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
								</button>
							</div>
						</div>

						<div class="kgtk-login-options">
							<label class="kgtk-login-remember">
								<input type="checkbox" name="remember" value="1" checked>
								<span>Ingat saya</span>
							</label>
						</div>

						<button type="submit" id="kgtk-login-btn" class="kgtk-login-btn">
							<span class="kgtk-btn-text">Masuk ke Portal Admin</span>
							<span class="kgtk-btn-spinner" aria-hidden="true" style="display:none;"></span>
						</button>
					</form>

					<div class="kgtk-login-footer">
						<div class="kgtk-security-badge">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
							<span>Khusus Administrator &amp; Pengelola Kegiatan</span>
						</div>
						<a href="<?php echo esc_url( $home_url ); ?>" class="kgtk-login-back">&larr; Kembali ke Beranda Situs</a>
					</div>
				</div>
			</div>

			<script>
			(function() {
				var form = document.getElementById('kgtk-portal-login-form');
				var alertBox = document.getElementById('kgtk-login-alert');
				var btn = document.getElementById('kgtk-login-btn');
				var btnText = btn.querySelector('.kgtk-btn-text');
				var btnSpinner = btn.querySelector('.kgtk-btn-spinner');
				var pwdInput = document.getElementById('kgtk-login-pwd');
				var toggleBtn = document.querySelector('.kgtk-pwd-toggle');
				var eyeOn = document.querySelector('.kgtk-icon-eye');
				var eyeOff = document.querySelector('.kgtk-icon-eye-off');

				if (toggleBtn && pwdInput) {
					toggleBtn.addEventListener('click', function(e) {
						e.preventDefault();
						if (pwdInput.type === 'password') {
							pwdInput.type = 'text';
							eyeOn.style.display = 'none';
							eyeOff.style.display = 'block';
						} else {
							pwdInput.type = 'password';
							eyeOn.style.display = 'block';
							eyeOff.style.display = 'none';
						}
					});
				}

				if (form) {
					form.addEventListener('submit', function(e) {
						e.preventDefault();
						var user = document.getElementById('kgtk-login-user').value.trim();
						var pwd = pwdInput.value;
						var remember = form.querySelector('input[name="remember"]').checked ? 1 : 0;

						if (!user || !pwd) {
							showAlert('Silakan lengkapi nama pengguna dan kata sandi.');
							return;
						}

						setLoading(true);
						hideAlert();

						var fd = new FormData();
						fd.append('action', 'kgtk_portal_login');
						fd.append('nonce', '<?php echo esc_js( $nonce ); ?>');
						fd.append('username', user);
						fd.append('password', pwd);
						fd.append('remember', remember);
						fd.append('redirect_to', '<?php echo esc_js( $current_url ); ?>');

						fetch('<?php echo esc_url( $ajax_url ); ?>', {
							method: 'POST',
							body: fd,
							credentials: 'same-origin'
						})
						.then(function(res) { return res.json(); })
						.then(function(data) {
							if (data.success) {
								btnText.textContent = 'Berhasil! Mengalihkan...';
								window.location.href = data.data && data.data.redirect ? data.data.redirect : '<?php echo esc_js( $current_url ); ?>';
							} else {
								setLoading(false);
								showAlert(data.data && data.data.message ? data.data.message : 'Login gagal. Silakan coba lagi.');
							}
						})
						.catch(function(err) {
							// Fallback ke submit form biasa jika AJAX terhalang
							form.submit();
						});
					});
				}

				function setLoading(isLoading) {
					if (isLoading) {
						btn.disabled = true;
						btnText.style.display = 'none';
						btnSpinner.style.display = 'inline-block';
					} else {
						btn.disabled = false;
						btnText.style.display = 'inline-block';
						btnSpinner.style.display = 'none';
					}
				}

				function showAlert(msg) {
					alertBox.textContent = msg;
					alertBox.classList.add('is-active');
				}

				function hideAlert() {
					alertBox.textContent = '';
					alertBox.classList.remove('is-active');
				}
			})();
			</script>
		</body>
		</html>
		<?php
	}

	/**
	 * Render halaman Dashboard Portal Mandiri untuk Administrator.
	 */
	public static function render_portal_dashboard() {
		$current_user = wp_get_current_user();
		$user_display = $current_user->display_name ? $current_user->display_name : $current_user->user_login;
		$user_initial = strtoupper( substr( $user_display, 0, 1 ) );

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'kgtk-pelatihan'; // phpcs:ignore WordPress.Security.NonceVerification
		$view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'list'; // phpcs:ignore WordPress.Security.NonceVerification

		$logo_url    = KGTK_URL . 'assets/images/logo-kemendikdasmen.svg';
		$public_url  = KGTK_Helpers::public_url();
		$logout_url  = wp_nonce_url( add_query_arg( 'kgtk_action', 'portal_logout', self::portal_url() ), 'kgtk_portal_logout' );

		// Data navigasi
		$nav_items = array(
			array(
				'slug'   => 'kgtk-pelatihan',
				'title'  => 'Pelatihan',
				'icon'   => '&#127891;',
				'url'    => self::portal_url( 'pelatihan' ),
				'active' => ( 'kgtk-pelatihan' === $page ),
			),
			array(
				'slug'   => 'kgtk-buat',
				'title'  => '+ Buat Pelatihan',
				'icon'   => '&#10133;',
				'url'    => self::portal_url( 'buat' ),
				'active' => ( 'kgtk-buat' === $page ),
			),
			array(
				'slug'   => 'kgtk-panitia',
				'title'  => 'Data Panitia',
				'icon'   => '&#128100;',
				'url'    => self::portal_url( 'panitia' ),
				'active' => ( 'kgtk-panitia' === $page ),
			),
			array(
				'slug'   => 'kgtk-rekap-guru',
				'title'  => 'Rekap Guru',
				'icon'   => '&#128101;',
				'url'    => self::portal_url( 'rekap-guru' ),
				'active' => ( 'kgtk-rekap-guru' === $page ),
			),
			array(
				'slug'   => 'kgtk-rekap',
				'title'  => 'Rekap Sekolah',
				'icon'   => '&#127979;',
				'url'    => self::portal_url( 'rekap' ),
				'active' => ( 'kgtk-rekap' === $page ),
			),
			array(
				'slug'   => 'kgtk-sekolah',
				'title'  => 'Data Sekolah',
				'icon'   => '&#128193;',
				'url'    => self::portal_url( 'sekolah' ),
				'active' => ( 'kgtk-sekolah' === $page ),
			),
			array(
				'slug'   => 'kgtk-pengaturan',
				'title'  => 'Pengaturan',
				'icon'   => '&#9881;&#65039;',
				'url'    => self::portal_url( 'pengaturan' ),
				'active' => ( 'kgtk-pengaturan' === $page ),
			),
		);
		$ver_admin_css  = file_exists( KGTK_PATH . 'assets/css/admin.css' ) ? filemtime( KGTK_PATH . 'assets/css/admin.css' ) : KGTK_VERSION;
		$ver_admin_js   = file_exists( KGTK_PATH . 'assets/js/admin.js' ) ? filemtime( KGTK_PATH . 'assets/js/admin.js' ) : KGTK_VERSION;
		$ver_common_css = file_exists( KGTK_PATH . 'assets/css/common.css' ) ? filemtime( KGTK_PATH . 'assets/css/common.css' ) : KGTK_VERSION;
		$ver_sekolah_js = file_exists( KGTK_PATH . 'assets/js/sekolah.js' ) ? filemtime( KGTK_PATH . 'assets/js/sekolah.js' ) : KGTK_VERSION;
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title>Portal Admin Pelatihan GTK - Kementerian Pendidikan Dasar dan Menengah</title>
			<link rel="preconnect" href="https://fonts.googleapis.com">
			<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
			<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
			<link rel="stylesheet" href="<?php echo esc_url( KGTK_URL . 'assets/css/common.css?v=' . $ver_common_css ); ?>">
			<link rel="stylesheet" href="<?php echo esc_url( KGTK_URL . 'assets/css/admin.css?v=' . $ver_admin_css ); ?>">
		</head>
		<body class="kgtk-portal-body">
			<!-- Header Portal Admin Mandiri -->
			<header class="kgtk-portal-header">
				<!-- Baris 1: Brand & Profil Pengguna -->
				<div class="kgtk-portal-topbar">
					<div class="kgtk-portal-topbar-inner">
						<a href="<?php echo esc_url( self::portal_url() ); ?>" class="kgtk-portal-brand" title="Ke Dashboard Pelatihan">
							<img src="<?php echo esc_url( $logo_url ); ?>" alt="Logo Kemendikdasmen" class="kgtk-portal-logo">
							<div class="kgtk-portal-brand-text">
								<span class="kgtk-portal-brand-title">Pelatihan GTK</span>
								<span class="kgtk-portal-brand-sub">Portal Pengelola</span>
							</div>
						</a>

						<div class="kgtk-portal-topbar-right">
							<a href="<?php echo esc_url( $public_url ); ?>" target="_blank" rel="noopener noreferrer" class="kgtk-portal-btn-public" title="Buka form pendaftaran peserta di tab baru">
								<span>Form Peserta</span>
								<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
							</a>

							<div class="kgtk-portal-user-badge">
								<div class="kgtk-portal-avatar" title="<?php echo esc_attr( $user_display ); ?>">
									<?php echo esc_html( $user_initial ); ?>
								</div>
								<div class="kgtk-portal-user-info">
									<span class="kgtk-portal-username"><?php echo esc_html( $user_display ); ?></span>
									<span class="kgtk-portal-role">Administrator</span>
								</div>
							</div>

							<a href="<?php echo esc_url( $logout_url ); ?>" class="kgtk-portal-logout-btn" title="Keluar dari Portal Admin">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
								<span>Keluar</span>
							</a>

							<!-- Tombol Hamburger Mobile -->
							<button type="button" class="kgtk-portal-hamburger" aria-label="Buka navigasi mobile" aria-expanded="false">
								<span></span>
								<span></span>
								<span></span>
							</button>
						</div>
					</div>
				</div>

				<!-- Baris 2: Tab Navigasi Utama -->
				<nav class="kgtk-portal-subnav" aria-label="Menu Utama">
					<div class="kgtk-portal-subnav-inner">
						<ul class="kgtk-portal-menu">
							<?php foreach ( $nav_items as $item ) : ?>
								<li>
									<a href="<?php echo esc_url( $item['url'] ); ?>" class="kgtk-portal-nav-link <?php echo $item['active'] ? 'is-active' : ''; ?>">
										<span class="kgtk-nav-icon" aria-hidden="true"><?php echo $item['icon']; ?></span>
										<span><?php echo esc_html( $item['title'] ); ?></span>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				</nav>

				<!-- Menu Mobile Dropdown -->
				<div class="kgtk-portal-mobile-drawer">
					<ul class="kgtk-portal-mobile-menu">
						<?php foreach ( $nav_items as $item ) : ?>
							<li>
								<a href="<?php echo esc_url( $item['url'] ); ?>" class="kgtk-portal-mobile-link <?php echo $item['active'] ? 'is-active' : ''; ?>">
									<span class="kgtk-nav-icon" aria-hidden="true"><?php echo $item['icon']; ?></span>
									<span><?php echo esc_html( $item['title'] ); ?></span>
								</a>
							</li>
						<?php endforeach; ?>
						<li class="kgtk-mobile-divider"></li>
						<li>
							<a href="<?php echo esc_url( $public_url ); ?>" target="_blank" rel="noopener noreferrer" class="kgtk-portal-mobile-link">
								<span>&#128279; Buka Form Peserta</span>
							</a>
						</li>
						<li>
							<a href="<?php echo esc_url( $logout_url ); ?>" class="kgtk-portal-mobile-link is-logout">
								<span>&#128682; Keluar</span>
							</a>
						</li>
					</ul>
				</div>
			</header>

			<!-- Kontainer Utama Halaman Portal -->
			<main class="kgtk-portal-main">
				<?php
				// Router tampilan halaman portal
				if ( 'kgtk-buat' === $page ) {
					KGTK_Admin::page_buat();
				} elseif ( 'kgtk-panitia' === $page ) {
					KGTK_Admin::page_panitia();
				} elseif ( 'kgtk-rekap-guru' === $page ) {
					KGTK_Sekolah_Admin::page_rekap_guru();
				} elseif ( 'kgtk-rekap' === $page ) {
					KGTK_Sekolah_Admin::page_rekap();
				} elseif ( 'kgtk-sekolah' === $page ) {
					KGTK_Sekolah_Admin::page_data();
				} elseif ( 'kgtk-pengaturan' === $page ) {
					KGTK_Admin::page_pengaturan();
				} else {
					if ( 'absen' === $view ) {
						KGTK_Admin::view_absen();
					} elseif ( 'tambah' === $view ) {
						KGTK_Admin::view_tambah();
					} else {
						KGTK_Admin::view_list();
					}
				}
				?>
			</main>

			<!-- Footer Portal -->
			<footer class="kgtk-portal-footer">
				<p>Portal Pelatihan GTK &bull; Kementerian Pendidikan Dasar dan Menengah &bull; Versi <?php echo esc_html( KGTK_VERSION ); ?></p>
			</footer>

			<!-- Global Scripts & Configurations -->
			<script>
				window.KGTK_ADMIN = {
					root: <?php echo wp_json_encode( esc_url_raw( rest_url( 'kgtk/v1/' ) ) ); ?>,
					nonce: <?php echo wp_json_encode( wp_create_nonce( 'wp_rest' ) ); ?>,
					list: <?php echo wp_json_encode( esc_url_raw( self::portal_url( 'pelatihan' ) ) ); ?>,
					pubroot: <?php echo wp_json_encode( esc_url_raw( rest_url( 'kgtk/v1/' ) ) ); ?>,
					kabkota: <?php echo wp_json_encode( KGTK_Helpers::kabkota_list() ); ?>,
					kop_html: <?php echo wp_json_encode( KGTK_Helpers::kop_html() ); ?>
				};

				// Mobile Drawer Toggle
				(function() {
					var burger = document.querySelector('.kgtk-portal-hamburger');
					var drawer = document.querySelector('.kgtk-portal-mobile-drawer');
					if (burger && drawer) {
						burger.addEventListener('click', function() {
							var isOpen = drawer.classList.toggle('is-open');
							burger.classList.toggle('is-open', isOpen);
							burger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
						});
					}
				})();
			</script>
			<script src="<?php echo esc_url( KGTK_URL . 'assets/js/sekolah.js?v=' . $ver_sekolah_js ); ?>"></script>
			<script src="<?php echo esc_url( KGTK_URL . 'assets/js/admin.js?v=' . $ver_admin_js ); ?>"></script>
		</body>
		</html>
		<?php
	}

	/**
	 * Shortcode callback [kgtk_admin_portal].
	 *
	 * @return string HTML output shortcode.
	 */
	public static function render_shortcode() {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			ob_start();
			self::render_login_page();
			return ob_get_clean();
		}
		ob_start();
		self::render_portal_dashboard();
		return ob_get_clean();
	}
}
