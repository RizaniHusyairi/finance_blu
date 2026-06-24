@php $rek = $supplier->rekening->first(); @endphp
@if($rek)
    <span class="fw-bold">{{ $rek->nama_bank }}</span><br>
    <small class=""><i class="bi bi-credit-card me-1"></i>{{ $rek->nomor_rekening }}</small>
@else
    <span class="badge bg-secondary">Belum disetel</span>
@endif
