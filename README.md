# KGTK Pelatihan

Plugin WordPress (PHP murni, tanpa framework) untuk pendaftaran pelatihan guru. Admin memakai akun WordPress (`manage_options`); peserta tanpa login, cukup kode pelatihan dan NIK.

## Struktur

```
kgtk-pelatihan/
├── kgtk-pelatihan.php          File utama, hook aktivasi
├── uninstall.php               Hapus data (opsional, lewat Pengaturan)
├── composer.json               PhpSpreadsheet + Dompdf
├── includes/
│   ├── class-kgtk-db.php       Tabel (dbDelta) + halaman pendaftaran
│   ├── class-kgtk-helpers.php  Query bersama, format tanggal, kode, link
│   ├── class-kgtk-rest.php     REST API kgtk/v1 + rate limit + transaksi
│   └── class-kgtk-export.php   Export PDF & Excel (admin-post, cek capability + nonce)
├── admin/class-kgtk-admin.php  Menu, daftar, buat, absen, pengaturan
├── public/class-kgtk-public.php Shortcode [kgtk_daftar]
└── assets/{css,js}/            common, admin, public (di-enqueue hanya di halaman terkait)
```

## Instal

1. Salin folder `kgtk-pelatihan` ke `wp-content/plugins/`.
2. Di folder itu jalankan (sekali, di komputer/server yang punya Composer):
   ```
   composer install --no-dev --optimize-autoloader
   ```
   Folder `vendor/` ikut terunggah ke hosting. Tanpa langkah ini plugin tetap jalan, hanya export PDF/Excel yang menampilkan pesan pemasangan.
3. WordPress Admin > Plugin > aktifkan **KGTK Pelatihan**.
   Saat aktivasi dibuat 4 tabel (`{prefix}kgtk_pelatihan`, `kgtk_kelas`, `kgtk_peserta`, `kgtk_guru`) dan halaman **Pendaftaran Pelatihan** berisi `[kgtk_daftar]`.
4. Buka menu **Pelatihan KGTK**. Persyaratan: PHP 7.4+, WordPress 5.8+, MySQL/MariaDB dengan InnoDB, ekstensi PHP `gd`, `zip`, `xml`, `mbstring` (untuk PhpSpreadsheet/Dompdf).

## Tampilan (mengikuti situs KGTK Kepri)

- Halaman peserta meniru gaya situs: banner gradien biru tua dengan pil breadcrumb dan judul bersorot oranye, menu langkah di samping (di layar kecil menjadi baris di atas), label bagian bergaris biru, kartu putih bersudut 16px, kartu informasi pelatihan biru tua, dan tombol oranye bulat. Warna diambil dari piksel tangkapan layar situs: `#0b2d56` (biru tua), `#0b64a4` (aksen biru), `#1c8aca`, `#f59e0b` (oranye), `#fbbf24` (kuning), `#f8fafc` (latar).
- Lebar halaman: default layar penuh (selebar layar di bawah header situs, judul halaman tema disembunyikan). Ubah di Pelatihan KGTK > Pengaturan > "Lebar halaman pendaftaran". Bila masih sempit, tema/page builder membatasi lebar: pilih template halaman "Lebar penuh" (Halaman > Pendaftaran Pelatihan > Atribut/Template) atau, di Elementor/Divi, jadikan section "Full width".
- Font halaman peserta mengikuti font tema situs (`inherit`), jadi otomatis sama dengan situs. Untuk memaksa font lain, tambahkan di CSS tema: `.kgtk-app { --kgtk-font: "Nama Font", sans-serif; }`.
- Semua warna ada sebagai variabel di baris pertama `assets/css/common.css` (`--navy`, `--blue`, `--sky`, `--yellow` = oranye, `--amber`, `--pill`, `--bg`). Ubah di sana bila situs berganti warna.
- Halaman admin memakai palet yang sama; fontnya Plus Jakarta Sans.

## Catatan teknis

