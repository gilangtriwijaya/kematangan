<?php
// config/penilaian.php

return [
    // Kegiatan yang dikunci untuk API publik
    'kegiatan_id' => env('PENILAIAN_KEGIATAN_ID', 1),

    // TTL cache detik (default 10 menit)
    'cache_ttl' => env('STATISTIK_PUB_CACHE_TTL', 600),

    // Ambang kategori skor (inklusif batas bawah, eksklusif batas atas, kecuali terakhir)
    // Sesuai ketentuan Anda: 10–19, 19.1–28, 28.1–37, 37.1–46, 46.1–55
    'kategori' => [
        ['nama' => 'Sangat Rendah', 'min' => 10.0,  'max' => 19.0],
        ['nama' => 'Rendah',         'min' => 19.1, 'max' => 28.0],
        ['nama' => 'Sedang',         'min' => 28.1, 'max' => 37.0],
        ['nama' => 'Tinggi',         'min' => 37.1, 'max' => 46.0],
        ['nama' => 'Sangat Tinggi',  'min' => 46.1, 'max' => 55.0],
    ],
];
