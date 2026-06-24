@php $av = ($kontrak->id % 5) ?: 5; @endphp
<div class="vendor-cell">
    <span class="vendor-avatar va-{{ $av }}">
        {{ \Illuminate\Support\Str::upper(mb_substr($kontrak->vendor->nama_perusahaan ?? '?', 0, 1)) }}
    </span>
    <span class="vendor-name">{{ \Illuminate\Support\Str::limit($kontrak->vendor->nama_perusahaan ?? 'N/A', 26) }}</span>
</div>
