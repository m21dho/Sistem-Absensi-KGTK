<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controller Admin utama untuk KGTK Pelatihan.
 * Menangani pendaftaran menu admin, settings, enqueue script/style,
 * serta menyiapkan data untuk file-file tampilan (views) di admin/views/.
 */
class KGTK_Admin {

	private static $hooks = array();

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	public static function menu() {
		self::$hooks[] = add_menu_page( 'Pelatihan KGTK', 'Pelatihan KGTK', 'manage_options', 'kgtk-pelatihan', array( __CLASS__, 'page_main' ), 'dashicons-welcome-learn-more', 26 );
		add_submenu_page( 'kgtk-pelatihan', 'Daftar pelatihan', 'Daftar pelatihan', 'manage_options', 'kgtk-pelatihan', array( __CLASS__, 'page_main' ) );
		self::$hooks[] = add_submenu_page( 'kgtk-pelatihan', 'Buat pelatihan', 'Buat pelatihan', 'manage_options', 'kgtk-buat', array( __CLASS__, 'page_buat' ) );
		self::$hooks[] = add_submenu_page( 'kgtk-pelatihan', 'Data Panitia', 'Data Panitia', 'manage_options', 'kgtk-panitia', array( __CLASS__, 'page_panitia' ) );
		self::$hooks[] = add_submenu_page( 'kgtk-pelatihan', 'Rekap Guru', 'Rekap Guru', 'manage_options', 'kgtk-rekap-guru', array( 'KGTK_Sekolah_Admin', 'page_rekap_guru' ) );
		self::$hooks[] = add_submenu_page( 'kgtk-pelatihan', 'Rekap Sekolah', 'Rekap Sekolah', 'manage_options', 'kgtk-rekap', array( 'KGTK_Sekolah_Admin', 'page_rekap' ) );
		self::$hooks[] = add_submenu_page( 'kgtk-pelatihan', 'Data Sekolah', 'Data Sekolah', 'manage_options', 'kgtk-sekolah', array( 'KGTK_Sekolah_Admin', 'page_data' ) );
		self::$hooks[] = add_submenu_page( 'kgtk-pelatihan', 'Pengaturan', 'Pengaturan', 'manage_options', 'kgtk-pengaturan', array( __CLASS__, 'page_pengaturan' ) );
	}