- Data sekolah: tabel `kgtk_sekolah` (kabupaten/kota, nama, NPSN, jenjang) diisi dari Excel/CSV lewat menu Pelatihan KGTK > Data Sekolah. Impor memakai pencocokan kolom manual (judul kolom dikenali otomatis, bisa diubah), aman diulang: sekolah dengan NPSN yang sama (atau kabupaten/kota + nama yang sama) diperbarui, bukan digandakan, dan data lama tidak dihapus. Selama tabel kosong, form peserta tetap memakai isian bebas untuk asal sekolah.
- Form peserta: Kabupaten/Kota (dari data sekolah) lalu pencarian sekolah pada kabupaten/kota itu. Pilihan "Sekolah saya tidak ada di daftar" dan "Lainnya (di luar daftar)" memungkinkan nama diketik sendiri. Nama sekolah yang tersimpan selalu diambil dari database, bukan dari kiriman browser.
- Kolom baru: `kabkota` dan `sekolah_id` di `kgtk_peserta` dan `kgtk_guru` (database v4, ditambahkan otomatis). Peserta lama tidak punya kabupaten/kota sehingga tidak masuk Rekap Sekolah.

- Kelas: nama bebas (maks 30 karakter, maks 20 kelas, tidak boleh kembar). Urutan di form = urutan pengisian. Saat mendaftar, server mengunci baris pelatihan (`SELECT ... FOR UPDATE`) lalu menempatkan peserta di kelas pertama yang masih punya slot. Peserta tidak memilih kelas.
- Input admin: halaman `view=tambah` memakai endpoint `POST kgtk/v1/admin/pelatihan/{id}/peserta` (capability `manage_options` + nonce) dan logika yang sama dengan pendaftaran publik (`validasi_peserta` dan `proses_daftar` di `includes/class-kgtk-rest.php`), sehingga aturan dan penempatan kelas selalu konsisten. Endpoint `GET kgtk/v1/admin/guru?nik=` hanya untuk admin dan dipakai mengisi form otomatis. Belum ada edit atau hapus peserta satuan.
- Penanggung jawab: kolom `penanggung_jawab` di tabel `kgtk_pelatihan` (database v3, ditambahkan otomatis). Pelatihan yang dibuat sebelum versi ini kosong, jadi nama di lembar cetak berupa titik-titik; belum ada fitur ubah pelatihan, buat ulang bila ingin mengisinya.
- Peran: Fasilitator, Pengajar, Narasumber, Penceramah, Peserta (daftar ada di konstanta `KGTK_Helpers::PERAN`; ubah di sana dan di `PERAN` pada `assets/js/public.js` bila perlu). Peran tidak memengaruhi slot atau urutan kelas; semua peran berbagi slot yang sama.
- Biodata peserta: NISN wajib 10 digit; NIP opsional 18 digit; email wajib; email belajar.id wajib dan harus berakhiran `belajar.id`; email guruku.id opsional (hanya dicek formatnya). Aturan ada di `includes/class-kgtk-rest.php` (fungsi `public_daftar`) dan dicerminkan di `assets/js/public.js`.
- Data lama: pembaruan versi database (v2) menambah kolom otomatis saat plugin dimuat. Peserta lama tetap ada, kolom barunya kosong.

- Pendaftaran: `START TRANSACTION`, kunci baris kelas dengan `SELECT ... FOR UPDATE`, cek slot, simpan guru + peserta, `COMMIT`. `UNIQUE (pelatihan_id, nik)` menjamin satu NIK satu kelas per pelatihan.
- Rate limit publik per IP (transient), bisa diubah lewat filter `kgtk_rate_limit_max` (parameter: batas, nama bucket `info`/`cek`/`daftar`). Bila situs di belakang proxy/CDN, `REMOTE_ADDR` harus berisi IP klien (atur di server) agar limit tidak berlaku untuk semua orang sekaligus.
- Fitur Biodata & Riwayat Saya: guru tanpa login dapat melihat biodata dan riwayat pelatihan yang pernah diikuti melalui verifikasi NIK (16 digit) + Nomor HP atau Email `belajar.id`.
- Pembatasan verifikasi (Rate Limit): Maksimal 5 percobaan gagal per kombinasi IP+NIK per 15 menit, dan maksimal 20 percobaan gagal per IP per 15 menit secara keseluruhan (menggunakan transient bertanda MD5 hash). NIK tidak dikunci secara global. Percobaan berhasil tidak menambah hitungan gagal.
- Pengamanan NIK & Celah Pengisian Otomatis: Endpoint `public/cek-nik` tidak lagi mengembalikan biodata tersimpan hanya dari NIK mentah. Di langkah 2 pendaftaran, guru diberi pilihan untuk memverifikasi kontak untuk isi otomatis atau lewati untuk isi manual.
- Kerahasiaan & Waktu Respons: Pesan kegagalan verifikasi HARUS dan SELALU identik (`"Data tidak cocok. Periksa NIK dan nomor HP atau email belajar.id yang Anda daftarkan."`) baik untuk NIK tidak ada maupun NIK ada tetapi kontak tidak cocok, serta menggunakan perbandingan waktu konstan (`hash_equals()`).
- Tanpa Sesi & Tanpa Storage: Hasil verifikasi dikirim langsung via POST JSON dengan header `Cache-Control: no-store` dan hanya disimpan di variabel JS selama view aktif (tidak menggunakan `localStorage` atau `sessionStorage`).
- Font Plus Jakarta Sans dimuat dari Google Fonts.
- Uninstall: data hanya dihapus bila opsi di **Pelatihan KGTK > Pengaturan** dicentang.
- Hanya untuk single-site (belum mendukung multisite network activation).

