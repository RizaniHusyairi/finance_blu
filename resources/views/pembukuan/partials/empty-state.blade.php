<div class="bku-empty">
    <i class="bi bi-inbox"></i>
    <p>{{ $title ?? 'Belum ada data' }}</p>
    @if(!empty($message))
        <span>{{ $message }}</span>
    @endif
</div>