	public static function settings() {
		register_setting(
			'kgtk_settings',
			'kgtk_lebar',
			array(
				'type'              => 'string',
				'sanitize_callback' => function ( $v ) {
					return 'tema' === $v ? 'tema' : 'penuh';
				},
				'default'           => 'penuh',
			)
		);
		register_setting(
			'kgtk_settings',
			'kgtk_wa_help',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '08136798163',
			)
		);
		register_setting(
			'kgtk_settings',
			'kgtk_hapus_saat_uninstall',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => function ( $v ) {
					return $v ? 1 : 0;
				},
				'default'           => 0,
			)
		);
	}

	public static function enqueue( $hook ) {
		if ( ! in_array( $hook, self::$hooks, true ) ) {
			return;
		}
		wp_enqueue_style( 'kgtk-font', 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap', array(), null );
		$ver_admin_js   = file_exists( KGTK_PATH . 'assets/js/admin.js' ) ? filemtime( KGTK_PATH . 'assets/js/admin.js' ) : KGTK_VERSION;
		$ver_admin_css  = file_exists( KGTK_PATH . 'assets/css/admin.css' ) ? filemtime( KGTK_PATH . 'assets/css/admin.css' ) : KGTK_VERSION;
		$ver_common_css = file_exists( KGTK_PATH . 'assets/css/common.css' ) ? filemtime( KGTK_PATH . 'assets/css/common.css' ) : KGTK_VERSION;
		$ver_sekolah_js = file_exists( KGTK_PATH . 'assets/js/sekolah.js' ) ? filemtime( KGTK_PATH . 'assets/js/sekolah.js' ) : KGTK_VERSION;
		wp_enqueue_style( 'kgtk-common', KGTK_URL . 'assets/css/common.css', array(), $ver_common_css );
		wp_enqueue_style( 'kgtk-admin', KGTK_URL . 'assets/css/admin.css', array( 'kgtk-common' ), $ver_admin_css );
		wp_enqueue_script( 'kgtk-sekolah', KGTK_URL . 'assets/js/sekolah.js', array(), $ver_sekolah_js, true );
		wp_enqueue_script( 'kgtk-admin', KGTK_URL . 'assets/js/admin.js', array( 'kgtk-sekolah' ), $ver_admin_js, true );
		wp_localize_script(
			'kgtk-admin',
			'KGTK_ADMIN',
			array(
				'root'     => esc_url_raw( rest_url( 'kgtk/v1/' ) ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'list'     => self::page_url( 'pelatihan' ),
				'pubroot'  => esc_url_raw( rest_url( 'kgtk/v1/' ) ),
				'kabkota'  => KGTK_Helpers::kabkota_list(),
				'kop_html' => KGTK_Helpers::kop_html(),
			)
		);
	}

	/* ====================== Helper Render View ====================== */

	/**
	 * Helper untuk me-render file template dari folder admin/views/.
	 *
	 * @param string $view_name Nama file view tanpa ekstensi .php.
	 * @param array  $args      Variabel yang diekstrak ke dalam template.
	 */
	public static function render_view( $view_name, $args = array() ) {
		if ( ! empty( $args ) && is_array( $args ) ) {
			extract( $args, EXTR_SKIP );
		}
		$file = KGTK_PATH . 'admin/views/' . $view_name . '.php';
		if ( file_exists( $file ) ) {
			include $file;
		}
	}

	/* ====================== Util Tampilan HTML ====================== */

	public static function head( $title, $sub = '', $actions = '' ) {
		echo '<hr class="wp-header-end"><div class="kgtk-head"><div><h1>' . esc_html( $title ) . '</h1>';
		if ( $sub ) {
			echo '<p>' . esc_html( $sub ) . '</p>';
		}
		echo '</div><div class="kgtk-head-actions">' . $actions . '</div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	public static function progress( $persen, $penuh ) {
		return '<div class="kgtk-progress' . ( $penuh ? ' is-full' : '' ) . '"><span style="width:' . (int) $persen . '%"></span></div>';
	}

	public static function chips( $kelas ) {
		$h = '<div class="kgtk-chips">';
		foreach ( $kelas as $k ) {
			$h .= '<span class="kgtk-chip' . ( $k->sisa < 1 ? ' is-full' : '' ) . '">' . esc_html( $k->nama . ' ' . $k->terisi . '/' . $k->slot ) . '</span>';
		}
		$h .= '</div>';
		return $h;
	}

	public static function notice_libs() {
		if ( KGTK_Export::libs_ready() ) {
			return;
		}
		echo '<div class="notice notice-warning is-dismissible"><p><strong>KGTK Pelatihan:</strong> Library PDF dan Excel belum terpasang. Fitur export PDF dan Excel dinonaktifkan sampai Anda menjalankan <code>composer install --no-dev -o</code> di folder plugin. Lihat petunjuk di <code>README.md</code>.</p></div>';
	}

	public static function field( $label, $name, $attrs = '', $hint = '' ) {
		return '<label class="kgtk-field"><span>' . esc_html( $label ) . '</span><input class="kgtk-input" name="' . esc_attr( $name ) . '" ' . $attrs . '>'
			. ( $hint ? '<small>' . esc_html( $hint ) . '</small>' : '' ) . '</label>';
	}

	public static function kelas_row( $nama, $slot ) {
		return '<div class="kgtk-kelas-row">'
			. '<span class="kgtk-kelas-no" aria-hidden="true"></span>'
			. '<input type="text" class="kgtk-input" data-knama maxlength="30" placeholder="Nama kelas, mis. Kelas A" aria-label="Nama kelas" value="' . esc_attr( $nama ) . '">'
			. '<label class="kgtk-slot">Slot <input type="number" class="kgtk-input" data-kslot min="1" max="2000" value="' . (int) $slot . '"></label>'
			. '<div class="kgtk-row-actions">'
			. '<button type="button" class="kgtk-iconbtn" data-up data-move="up" title="Geser ke atas" aria-label="Geser ke atas">&#8593;</button>'
			. '<button type="button" class="kgtk-iconbtn" data-down data-move="down" title="Geser ke bawah" aria-label="Geser ke bawah">&#8595;</button>'
			. '<button type="button" class="kgtk-iconbtn kgtk-iconbtn--del" data-del title="Hapus kelas" aria-label="Hapus kelas">&times;</button>'
			. '</div></div>';
	}

	public static function undangan_row_builder() {
		return '<div class="kgtk-und-row">'
			. '<span class="kgtk-und-no" aria-hidden="true"></span>'
			. '<input type="text" class="kgtk-input" data-unama maxlength="100" placeholder="Nama lengkap" aria-label="Nama">'
			. '<input type="text" class="kgtk-input" data-unik maxlength="16" inputmode="numeric" placeholder="NIK (16 digit)" aria-label="NIK">'
			. '<input type="tel" class="kgtk-input" data-uhp maxlength="20" placeholder="No. HP" aria-label="No. HP">'
			. '<input type="email" class="kgtk-input" data-uemail maxlength="100" placeholder="Email belajar.id" aria-label="Email belajar.id">'
			. '<button type="button" class="kgtk-iconbtn kgtk-iconbtn--del" data-und-del aria-label="Hapus baris">&times;</button>'
			. '</div>';
	}

	public static function undangan_row_html( $item, $no = 1 ) {
		$nama_tampil  = ! empty( $item['nama'] ) ? $item['nama'] : ( ! empty( $item['peserta_nama'] ) ? $item['peserta_nama'] : '' );
		$nik_tampil   = ! empty( $item['nik'] ) ? $item['nik'] : ( ! empty( $item['peserta_nik'] ) ? $item['peserta_nik'] : '' );
		$hp_tampil    = ! empty( $item['hp'] ) ? $item['hp'] : ( ! empty( $item['peserta_hp'] ) ? $item['peserta_hp'] : '' );
		$email_tampil = ! empty( $item['email_belajar'] ) ? $item['email_belajar'] : ( ! empty( $item['peserta_email_belajar'] ) ? $item['peserta_email_belajar'] : '' );

		$search_text = strtolower( $nama_tampil . ' ' . $nik_tampil . ' ' . $hp_tampil . ' ' . $email_tampil . ' ' . ( isset( $item['peserta_sekolah'] ) ? $item['peserta_sekolah'] : '' ) . ' ' . ( isset( $item['catatan'] ) ? $item['catatan'] : '' ) );
		$badge_class = 'is-open';
		if ( 'belum' === $item['status'] ) {
			$badge_class = 'is-muted';
		} elseif ( 'perlu' === $item['status'] ) {
			$badge_class = 'is-alert';
		}

		$cocok_text = '-';
		if ( 'nik' === $item['cocok_dengan'] ) {
			$cocok_text = 'NIK';
		} elseif ( 'email' === $item['cocok_dengan'] ) {
			$cocok_text = 'Email';
		} elseif ( 'hp' === $item['cocok_dengan'] ) {
			$cocok_text = 'No. HP';
		} elseif ( 'nama' === $item['cocok_dengan'] ) {
			$cocok_text = 'Nama (Perlu Konfirmasi)';
		} elseif ( 'manual' === $item['cocok_dengan'] ) {
			$cocok_text = 'Manual';
		}

		$kelas_info = '-';
		if ( $item['peserta_id'] > 0 ) {
			$k_lbl      = $item['peserta_kelas_lbl'] ? $item['peserta_kelas_lbl'] : 'Menunggu Kelas';
			$p_lbl      = $item['peserta_peran'] ? ' (' . $item['peserta_peran'] . ')' : '';
			$kelas_info = esc_html( $k_lbl . $p_lbl );
		}

		$daftar_at = $item['peserta_daftar_at'] ? esc_html( date_i18n( 'd M Y H:i', strtotime( $item['peserta_daftar_at'] ) ) ) : '-';

		$bio_attr = '';
		if ( (int) $item['peserta_id'] > 0 ) {
			$bio_data = array(
				'id'                 => (int) $item['peserta_id'],
				'nama'               => $nama_tampil,
				'gelar_depan'        => ! empty( $item['peserta_gelar_depan'] ) ? $item['peserta_gelar_depan'] : '',
				'gelar_belakang'     => ! empty( $item['peserta_gelar_belakang'] ) ? $item['peserta_gelar_belakang'] : '',
				'nik'                => $nik_tampil,
				'tempat_lahir'       => ! empty( $item['peserta_tempat_lahir'] ) ? $item['peserta_tempat_lahir'] : '-',
				'tanggal_lahir'      => ! empty( $item['peserta_tanggal_lahir'] ) ? $item['peserta_tanggal_lahir'] : '-',
				'jenis_kelamin'      => ! empty( $item['peserta_jenis_kelamin'] ) ? $item['peserta_jenis_kelamin'] : '-',
				'alamat'             => ! empty( $item['peserta_alamat'] ) ? $item['peserta_alamat'] : '-',
				'alamat_instansi'    => ! empty( $item['peserta_alamat_instansi'] ) ? $item['peserta_alamat_instansi'] : '-',
				'pangkat_golongan'   => ! empty( $item['peserta_pangkat_golongan'] ) ? $item['peserta_pangkat_golongan'] : '-',
				'agama'              => ! empty( $item['peserta_agama'] ) ? $item['peserta_agama'] : '-',
				'pendidikan'         => ! empty( $item['peserta_pendidikan'] ) ? $item['peserta_pendidikan'] : '-',
				'npwp'               => ! empty( $item['peserta_npwp'] ) ? $item['peserta_npwp'] : '-',
				'pengalaman_jabatan' => ! empty( $item['peserta_pengalaman_jabatan'] ) ? $item['peserta_pengalaman_jabatan'] : '-',
				'nuptk'              => ! empty( $item['peserta_nuptk'] ) ? $item['peserta_nuptk'] : '-',
				'nip'                => ! empty( $item['peserta_nip'] ) ? $item['peserta_nip'] : '-',
				'simpkb'             => ! empty( $item['peserta_simpkb'] ) ? $item['peserta_simpkb'] : '-',
				'sekolah'            => ! empty( $item['peserta_sekolah'] ) ? $item['peserta_sekolah'] : '-',
				'kabkota'            => ! empty( $item['peserta_kabkota'] ) ? $item['peserta_kabkota'] : '-',
				'peran'              => ! empty( $item['peserta_peran'] ) ? $item['peserta_peran'] : 'Peserta',
				'jabatan'            => ! empty( $item['peserta_jenis_gtk'] ) ? $item['peserta_jenis_gtk'] : '-',
				'bank'               => ! empty( $item['peserta_bank'] ) ? $item['peserta_bank'] : '-',
				'rekening'           => ! empty( $item['peserta_rekening'] ) ? $item['peserta_rekening'] : '-',
				'hp'                 => $hp_tampil,
				'email'              => ! empty( $item['peserta_email'] ) ? $item['peserta_email'] : '-',
				'email_belajar'      => $email_tampil,
				'created_at'         => ! empty( $item['peserta_daftar_at'] ) ? $item['peserta_daftar_at'] : '-',
				'pdf_url'            => KGTK_Export::biodata_pdf_url( (int) $item['peserta_id'] ),
			);
			$bio_attr = ' data-biodata="' . esc_attr( wp_json_encode( $bio_data ) ) . '"';
		}

		$h  = '<tr data-id="' . (int) $item['id'] . '" data-status="' . esc_attr( $item['status'] ) . '" data-search="' . esc_attr( $search_text ) . '"'
			. ' data-nama="' . esc_attr( $nama_tampil ) . '" data-nik="' . esc_attr( $nik_tampil ) . '" data-hp="' . esc_attr( $hp_tampil ) . '"'
			. ' data-email="' . esc_attr( $email_tampil ) . '" data-catatan="' . esc_attr( $item['catatan'] ) . '"' . $bio_attr . '>';
		$h .= '<td class="c-no"><span class="kgtk-row-num">' . (int) $no . '.</span></td>';

		// Kolom Nama
		$h .= '<td>';
		if ( (int) $item['peserta_id'] > 0 && ! empty( $nama_tampil ) ) {
			$h .= '<div class="kgtk-guru-cell">';
			$h .= '<strong class="kgtk-guru-nama"><a href="#" class="kgtk-link-biodata" data-view-bio title="Klik untuk lihat biodata lengkap">' . esc_html( $nama_tampil ) . '</a></strong>';
			if ( ! empty( $item['peserta_sekolah'] ) ) {
				$h .= '<div class="kgtk-muted" style="font-size:11px;margin-top:2px;">' . esc_html( $item['peserta_sekolah'] ) . '</div>';
			}
			if ( $item['catatan'] ) {
				$h .= '<div class="kgtk-muted" style="font-size:11px;margin-top:2px;"><em>' . esc_html( $item['catatan'] ) . '</em></div>';
			}
			$h .= '</div>';
		} else {
			$h .= '<strong>' . ( $nama_tampil ? esc_html( $nama_tampil ) : '<span class="kgtk-muted">(tanpa nama)</span>' ) . '</strong>';
			if ( $item['catatan'] ) {
				$h .= '<div class="kgtk-muted" style="font-size:11px;margin-top:2px;">' . esc_html( $item['catatan'] ) . '</div>';
			}
		}
		$h .= '</td>';

		// Kolom NIK
		$h .= '<td>' . ( $nik_tampil ? '<code class="kgtk-nik-code">' . esc_html( $nik_tampil ) . '</code>' : '-' ) . '</td>';

		// Kolom No. HP
		$h .= '<td>' . ( $hp_tampil ? esc_html( $hp_tampil ) : '-' ) . '</td>';

		// Kolom Email Belajar.id
		$h .= '<td>' . ( $email_tampil ? esc_html( $email_tampil ) : '-' ) . '</td>';

		$h .= '<td style="text-align:center;"><span class="kgtk-badge ' . $badge_class . '">' . esc_html( $item['status_label'] ) . '</span></td>';
		$h .= '<td style="text-align:center;"><small>' . esc_html( $cocok_text ) . '</small></td>';
		$h .= '<td>' . $kelas_info . '</td>';
		$h .= '<td><small>' . $daftar_at . '</small></td>';
		$h .= '<td style="text-align:center;"><div class="kgtk-row-actions">';

		if ( 'perlu' === $item['status'] ) {
			$h .= '<button type="button" class="kgtk-iconbtn kgtk-iconbtn--ok" data-konfirmasi="' . (int) $item['id'] . '" title="Konfirmasi kecocokan">&#10004;</button>';
			$h .= '<button type="button" class="kgtk-iconbtn kgtk-iconbtn--unlink" data-lepas="' . (int) $item['id'] . '" title="Lepas hubungan">&#128279;&#65039;</button>';
		} elseif ( 'sudah' === $item['status'] ) {
			$h .= '<button type="button" class="kgtk-iconbtn kgtk-iconbtn--unlink" data-lepas="' . (int) $item['id'] . '" title="Lepas hubungan">&#128279;&#65039;</button>';
		} else {
			$h .= '<button type="button" class="kgtk-iconbtn kgtk-iconbtn--link" data-hubungkan="' . (int) $item['id'] . '" title="Hubungkan ke peserta terdaftar">&#128279;</button>';
		}

		$h .= '<button type="button" class="kgtk-iconbtn" data-edit-undangan="' . (int) $item['id'] . '" title="Edit">&#9998;</button>';
		$h .= '<button type="button" class="kgtk-iconbtn kgtk-iconbtn--del" data-del-undangan="' . (int) $item['id'] . '" title="Hapus">&#128465;&#65039;</button>';
		$h .= '</div></td></tr>';

		return $h;
	}

	/* ====================== Render Halaman-Halaman Absen ====================== */

	public static function sheet_pages_html( $p, $k, $peran, $rows, $jumlah, $tampil_kelas, $tanggal_khusus = '' ) {
		$pages       = KGTK_Helpers::halaman( $rows, $jumlah );
		$total_pages = count( $pages );

		foreach ( $pages as $p_idx => $halaman ) {
			$is_last_page = ( $p_idx === $total_pages - 1 );
			echo '<section class="kgtk-page">';
			if ( 0 === $p_idx ) {
				echo KGTK_Helpers::kop_html(); // phpcs:ignore WordPress.Security.EscapeOutput
				echo '<h2 class="kgtk-doc-title">' . esc_html( KGTK_Helpers::judul_absen( $peran ) ) . '</h2>';
				echo KGTK_Helpers::baris_info_html( $p, $tanggal_khusus ); // phpcs:ignore WordPress.Security.EscapeOutput
				if ( $tampil_kelas ) {
					echo '<p class="kgtk-doc-kelas">' . esc_html( $k->label ) . '</p>';
				}
			}
			echo '<div class="kgtk-scroll"><table class="kgtk-table" style="table-layout:fixed;width:100%;">';
			echo '<colgroup><col style="width:7%;"><col style="width:30%;"><col style="width:37%;"><col style="width:13%;"><col style="width:13%;"></colgroup>';
			echo '<thead><tr><th class="c-no">No</th><th class="c-nama">Nama</th><th class="c-inst">Instansi</th><th class="c-ttd" colspan="2" style="white-space:nowrap;text-align:center;">Tanda Tangan</th></tr></thead><tbody>';
			for ( $i = 0; $i < $halaman['jumlah']; $i++ ) {
				$row       = isset( $halaman['rows'][ $i ] ) ? $halaman['rows'][ $i ] : null;
				$no_urutan = (int) ( $halaman['mulai'] + $i + 1 );
				$is_odd    = ( $no_urutan % 2 !== 0 );
				echo '<tr>';
				echo '<td class="c-no">' . $no_urutan . '</td>';
				echo '<td class="c-nama">' . ( $row ? esc_html( $row->nama ) : '' ) . '</td>';
				echo '<td class="c-inst">' . ( $row ? esc_html( $row->sekolah ) : '' ) . '</td>';
				if ( $is_odd ) {
					echo '<td class="c-ttd-col c-ttd-left">' . $no_urutan . '.</td><td class="c-ttd-col c-ttd-right">&nbsp;</td>';
				} else {
					echo '<td class="c-ttd-col c-ttd-left">&nbsp;</td><td class="c-ttd-col c-ttd-right">' . $no_urutan . '.</td>';
				}
				echo '</tr>';
			}
			echo '</tbody></table></div>';
			if ( $is_last_page ) {
				echo '<div class="kgtk-ttd-blok"><em>' . esc_html( KGTK_Helpers::label_pj( $p ) ) . '</em><div class="kgtk-ttd-space"></div><strong>' . esc_html( KGTK_Helpers::nama_pj( $p ) ) . '</strong></div>';
			}
			echo '</section>';
		}
	}

	public static function sheet_html( $p, $k, $peran, $rows, $jumlah, $sub, $active, $tampil_kelas ) {
		$daftar_hari  = KGTK_Helpers::daftar_hari_pelatihan( $p->tanggal_mulai, $p->tanggal_selesai );
		$is_multi_day = count( $daftar_hari ) > 1;
		$tgl_default  = KGTK_Helpers::rentang_tanggal( $p->tanggal_mulai, $p->tanggal_selesai );
		$label        = 'Cetak ' . $k->label . ' - ' . $peran . ( $is_multi_day ? ' (Semua Hari)' : '' );

		echo '<div class="kgtk-sub' . ( $active ? ' is-active' : '' ) . '" data-sub="' . esc_attr( $sub ) . '">';
		echo '<div class="kgtk-toolbar kgtk-toolbar--split">';
		if ( $is_multi_day ) {
			echo '<div class="kgtk-toolbar-date">';
			echo '<label for="kgtk-sel-tgl-' . esc_attr( $sub ) . '"><span>&#128197; Hari / Tanggal Absen:</span></label>';
			echo '<select class="kgtk-input kgtk-select-tanggal-absen" id="kgtk-sel-tgl-' . esc_attr( $sub ) . '">';
			echo '<option value="all_days" selected>Semua Hari (Masing-masing Hari Dicetak - ' . count( $daftar_hari ) . ' Hari)</option>';
			foreach ( $daftar_hari as $dh ) {
				echo '<option value="' . esc_attr( $dh['ymd'] ) . '" data-label="' . esc_attr( $dh['label_lengkap'] ) . '">' . esc_html( $dh['label_hari_ke'] ) . '</option>';
			}
			echo '<option value="range" data-label="' . esc_attr( $tgl_default ) . '">Rentang Pelaksanaan (1 Lembar: ' . esc_html( $tgl_default ) . ')</option>';
			echo '</select>';
			echo '</div>';
		} else {
			echo '<div></div>';
		}
		echo '<button type="button" class="kgtk-btn kgtk-btn--navy" data-cetak="' . esc_attr( $sub ) . '" data-base-label="' . esc_attr( 'Cetak ' . $k->label . ' - ' . $peran ) . '">&#128424;&#65039; ' . esc_html( $label ) . '</button>';
		echo '</div>';
		echo '<div class="kgtk-card kgtk-sheet">';

		if ( $is_multi_day ) {
			$last_idx = count( $daftar_hari ) - 1;
			foreach ( $daftar_hari as $d_idx => $dh ) {
				$is_last_day = ( $d_idx === $last_idx );
				echo '<div class="kgtk-sheet-day' . ( $is_last_day ? ' is-last-print-day' : '' ) . '" data-day="' . esc_attr( $dh['ymd'] ) . '">';
				echo '<div class="kgtk-sheet-day-badge"><span class="kgtk-badge-icon">&#128197;</span> <strong>Lembar Absen ' . esc_html( $dh['label_hari_ke'] ) . '</strong></div>';
				self::sheet_pages_html( $p, $k, $peran, $rows, $jumlah, $tampil_kelas, $dh['ymd'] );
				echo '</div>';
			}
			echo '<div class="kgtk-sheet-day is-hidden" data-day="range">';
			echo '<div class="kgtk-sheet-day-badge"><span class="kgtk-badge-icon">&#128197;</span> <strong>Lembar Absen Rentang Pelaksanaan : ' . esc_html( $tgl_default ) . '</strong></div>';
			self::sheet_pages_html( $p, $k, $peran, $rows, $jumlah, $tampil_kelas, 'range' );
			echo '</div>';
		} else {
			self::sheet_pages_html( $p, $k, $peran, $rows, $jumlah, $tampil_kelas, '' );
		}

		echo '</div></div>';
	}

	/* ====================== Routing Halaman Admin ====================== */

	public static function page_url( $page = 'pelatihan', $params = array() ) {
		if ( class_exists( 'KGTK_Admin_Portal' ) && KGTK_Admin_Portal::is_portal_request() ) {
			return KGTK_Admin_Portal::portal_url( $page, $params );
		}
		$slug = 0 === strpos( $page, 'kgtk-' ) ? $page : 'kgtk-' . $page;
		return add_query_arg( array_merge( array( 'page' => $slug ), $params ), admin_url( 'admin.php' ) );
	}

	public static function page_main() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Anda tidak memiliki akses.' );
		}
		$view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'list'; // phpcs:ignore WordPress.Security.NonceVerification
		if ( 'absen' === $view ) {
			self::view_absen();
		} elseif ( 'tambah' === $view ) {
			self::view_tambah();
		} else {
			self::view_list();
		}
	}

	public static function view_list() {
		global $wpdb;
		$t         = KGTK_DB::table( 'pelatihan' );
		$rows      = $wpdb->get_results( "SELECT * FROM {$t} ORDER BY tanggal_mulai DESC, id DESC" );
		$stats     = KGTK_Helpers::all_kelas_stats();
		$und_stats = KGTK_Undangan::all_undangan_stats();

		self::render_view( 'view-list', compact( 'rows', 'stats', 'und_stats' ) );
	}

	public static function view_absen() {
		$id   = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$data = $id ? KGTK_Helpers::absen_data( $id ) : null;

		if ( ! $data ) {
			echo '<div class="wrap kgtk-wrap kgtk-ui">';
			self::head( 'Pelatihan tidak ditemukan', '', '<a class="kgtk-btn" href="' . esc_url( self::page_url( 'pelatihan' ) ) . '">Kembali</a>' );
			echo '</div>';
			return;
		}

		$p               = $data['pelatihan'];
		$kelas           = $data['kelas'];
		$tot             = KGTK_Helpers::totals( $kelas );
		$all_peserta     = KGTK_Helpers::peserta_pelatihan( $p->id );
		$unassigned      = KGTK_Helpers::unassigned_count( $p->id );
		$total_pendaftar = count( $all_peserta );
		$link            = KGTK_Helpers::share_url( $p->kode );
		$tgl             = KGTK_Helpers::rentang_tanggal( $p->tanggal_mulai, $p->tanggal_selesai );
		$lok             = '' !== $p->lokasi ? $p->lokasi : '-';
		$und_stats       = KGTK_Undangan::get_stats( $p->id );
		$multi           = count( $kelas ) > 1;

		$kelas_map = array();
		foreach ( $kelas as $k ) {
			$kelas_map[ (int) $k->id ] = $k->label;
		}

		$linked_map = KGTK_Undangan::linked_peserta_map( $p->id );
		$panitia    = isset( $data['panitia'] ) ? $data['panitia'] : KGTK_Panitia::get_by_pelatihan( $p->id );

		self::render_view(
			'view-absen',
			compact(
				'p',
				'kelas',
				'tot',
				'all_peserta',
				'unassigned',
				'total_pendaftar',
				'link',
				'tgl',
				'lok',
				'und_stats',
				'kelas_map',
				'linked_map',
				'multi',
				'panitia'
			)
		);
	}

	public static function view_tambah() {
		$id   = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$p    = $id ? KGTK_Helpers::get_by_id( $id ) : null;
		$url  = self::page_url( 'pelatihan' );

		if ( ! $p ) {
			echo '<div class="wrap kgtk-wrap kgtk-ui">';
			self::head( 'Pelatihan tidak ditemukan', '', '<a class="kgtk-btn" href="' . esc_url( $url ) . '">Kembali</a>' );
			echo '</div>';
			return;
		}
		$kelas = KGTK_Helpers::kelas_stats( $p->id );
		$back  = self::page_url( 'pelatihan', array( 'view' => 'absen', 'id' => (int) $p->id ) );

		self::render_view( 'view-tambah', compact( 'p', 'kelas', 'back' ) );
	}

	public static function page_buat() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Anda tidak memiliki akses.' );
		}
		$kode              = KGTK_Helpers::generate_kode();
		$panitia_tersimpan = KGTK_Panitia::get_all();
		self::render_view( 'view-buat', compact( 'kode', 'panitia_tersimpan' ) );
	}

	public static function page_panitia() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Anda tidak memiliki akses.' );
		}
		$panitia_list = KGTK_Panitia::get_all();
		self::render_view( 'view-panitia', compact( 'panitia_list' ) );
	}

	public static function page_pengaturan() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Anda tidak memiliki akses.' );
		}
		$lebar   = get_option( 'kgtk_lebar', 'penuh' );
		$wa_help = get_option( 'kgtk_wa_help', '08136798163' );

		self::render_view( 'view-pengaturan', compact( 'lebar', 'wa_help' ) );
	}
}
