<div class="book-empty">
    <i class="bi bi-inbox"></i>
    <div class="fw-semibold">{{ $title ?? 'Belum ada data' }}</div>
    @if(!empty($message))
        <div class="small mt-1">{{ $message }}</div>
    @endif
</div>