## Daftar tes manual

**1. Buat pelatihan**
- Buka Pelatihan KGTK > + Buat pelatihan. Kode terisi otomatis format `KGTK-XXXX`.
- Simpan tanpa nama: error. Tanpa tanggal mulai: error. Tanggal selesai < mulai: error.
- Kelas: awalnya ada satu baris. Klik "+ Tambah kelas" beberapa kali; tombol panah menukar urutan, tombol x menghapus (tidak bisa jika tinggal satu).
- Simpan dengan nama kelas kosong: error. Dua nama kelas sama (mis. "Kelas A" dan "kelas a"): error. Slot 0 atau 2001: error.
- Kode `ab` (2 karakter) atau berspasi: error. Kode yang sudah dipakai: "Kode sudah dipakai".
- Isi lengkap dengan kelas "Kelas A" (slot 2) dan "Kelas B" (slot 2) dan tanggal selesai kosong. Simpan: masuk halaman absen; tanggal selesai = tanggal mulai; tab muncul sesuai nama dan urutan yang Anda ketik.
- Kembali ke daftar: kartu menampilkan `0/4`, chip "Kelas A 0/2", "Kelas B 0/2", status Dibuka.

**2. Daftar sampai kelas penuh (pengisian berurutan)**
- Buka link (Salin link) di jendela privat: langkah kode dilewati.
- Kode salah (ketik manual): "Kode pelatihan tidak ditemukan".
- NIK kurang dari 16 digit / berisi huruf: ditolak. NIK baru 16 digit: judul "Lengkapi biodata", form kosong. Tidak ada pilihan kelas; tertulis "Saat ini pendaftar masuk ke Kelas A".
- Uji validasi field: NISN bukan 10 digit ditolak; NIP diisi selain 18 digit ditolak (kosong boleh); Email kosong/salah ditolak; Email belajar.id yang bukan berakhiran belajar.id (mis. gmail.com) ditolak; Email guruku.id boleh kosong.
- Isi biodata dengan benar lalu Daftar: "Pendaftaran berhasil", "Nomor urut: 1 dari 2 slot Kelas A".
- Daftarkan peserta ke-2: masuk Kelas A, nomor urut 2. Peserta ke-3: otomatis masuk Kelas B, nomor urut 1 dari 2. Info peserta ke-4 sama, ke Kelas B nomor 2.
- Peserta ke-5 (NIK baru): setelah cek NIK tampil "Maaf, semua kelas pelatihan ini sudah penuh." Kartu di admin berstatus Penuh, progress hijau.
- Halaman absen: tab Kelas A berisi 2 peserta, tab Kelas B 2 peserta, baris sesuai slot.
- Uji balapan: dengan satu slot tersisa, buka dua tab di langkah biodata, kirim hampir bersamaan. Satu berhasil, satu diarahkan ke layar "semua kelas penuh" (atau ke kelas berikutnya bila masih ada slot).

**3. NIK ganda**
- Masukkan NIK yang sudah terdaftar pada pelatihan itu: layar "Kamu sudah terdaftar" dengan kelas dan nomor urut yang sama seperti saat daftar.
- Cek di database: `SELECT pelatihan_id, nik, COUNT(*) FROM wp_kgtk_peserta GROUP BY 1,2 HAVING COUNT(*)>1` harus kosong.

**4. NIK pernah terdaftar (pengisian otomatis terverifikasi)**
- Buat pelatihan kedua. Pakai NIK dari pelatihan pertama: muncul pemberitahuan bahwa data pernah tersimpan dan pilihan verifikasi kontak (HP / Email belajar.id).
- Lewati verifikasi: form biodata terbuka kosong.
- Isi nomor HP atau email belajar.id yang terdaftar: form biodata terisi otomatis. Ubah sekolah dan salah satu email lalu daftar. Cek tabel `kgtk_guru`: satu baris per NIK, data ikut berubah; baris di `kgtk_peserta` menyimpan data saat pendaftaran itu.

