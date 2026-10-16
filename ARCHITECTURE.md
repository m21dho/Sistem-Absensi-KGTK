# Arsitektur & Panduan Pemeliharaan Plugin KGTK Pelatihan

Dokumen ini disusun untuk memudahkan pengembang baru (*developer handover*) dalam memahami struktur, arsitektur, dan alur kerja plugin **KGTK Pelatihan**.

---

## 1. Ikhtisar Arsitektur

Plugin ini menggunakan pola **MVC (Model-View-Controller) Modular** yang ramah WordPress (*Zero-Build-Step*, tanpa perlu menjalankan npm/vite build):

```
kgtk-pelatihan/
├── admin/
│   ├── views/                         # Template Tampilan Admin (Pure View HTML/PHP)
│   │   ├── partials/                  # Komponen Modal & Pane Parsial
│   │   │   ├── modal-biodata.php      # Modal preview biodata
│   │   │   ├── modal-export-pdf.php   # Modal opsi filter export PDF
│   │   │   ├── modals-undangan.php    # Modal tambah/hubungkan/impor undangan
│   │   │   └── pane-undangan.php      # Tab pane peserta ditetapkan
│   │   ├── view-absen.php             # Halaman kelola kelas & lembar hadir
│   │   ├── view-buat.php              # Form pembuatan pelatihan baru
│   │   ├── view-list.php              # Grid kartu daftar pelatihan
│   │   ├── view-pengaturan.php        # Halaman pengaturan plugin
│   │   └── view-tambah.php            # Form tambah peserta manual oleh admin
│   ├── class-kgtk-admin.php           # Controller Admin Utama (Menu, Enqueue, Delegasi View)
│   └── class-kgtk-sekolah-admin.php   # Controller Impor & Rekap Sekolah/Guru
│
├── includes/
│   ├── rest/                          # Sub-Controller REST API Modular
│   │   ├── class-kgtk-rest-base.php            # Base Class (Response, Rate Limit, Permission, Payload)
│   │   ├── class-kgtk-rest-public.php          # Info, Cek NIK, Pendaftaran Publik, Riwayat Biodata
│   │   ├── class-kgtk-rest-admin-pelatihan.php # Buat & Hapus Pelatihan
│   │   ├── class-kgtk-rest-admin-peserta.php   # Kelola Peserta & Penempatan Kelas
│   │   └── class-kgtk-rest-admin-undangan.php  # CRUD & Impor Peserta Ditetapkan
│   ├── class-kgtk-rest.php            # Main Router REST API (/wp-json/kgtk/v1/...)
│   ├── class-kgtk-admin-portal.php    # Standalone Frontend Admin Portal & Custom Login Controller
│   ├── class-kgtk-db.php              # Skema Tabel & Upgrade Database
│   ├── class-kgtk-helpers.php         # Fungsi Helper, Format Tanggal, Kop Surat Resmi
│   ├── class-kgtk-undangan.php        # Business Logic Pencocokan Peserta Ditetapkan
│   └── class-kgtk-export.php          # Generator Export PDF (Dompdf) & Excel (PhpSpreadsheet)
│
├── public/
│   └── class-kgtk-public.php          # Shortcode frontend & asset enqueue
│
├── assets/
│   ├── css/                           # Styling (admin.css, public.css, common.css)
│   ├── js/                            # Script Frontend & Admin (admin.js, public.js, sekolah.js)
│   └── images/                        # Logo Kemendikdasmen resmi & Header PNG
│
├── kgtk-pelatihan.php                 # Entry Point Plugin WordPress
└── README.md                          # Dokumentasi Instalasi & Dependensi Composer
```

---

## 2. Portal Admin Mandiri (Standalone Frontend Admin)

Plugin menyediakan portal admin mandiri khusus yang dapat diakses langsung oleh pengelola tanpa membuka dashboard WordPress `/wp-admin/`:

- **URL Akses**: `https://domain.com/admin-pelatihan/`
  - Fallback permalink biasa: `https://domain.com/?kgtk_admin_portal=1`
  - Shortcode WordPress: `[kgtk_admin_portal]`
- **Sistem Login**:
  - Halaman login mandiri bertema resmi Kemendikdasmen tanpa logo atau embel-embel `/wp-login.php`.
  - Menggunakan akun Administrator WordPress standar dengan verifikasi capability `manage_options`.
  - Dilengkapi fitur show/hide password, AJAX login tanpa reload halaman, dan proteksi sesi keamanan nonce.
- **Navigasi Dashboard Mandiri**:
  - Topbar khusus pengelola kegiatan (Pelatihan, Buat Pelatihan, Rekap Guru, Rekap Sekolah, Data Sekolah, Pengaturan).
  - Avatar user login, tombol link cepat ke Form Pendaftaran Peserta, dan tombol Keluar (Logout) aman.

