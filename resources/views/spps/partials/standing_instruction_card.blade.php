@php
    $kpaStatus = $spp->kpa_approval_status;
    
    // Status Badge Logic
    $badgeClass = 'bg-secondary text-white';
    $statusText = 'Belum Diajukan';
    $iconClass = 'bi-dash-circle';
    
    if ($kpaStatus === 'PENDING_KPA') {
        $badgeClass = 'bg-warning text-dark';
        $statusText = 'Menunggu Persetujuan';
        $iconClass = 'bi-clock-fill';
    } elseif ($kpaStatus === 'APPROVED') {
        $badgeClass = 'bg-success text-white';
        $statusText = 'Disetujui KPA';
        $iconClass = 'bi-check-circle-fill';
    } elseif ($kpaStatus === 'REJECTED') {
        $badgeClass = 'bg-danger text-white';
        $statusText = 'Ditolak KPA';
        $iconClass = 'bi-x-circle-fill';
    }
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(145deg, #ffffff, #f8f9fa);">
    <div class="card-header bg-transparent border-0 p-4 pb-0">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: linear-gradient(135deg, #e0e8ff, #f0f4ff); box-shadow: inset 0 2px 4px rgba(255,255,255,0.5);">
                    <i class="bi bi-shield-check text-primary fs-4"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.3px;">Persetujuan KPA</h5>
                    <small class="text-muted fw-medium">Verifikasi Tagihan via WA</small>
                </div>
            </div>
            <div>
                <span class="badge {{ $badgeClass }} rounded-pill px-3 py-2 fw-medium shadow-sm d-flex align-items-center gap-2" style="font-size: 0.85rem;">
                    <i class="bi {{ $iconClass }}"></i>
                    {{ $statusText }}
                </span>
            </div>
        </div>
    </div>
    
    <div class="card-body p-4 pt-3">
        @if(!$kpaStatus || $kpaStatus === 'REJECTED')
            @if($kpaStatus === 'REJECTED')
                <div class="alert border-0 rounded-4 p-3 mb-3" style="background: linear-gradient(to right, rgba(220, 53, 69, 0.08), rgba(220, 53, 69, 0.02)); border-left: 4px solid #dc3545 !important;">
                    <div class="d-flex align-items-start gap-3">
                        <i class="bi bi-exclamation-triangle-fill fs-4 text-danger mt-1"></i>
                        <div>
                            <h6 class="fw-bold text-danger mb-1">Tagihan Ditolak oleh KPA</h6>
                            <p class="mb-0 text-dark fs-7">
                                <strong>Catatan:</strong> {{ $spp->kpa_approval_notes ?? 'Tidak ada catatan.' }}
                            </p>
                        </div>
                    </div>
                </div>
            @else
                <div class="alert border-0 rounded-4 p-3 mb-3 d-flex align-items-center gap-3" style="background-color: #f8f9fa; border: 1px dashed #dee2e6 !important;">
                    <div class="bg-white rounded-circle p-2 shadow-sm d-flex align-items-center justify-content-center">
                        <i class="bi bi-info-circle-fill text-secondary fs-5"></i>
                    </div>
                    <p class="mb-0 text-secondary fw-medium fs-7">
                        Anda belum mengajukan permohonan persetujuan tagihan ini ke KPA.
                    </p>
                </div>
            @endif

            @if(Auth::user()->hasRole('PPK'))
                <form action="{{ route('kpa.approval.send-wa', $spp->id) }}" method="POST" class="mt-2">
                    @csrf
                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm" style="background: linear-gradient(135deg, #0d6efd, #0b5ed7); border: none; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(13,110,253,0.3)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.075)';">
                        <i class="bi bi-whatsapp me-2"></i> Ajukan Persetujuan ke KPA via WA
                    </button>
                </form>
            @endif

        @elseif($kpaStatus === 'PENDING_KPA')
            @php
                $kpaUser = \App\Models\User::role('KPA')->first();
                $magicLink = null;
                if ($kpaUser) {
                    $magicLink = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                        'kpa.approval.show', 
                        now()->addHours(24), 
                        ['sppId' => $spp->id, 'user_id' => $kpaUser->id]
                    );
                }
            @endphp
            
            <div class="alert border-0 rounded-4 p-3 mb-3" style="background: linear-gradient(to right, rgba(255, 193, 7, 0.1), rgba(255, 193, 7, 0.02)); border-left: 4px solid #ffc107 !important;">
                <div class="d-flex align-items-start gap-3">
                    <i class="bi bi-hourglass-split fs-4 text-warning mt-1"></i>
                    <div class="w-100">
                        <h6 class="fw-bold text-dark mb-1">Menunggu Tindakan KPA</h6>
                        <p class="mb-2 text-secondary fs-7">
                            Tautan persetujuan telah dikirimkan ke WhatsApp KPA. Menunggu konfirmasi KPA sebelum SPP ini dapat diverifikasi.
                        </p>
                        
                        @if($magicLink)
                            <div class="mt-3 p-3 bg-white rounded-3 shadow-sm border border-warning border-opacity-25">
                                <label class="form-label fs-8 fw-bold text-muted text-uppercase letter-spacing-1 mb-2">Tautan Alternatif (Magic Link)</label>
                                <div class="input-group input-group-sm mb-2">
                                    <input type="text" class="form-control bg-light border-0 text-muted" value="{{ $magicLink }}" id="magicLinkInput" readonly style="font-family: monospace; font-size: 0.75rem;">
                                    <button class="btn btn-warning text-dark fw-bold px-3" type="button" onclick="copyMagicLink()" title="Salin Tautan">
                                        <i class="bi bi-clipboard"></i> Salin
                                    </button>
                                    <a href="{{ $magicLink }}" target="_blank" class="btn btn-dark fw-bold px-3" title="Buka Tautan">
                                        Buka <i class="bi bi-box-arrow-up-right ms-1"></i>
                                    </a>
                                </div>
                                <div class="d-flex align-items-center gap-1 text-muted" style="font-size: 0.7rem;">
                                    <i class="bi bi-info-circle"></i>
                                    <span>Tautan baru, berlaku 24 jam. Salin jika perlu dikirim manual.</span>
                                </div>
                            </div>
                            <script>
                                function copyMagicLink() {
                                    var copyText = document.getElementById("magicLinkInput");
                                    copyText.select();
                                    copyText.setSelectionRange(0, 99999);
                                    navigator.clipboard.writeText(copyText.value).then(function() {
                                        alert("Tautan berhasil disalin ke clipboard!");
                                    });
                                }
                            </script>
                        @endif
                    </div>
                </div>
            </div>

            @if(Auth::user()->hasRole('PPK'))
                <form action="{{ route('kpa.approval.send-wa', $spp->id) }}" method="POST" class="mt-2">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary w-100 rounded-pill py-2 fw-bold" style="transition: all 0.2s;" onmouseover="this.classList.add('shadow-sm');" onmouseout="this.classList.remove('shadow-sm');">
                        <i class="bi bi-arrow-clockwise me-2"></i> Kirim Ulang Pesan ke WA
                    </button>
                </form>
            @endif

        @elseif($kpaStatus === 'APPROVED')
            <div class="bg-light rounded-4 p-3 mb-3 border border-success border-opacity-25 position-relative overflow-hidden">
                <div class="position-absolute end-0 top-0 text-success" style="transform: translate(20%, -20%); opacity: 0.1;">
                    <i class="bi bi-check-circle-fill" style="font-size: 6rem;"></i>
                </div>
                
                <div class="position-relative z-1">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted fs-8 text-uppercase fw-bold">Disetujui Oleh</span>
                        <span class="text-dark fw-bold fs-7">{{ \App\Models\User::find($spp->kpa_approved_by)?->profilable?->nama_lengkap ?? 'KPA' }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted fs-8 text-uppercase fw-bold">Waktu Persetujuan</span>
                        <span class="text-dark fw-medium fs-7">{{ \Carbon\Carbon::parse($spp->kpa_approved_at)->translatedFormat('d F Y H:i') }}</span>
                    </div>
                    <hr class="border-secondary opacity-10 my-2">
                    <div class="d-flex flex-column mb-1">
                        <span class="text-muted fs-8 text-uppercase fw-bold mb-1">Catatan KPA</span>
                        <div class="bg-white p-2 rounded text-dark fs-7 fst-italic shadow-sm">
                            {{ $spp->kpa_approval_notes ?? 'Tidak ada catatan khusus.' }}
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-success d-flex align-items-center gap-3 border-0 shadow-sm rounded-pill py-2 px-3 m-0" style="background-color: rgba(25, 135, 84, 0.1);">
                <i class="bi bi-check-circle-fill text-success"></i>
                <p class="mb-0 text-success fw-bold fs-7">
                    Anda dapat melanjutkan verifikasi SPP.
                </p>
            </div>
        @endif
    </div>
</div>