**5. Peran dan cetak per peran**
- Form peserta: "Peran" berisi 5 pilihan (Fasilitator, Pengajar, Narasumber, Penceramah, Peserta), default Peserta. Daftarkan beberapa orang dengan peran berbeda, termasuk dua orang berperan sama.
- Buat pelatihan: isi "Penanggung Jawab" dengan nama dan gelar (mis. `Dr. Budi Santoso, M.Pd.`).
- Buka Lihat absen > tab kelas. Di atas lembar ada sub-tab satu per peran yang punya peserta (dengan jumlahnya). Peran yang belum punya peserta tidak muncul. Kelas tanpa peserta menampilkan formulir kosong "DAFTAR HADIR" sebanyak slot kelas.
- Bentuk lembar (mengikuti formulir "DH Daftar Hadir 2026"): judul `DAFTAR HADIR FASILITATOR` (peran huruf besar); satu baris `Nama pelatihan, tanggal, tempat`; tabel No / Nama / Instansi / Tanda Tangan. Jumlah baris = slot kelas (mis. slot 22 = 22 baris), dibagi 15 baris per halaman: halaman 1 berisi 1-15, halaman 2 berisi 16-22. Baris yang belum terisi dibiarkan kosong. Nomor `1.` `2.` dst; blok `(Penanggung Jawab),` dengan nama penanggung jawab di bawahnya.
- Bila pelatihan punya lebih dari satu kelas, ada baris nama kelas (mis. "Kelas A") di bawah baris kegiatan. Pelatihan satu kelas tidak menampilkannya.
- Tiap halaman punya judul, tabel, dan blok penanggung jawab sendiri. Kelas dengan slot 15 atau kurang hanya satu halaman. Setiap lembar peran berisi baris sebanyak slot kelas, jadi peran yang hanya 2 orang tetap mencetak baris sebanyak slot (baris sisanya kosong).
- Penanggung jawab dikosongkan: bagian nama berisi titik-titik untuk diisi tangan.
- Klik "Cetak Kelas A - Fasilitator": pratinjau cetak hanya memuat lembar peran itu, satu halaman A4 per 15 baris, tanpa menu admin, ringkasan, dan tombol.
- Peserta lama berperan "Guru"/"Pemateri" (versi sebelumnya) tetap muncul sebagai sub-tab sendiri di belakang peran baru.

**6. Export PDF dan Excel**
- Export PDF: baris sebanyak slot kelas per kelas per peran, 15 baris per halaman, bentuk sama dengan lembar di layar. Cek bahwa tiap halaman memuat blok penanggung jawab dan tidak ada halaman yang terpotong.
- Export Excel: satu sheet per kelas per peran, judul seperti "Kelas A - Fasilitator" (dipotong 28 karakter bila panjang, tetap unik). Baris absen sebanyak slot kelas; tiap blok (maksimal 15 baris) memuat judul, baris kegiatan, tabel, dan blok penanggung jawab, dipisah page break.
- Nama yang diawali tanda `=` (mis. `=1+1`) tampil sebagai teks, bukan rumus.
- Kolom NIK tidak ada di lembar absen (sesuai formulir); NIK tetap tersimpan di database.
- Export tidak memuat lembar gabungan "semua peran".
- Salin URL export, buka di jendela privat tanpa login: ditolak. Ubah `_wpnonce` di URL: "link sudah kedaluwarsa".

**7. Hapus**
- Hapus pelatihan (konfirmasi muncul): kartu hilang; tabel kelas dan peserta tidak lagi memuat datanya; `kgtk_guru` tetap ada.

**8. Admin menginput peserta**
- Buka Lihat absen > "+ Tambah peserta". Form berisi NIK, nama, sekolah, peran, NISN, NIP, HP, email, email belajar.id, email guruku.id, dan pilihan Kelas (default "Otomatis").
- Isi NIK 16 digit yang pernah tersimpan: data terisi otomatis dan muncul keterangan kuning. NIK baru: form tetap kosong.
- Kirim dengan data salah (NISN 5 digit, email belajar.id berakhiran gmail.com): muncul pesan merah yang sama dengan form peserta.
- Simpan dengan benar: pesan hijau "... berhasil didaftarkan ke Kelas A, nomor urut N dari M slot.", form dikosongkan, kursor kembali ke NIK, dan pilihan kelas menampilkan sisa slot terbaru. Isi peserta berikutnya tanpa membuka ulang halaman.
- Pilih "Otomatis": peserta masuk ke kelas urutan teratas yang masih punya slot. Pilih kelas tertentu: peserta masuk ke kelas itu; kelas penuh tampil "Penuh" dan tidak bisa dipilih.
- NIK yang sudah terdaftar di pelatihan itu: ditolak ("NIK ini sudah terdaftar pada pelatihan ini").
- Kembali ke halaman absen (tombol Selesai): peserta muncul di lembar kelas dan sub-tab perannya; kartu di daftar pelatihan menampilkan jumlah terisi yang baru.
- Peserta yang diinput admin tidak bisa mengisi lebih dari slot kelas; slot tetap dihormati.

