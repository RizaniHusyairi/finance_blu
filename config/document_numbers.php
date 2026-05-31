<?php

return [
    'default_padding' => 4,

    'default_suffix_code' => 'PPK.BB/APTP',

    'default_sequence_group' => 'KONTRAK_PPK_BB_APTP',

    'documents' => [
        'SPK' => [
            'series_prefix' => 'PL.107',
            'suffix_code' => 'PPK.BB/APTP',
            'sequence_group' => 'KONTRAK_PPK_BB_APTP',
        ],
        'SPMK' => [
            'series_prefix' => 'PL.107',
            'suffix_code' => 'PPK.BB/APTP',
            'sequence_group' => 'KONTRAK_PPK_BB_APTP',
        ],
        'BAPP' => [
            'series_prefix' => 'PL.108',
            'suffix_code' => 'PPK.BB/APTP',
            'sequence_group' => 'KONTRAK_PPK_BB_APTP',
        ],
        'BAST' => [
            'series_prefix' => 'PL.108',
            'suffix_code' => 'PPK.BB/APTP',
            'sequence_group' => 'KONTRAK_PPK_BB_APTP',
        ],
        'BAP' => [
            'series_prefix' => 'PL.109',
            'suffix_code' => 'PPK.BB/APTP',
            'sequence_group' => 'KONTRAK_PPK_BB_APTP',
        ],
        'LAINNYA' => [
            'series_prefix' => 'LAINNYA',
            'suffix_code' => 'PPK.BB/APTP',
            'sequence_group' => 'KONTRAK_PPK_BB_APTP',
        ],

        // ── Surat berawalan KU (dikelola Koordinator Keuangan) ──
        // Ketiga jenis ini BERBAGI satu sequence_group (KU_APTP) sehingga nomor
        // urut 4 digit-nya unik lintas tipe (Honorarium, Perjaldin, Surat Pengantar Jasa).
        'KU_HONOR' => [
            'series_prefix' => 'KU.201',
            'suffix_code' => 'APTP',
            'sequence_group' => 'KU_APTP',
        ],
        'KU_PERJALDIN' => [
            'series_prefix' => 'KU.201',
            'suffix_code' => 'APTP',
            'sequence_group' => 'KU_APTP',
        ],
        'KU_SURAT_PENGANTAR_JASA' => [
            'series_prefix' => 'KU.102',
            'suffix_code' => 'APTP',
            'sequence_group' => 'KU_APTP',
        ],
    ],
];
