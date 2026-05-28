<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumen Disetujui</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 text-center">
                    <div class="card-body p-5">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                        <h3 class="mt-4 fw-bold">Dokumen Berhasil Disetujui!</h3>
                        <p class="text-muted mt-2">Terima kasih <strong>{{ $signature->signer_name }}</strong>, Anda telah menyetujui dokumen <strong>{{ $signature->document_label }}</strong>.</p>
                        
                        <div class="alert alert-success text-start mt-4">
                            <ul class="mb-0 list-unstyled">
                                <li><i class="bi bi-calendar-check me-2"></i><strong>Waktu TTD:</strong> {{ $signature->signed_at->format('d M Y H:i:s') }}</li>
                                <li><i class="bi bi-laptop me-2"></i><strong>IP Address:</strong> {{ $signature->ip_address }}</li>
                            </ul>
                        </div>
                        
                        <p class="small text-muted mt-4">Jendela ini sudah bisa Anda tutup.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
