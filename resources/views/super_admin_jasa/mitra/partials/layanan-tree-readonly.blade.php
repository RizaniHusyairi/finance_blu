@php
    $items = $childrenByParent[$parentId ?? 'root'] ?? collect();
@endphp

@foreach($items as $layanan)
    @continue(!in_array($layanan->id, $visibleLayananIds ?? [], true))

    @php
        $children = ($childrenByParent[$layanan->id] ?? collect())
            ->filter(fn ($child) => in_array($child->id, $visibleLayananIds ?? [], true));
        $isSelected = in_array($layanan->id, $selectedLayananIds ?? [], true);
        $depth = $depth ?? 0;
        $badgeClass = $isSelected ? 'bg-success' : (($depth === 0) ? 'bg-primary' : 'bg-warning text-dark');
        $badgeText = $isSelected ? 'Item Tarif Aktif' : (($depth === 0) ? 'Jenis Layanan' : 'Kategori');
    @endphp

    <div class="mb-2">
        @if($children->isNotEmpty())
            <details class="layanan-tree-node">
                <summary class="layanan-tree-row">
                    <span class="layanan-tree-branch"></span>
                    <span class="layanan-tree-icon"><i class="bi bi-folder-fill"></i></span>
                    <span class="layanan-tree-title">
                        {{ $layanan->nama_layanan }}
                        <span class="badge {{ $badgeClass }} ms-2">{{ $badgeText }}</span>
                    </span>
                    <span class="small text-muted">{{ $children->count() }} item</span>
                </summary>
                <div class="layanan-tree-children">
                    @include('super_admin_jasa.mitra.partials.layanan-tree-readonly', [
                        'childrenByParent' => $childrenByParent,
                        'parentId' => $layanan->id,
                        'depth' => $depth + 1,
                        'selectedLayananIds' => $selectedLayananIds,
                        'visibleLayananIds' => $visibleLayananIds,
                    ])
                </div>
            </details>
        @else
            <div class="layanan-tree-leaf">
                <div class="layanan-tree-row">
                    <span class="layanan-tree-branch"></span>
                    <span class="layanan-tree-icon"><i class="bi bi-file-earmark-text"></i></span>
                    <span class="layanan-tree-title fw-semibold">
                        {{ $layanan->nama_layanan }}
                        <span class="badge {{ $badgeClass }} ms-2">{{ $badgeText }}</span>
                    </span>
                </div>
                <div class="small text-muted layanan-tree-meta">
                    Akun {{ $layanan->kode_akun ?: '-' }} | {{ $layanan->satuan ?: '-' }}
                </div>
            </div>
        @endif
    </div>
@endforeach
