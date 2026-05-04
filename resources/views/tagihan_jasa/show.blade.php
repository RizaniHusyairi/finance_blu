@extends('layouts.app')
@section('title', 'Detail Tagihan Jasa (PNBP)')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
    <div>
        <h4 class="mb-0 fw-bold">Detail Tagihan Jasa (PNBP)</h4>
        <p class="mb-0 small">No. Tagihan: {{ $tagihan->nomor_tagihan }}</p>
    </div>
    <a href="{{ route('tagihan-jasa.index') }}" class="btn btn-secondary shadow-sm fw-bold">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 bg-success text-white alert-dismissible fade show shadow-sm mb-4">
        <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('wa_message_preview'))
    <div class="alert alert-info border-0 shadow-sm mb-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-whatsapp me-2 text-success"></i>Preview Pesan WhatsApp / Notifikasi</h5>
        <div class="bg-white p-3 rounded border" style="white-space: pre-wrap; font-family: monospace; font-size: 0.9rem;">{{ session('wa_message_preview') }}</div>
        
        @if(session('is_new_mitra'))
            <hr class="my-3">
            <h6 class="fw-bold text-danger mb-2"><i class="bi bi-exclamation-triangle me-2"></i>PENTING: Akun Mitra Baru Terbuat!</h6>
            <p class="small mb-2">Sistem baru saja membuatkan akun portal untuk Mitra ini. Karena alasan keamanan, password ini hanya ditampilkan satu kali ini saja. Harap salin dan berikan kredensial ini kepada Mitra:</p>
            <div class="bg-white p-3 rounded border border-danger">
                <div><strong>Email Login:</strong> {{ session('mitra_email') }}</div>
                <div><strong>Password:</strong> <span class="fw-bold text-danger">{{ session('mitra_password') }}</span></div>
                <div class="mt-2 text-muted small">Mitra dapat mengubah password ini setelah login di menu Profile.</div>
            </div>
        @else
            <hr class="my-3">
            <h6 class="fw-bold text-success mb-2"><i class="bi bi-check-circle me-2"></i>Akun Mitra Terdaftar</h6>
            <p class="small mb-2">Mitra ini sudah memiliki akun terdaftar sebelumnya di dalam sistem.</p>
            <div class="bg-white p-2 px-3 rounded border">
                <div><strong>Email Login:</strong> {{ session('mitra_email') }}</div>
                <div class="mt-1 text-muted small">Mitra dapat login dengan password yang sudah mereka miliki sebelumnya.</div>
            </div>
        @endif
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger border-0 bg-danger text-white alert-dismissible fade show shadow-sm mb-4">
        <i class="bi bi-x-circle me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <!-- Kolom Kiri: Detail -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white p-4 border-bottom rounded-top-4 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="bi bi-file-earmark-text text-primary me-2"></i>Informasi Tagihan</h5>
                @php
                    $statusClass = match($tagihan->status) {
                        'PUBLISHED', 'LUNAS' => 'bg-success',
                        'DRAFT' => 'bg-secondary',
                        'DITOLAK' => 'bg-danger',
                        default => 'bg-warning text-dark',
                    };
                @endphp
                <span class="badge {{ $statusClass }} px-3 py-2 fs-6">{{ str_replace('_', ' ', $tagihan->status) }}</span>
            </div>
            <div class="card-body p-4">
                <div class="row mb-4">
                    <div class="col-sm-6 mb-3 mb-sm-0">
                        <p class="mb-1 text-muted small fw-bold">Diterbitkan Kepada (Mitra)</p>
                        <h6 class="fw-bold">{{ $tagihan->mitra->nama_pihak }}</h6>
                        <div class="small">
                            {{ $tagihan->mitra->alamat ?? '-' }}<br>
                            NPWP: {{ $tagihan->mitra->npwp ?? '-' }}
                        </div>
                    </div>
                    <div class="col-sm-6 text-sm-end">
                        <p class="mb-1 text-muted small fw-bold">Nomor Tagihan</p>
                        <h6 class="fw-bold text-primary">{{ $tagihan->nomor_tagihan }}</h6>
                        <p class="mb-1 mt-3 text-muted small fw-bold">Tanggal Tagihan</p>
                        <h6 class="fw-bold">{{ \Carbon\Carbon::parse($tagihan->tanggal_tagihan)->format('d F Y') }}</h6>
                    </div>
                </div>

                @if($tagihan->nomor_kontrak)
                <div class="bg-light p-3 rounded-3 mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <p class="mb-1 text-muted small fw-bold">Nomor Kontrak Terkait</p>
                            <h6 class="mb-0 fw-bold">{{ $tagihan->nomor_kontrak }}</h6>
                            <small class="text-muted">
                                {{ $tagihan->tanggal_mulai_kontrak ? \Carbon\Carbon::parse($tagihan->tanggal_mulai_kontrak)->format('d M Y') : '-' }} s/d 
                                {{ $tagihan->tanggal_selesai_kontrak ? \Carbon\Carbon::parse($tagihan->tanggal_selesai_kontrak)->format('d M Y') : '-' }}
                            </small>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            @if($tagihan->file_kontrak)
                                <a href="{{ Storage::url($tagihan->file_kontrak) }}" target="_blank" class="btn btn-sm btn-outline-primary fw-bold">
                                    <i class="bi bi-file-pdf me-1"></i> Lihat Dokumen
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                <h6 class="fw-bold mb-3">Rincian Layanan (PNBP)</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="5%" class="text-center">No</th>
                                <th width="45%">Deskripsi Layanan</th>
                                <th width="10%" class="text-center">Qty</th>
                                <th width="20%" class="text-end">Harga Satuan</th>
                                <th width="20%" class="text-end">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tagihan->details as $detail)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>{{ $detail->layananJasa->nama_layanan }}</td>
                                    <td class="text-center">{{ rtrim(rtrim(number_format($detail->qty, 2, ',', '.'), '0'), ',') }}</td>
                                    <td class="text-end">Rp {{ number_format($detail->harga_satuan, 0, ',', '.') }}</td>
                                    <td class="text-end fw-bold">Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end fw-bold">TOTAL TAGIHAN :</td>
                                <td class="text-end fw-bold text-success fs-5">Rp {{ number_format($tagihan->total_tagihan, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if($tagihan->nomor_va)
                    <div class="alert alert-info mt-4 d-flex align-items-center">
                        <i class="bi bi-credit-card fs-3 me-3"></i>
                        <div>
                            <p class="mb-0 small fw-bold">Nomor Virtual Account (VA) Bank BTN</p>
                            <h4 class="mb-0 fw-bold">{{ $tagihan->nomor_va }}</h4>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Approval & Timeline -->
    <div class="col-lg-4">
        <!-- Kotak Aksi Approval -->
        @php
            $wfInstance = $tagihan->workflowInstance;
            $currentApproval = $wfInstance ? $wfInstance->approvals->where('urutan_step', $wfInstance->step_saat_ini)->first() : null;
            $canApprove = false;
            
            if ($wfInstance && $wfInstance->status == 'IN_PROGRESS' && $currentApproval) {
                // If the current step's role matches the user's role
                if (Auth::user()->hasRole($currentApproval->role_code)) {
                    $canApprove = true;
                }
            }
        @endphp

        @if($canApprove)
            <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-4 border-warning">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-shield-check text-warning me-2"></i>Tindakan Verifikasi</h5>
                    <p class="small text-muted mb-4">Anda bertugas sebagai <strong>{{ $currentApproval->role_code }}</strong> untuk memverifikasi dokumen tagihan ini.</p>
                    
                    <form action="{{ route('tagihan-jasa.approve', $tagihan->id) }}" method="POST" id="formApprove" class="mb-2">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Catatan (Opsional)</label>
                            <textarea name="catatan" class="form-control" rows="2" placeholder="Tuliskan catatan jika ada..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100 fw-bold" onclick="return confirm('Apakah Anda yakin menyetujui dokumen ini?')">
                            <i class="bi bi-check-lg me-1"></i> Setujui Dokumen
                        </button>
                    </form>
                    
                    <button type="button" class="btn btn-outline-danger w-100 fw-bold" data-bs-toggle="modal" data-bs-target="#modalTolak">
                        <i class="bi bi-x-lg me-1"></i> Tolak Dokumen
                    </button>
                </div>
            </div>
        @endif

        @if(Auth::user()->hasRole(['Super Admin', 'Admin Jasa']) && $wfInstance && $wfInstance->status === 'IN_PROGRESS')
            <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-4 border-info">
                <div class="card-body p-4 text-center">
                    <h5 class="fw-bold mb-2"><i class="bi bi-lightning-charge text-info me-2"></i>Mode Cepat (Testing)</h5>
                    <p class="small text-muted mb-3">Approve semua step verifikasi sekaligus tanpa perlu login satu-satu.</p>
                    <form action="{{ route('tagihan-jasa.auto-approve', $tagihan->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-info text-white w-100 fw-bold" onclick="return confirm('Auto-approve semua step verifikasi yang tersisa?')">
                            <i class="bi bi-fast-forward-fill me-1"></i> Auto-Approve Semua Step
                        </button>
                    </form>
                </div>
            </div>
        @endif

        @if(Auth::user()->hasRole('Admin Jasa') && $wfInstance && $wfInstance->status === 'APPROVED' && !in_array($tagihan->status, ['PUBLISHED', 'LUNAS']))
            <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-4 border-success">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-send text-success me-2"></i>Terbitkan Tagihan</h5>
                    <p class="small text-muted mb-4">Tagihan telah disetujui sepenuhnya oleh Kabandara. Anda dapat mem-publish tagihan ini untuk men-generate VA dan mengirim notifikasi otomatis ke Mitra.</p>
                    
                    <button type="button" class="btn btn-success w-100 fw-bold mb-2" data-bs-toggle="modal" data-bs-target="#modalPublish">
                        <i class="bi bi-rocket-takeoff me-1"></i> Publish & Kirim Notifikasi WA
                    </button>
                    <a href="{{ route('tagihan-jasa.pdf', $tagihan->id) }}" target="_blank" class="btn btn-outline-primary w-100 fw-bold">
                        <i class="bi bi-file-pdf me-1"></i> Cek PDF Sebelum Publish
                    </a>
                </div>
            </div>
        @endif

        @if($tagihan->status === 'PUBLISHED')
            <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-4 border-primary">
                <div class="card-body p-4 text-center">
                    <h5 class="fw-bold mb-3"><i class="bi bi-cash-coin text-primary me-2"></i>Status Pembayaran</h5>
                    <p class="small text-muted mb-4">Tagihan ini sedang menunggu pembayaran dari Mitra via Virtual Account. Untuk keperluan simulasi, Anda dapat menandai tagihan ini menjadi LUNAS secara manual.</p>
                    
                    <form action="{{ route('tagihan-jasa.mark-lunas', $tagihan->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100 fw-bold" onclick="return confirm('Tandai tagihan ini sebagai LUNAS? Ini mensimulasikan callback sukses dari Bank BTN.')">
                            <i class="bi bi-check-circle me-1"></i> Simulasi: Konfirmasi Lunas
                        </button>
                    </form>
                </div>
            </div>
        @endif

        @if($tagihan->status === 'LUNAS')
            <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-4 border-success bg-success-subtle">
                <div class="card-body p-4 text-center">
                    <h5 class="fw-bold mb-2 text-success"><i class="bi bi-patch-check-fill me-2"></i>PEMBAYARAN LUNAS</h5>
                    <p class="small text-success mb-0">Pembayaran untuk tagihan ini telah dikonfirmasi oleh sistem Bank.</p>
                </div>
            </div>
        @endif

        <!-- Timeline Log -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white p-3 border-bottom rounded-top-4">
                <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history text-secondary me-2"></i>Riwayat Proses</h6>
            </div>
            <div class="card-body p-4">
                <div class="timeline">
                    <!-- Status Draft -->
                    <div class="timeline-item mb-4 position-relative border-start border-2 border-primary ps-4 pb-2">
                        <div class="timeline-icon bg-primary text-white position-absolute rounded-circle d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; left: -13px; top: 0;">
                            <i class="bi bi-file-earmark-plus" style="font-size: 10px;"></i>
                        </div>
                        <div class="fw-bold small text-primary mb-1">Dibuat oleh {{ $tagihan->creator->name ?? 'Admin' }}</div>
                        <div class="small text-muted">{{ \Carbon\Carbon::parse($tagihan->created_at)->format('d M Y, H:i') }}</div>
                    </div>

                    @if($wfInstance)
                        @foreach($wfInstance->approvals as $approval)
                            @php
                                $isApproved = $approval->status === 'APPROVED';
                                $isRejected = $approval->status === 'REJECTED';
                                $color = $isApproved ? 'success' : ($isRejected ? 'danger' : 'warning');
                                $icon = $isApproved ? 'check' : ($isRejected ? 'x' : 'hourglass');
                            @endphp
                            <div class="timeline-item mb-4 position-relative border-start border-2 border-{{ $color }} ps-4 pb-2">
                                <div class="timeline-icon bg-{{ $color }} text-white position-absolute rounded-circle d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; left: -13px; top: 0;">
                                    <i class="bi bi-{{ $icon }}" style="font-size: 12px;"></i>
                                </div>
                                <div class="fw-bold small text-{{ $color }} mb-1">{{ $approval->nama_step }}</div>
                                <div class="small fw-semibold">{{ $approval->actedByUser->name ?? $approval->role_code }}</div>
                                @if($approval->catatan)
                                    <div class="small bg-light p-2 rounded mt-1 fst-italic">"{{ $approval->catatan }}"</div>
                                @endif
                                <div class="small text-muted mt-1">{{ \Carbon\Carbon::parse($approval->created_at)->format('d M Y, H:i') }}</div>
                            </div>
                        @endforeach
                    @endif
                    
                    @if(in_array($tagihan->status, ['PUBLISHED', 'LUNAS']))
                    <div class="timeline-item position-relative border-start border-2 border-info ps-4 pb-2">
                        <div class="timeline-icon bg-info text-white position-absolute rounded-circle d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; left: -13px; top: 0;">
                            <i class="bi bi-send" style="font-size: 10px;"></i>
                        </div>
                        <div class="fw-bold small text-info mb-1">Tagihan Dipublish (Terkirim ke Mitra)</div>
                        <div class="small text-muted">{{ \Carbon\Carbon::parse($tagihan->updated_at)->format('d M Y, H:i') }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        @if(in_array($tagihan->status, ['PUBLISHED', 'LUNAS']))
        <!-- Persistent WA Preview for Published Bills -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 border-start border-4 border-success">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3"><i class="bi bi-whatsapp text-success me-2"></i>Pesan Tagihan WA Mitra</h6>
                @php
                    $persistentPesan = "*PEMBERITAHUAN TAGIHAN PNBP*\n\n";
                    $persistentPesan .= "Yth. " . ($tagihan->mitra->nama_pihak ?? '') . ",\n\n";
                    $persistentPesan .= "Berikut adalah informasi tagihan layanan Anda:\n";
                    $persistentPesan .= "No Tagihan: *" . $tagihan->nomor_tagihan . "*\n";
                    $persistentPesan .= "Total Tagihan: *Rp " . number_format($tagihan->total_tagihan, 0, ',', '.') . "*\n\n";
                    $persistentPesan .= "Silakan lakukan pembayaran melalui Virtual Account Bank BTN berikut:\n";
                    $persistentPesan .= "💳 No VA: *" . ($tagihan->nomor_va ?? '-') . "*\n\n";
                    $persistentPesan .= "----------------------------------------\n";
                    $persistentPesan .= "ℹ️ *AKUN PORTAL MITRA*\n";
                    $persistentPesan .= "Silakan login menggunakan akun Mitra Anda yang sudah terdaftar.\n";
                    $persistentPesan .= "Login Portal: " . route('login') . "\n";
                    $persistentPesan .= "----------------------------------------\n\n";
                    $persistentPesan .= "Terima kasih atas kerja sama Anda.\n";
                    $persistentPesan .= "_Sistem Informasi Keuangan (SIKEREN)_";
                @endphp
                <div class="bg-light p-3 rounded border" style="white-space: pre-wrap; font-family: monospace; font-size: 0.8rem;">{{ $persistentPesan }}</div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Modal Tolak -->
<div class="modal fade" id="modalTolak" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <form action="{{ route('tagihan-jasa.reject', $tagihan->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title fw-bold">Tolak Dokumen Tagihan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-light border mb-4 small">
                        Dokumen yang ditolak akan dikembalikan ke status DITOLAK dan kreator harus memperbaikinya.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea name="catatan" class="form-control" rows="3" required placeholder="Jelaskan alasan dokumen ditolak..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger fw-bold"><i class="bi bi-x-circle me-1"></i> Tolak Tagihan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal Publish -->
<div class="modal fade" id="modalPublish" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <form action="{{ route('tagihan-jasa.publish', $tagihan->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title fw-bold">Publish Tagihan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-light border mb-4 small">
                        Tagihan akan diterbitkan (VA di-generate) dan notifikasi pesan instan otomatis dikirimkan via WhatsApp.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">No WhatsApp Tujuan <span class="text-danger">*</span></label>
                        <!-- Defaulting to user's testing number as requested -->
                        <input type="text" name="wa_tujuan" class="form-control" value="0895810274829" required>
                        <small class="text-muted">Ganti nomor ini jika ingin mengirim ke WhatsApp lain.</small>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success fw-bold" onclick="return confirm('Apakah Anda yakin ingin mem-publish dan mengirim WA?')">
                        <i class="bi bi-send me-1"></i> Publish & Kirim WA
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
