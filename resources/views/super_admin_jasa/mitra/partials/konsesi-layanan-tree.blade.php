@php
    $childrenByParent = $layanans->groupBy(fn ($item) => $item->parent_id ?: 'root');
@endphp

@foreach(($childrenByParent[$parentId ?? 'root'] ?? collect()) as $layanan)
    @php
        $children = $childrenByParent[$layanan->id] ?? collect();
        $isLeaf = (bool) $layanan->is_leaf;
        $checked = ((int) old('layanan_jasa_id', $selectedId ?? 0)) === (int) $layanan->id;
    @endphp
    <div class="mb-2 layanan-node" data-layanan-id="{{ $layanan->id }}" style="margin-left: {{ (($depth ?? 0) * 18) }}px;">
        <div class="d-flex align-items-start gap-2 p-2 rounded border bg-white {{ $checked ? 'border-primary bg-primary bg-opacity-10' : '' }}">
            @if($isLeaf)
                <input type="radio" class="form-check-input mt-1 layanan-radio" name="layanan_jasa_id" value="{{ $layanan->id }}" data-persen="{{ floatval($layanan->persentase_konsesi) }}" @checked($checked)>
            @else
                <div style="width: 16px; display: inline-block;"></div>
            @endif
            <div class="flex-grow-1">
                <div class="fw-semibold d-flex align-items-center gap-2">
                    @if($children->isNotEmpty())
                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none layanan-toggle" aria-expanded="{{ $checked ? 'true' : 'false' }}" title="Buka/tutup turunan">
                            <i class="bi {{ $checked ? 'bi-caret-down-fill' : 'bi-caret-right-fill' }}"></i>
                        </button>
                    @else
                        <span style="width: 14px; display: inline-block;"></span>
                    @endif
                    <span>{{ $layanan->nama_layanan }}</span>
                    @if($isLeaf)
                        <span class="badge bg-success ms-1">Item Tarif</span>
                        @if($layanan->mendukung_konsesi ?? false)
                            <span class="badge bg-dark ms-1">Ada Konsesi</span>
                        @endif
                    @elseif(($depth ?? 0) === 0)
                        <span class="badge bg-primary ms-1">Jenis Layanan</span>
                    @else
                        <span class="badge bg-warning text-dark ms-1">Kategori</span>
                    @endif
                </div>
                <div class="small text-muted">
                    {{ $layanan->kode_layanan ?: str_pad($layanan->id, 6, '0', STR_PAD_LEFT) }}
                </div>
            </div>
        </div>
        @if($children->isNotEmpty())
            <div class="layanan-children {{ $checked ? '' : 'd-none' }}">
                @include('super_admin_jasa.mitra.partials.konsesi-layanan-tree', [
                    'layanans' => $layanans,
                    'selectedId' => $selectedId ?? null,
                    'parentId' => $layanan->id,
                    'depth' => ($depth ?? 0) + 1,
                ])
            </div>
        @endif
    </div>
@endforeach
