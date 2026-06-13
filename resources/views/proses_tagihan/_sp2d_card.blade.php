@include('proses_tagihan._dokumen_card', [
    'tagihan' => $tagihan,
    'jenis' => 'sp2d',
    'label' => 'SP2D (Surat Perintah Pencairan Dana)',
    'icon' => 'bi-envelope-paper',
    'color' => 'info',
    'document' => $state['sp2d'],
    'instance' => $state['sp2dInstance'],
    'myApprovals' => $state['myApprovals']['sp2d'],
    'chainDocs' => array_filter([
        'tagihan' => ['label' => 'Data Tagihan & Dokumen Pendukung', 'nomor' => $tagihan->nomor_tagihan],
        'spp' => $state['spp'] ? ['label' => 'SPP', 'nomor' => $state['spp']->nomor_spp] : null,
        'spm' => $state['spm'] ? ['label' => 'SPM', 'nomor' => $state['spm']->nomor_spm] : null,
        'npi' => $state['npi'] ? ['label' => 'NPI', 'nomor' => $state['npi']->nomor_npi] : null,
    ]),
    'canSubmit' => false,
    'submitRoute' => null,
    'pdfRoute' => $state['sp2d'] ? route('sp2ds.cetak-pdf', $state['sp2d']->id) : null,
])
