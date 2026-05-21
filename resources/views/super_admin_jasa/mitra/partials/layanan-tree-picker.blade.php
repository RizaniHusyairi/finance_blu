@foreach(($childrenByParent[$parentId ?? 'root'] ?? collect()) as $layanan)
    @php
        $children = $childrenByParent[$layanan->id] ?? collect();
        $isLeaf = (bool) $layanan->is_leaf;
        $badge = ($depth ?? 0) === 0 ? 'Jenis Layanan' : ($isLeaf ? 'Item Tarif' : 'Kategori');
        $badgeClass = ($depth ?? 0) === 0 ? 'bg-primary' : ($isLeaf ? 'bg-success' : 'bg-warning text-dark');
        $tarif = (float) ($layanan->tarif_dasar ?? 0);
        $satuan = (string) ($layanan->satuan ?? '');
        $tarifLabel = null;
        if ($isLeaf && $tarif > 0) {
            $tarifLabel = str_starts_with(trim($satuan), '%')
                ? 'Tarif ' . number_format($tarif, 0, ',', '.') . $satuan
                : 'Tarif Rp ' . number_format($tarif, 0, ',', '.') . ($satuan ? ' / ' . $satuan : '');
        }
    @endphp
    <div class="mitra-service-node" style="margin-left: {{ (($depth ?? 0) * 16) }}px;">
        <div class="mitra-service-row">
            @if($children->isNotEmpty())
                <button type="button" class="btn btn-sm btn-link p-0 service-tree-toggle" aria-expanded="false">
                    <i class="bi bi-caret-right-fill"></i>
                </button>
            @else
                <span class="service-tree-spacer"></span>
            @endif

            <button type="button" class="service-tree-select {{ $isLeaf ? '' : 'service-tree-select-parent' }}" data-service-id="{{ $layanan->id }}" data-service-name="{{ $layanan->nama_layanan }}" data-is-leaf="{{ $isLeaf ? '1' : '0' }}">
                <span class="service-tree-check"><i class="bi bi-square"></i></span>
                <i class="bi {{ $isLeaf ? 'bi-file-earmark-text' : 'bi-folder-fill text-primary' }} me-1"></i>{{ $layanan->nama_layanan }}
            </button>

            <span class="badge {{ $badgeClass }}">{{ $badge }}</span>
        </div>

        <div class="small text-muted service-tree-meta">
            {{ $layanan->kode_layanan ?: str_pad($layanan->id, 6, '0', STR_PAD_LEFT) }}
            @if($layanan->kode_akun)
                | Akun {{ $layanan->kode_akun }}
            @endif
            @if($tarifLabel)
                | {{ $tarifLabel }}
            @elseif($layanan->satuan)
                | {{ $layanan->satuan }}
            @endif
        </div>

        @if($children->isNotEmpty())
            <div class="service-tree-children d-none">
                @include('super_admin_jasa.mitra.partials.layanan-tree-picker', [
                    'childrenByParent' => $childrenByParent,
                    'parentId' => $layanan->id,
                    'depth' => ($depth ?? 0) + 1,
                ])
            </div>
        @endif
    </div>
@endforeach
