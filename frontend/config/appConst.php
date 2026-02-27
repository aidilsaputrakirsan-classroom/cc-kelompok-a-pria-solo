<?php

return
[
	'witels' => ['Regional' => 'Regional', 'Balikpapan' => 'Witel Balikpapan', 'Kalbar' => 'Witel Kalbar', 'Kalselteng' => 'Witel Kalselteng', 'Kalsel' => 'Witel Kalsel', 'Kalteng' => 'Witel Kalteng', 'Kaltimtara' => 'Witel Kaltimtara', 'Kaltim' => 'Witel Kaltim', 'Kaltara' => 'Witel Kaltara'],
	
	'tahuns' => ['2019' => 'Tahun 2019', '2020' => 'Tahun 2020', '2021' => 'Tahun 2021', '2022' => 'Tahun 2022', '2023' => 'Tahun 2023', '2024' => 'Tahun 2024', '2025' => 'Tahun 2025', '2026' => 'Tahun 2026'],
	
	'segmens' => ['RES' => 'Segmen Enterprise SOE', 'RPS' => 'Segmen Enterprise Private', 'REPS' => 'Segmen Enterprise SOE & Private', 'RGS' => 'Segmen Government', 'SME' => 'Segmen SME'],
	
	'pstatus' => ['10' => '1) Request For Proposal', '15' => '2) Proposal On Going Process', '20' => '3) Request Need Revision', '30' => '4) RAB and Proposal Indicatif', '50' => '5) Review Proposal Indicatif', '60' => '6) Proposal Indicatif Need Revision', '70' => '7) Collecting Doc and P1', '90' => '8) Waiting for Kickoff', '100' => '9) Post Kickoff - Need Revision', '120' => '10) Post Kickoff - Need OBL', '170' => '11) RAB Final', '212' => '12) Draft KB', '214' => '13) Review KB', '216' => '14) Sirkulir KB', '218' => '15) Input Quote', '220' => '16) Draft KL', '221' => '17) Review KL', '222' => '18) Review Legal-Mitra', '223' => '19) Verifikasi Dok OBL', '224' => '20) Sirkulir Internal', '225' => '21) Sirkulir Mitra', '226' => '22) Closing SM', '227' => '23) Input Order', '228' => '24) OBL Done', '299' => '99) DROPPED'],
	
	'dstatus' => ['130' => '1.1) OBL - Simple - Start', '131' => '1.2) OBL - Simple - P2 Bakal Mitra', '132' => '1.3) OBL - Simple - P3 SPPH', '133' => '1.4) OBL - Simple - P4 Penjelasan', '134' => '1.5) OBL - Simple - SPH', '140' => '2.1) OBL - Multi - Start', '141' => '2.2) OBL - Multi - Contest', '142' => '2.3) OBL - Multi - P2 Bakal Mitra', '143' => '2.4) OBL - Multi - P3 SPPH', '144' => '2.5) OBL - Multi - P4 Penjelasan', '145' => '2.6) OBL - Multi - SPH', '146' => '2.7) OBL - Multi - Skoring Contest', '150' => '3) OBL - Negosiasi', '155' => '4) OBL - P6', '160' => '5) OBL - SKM Ketetapan Mitra', '170' => '6) OBL - Nego Done (wait KB)', '212' => '7) Draft KB', '214' => '8) Review KB', '216' => '9) Sirkulir KB', '218' => '10) Input Quote', '220' => '11) Draft KL', '221' => '12) Review KL', '222' => '13) Review Legal-Mitra', '223' => '14) Verifikasi Dok OBL', '224' => '15) Sirkulir Internal', '225' => '16) Sirkulir Mitra', '226' => '17) Closing SM', '227' => '18) Input Order', '228' => '19) OBL Done'],
	
	'statusColors' => [
		'20' => 'secondary',
		'70' => 'secondary',
		'10' => 'warning',
		'15' => 'warning',
		'30' => 'warning',
		'60' => 'warning',
		'100' => 'warning',
		'50' => 'primary',
		'90' => 'primary',
		'110' => 'success',
		'120' => 'success',
		'170' => 'warning',
		'181' => 'warning',
		'182' => 'warning',
		'212' => 'warning',
		'214' => 'success',
		'216' => 'warning',
		'218' => 'warning',
		'220' => 'success',
		'221' => 'success',
		'222' => 'success',
		'223' => 'success',
		'224' => 'success',
		'225' => 'success',
		'226' => 'success',
		'227' => 'warning',
		'228' => 'success',
		'240' => 'danger',
		'242' => 'danger',
		'244' => 'danger',
		'299' => 'dark'
	],
	
	'officer_obl' => [
		'960235' => '960235_SEPTIAN ZAKARIA_OFF 2 BIDDING & OUTBOND LOGISTIC',
	],
	
	'mgr_obl' => [
		'850057' => '850057_YESRU MARDANIATI_MGR BIDDING MGMT & BUSINESS SUPPORT',
	],
	
	'sm_rso' => [
		'810033' => '810033_SEPTIANA RAHMAYANTI_SM REGIONAL SERVICE, DELIVERY & ASSURANCE REG IV',
		'800075' => '800075_BUDHI SUPRIYONO_SM REGIONAL SOLUTION & OPERATION REG IV',
	],
		
	'days' => [
		'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu',
	],
	
	'tipe_project' => [
		'OwnChannel' => 'Own Channel / IBL Only', 'GTMA' => 'GTMA / Combined OBL-OwnChannel', 'NGTMA' => 'New GTMA',
	],
	
	'skema_bisnis' => [
		'sewa_murni' => 'Sewa Murni', 'beli_putus' => 'Beli Putus',
	],
	
	'term_of_payment' => [
		'bulanan' => 'Bulanan', 'otc' => 'One Time Charge', 'bulanan_otc' => 'Bulanan-OTC', 'termin' => 'Termin',
	],
	
	'jenis_spk' => [
		'nota_pesanan' => 'Nota Pesanan', 'kontrak_layanan' => 'Kontrak Layanan', 'work_order' => 'Work Order', 'surat_pesanan' => 'Surat Pesanan',
	],
	
	'tipe_spk' => [
		'psb' => 'Pasang Baru', 'amandemen' => 'Amandemen', 'perpanjangan' => 'Perpanjangan', 'amd_perpanjangan' => 'Amandemen-Perpanjangan',
	],
	
	'tipe_spk_wording' => [
		'psb' => 'Pengadaan ', 'amandemen' => 'Amandemen pengadaan ', 'perpanjangan' => 'Perpanjangan pengadaan ', 'amd_perpanjangan' => 'Amandemen Perpanjangan pengadaan ',
	],
];