**9. Data sekolah dan pilihan sekolah**
- Sebelum impor: buka form peserta, "Asal sekolah" berupa isian teks biasa.
- Menu Data Sekolah > unggah berkas Excel Anda (sheet pertama). Di langkah "Cocokkan kolom": periksa baris judul yang terdeteksi, kolom Kabupaten/Kota dan Nama sekolah terpilih benar (ubah bila salah), NPSN/Jenjang opsional, lihat contoh isi, lalu Impor.
- Hasil: pesan "N sekolah baru, N diperbarui, N dilewati". Tabel ringkas di halaman itu menampilkan jumlah sekolah per kabupaten/kota; cocokkan dengan jumlah di Excel Anda (selisih = baris kosong atau duplikat).
- Impor berkas yang sama lagi: semua dilewati, tidak ada penggandaan. Ubah satu nama sekolah di Excel (NPSN sama), impor lagi: 1 diperbarui.
- Berkas .csv (titik koma atau koma, dengan atau tanpa BOM) juga diterima. Berkas di atas 10 MB, atau format lain: ditolak dengan pesan.
- Form peserta setelah impor: muncul "Kabupaten/Kota". Pilih satu: kolom pencarian sekolah muncul dan hanya berisi sekolah kabupaten/kota itu (ketik sebagian nama atau NPSN untuk menyaring). Ganti kabupaten/kota: daftar berganti dan pilihan sekolah dikosongkan.
- Pilih "Sekolah saya tidak ada di daftar": muncul isian nama sekolah. Pilih "Lainnya (di luar daftar)": hanya isian nama sekolah.
- Kirim tanpa kabupaten/kota, atau tanpa memilih sekolah: ditolak dengan pesan.
- Daftar dengan NIK yang pernah tersimpan: kabupaten/kota dan sekolah terpilih otomatis (juga di form Tambah peserta oleh admin).

**10. Rekap sekolah**
- Menu Rekap Sekolah: pilih kabupaten/kota, lalu (opsional) pelatihan dan "Tampilkan" (semua/sudah ikut/belum ikut).
- Tabel: sekolah, NPSN, jenjang, jumlah guru, status "Sudah ikut"/"Belum ikut", dan nama pelatihan yang diikuti. Kotak ringkasan: jumlah sekolah, sudah/belum ikut, jumlah guru.
- Bagian "Guru dari sekolah di luar daftar" memuat sekolah yang diketik sendiri pada kabupaten/kota itu. Pilihan "Lainnya" menampilkan hanya bagian ini.
- Export Excel: file berisi sheet "Rekap Sekolah" (dan "Di luar daftar" bila ada). NPSN tampil utuh sebagai teks.
- Cocokkan dengan data: jumlah guru per sekolah = NIK berbeda dari sekolah itu (satu guru yang ikut dua pelatihan dihitung satu bila filter "Semua pelatihan").

**11. Rekap guru**
- Menu Rekap Guru (`admin.php?page=kgtk-rekap-guru`): menampilkan seluruh guru yang telah mengikuti pelatihan beserta seluruh riwayat pelatihan yang pernah mereka ikuti.
- Filter pencarian teks (nama, NIK, NIP, atau asal sekolah), kabupaten/kota, pelatihan, dan jenis GTK.
- Kartu statistik: Total Guru unik, Total Pelatihan Diikuti (partisipasi), dan Guru yang mengikuti lebih dari 1 pelatihan.
- Tabel memuat identitas lengkap guru, NIK (dengan tombol salin 1-klik), link WhatsApp langsung, asal sekolah, jenis GTK, peran, serta daftar terperinci semua pelatihan yang pernah diikuti (nama pelatihan, tautan lembar absen, tanggal pelaksanaan, kelas, peran).
- Export Excel (`.xlsx`): mengunduh berkas spreadsheet rekap guru lengkap dengan seluruh riwayat pelatihan terformat rapi.

