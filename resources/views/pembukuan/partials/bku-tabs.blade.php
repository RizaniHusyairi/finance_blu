{{-- Tab BKU gabungan: satu menu "Buku Kas Umum", dua peran (saldo tetap dihitung
     terpisah per peran/rekening). $active = 'PENGELUARAN' | 'PENERIMAAN'. --}}
<ul class="nav nav-pills mb-3 gap-2">
    @hasanyrole('Bendahara Pengeluaran|Super Admin')
        <li class="nav-item">
            <a class="nav-link {{ ($active ?? '') === 'PENGELUARAN' ? 'active' : '' }}" href="{{ route('pembukuan.pengeluaran.index') }}">
                <i class="bi bi-arrow-up-right-circle me-1"></i>BKU Pengeluaran
            </a>
        </li>
    @endhasanyrole
    @hasanyrole('Bendahara Penerimaan|Super Admin')
        <li class="nav-item">
            <a class="nav-link {{ ($active ?? '') === 'PENERIMAAN' ? 'active' : '' }}" href="{{ route('pembukuan.penerimaan.index') }}">
                <i class="bi bi-arrow-down-left-circle me-1"></i>BKU Penerimaan
            </a>
        </li>
    @endhasanyrole
</ul>
