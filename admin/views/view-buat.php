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

	<form id="kgtk-form-buat" class="kgtk-card kgtk-form" method="post" novalidate>
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
			<span>Batas Waktu / Deadline Pendaftaran</span>
			<input type="datetime-local" class="kgtk-input" name="deadline_pendaftaran">
			<small>Opsional. Tentukan batas akhir pendaftaran guru. Jika diisi, guru yang mendaftar melewati waktu ini otomatis ditolak dengan pemberitahuan pendaftaran ditutup.</small>
		</label>

		<div class="kgtk-row2">
			<label class="kgtk-field">
				<span>Lokasi / Tempat Pelatihan</span>
				<input type="text" class="kgtk-input" name="lokasi" maxlength="255" placeholder="mis. Hotel CK / Zoom Meeting / Aula Gedung">
				<small>Nama gedung, hotel, platform, atau ruang kegiatan.</small>
			</label>
			<label class="kgtk-field">
				<span>Kab/Kota Tempat Pelatihan <b>*</b></span>
				<select class="kgtk-input" name="kabkota" required>
					<option value="">-- Pilih Kab/Kota Pelatihan --</option>
					<?php foreach ( KGTK_Helpers::kabkota_pelatihan_list() as $kab ) : ?>
						<option value="<?php echo esc_attr( $kab ); ?>"><?php echo esc_html( $kab ); ?></option>
					<?php endforeach; ?>
				</select>
				<small>Kab/kota tempat pelatihan dijalankan. Tercetak di tanggal tanda tangan biodata peserta.</small>
			</label>
		</div>

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
			<div class="kgtk-kelas" id="kgtk-kelas-list"><?php echo KGTK_Admin::kelas_row( 'Kelas A', 30 ); ?></div>
			<template id="kgtk-kelas-tpl"><?php echo KGTK_Admin::kelas_row( '', 30 ); ?></template>
			<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-kelas-add">+ Tambah kelas</button>
		</fieldset>

		<fieldset class="kgtk-field">
			<legend>Panitia Pelatihan (opsional)</legend>
			<small>Tambahkan susunan panitia untuk pelatihan ini. Anda dapat memilih dari daftar panitia yang sudah tersimpan atau mengetik panitia baru secara langsung. Panitia tidak memakai kuota slot peserta dan akan otomatis tercatat di lembar hadir panitia.</small>

			<div class="kgtk-panitia-table-wrap" id="kgtk-panitia-wrap" style="margin-top:10px;display:none;">
				<table class="kgtk-table kgtk-table--panitia" id="kgtk-panitia-table">
					<thead>
						<tr>
							<th style="width:46px;text-align:center;">No</th>
							<th style="width:230px;">Nama Lengkap Panitia *</th>
							<th style="width:190px;">Tugas / Jabatan *</th>
							<th style="width:200px;">Asal Instansi</th>
							<th style="width:140px;">No. HP / WhatsApp</th>
							<th style="width:140px;">NIK (opsional)</th>
							<th style="width:46px;text-align:center;">Aksi</th>
						</tr>
					</thead>
					<tbody id="kgtk-panitia-tbody">
						<!-- Baris panitia akan ditambahkan di sini secara dinamis -->
					</tbody>
				</table>
			</div>
			<div id="kgtk-panitia-empty" class="kgtk-muted" style="padding:16px 18px;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:8px;font-size:13px;display:flex;align-items:center;gap:12px;margin-top:10px;">
				<span style="font-size:20px;">&#128100;</span>
				<span>Belum ada panitia yang ditambahkan untuk pelatihan ini. Klik tombol di bawah untuk memilih dari data tersimpan atau menambahkan baru.</span>
			</div>

			<div style="margin-top:10px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
				<button type="button" class="kgtk-btn kgtk-btn--primary" id="kgtk-btn-pilih-panitia" onclick="if(window.kgtkBukaModalPanitia){window.kgtkBukaModalPanitia();}return false;">&#128100; Pilih dari Data Panitia Tersimpan</button>
				<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-btn-tambah-panitia-manual" onclick="if(window.kgtkTambahPanitiaRow){window.kgtkTambahPanitiaRow();}return false;">+ Tambah Baris Panitia Baru</button>
			</div>
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

	<!-- Template Baris Panitia Baru -->
	<template id="kgtk-panitia-row-tpl">
		<tr class="kgtk-panitia-row">
			<td class="c-no" style="text-align:center;"><span class="kgtk-pan-num">1</span></td>
			<td>
				<input type="text" class="kgtk-input kgtk-pan-nama" name="panitia_nama[]" placeholder="Nama lengkap panitia *" required maxlength="100">
			</td>
			<td>
				<input type="text" class="kgtk-input kgtk-pan-jabatan" name="panitia_jabatan[]" list="kgtk-jabatan-panitia-options" placeholder="Ketua / Sekretaris / Anggota" value="Panitia Pelaksana" maxlength="100">
			</td>
			<td>
				<input type="text" class="kgtk-input kgtk-pan-nik" name="panitia_nik[]" placeholder="16 digit (opsional)" inputmode="numeric" maxlength="16">
			</td>
			<td>
				<input type="text" class="kgtk-input kgtk-pan-hp" name="panitia_hp[]" placeholder="08xxxxxxxxxx" maxlength="20">
			</td>
			<td>
				<input type="text" class="kgtk-input kgtk-pan-instansi" name="panitia_instansi[]" placeholder="Asal instansi / unit kerja" maxlength="200">
			</td>
			<td style="text-align:center;">
				<button type="button" class="kgtk-iconbtn kgtk-iconbtn--del" data-del-panitia-row title="Hapus baris">&times;</button>
			</td>
		</tr>
	</template>
	<datalist id="kgtk-jabatan-panitia-options">
		<?php foreach ( KGTK_Helpers::JABATAN_PANITIA as $jp ) : ?>
			<option value="<?php echo esc_attr( $jp ); ?>"></option>
		<?php endforeach; ?>
	</datalist>

	<!-- Modal Pilih Panitia Tersimpan -->
	<div class="kgtk-modal-overlay" id="kgtk-modal-pilih-panitia" style="display:none;">
		<div class="kgtk-modal-dialog kgtk-ui" style="max-width:680px;width:95%;">
			<div class="kgtk-modal-top" style="align-items:flex-start;">
				<div class="kgtk-modal-icon is-primary">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
					</svg>
				</div>
				<div class="kgtk-modal-content" style="width:100%;">
					<div style="display:flex;justify-content:space-between;align-items:flex-start;">
						<div>
							<h3 class="kgtk-modal-title" style="margin:0;font-size:17px;">Pilih Panitia dari Data Tersimpan</h3>
							<p class="kgtk-muted" style="margin:2px 0 10px;font-size:12.5px;">Centang nama panitia yang ingin dimasukkan ke dalam susunan kepanitiaan pelatihan ini.</p>
						</div>
						<button type="button" class="kgtk-search-clear" id="kgtk-btn-modal-panitia-close" style="position:static;width:28px;height:28px;font-size:20px;" title="Tutup">&times;</button>
					</div>

					<div class="kgtk-filter-bar" style="margin-bottom:12px;">
						<input type="text" class="kgtk-input" id="kgtk-modal-panitia-search" placeholder="Cari nama, NIK, atau instansi panitia..." style="width:100%;height:36px;font-size:13px;">
					</div>

					<div id="kgtk-modal-panitia-list" style="max-height:340px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:8px;padding:6px;display:flex;flex-direction:column;gap:6px;">
						<?php if ( empty( $panitia_tersimpan ) ) : ?>
							<div class="kgtk-muted" style="text-align:center;padding:24px 10px;font-size:13px;" id="kgtk-modal-panitia-empty-msg">
								Belum ada data panitia yang tersimpan di sistem.<br>Silakan gunakan tombol &ldquo;+ Tambah Baris Panitia Baru&rdquo; untuk mengisi langsung.
							</div>
						<?php else : ?>
							<?php foreach ( $panitia_tersimpan as $pan ) :
								$pan_json = wp_json_encode( array(
									'id'       => (int) $pan->id,
									'nama'     => $pan->nama,
									'jabatan'  => $pan->jabatan ?: 'Panitia Pelaksana',
									'nik'      => $pan->nik,
									'nip'      => $pan->nip,
									'nuptk'    => $pan->nuptk,
									'hp'       => $pan->hp,
									'instansi' => $pan->instansi,
									'kabkota'  => $pan->kabkota,
									'email'    => $pan->email,
									'npwp'     => $pan->npwp,
								) );
								$search_str = strtolower( $pan->nama . ' ' . $pan->nik . ' ' . $pan->instansi . ' ' . $pan->jabatan . ' ' . $pan->hp );
							?>
								<label class="kgtk-panitia-pick-item" data-search="<?php echo esc_attr( $search_str ); ?>" data-panitia="<?php echo esc_attr( $pan_json ); ?>" style="display:flex;align-items:center;gap:10px;padding:8px 12px;border:1px solid #f1f5f9;background:#fff;border-radius:6px;cursor:pointer;transition:all .15s ease;">
									<input type="checkbox" class="kgtk-panitia-chk" value="<?php echo (int) $pan->id; ?>">
									<div style="flex:1;">
										<div style="font-weight:600;font-size:13.5px;color:#0b2d56;"><?php echo esc_html( $pan->nama ); ?></div>
										<div class="kgtk-muted" style="font-size:11.5px;display:flex;gap:8px;flex-wrap:wrap;margin-top:2px;">
											<span>&#128188; <?php echo esc_html( $pan->jabatan ?: 'Panitia Pelaksana' ); ?></span>
											<?php if ( $pan->instansi ) : ?>
												<span>&middot; &#127979; <?php echo esc_html( $pan->instansi ); ?></span>
											<?php endif; ?>
											<?php if ( $pan->hp ) : ?>
												<span>&middot; &#128241; <?php echo esc_html( $pan->hp ); ?></span>
											<?php endif; ?>
											<?php if ( $pan->nik ) : ?>
												<span>&middot; NIK: <?php echo esc_html( $pan->nik ); ?></span>
											<?php endif; ?>
										</div>
									</div>
								</label>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<div class="kgtk-modal-actions" style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;">
				<div class="kgtk-muted" id="kgtk-modal-panitia-count" style="font-size:12.5px;">0 panitia dipilih</div>
				<div style="display:flex;gap:8px;">
					<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-btn-modal-panitia-batal">Batal</button>
					<button type="button" class="kgtk-btn kgtk-btn--navy" id="kgtk-btn-modal-panitia-submit">Gunakan Panitia Terpilih</button>
				</div>
			</div>
		</div>
	</div>
</div>
