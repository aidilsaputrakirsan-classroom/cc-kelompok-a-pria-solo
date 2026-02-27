<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'name' => 'Bhakti Unggul Teknovasi',
                'address' => "Jl. Telekomunikasi No.1, Sukapura, Dayeuhkolot\nKabupaten Bandung, Jawa Barat 40257"
            ],
            [
                'name' => 'CENTRA MITRA TELEMATIKA',
                'address' => "Komplek Ruko Biru\nJl. KH. Abdullah Syafei No.50-E\nBukit Duri, Tebet\nJakarta Selatan 12840 Telp. 021-83789234"
            ],
            [
                'name' => 'COMMTECH AGUNG',
                'address' => "Jl. Bukit siguntang no 6A\nMedan 20238\nTelp 061-6632009 fax 6632002"
            ],
            [
                'name' => 'DAYAMITRA TELEKOMUNIKASI',
                'address' => "Komplek Telkom Landmark Tower, Tower II Lantai 26\nJalan Jend. Gatot Subroto Kav 52\nJakarta 12710"
            ],
            [
                'name' => 'DIGISERVE',
                'address' => "Eighty Eight @Kasablanka, Lantai 35,\nJl. Casablanca Raya Kav 88, Jakarta Selatan"
            ],
            [
                'name' => 'GRAHA SARANA DUTA',
                'address' => "GM Telkom Property Regional 6 Kalimantan\nJl.MT.Haryono No.169 Balikpapan"
            ],
            [
                'name' => 'INDOSAT',
                'address' => "Jl. Medan Merdeka Barat No. 21\nJakarta 10110"
            ],
            [
                'name' => 'SWADHARMA SARANA INFORMATIKA',
                'address' => "Bellagio Office Park, Unit OUG 31-32 JL. Mega Kuningan Barat Kav E4.3\nKawasan Mega Kuningan, Setiabudi Jakarta Selatan 12950"
            ],
            [
                'name' => 'INFOMEDIA NUSANTARA',
                'address' => "Jl. RS. Fatmawati 77-81\nJakarta 12150 Indonesia\nTelp. 021-7201221 Fax. 021-7201226"
            ],
            [
                'name' => 'INDONESIA COMNET PLUS',
                'address' => "PLN Icon Plus Jl. Gatot Subroto, RT.6/RW.1, Kuningan Bar.,\nKec. Mampang Prpt., Kota Jakarta Selatan 12710"
            ],
            [
                'name' => 'INFOMEDIA SOLUSI HUMANIKA',
                'address' => "Jl. R.S Fatmawati No. 77-81, Jakarta 12150\nTelp. 085220532932"
            ],
            [
                'name' => 'JALIN MAYANTARA INDONESIA',
                'address' => "Komp Ruko Grand Bintaro, Blok B Kav.13\nJl. Bintaro Permai Raya No.1 RT.012 RW.09\nKel. Bintaro Kec. Pesanggrahan, Jakarta Selatan"
            ],
            [
                'name' => 'KOPEGTEL BALIKPAPAN',
                'address' => "Jl. Telepon No. 1 Balikpapan 76114\nTelp. 0542-876650 Fax. 0542-876640"
            ],
            [
                'name' => 'KOPEGTEL BANJARMASIN',
                'address' => "Jl. Hasan Basri Komplek Ruko Kayutangi Permai Blok E No.6\nBanjarmasin 70123\nTlp. 0511-3307868"
            ],
            [
                'name' => 'KOPEGTEL PONTIANAK',
                'address' => "Jl. Teuku Umar No.16 B\nPontianak 78117 Kalimantan Barat\nTel. 0561-743377 Fax. 0561-734943"
            ],
            [
                'name' => 'KOPERASI METROPOLITAN',
                'address' => "Menara Multimedia 1st FI\nJl. Kebon Sirih No. 10-12\nJakarta Pusat 10110"
            ],
            [
                'name' => 'KOPKAR SMART MEDIA',
                'address' => "Jl. Ketintang No. 156\nTelp. 031-8274187\nSurabaya - 60231"
            ],
            [
                'name' => 'METRA DIGITAL MEDIA',
                'address' => "The Telkom Hub\nTelkom Landmark Tower Lt 18\nJl. Jend Gatot Soebroto Kav. 52\nJakarta Selatan"
            ],
            [
                'name' => 'METRA-NET',
                'address' => "PT Metra-Net\nGedung Mulia Business Park C 58-60\nPancoran - Jakarta Selatan 12780\nTelp. 021-79187250 Faks. 021-79187252"
            ],
            [
                'name' => 'MITRA INOVASI JAYANTARA',
                'address' => "Komp Ruko Grand Bintaro, Blok B Kav. 13\nJl. Bintaro Permai Raya No. 1 RT.012 RW.09\nKel. Bintaro Kec. Pesanggrahan, Jakarta Selatan\nTelp. 021-73883676"
            ],
            [
                'name' => 'NUTECH INTEGRASI',
                'address' => "Jl. Tanjung Barat Raya no. 17 Pejaten Timur,\nPasar Minggu Jakarta Selatan"
            ],
            [
                'name' => 'PINS INDONESIA',
                'address' => "Telkom Landmark Tower\nJl. Jendral Gatot Subroto Kav. 52, Lt.42\nJakarta Selatan 12710 – Indonesia"
            ],
            [
                'name' => 'POINTER',
                'address' => "Plasa Telkom Group 2nd Floor\nJl. RS Fatmawati No.65 Cilandak Barat\nJakarta Selatan 12430,\nTelp. 021-227-67982"
            ],
            [
                'name' => 'PUTRA BISTEL SOLUSINDO',
                'address' => "Jl. Awang Long No. 8\nSamarinda 75121"
            ],
            [
                'name' => 'SEMPURNA MITRA HUTAMA',
                'address' => "Jl. Griya Agung No.25 RT.2/ RW.20\nSunter Agung, Tj. Priok, Kota Jakarta Utara\nDaerah Khusus Ibukota Jakarta 14410"
            ],
            [
                'name' => 'SIGMA CIPTA CARAKA',
                'address' => "Telkom Landmark Tower 23rd Floor\nJl. Jend. Gatot Subroto Kav. 52\nJakarta 12710"
            ],
            [
                'name' => 'SISTELINDO MITRALINTAS',
                'address' => "GP Plaza, Slipi, Palmerah, Gelora 2 No.1, RT.1/RW.1, Gelora,\nKecamatan Tanah Abang, Kota Jakarta Pusat,\nDaerah Khusus Ibukota Jakarta 10270"
            ],
            [
                'name' => 'SKILL NUSA INFOTAMA',
                'address' => "Jl. Gajah No.21\nBandung - West Java\nIndonesia 40264\nT: +62227318113 ; +62227325253\nF: +62227308280\nwww.skillnusa.co.id"
            ],
            [
                'name' => 'SUMBER SOLUSINDO HITECH',
                'address' => "Komp. Jembatan Lima Indah 15 E/20\nJl. K. H. Moch Mansyur Jakarta 10140\nTelp. 021-6306807\nFax.021-6316168"
            ],
            [
                'name' => 'SYNETCOM LINTAS BUANA',
                'address' => "Perkantoran Grand Puri Niaga Blok K6 No. 5K,\nJl. Puri Kencana Raya, Jakarta Barat 11611\nPhone: +62-21-5835-1632 Fax: +62-21-5835-1633"
            ],
            [
                'name' => 'TELKOM SATELIT INDONESIA',
                'address' => "Telkom Landmark Tower lt.21\nJl. Gatot Subroto Kav 52\nJakarta Selatan"
            ],
            [
                'name' => 'TELKOM AKSES',
                'address' => "Gedung Telkom Jakarta Barat\nJl. S. Parman Kav. 8\nJakarta Barat 11440"
            ],
            [
                'name' => 'TELKOM PRIMA CIPTA CERTIFIA',
                'address' => "Jl. Picung 47 Sukasari\nBandung"
            ],
            [
                'name' => 'TELKOMSEL',
                'address' => "PT Telekomunikasi Seluler\nJl. Gatot Subroto No.Kav. 52, RT.6/RW.1, Kuningan Bar.,\nKec. Mampang Prapatan, Kota Jakarta Selatan,\nDaerah Khusus Ibukota Jakarta 12710"
            ],
            [
                'name' => 'WAVE COMMUNICATION INDONESIA',
                'address' => "Komp. Golden Plaza Blok G-03\nJl. RS. Fatmawati Raya No. 15\nJakarta Selatan 12420 - Indonesia\nTelp. 021-29124650 Fax. 021-29124652"
            ],
            [
                'name' => 'SISINDOKOM LINTASBUANA',
                'address' => "Graha Sisindokom, Jl. Penataran No.2, Pegangsaan\nJakarta Pusat 10320"
            ],
            [
                'name' => 'PERSADA SOKKA TAMA',
                'address' => "Persada Office Park B Building 7 th Floor\nJl. KH.Noer Ali No.3A\nKalimalang – Bekasi 17144"
            ],
            [
                'name' => 'TRIMITRA KOLABORASI MANDIRI',
                'address' => "Jl. Premier Park No. 10 Blok AA Rt 09/ Rw 03 Kel. Cikokol, Kec. Tangerang,\nKota Tangerang Banten 15117"
            ],
            [
                'name' => 'MAHARDIKA TEKNOTAMA INTEGRASI',
                'address' => "OFFICE 8 LEVEL 18A, JL.SENOPATI NO. 8B\nDesa/Kelurahan Senayan, Kec. Kebayoran Baru,\nKota Adm. Jakarta Selatan, Provinsi DKI Jakarta\nKode Pos: 12190"
            ],
            [
                'name' => 'PUTRA ARGA BINANGUN',
                'address' => "Cyber Building I, 6th floor. Jl. Kuningan Barat Raya No. 8\nJakarta Selatan - 12710"
            ],
            [
                'name' => 'PERSADA SOKKA TAMA',
                'address' => "Persada Office Park B Building 7 th Floor\nJl.KH.Noer Ali No.3A\nKalimalang – Bekasi 17144"
            ],
            [
                'name' => 'TRIMITRA KOLABORASI MANDIRI',
                'address' => "Jl. Premier Park No. 10 Blok AA Rt 09/ Rw 03 Kel. Cikokol, Kec.\nTangerang, Kota Tangerang Banten 15117"
            ],
            [
                'name' => 'PUTRA ARGA BINANGUN',
                'address' => "Cyber Building I, 6th floor. Jl. Kuningan Barat Raya No. 8\nJakarta Selatan - 12710"
            ],
        ];

        foreach ($data as $item) {
            Company::create($item);
        }
    }
}