**12. Keamanan**
- `curl -X POST .../wp-json/kgtk/v1/admin/pelatihan` tanpa login: 401/403. Sama untuk `.../admin/pelatihan/ID/peserta` dan `.../admin/guru?nik=...`.
- Kirim `peran=Admin` ke endpoint daftar: ditolak. Kirim >30 permintaan daftar dalam 10 menit dari satu IP: 429.
- Perlindungan biodata tersimpan (`kgtk_guru`): Baris `kgtk_guru` hanya dibuat/diperbarui bila: (1) belum ada data guru untuk NIK tersebut (pendaftar pertama); (2) permintaan menyertakan verifikasi kontak yang sah (nomor HP atau email belajar.id); atau (3) dilakukan melalui aksi admin (`admin_tambah_peserta`). Pendaftaran publik tanpa verifikasi tetap dicatat di `kgtk_peserta` dengan status `terverifikasi = 0`, tanpa mengubah atau menimpa data tersimpan pada `kgtk_guru`.
- Verifikasi kredensial (`verify_guru_credentials`): hanya mencocokkan input terhadap baris `kgtk_guru` (tidak pernah mencocokkan ke baris `kgtk_peserta`). Menggunakan `hash_equals` dengan timing konsisten, serta menjaga string kosong agar tidak pernah cocok dengan string kosong.
- Riwayat pelatihan (`public_biodata_saya`): hanya menampilkan baris pendaftaran yang berstatus terverifikasi (`terverifikasi = 1`).
- Pembatasan laju verifikasi: maksimal 5 kegagalan per IP+NIK dan 20 per IP dalam jendela 15 menit untuk mencegah brute-force. Kunci transient disimpan dalam bentuk hash (md5).
- Penutupan kebocoran keberadaan NIK: Endpoint `public/cek-nik` tidak lagi mengembalikan `ada_biodata` atau penanda apa pun terkait apakah NIK pernah terdaftar di sistem. Untuk NIK yang belum terdaftar pada pelatihan tersebut dan tanpa verifikasi, respons selalu identik (`status: 'ok', guru: null`) baik NIK tersebut pernah ada di sistem maupun belum. Di antarmuka pengguna (`public.js`), blok verifikasi opsional selalu ditampilkan kepada semua pendaftar.
- **Risiko sisa**:
  1. Respons `"NIK sudah terdaftar pada pelatihan ini"` (status 409) tetap ada karena dibutuhkan untuk mencegah pendaftaran ganda pada pelatihan yang sama.
  2. Pendaftar pertama yang menggunakan NIK orang lain dapat "mengklaim" NIK tersebut karena sistem belum memiliki data pembanding sebelumnya pada `kgtk_guru`. Mitigasi: Admin yang berwenang dapat memeriksa dan menghapus pendaftaran yang tidak sah melalui menu admin pelatihan (fitur Hapus peserta), atau mendaftarkan/memperbarui biodata peserta yang sah melalui panel admin.

**13. Biodata & Riwayat Saya**
- Buka tombol "Lihat Biodata & Riwayat Saya" atau akses URL `?menu=biodata`.
- Verifikasi dengan NIK + HP (format `0812...`, `62812...`, atau `+62812...`): menampilkan kartu Biodata Saya dengan NIK tersamarkan (`3201************`) dan daftar Riwayat Pelatihan.
- Verifikasi dengan NIK + Email belajar.id (bebas huruf besar/kecil): menampilkan biodata dan riwayat.
- NIK tidak ada atau NIK ada tetapi kontak salah: menampilkan pesan kesalahan yang identik (`"Data tidak cocok. Periksa NIK dan nomor HP atau email belajar.id yang Anda daftarkan."`).
- Lakukan percobaan gagal ke-6 dari IP+NIK yang sama: diblokir status 429 (`"Terlalu banyak percobaan verifikasi yang gagal. Coba lagi 15 menit lagi."`). NIK lain dari IP yang sama tetap bisa dicoba hingga batas per IP (20 kali).
- Guru yang mengikuti lebih dari satu pelatihan: riwayat menampilkan seluruhnya dari tanggal terbaru ke terlama beserta nomor urut kelas/peran.
- Klik "Cetak": pratinjau cetak browser hanya menampilkan kartu hasil biodata & riwayat (tanpa elemen situs/navigasi). Klik "Selesai": menghapus data dari memori JS dan kembali ke tampilan awal.

