<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivitas Tagihan {{ $tagihan->nomor_tagihan }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background:#f5f7fa; }
        .page-header { background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); color:#fff; }
    </style>
</head>
<body>
    <div class="page-header py-4 shadow-sm">
        <div class="container">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="bg-white text-primary rounded-3 p-2" style="width:48px; height:48px; display:flex; align-items:center; justify-content:center;">
                    <i class="bi bi-receipt fs-4"></i>
                </div>
                <div>
                    <div class="small opacity-75">Rekam Aktivitas Tagihan</div>
                    <h4 class="fw-bold mb-0">{{ $tagihan->nomor_tagihan }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="text-muted small">Tipe</div>
                        <div class="fw-bold">{{ $tagihan->tipe_tagihan }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Status Saat Ini</div>
                        <div class="fw-bold">{{ $tagihan->status }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Total Netto</div>
                        <div class="fw-bold text-success">Rp {{ number_format($tagihan->total_netto, 0, ',', '.') }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Dibuat</div>
                        <div class="fw-bold">{{ optional($tagihan->created_at)->format('d M Y') ?? '-' }}</div>
                    </div>
                    @if($tagihan->deskripsi)
                        <div class="col-12 mt-3 pt-3 border-top">
                            <div class="text-muted small">Uraian</div>
                            <div>{{ $tagihan->deskripsi }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @include('tagihan.partials.aktivitas-content', ['tagihan' => $tagihan])

        <div class="text-center text-muted small mt-4 mb-3">
            <i class="bi bi-shield-check me-1"></i>
            Halaman ini diakses melalui tautan QR yang ter-signed dari sistem BLU APTP.
            <br>
            Dicetak: {{ now()->format('d M Y, H:i') }}
        </div>
    </div>
</body>
</html>
