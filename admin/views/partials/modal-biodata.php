<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Partial View: Modal Biodata Peserta
 */
?>
<div class="kgtk-modal-overlay" id="kgtk-modal-biodata" style="display:none;">
	<div class="kgtk-modal-dialog kgtk-ui" style="max-width:580px;width:95%;">
		<div class="kgtk-modal-top" style="align-items:flex-start;">
			<div class="kgtk-modal-icon is-primary">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
				</svg>
			</div>
			<div class="kgtk-modal-content" style="width:100%;">
				<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
					<div>
						<h3 class="kgtk-modal-title" id="kgtk-bio-nama" style="margin:0;font-size:17px;">Biodata Peserta</h3>
						<p class="kgtk-muted" id="kgtk-bio-sub" style="margin:2px 0 10px;font-size:12.5px;">Detail lengkap pendaftaran peserta</p>
					</div>
					<button type="button" class="kgtk-search-clear" id="kgtk-btn-bio-close" style="position:static;width:28px;height:28px;line-height:26px;font-size:20px;" title="Tutup">&times;</button>
				</div>
				<div id="kgtk-bio-body" style="max-height:62vh;overflow-y:auto;padding-right:2px;"></div>
			</div>
		</div>
		<div class="kgtk-modal-actions" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
			<div style="display:flex;gap:8px;">
				<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-btn-bio-cetak" title="Cetak lembar biodata resmi">&#128424;&#65039; Cetak</button>
				<a href="#" class="kgtk-btn kgtk-btn--navy" id="kgtk-btn-bio-pdf" target="_blank" title="Unduh lembar PDF biodata">&#128229; Download PDF</a>
				<button type="button" class="kgtk-btn kgtk-btn--yellow" id="kgtk-btn-bio-edit" title="Edit data peserta ini">&#9998; Edit Data</button>
			</div>
			<button type="button" class="kgtk-btn kgtk-btn--ghost" id="kgtk-btn-bio-batal">Tutup</button>
		</div>
	</div>
</div>