## Fitur Peserta Ditetapkan (Kementerian)

Fitur untuk mengelola daftar peserta yang telah ditetapkan lebih dulu oleh Kementerian/Ditjen GTK sebelum pelatihan dimulai. Cukup salah satu dari data berikut yang tersedia: **Nama**, **NIK**, **No. HP**, atau **Email belajar.id** (sisanya boleh dikosongkan).

### 1. Alur Penggunaan
- **Saat Membuat Pelatihan**: Bagian *"Peserta yang ditetapkan (opsional)"* menyediakan baris isian dinamis (Nama, NIK, No. HP, Email belajar.id). Data disimpan dalam satu transaksi atomik bersamaan dengan pembuatan pelatihan. Jika ada baris yang tidak valid, seluruh pembuatan dibatalkan dengan pesan yang jelas.
- **Setelah Pelatihan Dibuat**: Tab *"Peserta Ditetapkan"* pada halaman detail/daftar hadir menyediakan manajemen data lengkap:
  - **Ringkasan & Progress Bar**: Persentase pendaftaran, jumlah total ditetapkan, sudah mendaftar, belum mendaftar, dan perlu konfirmasi.
  - **Kartu Statistik Interaktif & Filter**: Klik kartu status atau tombol filter (*Semua / Sudah mendaftar / Belum mendaftar / Perlu konfirmasi*) serta pencarian teks langsung (nama, NIK, HP, email belajar.id).
  - **Aksi Per Baris**:
    - **Ubah / Hapus**: Mengedit atau menghapus calon peserta ditetapkan.
    - **Konfirmasi**: Mengonfirmasi pencocokan berbasis nama tunggal.
    - **Hubungkan ke Peserta**: Menghubungkan baris manual ke peserta terdaftar yang belum terhubung.
    - **Lepas Hubungan**: Melepaskan tautan sehingga status kembali menjadi *"Belum mendaftar"*.
  - **Aksi Massal**:
    - **+ Tambah**: Menambah calon peserta satuan lewat modal dialog.
    - **Impor Excel/CSV**: Mengunggah berkas `.xlsx`, `.xls`, atau `.csv` dengan pencocokan kolom fleksibel (Nama, NIK, HP, Email belajar.id). Maksimal 2.000 baris per impor dan 5.000 peserta ditetapkan per pelatihan.
    - **Sinkronkan Ulang**: Menjalankan ulang pencocokan otomatis untuk seluruh peserta yang belum cocok.
    - **Salin Belum Mendaftar**: Menyalin daftar calon peserta yang belum mendaftar ke clipboard untuk dihubungi via WA/telepon.
    - **Export Excel**: Mengunduh berkas `.xlsx` status peserta ditetapkan dengan format sel teks eksplisit dan proteksi formula injection.
- **Indikator Visual**:
  - Kartu pelatihan di halaman utama menampilkan chip `"Ditetapkan: X/Y sudah mendaftar"`.
  - Tabel daftar hadir peserta memuat badge `"Ditetapkan"` pada peserta yang terhubung.

### 2. Aturan Pencocokan Otomatis
Pencocokan dijalankan secara otomatis saat:
1. Peserta mendaftar lewat form publik (`public_daftar`).
2. Admin mendaftarkan peserta lewat form admin (`admin_tambah_peserta`).
3. Admin menambahkan atau mengubah baris peserta ditetapkan (retroaktif).
4. Admin menekan tombol *"Sinkronkan ulang"*.

**Urutan Prioritas Pencocokan** (hanya terhadap baris ditetapkan yang belum terhubung pada pelatihan tersebut):
1. **NIK sama persis**: Status otomatis menjadi **Sudah mendaftar** (`cocok_dengan = 'nik'`).
2. **Email belajar.id sama**: Setelah dinormalkan ke huruf kecil, status menjadi **Sudah mendaftar** (`cocok_dengan = 'email'`).
3. **Nomor HP sama**: Setelah dinormalkan (variasi `0812...`, `62812...`, `+62812...` dianggap setara; nomor kosong tidak pernah dicocokkan), status menjadi **Sudah mendaftar** (`cocok_dengan = 'hp'`).
4. **Nama sama persis**: Setelah dinormalkan (huruf kecil dan perapian spasi) **HANYA bila tepat satu kandidat**. Hasilnya berstatus **Perlu konfirmasi** (`cocok_dengan = 'nama'`), bukan langsung sudah mendaftar, hingga admin mengonfirmasinya.

