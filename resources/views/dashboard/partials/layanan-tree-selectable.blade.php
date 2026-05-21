@php
    $items = $childrenByParent[$parentId ?? 'root'] ?? collect();
@endphp

@foreach($items as $layanan)
    @continue(!in_array($layanan->id, $visibleLayananIds ?? [], true))

    @php
        $children = ($childrenByParent[$layanan->id] ?? collect())
            ->filter(fn ($child) => in_array($child->id, $visibleLayananIds ?? [], true));
        $isSelectable = in_array($layanan->id, $selectableLayananIds ?? [], true);
        $depth = $depth ?? 0;
        $badgeClass = $isSelectable ? 'bg-success' : (($depth === 0) ? 'bg-primary' : 'bg-warning text-dark');
        $badgeText = $isSelectable ? 'Aktif' : (($depth === 0) ? 'Jenis Layanan' : 'Kategori');
    @endphp

    <div class="mb-2">
        @if($children->isNotEmpty())
            <details class="layanan-tree-node" {{ $depth === 0 ? 'open' : '' }}>
                <summary class="layanan-tree-row">
                    <span class="layanan-tree-branch"></span>
                    <span class="layanan-tree-icon"><i class="bi bi-folder"></i></span>
                    <span class="layanan-tree-title">
                        {{ $layanan->nama_layanan }}
                        <span class="badge {{ $badgeClass }} ms-2">{{ $badgeText }}</span>
                    </span>
                    <span class="small text-muted">{{ $children->count() }} item</span>
                </summary>
                <div class="layanan-tree-children">
                    @include('dashboard.partials.layanan-tree-selectable', [
                        'childrenByParent' => $childrenByParent,
                        'parentId' => $layanan->id,
                        'depth' => $depth + 1,
                        'selectableLayananIds' => $selectableLayananIds,
                        'visibleLayananIds' => $visibleLayananIds,
                        'selectedLayananId' => $selectedLayananId,
                    ])
                </div>
            </details>
        @else
            <div class="layanan-tree-leaf">
                <div class="layanan-tree-row align-items-center">
                    <span class="layanan-tree-branch"></span>
                    <span class="layanan-tree-icon">
                        @if($isSelectable)
                            <div class="form-check m-0 d-inline-block">
                                <input class="form-check-input" type="radio" name="layanan_jasa_id" id="layanan_{{ $layanan->id }}" value="{{ $layanan->id }}" {{ (int) $selectedLayananId === (int) $layanan->id ? 'checked' : '' }} required>
                            </div>
                        @else
                            <i class="bi bi-file-earmark-text"></i>
                        @endif
                    </span>
                    <span class="layanan-tree-title fw-semibold">
                        @if($isSelectable)
                            <label class="form-check-label ms-1" for="layanan_{{ $layanan->id }}" style="cursor: pointer;">
                                {{ $layanan->nama_layanan }}
                            </label>
                        @else
                            {{ $layanan->nama_layanan }}
                        @endif
                        <span class="badge {{ $badgeClass }} ms-2">{{ $badgeText }}</span>
                    </span>
                </div>
                <div class="small text-muted layanan-tree-meta">
                    @if($layanan->tarif_dasar)
                        Tarif Rp {{ number_format($layanan->tarif_dasar, 0, ',', '.') }} / {{ $layanan->satuan ?: 'per penumpang' }}
                    @endif
                </div>
            </div>
        @endif
    </div>
@endforeach
