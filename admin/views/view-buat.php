<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * View: Form Buat Pelatihan
 *
 * @var string $kode Kode acak pelatihan default.
 */
?>
<div class="wrap kgtk-wrap kgtk-ui">
	<?php KGTK_Admin::head( 'Buat pelatihan', 'Isi data pelatihan, lalu bagikan link pendaftarannya.' ); ?>

	<form id="kgtk-form-buat" class="kgtk-card kgtk-form" novalidate>
		<div id="kgtk-form-error" class="kgtk-alert" role="alert" hidden></div>

		<label class="kgtk-field">
			<span>Nama pelatihan <b>*</b></span>
			<input type="text" class="kgtk-input" name="nama" maxlength="200" required>
		</label>

		<label class="kgtk-field">
			<span>Penyelenggara</span>
			<input type="text" class="kgtk-input" name="penyelenggara" maxlength="200">
		</label>

		<div class="kgtk-row2">
			<label class="kgtk-field">
				<span>Tanggal mulai <b>*</b></span>
				<input type="date" class="kgtk-input" name="tanggal_mulai" required>
			</label>
			<label class="kgtk-field">
				<span>Tanggal selesai</span>
				<input type="date" class="kgtk-input" name="tanggal_selesai">
				<small>Kosongkan jika sama dengan tanggal mulai.</small>
			</label>
		</div>

		<label class="kgtk-field">
			<span>Lokasi</span>
			<input type="text" class="kgtk-input" name="lokasi" maxlength="255">
		</label>

		<div class="kgtk-row2">
			<label class="kgtk-field">
				<span>Jabatan Penandatangan</span>
				<select class="kgtk-input" name="tipe_pj">
					<option value="Penanggung Jawab">Penanggung Jawab</option>
					<option value="Ketua">Ketua</option>
				</select>
				<small>Pilihan jabatan yang tercetak di atas tanda tangan daftar hadir.</small>
			</label>
			<label class="kgtk-field">
				<span>Nama Penandatangan</span>
				<input type="text" class="kgtk-input" name="penanggung_jawab" maxlength="150" placeholder="Nama beserta gelar">
				<small>Tercetak di bagian bawah daftar hadir. Jika kosong, diberi titik-titik untuk diisi tangan.</small>
			</label>
		</div>

		<label class="kgtk-field">
			<span>Keterangan</span>
			<textarea class="kgtk-input" name="keterangan" rows="4" maxlength="2000"></textarea>
		</label>

		<fieldset class="kgtk-field">
			<legend>Kelas dan slot <b>*</b></legend>
			<small>Ketik nama kelas sesuai kebutuhan dan jumlah slotnya. Pendaftar otomatis masuk ke kelas paling atas yang masih punya slot; kelas berikutnya terisi setelah kelas sebelumnya penuh. Gunakan tombol panah untuk mengatur urutan.</small>
			<div class="kgtk-kelas" id="kgtk-kelas-list"><?php echo KGTK_Admin::kelas_row( '', 30 ); ?></div>
			<template id="kgtk-kelas-tpl"><?php echo KGTK_Admin::kelas_row( '', 30 ); ?></template>
			<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-kelas-add">+ Tambah kelas</button>
		</fieldset>

		<fieldset class="kgtk-field">
			<legend>Peserta yang ditetapkan (opsional)</legend>
			<small>Sebagian pelatihan pesertanya ditetapkan oleh Kementerian. Anda dapat memasukkan data awal peserta di sini. Cukup isi salah satu dari: Nama, NIK, No. HP, atau Email belajar.id (sisanya boleh kosong). Impor massal dari Excel/CSV juga dapat dilakukan pada tab Peserta Ditetapkan di halaman detail setelah pelatihan dibuat.</small>
			<div class="kgtk-undangan-builder" id="kgtk-undangan-list"></div>
			<template id="kgtk-undangan-tpl"><?php echo KGTK_Admin::undangan_row_builder(); ?></template>
			<div style="margin-top:10px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
				<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-undangan-add">+ Tambah baris peserta</button>
				<span class="kgtk-muted" style="font-size:12px;">Isi salah satu saja, sisanya boleh kosong. Baris yang semuanya kosong akan diabaikan.</span>
			</div>
		</fieldset>

		<label class="kgtk-field">
			<span>Kode pelatihan</span>
			<input type="text" class="kgtk-input" name="kode" value="<?php echo esc_attr( $kode ); ?>" maxlength="20" pattern="[A-Za-z0-9-]{3,20}" style="text-transform:uppercase">
			<small>3-20 karakter: huruf, angka, tanda hubung. Harus unik.</small>
		</label>

		<div class="kgtk-form-actions">
			<a class="kgtk-btn kgtk-btn--ghost" href="<?php echo esc_url( KGTK_Admin::page_url( 'pelatihan' ) ); ?>">Batal</a>
			<button type="submit" class="kgtk-btn">Simpan pelatihan</button>
		</div>
	</form>
</div>
