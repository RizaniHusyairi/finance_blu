@include('proses_tagihan._dokumen_card', [
    'tagihan' => $tagihan,
    'jenis' => 'sp2d',
    'label' => 'SP2D',
    'document' => $state['sp2d'],
    'instance' => $state['sp2dInstance'],
    'myApprovals' => $state['myApprovals']['sp2d'],
    'canSubmit' => false,
    'submitRoute' => null,
    'pdfRoute' => $state['sp2d'] ? route('sp2ds.cetak-pdf', $state['sp2d']->id) : null,
])
