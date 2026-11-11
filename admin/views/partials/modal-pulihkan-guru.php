<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Partial View: Modal Cari Guru di Master Data / Pulihkan Peserta yang Terhapus
 */
?>
<div class="kgtk-modal-overlay" id="kgtk-modal-pulihkan-guru" style="display:none;">
	<div class="kgtk-modal-dialog kgtk-ui" style="max-width:820px;width:95%;max-height:calc(100vh - 40px);display:flex;flex-direction:column;overflow:hidden;padding:22px 24px 18px;box-sizing:border-box;">
		
		<!-- Header Modal -->
		<div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:12px;border-bottom:1px solid #e2e8f0;flex-shrink:0;">
			<div style="display:flex;align-items:center;gap:12px;">
				<div class="kgtk-modal-icon is-primary" style="margin:0;width:40px;height:40px;border-radius:10px;flex-shrink:0;">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
						<circle cx="9" cy="7" r="4"></circle>
						<path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
						<path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
					</svg>
				</div>
				<div>
					<h3 class="kgtk-modal-title" style="margin:0;font-size:16.5px;color:#0f172a;">Master Data Guru (Belum Masuk Pelatihan Ini)</h3>
					<p class="kgtk-muted" style="margin:2px 0 0;font-size:12px;">Temukan guru yang pernah terdaftar atau tidak sengaja terhapus untuk didaftarkan kembali</p>
				</div>
			</div>
			<button type="button" class="kgtk-search-clear" id="kgtk-btn-pulihkan-close" style="position:static;width:28px;height:28px;line-height:26px;font-size:20px;" title="Tutup">&times;</button>
		</div>

		<!-- Pencarian Cepat di Modal -->
		<div style="margin:14px 0 10px;flex-shrink:0;">
			<div class="kgtk-search-wrap" style="width:100%;">
				<span class="kgtk-search-icon">&#128269;</span>
				<input type="search" id="kgtk-pulihkan-search" class="kgtk-input" placeholder="Cari nama guru, NIK, atau asal sekolah..." style="width:100%;height:38px;font-size:13px;">
			</div>
		</div>

		<!-- Area Pesan Status / Error -->
		<div id="kgtk-pulihkan-status" class="kgtk-alert" style="margin-bottom:10px;flex-shrink:0;" hidden></div>

		<!-- Tabel Daftar Guru (Scrollable) -->
		<div style="flex:1 1 auto;min-height:200px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:8px;">
			<div id="kgtk-pulihkan-loading" style="padding:40px;text-align:center;color:#64748b;font-size:13.5px;">
				<span style="display:inline-block;animation:spin 1s linear infinite;">&#8987;</span> Memuat daftar guru dari Master Data...
			</div>
			<div id="kgtk-pulihkan-empty" style="padding:40px;text-align:center;color:#64748b;font-size:13.5px;display:none;">
				Tidak ada guru di Master Data yang belum terdaftar di pelatihan ini.
			</div>
			<table class="kgtk-table" id="kgtk-tbl-pulihkan" style="width:100%;font-size:12.5px;display:none;">
				<thead>
					<tr style="background:#f8fafc;position:sticky;top:0;z-index:1;">
						<th style="width:40px;text-align:center;">No</th>
						<th>Nama Lengkap</th>
						<th style="width:150px;">NIK</th>
						<th>Asal Sekolah / Instansi</th>
						<th style="width:110px;">Peran Terakhir</th>
						<th style="width:140px;text-align:center;">Aksi</th>
					</tr>
				</thead>
				<tbody id="kgtk-pulihkan-tbody"></tbody>
			</table>
		</div>

		<!-- Footer Dialog -->
		<div class="kgtk-modal-actions" style="margin-top:12px;padding-top:10px;border-top:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;flex-shrink:0;">
			<div style="font-size:12px;color:#64748b;">
				<span id="kgtk-pulihkan-count">0</span> guru tersedia di Master Data
			</div>
			<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-btn-pulihkan-batal">Tutup</button>
		</div>
	</div>
</div>