### 3. Batasan & Keamanan
- **Pencocokan nama hanya saran**: Kesamaan nama manusia rentan terhadap duplikasi (nama pasaran). Oleh karena itu statusnya ditandai *"Perlu konfirmasi"*.
- **Nama ambigu tidak dicocokkan otomatis**: Jika ditemukan lebih dari satu kandidat nama yang sama, sistem membiarkannya tetap belum cocok agar admin memutuskan tautan secara manual.
- **Relasi 1-ke-1**: Satu baris ditetapkan terhubung ke paling banyak satu peserta terdaftar dan sebaliknya.
- **Ketahanan pendaftaran**: Kegagalan pencocokan otomatis tidak pernah menggagalkan pendaftaran peserta.
- **Kerahasiaan data**: Respons endpoint publik (`public_daftar`, `public_cek_nik`, `public_biodata_saya`) 100% identik antara peserta ditetapkan dan peserta umum. Tidak ada informasi undangan yang bocor ke publik.
- **Isolasi kredensial**: Data undangan tidak pernah mencemari `kgtk_guru` dan tidak digunakan sebagai kredensial di `verify_guru_credentials`.

---

**14. Peserta Ditetapkan (Manual Test Checklist)**
- **Buat Pelatihan dengan Peserta Ditetapkan**:
  - Buka Buat Pelatihan, tambahkan 3 baris di bagian "Peserta yang ditetapkan": Baris 1 (Nama & NIK), Baris 2 (Hanya No. HP), Baris 3 (Hanya Email belajar.id). Simpan.
  - Periksa di halaman detail > tab "Peserta Ditetapkan": ketiga peserta muncul dengan status *"Belum mendaftar"*.
  - Buat pelatihan dengan NIK salah (kurang dari 16 digit): form menolak dan tidak ada data pelatihan setengah tersimpan (transaksi di-rollback).
- **Pencocokan Otomatis**:
  - Daftarkan peserta publik dengan NIK yang sama dengan Baris 1: status berubah menjadi *"Sudah mendaftar"* (dicocokkan lewat NIK).
  - Daftarkan peserta dengan No. HP format `+62 812...` yang cocok dengan Baris 2 (`0812...`): status berubah menjadi *"Sudah mendaftar"* (dicocokkan lewat No. HP).
  - Daftarkan peserta dengan Email belajar.id huruf besar yang cocok dengan Baris 3: status berubah menjadi *"Sudah mendaftar"* (dicocokkan lewat Email).
- **Pencocokan Nama & Konfirmasi**:
  - Tambah baris ditetapkan hanya berisi nama unik "Drs. Joko Widodo".
  - Daftarkan peserta publik dengan nama tersebut: status menjadi *"Perlu konfirmasi"* dengan badge peringatan kuning.
  - Klik tombol centang hijau (Konfirmasi): status berubah menjadi *"Sudah mendaftar"*.
- **Pencocokan Retroaktif**:
  - Daftarkan peserta publik baru (mis. NIK `3201010101018888`).
  - Setelah itu, admin membuka tab Peserta Ditetapkan dan klik "+ Tambah" dengan NIK `3201010101018888`: baris langsung tersimpan dan otomatis berstatus *"Sudah mendaftar"*.
- **Hubungkan Manual & Lepas Hubungan**:
  - Klik tombol rantai (Hubungkan): modal terbuka menampilkan peserta yang belum terhubung, pilih salah satu lalu simpan.
  - Klik tombol lepas hubungan: status peserta kembali menjadi *"Belum mendaftar"*.
- **Impor Excel & CSV**:
  - Siapkan file Excel/CSV dengan kolom acak (Nama, NIK, HP, Email belajar.id) dan baris kosong.
  - Unggah lewat tombol "Impor Excel/CSV", cocokkan kolom, lalu impor: data masuk rapi, baris kosong dilewati dan dilaporkan.
- **Salin Belum Mendaftar & Export Excel**:
  - Klik tombol "Salin Belum Mendaftar": data tersalin ke clipboard.
  - Klik tombol "Export Excel": file spreadsheet terunduh dengan NIK dan HP tersimpan sebagai teks, formula injection dinetralkan dengan `'`.
- **Hapus Data**:
  - Hapus peserta terdaftar di daftar hadir: baris undangan terkait kembali berstatus *"Belum mendaftar"*.
  - Hapus pelatihan: seluruh baris peserta ditetapkan pada pelatihan tersebut ikut terhapus bersih.