---

## 3. Peta Endpoint REST API (`kgtk/v1`)

| Endpoint | Method | Controller | Fungsi |
|---|---|---|---|
| `/public/pelatihan` | `GET` | `KGTK_REST_Public` | Ambil info pelatihan berdasarkan kode |
| `/public/sekolah` | `GET` | `KGTK_REST_Public` | Ambil daftar sekolah per kabupaten/kota |
| `/public/cek-nik` | `POST` | `KGTK_REST_Public` | Validasi NIK pendaftar & verifikasi akun |
| `/public/daftar` | `POST` | `KGTK_REST_Public` | Simpan pendaftaran guru baru |
| `/public/biodata-saya` | `POST` | `KGTK_REST_Public` | Ambil biodata & riwayat pelatihan guru |
| `/admin/pelatihan` | `POST` | `KGTK_REST_Admin_Pelatihan` | Buat pelatihan baru dengan kelas-kelasnya |
| `/admin/pelatihan/{id}` | `DELETE` | `KGTK_REST_Admin_Pelatihan` | Hapus pelatihan beserta data relasinya |
| `/admin/pelatihan/{id}/peserta` | `POST` | `KGTK_REST_Admin_Peserta` | Tambah peserta manual oleh admin |
| `/admin/peserta/{id}/kelas` | `PATCH` | `KGTK_REST_Admin_Peserta` | Ubah kelas peserta tunggal |
| `/admin/pelatihan/{id}/bulk-kelas` | `POST` | `KGTK_REST_Admin_Peserta` | Pindahkan banyak peserta sekaligus |
| `/admin/peserta/{id}` | `DELETE` | `KGTK_REST_Admin_Peserta` | Hapus peserta dari pelatihan |
| `/admin/guru` | `GET` | `KGTK_REST_Admin_Peserta` | Lookup biodata guru tersimpan by NIK |
| `/admin/pelatihan/{id}/undangan` | `GET/POST` | `KGTK_REST_Admin_Undangan` | List / Tambah peserta ditetapkan |
| `/admin/undangan/{id}` | `PUT/DEL` | `KGTK_REST_Admin_Undangan` | Ubah / Hapus peserta ditetapkan |
| `/admin/pelatihan/{id}/undangan/sync` | `POST` | `KGTK_REST_Admin_Undangan` | Sinkronkan ulang pencocokan peserta |
| `/admin/pelatihan/{id}/undangan/upload` | `POST` | `KGTK_REST_Admin_Undangan` | Unggah file Excel/CSV calon peserta |
| `/admin/pelatihan/{id}/undangan/import` | `POST` | `KGTK_REST_Admin_Undangan` | Proses impor baris peserta ditetapkan |

---

## 4. Skema Basis Data Custom

Tabel custom menggunakan prefix WordPress (`wp_`):
1. **`wp_kgtk_pelatihan`**: Data induk kegiatan (kode, nama, tanggal mulai/selesai, lokasi, penanggung jawab).
2. **`wp_kgtk_kelas`**: Data kelas di bawah pelatihan tertentu (`pelatihan_id`, `nama`, `slot`, `urutan`).
3. **`wp_kgtk_peserta`**: Relasi peserta dengan pelatihan dan kelas (`pelatihan_id`, `kelas_id`, data identitas lengkap, peran).
4. **`wp_kgtk_guru`**: Master biodata guru unik berdasarkan `nik` (menyimpan riwayat nomor HP, rekening, asal sekolah, dll untuk autofill).
5. **`wp_kgtk_undangan`**: Daftar peserta yang ditetapkan Kementerian sebelum mendaftar, dicocokkan otomatis via NIK/Email/HP.
6. **`wp_kgtk_sekolah`**: Data referensi sekolah di wilayah Kepri (NPSN, nama, jenjang, kabupaten/kota).

---

## 5. Panduan Ekstensi & Pemeliharaan

- **Menambahkan View Baru di Admin**: Letakkan file PHP di `admin/views/`, lalu panggil melalui `KGTK_Admin::render_view( 'nama-file', $data )`.
- **Menambahkan Endpoint Baru**: Tambahkan method di sub-controller terkait di `includes/rest/`, lalu daftarkan rutenya di `KGTK_REST::routes()`.
- **Ekspor Dokumen**: Format cetak HTML dan PDF menggunakan template resmi Kemendikdasmen dengan margin standar `0.51cm 1.0cm` (A4). Logika styling terpusat di `KGTK_Helpers::kop_html()` dan `class-kgtk-export.php`.
