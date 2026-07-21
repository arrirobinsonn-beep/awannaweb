<?php

const ACEH = 'NANGGROE ACEH DARUSSALAM (NAD)';
const YOGYA = 'DI YOGYAKARTA';
const JAKARTA = 'DKI JAKARTA';

return [

    /*
    |--------------------------------------------------------------------------
    | Master List Provinsi
    |--------------------------------------------------------------------------
    | 34 provinsi standar Indonesia. Digunakan sebagai referensi utama
    | untuk menampilkan tabel regional (sticky column).
    */
    'master_provinces' => [
        ACEH,
        'BALI',
        'BANGKA BELITUNG',
        'BANTEN',
        'BENGKULU',
        YOGYA,
        JAKARTA,
        'GORONTALO',
        'JAMBI',
        'JAWA BARAT',
        'JAWA TENGAH',
        'JAWA TIMUR',
        'KALIMANTAN BARAT',
        'KALIMANTAN SELATAN',
        'KALIMANTAN TENGAH',
        'KALIMANTAN TIMUR',
        'KALIMANTAN UTARA',
        'KEPULAUAN RIAU',
        'LAMPUNG',
        'MALUKU',
        'MALUKU UTARA',
        'NUSA TENGGARA BARAT (NTB)',
        'NUSA TENGGARA TIMUR (NTT)',
        'PAPUA',
        'PAPUA BARAT',
        'RIAU',
        'SULAWESI BARAT',
        'SULAWESI SELATAN',
        'SULAWESI TENGAH',
        'SULAWESI TENGGARA',
        'SULAWESI UTARA',
        'SUMATRA BARAT',
        'SUMATRA SELATAN',
        'SUMATRA UTARA',
    ],

    /*
    |--------------------------------------------------------------------------
    | Mapping Variasi Penulisan Provinsi
    |--------------------------------------------------------------------------
    | Menangani typo/variasi penulisan dari data raw Excel.
    | Key = variasi penulisan, Value = nama provinsi standar.
    */
    'province_mapping' => [
        'ACEH' => ACEH,
        'NAD' => ACEH,
        'NANGGROE ACEH DARUSSALAM (NAD)' => ACEH,
        'YOGYAKARTA' => YOGYA,
        'JOGJA' => YOGYA,
        'DIY' => YOGYA,
        'JAKARTA' => JAKARTA,
        'NTB' => 'NUSA TENGGARA BARAT (NTB)',
        'NTT' => 'NUSA TENGGARA TIMUR (NTT)',
        'KEP RIAU' => 'KEPULAUAN RIAU',
        'KEP. RIAU' => 'KEPULAUAN RIAU',
        'BANGKA' => 'BANGKA BELITUNG',
        'BABEL' => 'BANGKA BELITUNG',
        'SULSEL' => 'SULAWESI SELATAN',
        'SULTENG' => 'SULAWESI TENGAH',
        'SULTRA' => 'SULAWESI TENGGARA',
        'SULUT' => 'SULAWESI UTARA',
        'KALBAR' => 'KALIMANTAN BARAT',
        'KALSEL' => 'KALIMANTAN SELATAN',
        'KALTENG' => 'KALIMANTAN TENGAH',
        'KALTIM' => 'KALIMANTAN TIMUR',
        'KALUT' => 'KALIMANTAN UTARA',
    ],

    /*
    |--------------------------------------------------------------------------
    | Kolom Wajib di File Excel
    |--------------------------------------------------------------------------
    | Mapping nama kolom dari file Excel ke logika sistem.
    */
    'columns' => [
        'province' => 'province',
        'payment_status' => 'payment_status',
        'created_at' => 'created_at',
    ],
];
