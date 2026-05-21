@php
    $childrenByParent = $layanans->groupBy(fn ($item) => $item->parent_id ?: 'root');
@endphp

@foreach(($childrenByParent[$parentId ?? 'root'] ?? collect()) as $layanan)
    @php
        $children = $childrenByParent[$layanan->id] ?? collect();
        $isBillable = (bool) $layanan->is_leaf;
        $checked = in_array($layanan->id, $selectedIds ?? [], true);
        $tarif = (float) ($layanan->tarif_dasar ?? 0);
        $satuan = (string) ($layanan->satuan ?? '');
        $tarifLabel = null;
        if ($isBillable && $tarif > 0) {
            $tarifLabel = str_starts_with(trim($satuan), '%')
                ? 'Tarif ' . number_format($tarif, 0, ',', '.') . $satuan
                : 'Tarif Rp ' . number_format($tarif, 0, ',', '.') . ($satuan ? ' / ' . $satuan : '');
        }
    @endphp
    <div class="mb-2 layanan-node" data-layanan-id="{{ $layanan->id }}" data-is-leaf="{{ $isBillable ? 1 : 0 }}" style="margin-left: {{ (($depth ?? 0) * 18) }}px;">
        <div class="d-flex align-items-start gap-2 p-2 rounded border bg-white">
            @if($isBillable)
                <input type="checkbox" class="form-check-input mt-1 layanan-check layanan-leaf-check" name="layanan_ids[]" value="{{ $layanan->id }}" @checked($checked)>
            @else
                <input type="checkbox" class="form-check-input mt-1 layanan-check layanan-parent-check" value="{{ $layanan->id }}">
            @endif
            <div class="flex-grow-1">
                <div class="fw-semibold d-flex align-items-center gap-2">
                    @if($children->isNotEmpty())
                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none layanan-toggle" aria-expanded="false" title="Buka/tutup turunan">
                            <i class="bi bi-caret-right-fill"></i>
                        </button>
                    @else
                        <span style="width: 14px; display: inline-block;"></span>
                    @endif
                    <span>{{ $layanan->nama_layanan }}</span>
                    @if($isBillable)
                        <span class="badge bg-success ms-1">Item Tarif</span>
                    @elseif(($depth ?? 0) === 0)
                        <span class="badge bg-primary ms-1">Jenis Layanan</span>
                    @else
                        <span class="badge bg-warning text-dark ms-1">Kategori</span>
                    @endif
                    @if($isBillable)
                        <span class="badge {{ ($layanan->tipe_layanan ?? 'PNBP') === 'KONSESI' ? 'bg-info text-dark' : 'bg-primary' }} ms-1">
                            {{ ($layanan->tipe_layanan ?? 'PNBP') === 'KONSESI' ? 'Konsesi Saja' : 'PNBP' }}
                        </span>
                        @if($layanan->mendukung_konsesi ?? false)
                            <span class="badge bg-dark ms-1">Ada Konsesi</span>
                        @endif
                    @endif
                </div>
                <div class="small text-muted">
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
            </div>
        </div>
        @if($children->isNotEmpty())
            <div class="layanan-children d-none">
                @include('jasa_assignments.partials.layanan-tree', [
                    'layanans' => $layanans,
                    'selectedIds' => $selectedIds,
                    'parentId' => $layanan->id,
                    'depth' => ($depth ?? 0) + 1,
                ])
            </div>
        @endif
    </div>
@endforeach
