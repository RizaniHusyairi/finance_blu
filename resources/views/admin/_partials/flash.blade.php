@if (session('success'))
    <div class="alert alert-success border-0 shadow-sm rounded-3 d-flex align-items-center gap-2 alert-dismissible fade show">
        <i class="bi bi-check-circle-fill"></i>
        <div>{{ session('success') }}</div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
@endif
@if (session('error'))
    <div class="alert alert-danger border-0 shadow-sm rounded-3 d-flex align-items-center gap-2 alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>{{ session('error') }}</div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
@endif
@if ($errors->any())
    <div class="alert alert-warning border-0 shadow-sm rounded-3 alert-dismissible fade show">
        <strong><i class="bi bi-exclamation-circle me-1"></i> Periksa kembali input Anda:</strong>
        <ul class="mb-0 mt-1 ps-3">
            @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
