<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th class="text-center" width="5%">No</th>
                <th width="12%">Kode Pajak</th>
                <th width="18%">Jenis Pajak</th>
                <th width="10%" class="text-center">Persentase</th>
                <th width="18%">Rumus</th>
                <th width="17%">Periode Berlaku</th>
                <th width="10%" class="text-center">Status</th>
                <th width="10%" class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pajaks as $pajak)
                @php
                    $mulai = $pajak->berlaku_mulai ? \Carbon\Carbon::parse($pajak->berlaku_mulai) : null;
                    $sampai = $pajak->berlaku_sampai ? \Carbon\Carbon::parse($pajak->berlaku_sampai) : null;

                    $periodLabel = '-';
                    if ($mulai && $sampai) {
                        $periodLabel = $mulai->format('d-m-Y') . ' s/d ' . $sampai->format('d-m-Y');
                    } elseif ($mulai && !$sampai) {
                        $periodLabel = 'Mulai ' . $mulai->format('d-m-Y');
                    }

                    $validityBadge = null;
                    if ($pajak->status_aktif) {
                        if ($mulai && $mulai->gt($today)) {
                            $validityBadge = ['label' => 'Belum Berlaku', 'class' => 'bg-warning text-dark'];
                        } elseif ($sampai && $sampai->lt($today)) {
                            $validityBadge = ['label' => 'Expired', 'class' => 'bg-danger'];
                        } else {
                            $validityBadge = ['label' => 'Berlaku', 'class' => 'bg-info text-dark'];
                        }
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $pajaks->firstItem() + $loop->index }}</td>
                    <td>
                        <span class="badge bg-light text-dark border fw-semibold">{{ $pajak->kode_pajak ?? '-' }}</span>
                    </td>
                    <td class="fw-semibold">{{ $pajak->jenis_pajak }}</td>
                    <td class="text-center">
                        <span class="fw-bold text-primary">{{ rtrim(rtrim(number_format($pajak->persentase, 4, ',', '.'), '0'), ',') }}%</span>
                    </td>
                    <td>
                        @if($pajak->rumus)
                            <span class="text-muted" title="{{ $pajak->rumus }}">{{ \Illuminate\Support\Str::limit($pajak->rumus, 40) }}</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        <div>{{ $periodLabel }}</div>
                        @if($validityBadge)
                            <span class="badge {{ $validityBadge['class'] }} mt-1" style="font-size: 10px;">{{ $validityBadge['label'] }}</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $pajak->status_aktif ? 'bg-success' : 'bg-secondary' }}">
                            {{ $pajak->status_aktif ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="btn-group">
                            <a href="{{ route('master-pajak.show', $pajak) }}" class="btn btn-sm btn-primary">Detail</a>
                            <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="visually-hidden">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <li><a class="dropdown-item" href="{{ route('master-pajak.edit', $pajak) }}">Edit</a></li>
                                <li>
                                    <form action="{{ route('master-pajak.toggle', $pajak) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            {{ $pajak->status_aktif ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">Belum ada data tarif pajak yang sesuai dengan filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($pajaks->hasPages())
    <div class="mt-4 d-flex justify-content-end">
        {{ $pajaks->withQueryString()->links() }}
    </div>
@endif
