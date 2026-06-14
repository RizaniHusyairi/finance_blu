<!--start sidebar-->
<aside class="sidebar-wrapper" data-simplebar="true">
  <div class="sidebar-header">
    <div class="logo-icon">
      <img src="{{ URL::asset('logo/minilogo-sikeren.png') }}" class="logo-img" alt="">
    </div>
    <div class="logo-name flex-grow-1">
      <h5 class="mb-0" style="font-size: 19px;">SIKEREN-BLU</h5>
        </div>
    <div class="sidebar-close">
      <span class="material-icons-outlined">close</span>
    </div>
  </div>
  <div class="sidebar-nav">
    <!--navigation-->
    <ul class="metismenu" id="sidenav">
      @auth
        {{-- ════════════════════════════════════════════════════
             UTAMA
             ════════════════════════════════════════════════════ --}}
        <li class="menu-label">Utama</li>
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">home</i>
            </div>
            <div class="menu-title">Dashboard</div>
          </a>
          <ul>
            @unlessrole('Mitra|Mitra Jasa|Koordinator Jasa')
            <li><a href="{{ route('dashboard') }}"><i class="material-icons-outlined">arrow_right</i>Dashboard
                Internal</a>
            </li>
            @endunlessrole
            @hasrole('PPSPM')
            <li><a href="{{ route('dashboard.ppspm') }}"><i class="material-icons-outlined">arrow_right</i>Dashboard PPSPM</a>
            </li>
            @endhasrole
            @hasrole('Koordinator Keuangan')
            <li><a href="{{ route('dashboard.koordinator-keuangan') }}"><i class="material-icons-outlined">arrow_right</i>Dashboard Koordinator Keuangan</a>
            </li>
            @endhasrole
            @hasanyrole('Super Admin|Super Admin Jasa')
            <li><a href="{{ route('super-admin-jasa.dashboard') }}"><i class="material-icons-outlined">arrow_right</i>Dashboard Jasa</a>
            </li>
            @endhasanyrole
            @hasrole('Koordinator Jasa')
            @unlessrole('Super Admin|Super Admin Jasa')
            <li><a href="{{ route('koordinator-jasa.dashboard') }}"><i class="material-icons-outlined">arrow_right</i>Dashboard Koordinator Jasa</a>
            </li>
            @endunlessrole
            @endhasrole
            @hasrole('Admin Jasa')
            <li><a href="{{ route('admin-jasa.dashboard') }}"><i class="material-icons-outlined">arrow_right</i>Dashboard Admin Jasa</a>
            </li>
            @endhasrole
            @hasanyrole('Mitra|Mitra Jasa')
            <li><a href="{{ route('mitra.dashboard') }}"><i class="material-icons-outlined">arrow_right</i>Dashboard
                Mitra</a>
            </li>
            @hasrole('Mitra Jasa')
            <li><a href="{{ route('mitra.layanan-aktif') }}"><i class="material-icons-outlined">arrow_right</i>Layanan
                Aktif</a>
            </li>
            @php
                $mitraPjp2u = false;
                $mitraKonsesi = false;
                if (auth()->check() && auth()->user()->profilable instanceof \App\Models\MitraJasa) {
                    $mitraProfile = auth()->user()->profilable;
                    $mitraPjp2u = $mitraProfile->pjp2uAktif()->exists();
                    $layanans = $mitraProfile->layananJasaAktif()->with('parent.parent.parent.parent.parent')->get();
                    foreach ($layanans as $ly) {
                        if (($ly->mendukung_konsesi ?? false) || ($ly->tipe_layanan ?? null) === 'KONSESI') {
                            $mitraKonsesi = true;
                        }
                        $curr = $ly;
                        while ($curr) {
                            if (($curr->mendukung_konsesi ?? false) || ($curr->tipe_layanan ?? null) === 'KONSESI' || stripos($curr->nama_layanan, 'Konsesi') !== false) {
                                $mitraKonsesi = true;
                            }
                            $curr = $curr->parent;
                        }
                    }
                }
            @endphp
            @if($mitraKonsesi)
            <li>
              <a href="javascript:;" class="has-arrow">
                <i class="material-icons-outlined">arrow_right</i>Laporan Konsesi
              </a>
              <ul>
                <li><a href="{{ route('mitra.konsesi-penjualan') }}"><i class="material-icons-outlined">arrow_right</i>Riwayat Laporan</a></li>
                <li><a href="{{ route('mitra.penjualan.create') }}"><i class="material-icons-outlined">arrow_right</i>Input Laporan</a></li>
              </ul>
            </li>
            @endif
            @if($mitraPjp2u)
            <li>
              <a href="javascript:;" class="has-arrow">
                <i class="material-icons-outlined">arrow_right</i>Laporan PAX PJP2U
              </a>
              <ul>
                <li><a href="{{ route('mitra.pjp2u-penjualan') }}"><i class="material-icons-outlined">arrow_right</i>Riwayat Laporan</a></li>
                <li><a href="{{ route('mitra.pax.create') }}"><i class="material-icons-outlined">arrow_right</i>Input Laporan</a></li>
              </ul>
            </li>
            @endif
            <li><a href="{{ route('mitra.profile') }}"><i class="material-icons-outlined">arrow_right</i>Profil &
                Password</a>
            </li>
            @endhasrole
            @endhasanyrole
          </ul>
        </li>

        {{-- ════════════════════════════════════════════════════
             PERSETUJUAN & VERIFIKASI
             ════════════════════════════════════════════════════ --}}
        @hasanyrole('Super Admin|Super Admin Jasa|KPA|PLT/PLH|PPK|PPSPM|Koordinator Keuangan|Bendahara Pengeluaran|Bendahara Penerimaan|Kepala Subbagian Keuangan dan Tata Usaha|Koordinator Jasa|Kepala Seksi Pelayanan dan Kerjasama|Admin Jasa')
        <li class="menu-label">Persetujuan &amp; Verifikasi</li>
        @endhasanyrole

        {{-- Standing Instruction (KPA) --}}
        @hasanyrole('Super Admin|KPA|PLT/PLH')
        <li>
          <a href="{{ route('standing-instruction.index') }}">
            <div class="parent-icon"><i class="material-icons-outlined">fact_check</i></div>
            <div class="menu-title">Standing Instruction</div>
          </a>
        </li>
        @endhasanyrole

        @hasrole('PPK')
        <li>
          <a href="{{ route('contracts.verifikasi') }}">
            <div class="parent-icon"><i class="material-icons-outlined">verified</i>
            </div>
            <div class="menu-title">Approve Kontrak</div>
          </a>
        </li>
        @endhasrole

        {{-- Verifikasi Tagihan — seragam untuk SEMUA verifikator (PPK, PPSPM, Koor.Keu, Bendahara×2, Kasubbag, Koord. Jasa, Kasi Pelayanan & Kerjasama, KPA) --}}
        @hasanyrole('PPK|PPSPM|Koordinator Keuangan|Bendahara Pengeluaran|Bendahara Penerimaan|Kepala Subbagian Keuangan dan Tata Usaha|Koordinator Jasa|Kepala Seksi Pelayanan dan Kerjasama|KPA|PLT/PLH')
        @php
            // Perjaldin masih memakai route per-role. Honorarium sudah disatukan ke endpoint terpadu
            // `verifikasi-tagihan-honorarium.*` yang dapat melayani 6 role + user dual-role dalam satu halaman.
            $u = auth()->user();
            $roleRouteMap = [
                'PPK' => ['perjaldin' => 'verifikasi-ppk.perjaldin.index', 'badge' => 'PPK'],
                'PPSPM' => ['perjaldin' => 'verifikasi-ppspm.perjaldin.index', 'badge' => 'PPSPM'],
                'Koordinator Keuangan' => ['perjaldin' => 'verifikasi-koordinator.perjaldin.index', 'badge' => 'Koordinator'],
                'Bendahara Pengeluaran' => ['perjaldin' => 'verifikasi-bendahara.perjaldin.index', 'badge' => 'Bend. Keluar'],
                'Bendahara Penerimaan' => ['perjaldin' => 'verifikasi-bendahara-penerimaan.perjaldin.index', 'badge' => 'Bend. Terima'],
                'Kepala Subbagian Keuangan dan Tata Usaha' => ['perjaldin' => 'verifikasi-kasubag.index', 'badge' => 'Kasubbag'],
            ];
            $perjaldinLinks = [];
            foreach ($roleRouteMap as $role => $cfg) {
                if ($u?->hasRole($role)) {
                    if (Route::has($cfg['perjaldin']))  $perjaldinLinks[]  = ['route' => $cfg['perjaldin'],  'badge' => $cfg['badge']];
                }
            }
            $showBadge = count($perjaldinLinks) > 1;
            $isJasaOnlyVerifier = $u?->hasAnyRole(['Koordinator Jasa', 'Kepala Seksi Pelayanan dan Kerjasama', 'KPA', 'PLT/PLH'])
                && ! $u?->hasAnyRole(['PPK', 'PPSPM', 'Koordinator Keuangan', 'Bendahara Pengeluaran', 'Bendahara Penerimaan', 'Kepala Subbagian Keuangan dan Tata Usaha']);
        @endphp
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">fact_check</i></div>
            <div class="menu-title">{{ $isJasaOnlyVerifier ? 'Verifikasi Tagihan Jasa' : 'Verifikasi Tagihan' }}</div>
          </a>
          <ul>
            @if($isJasaOnlyVerifier)
            <li>
              <a href="{{ route('verifikasi-tagihan-jasa.index') }}">
                <i class="material-icons-outlined">arrow_right</i>Tagihan Jasa
              </a>
            </li>
            @else
            <li>
              <a href="{{ route('verifikasi-tagihan-kontrak.index') }}">
                <i class="material-icons-outlined">arrow_right</i>Kontrak
              </a>
            </li>
            @if(count($perjaldinLinks) > 0)
            @php
                $combinedRoute = $perjaldinLinks[0]['route'];
                $combinedBadges = collect($perjaldinLinks)->pluck('badge')->join(' & ');
            @endphp
            <li>
              <a href="{{ route($combinedRoute) }}">
                <i class="material-icons-outlined">arrow_right</i>Perjaldin
                @if(count($perjaldinLinks) > 1)
                  <small class="badge bg-info ms-1" style="font-size:9px" title="{{ $combinedBadges }}">{{ count($perjaldinLinks) }} Peran</small>
                @endif
              </a>
            </li>
            @else
            <li>
              <a href="javascript:;" class="text-muted" style="cursor: not-allowed;">
                <i class="material-icons-outlined">arrow_right</i>Perjaldin <small class="badge bg-secondary ms-1">soon</small>
              </a>
            </li>
            @endif
            <li>
              <a href="{{ route('verifikasi-tagihan-honorarium.index') }}">
                <i class="material-icons-outlined">arrow_right</i>Honorarium
              </a>
            </li>
            @unless($u?->hasAnyRole(['PPK', 'PPSPM', 'Koordinator Keuangan', 'Bendahara Pengeluaran', 'Bendahara Penerimaan']))
            <li>
              <a href="{{ route('verifikasi-tagihan-jasa.index') }}">
                <i class="material-icons-outlined">arrow_right</i>Tagihan Jasa
              </a>
            </li>
            @endunless
            @endif
          </ul>
        </li>
        @endhasanyrole

        {{-- Verifikasi Laporan Jasa (Konsesi/PJP2U/Utilitas) --}}
        @hasanyrole('Super Admin|Super Admin Jasa|Kepala Seksi Pelayanan dan Kerjasama|Kepala Subbagian Keuangan dan Tata Usaha|KPA|PLT/PLH')
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">fact_check</i></div>
            <div class="menu-title">Verifikasi Laporan</div>
          </a>
          <ul>
            <li>
              <a href="{{ route('jasa.mitra.penjualan.index') }}">
                <i class="material-icons-outlined">arrow_right</i>Konsesi
              </a>
            </li>
            <li>
              <a href="{{ route('jasa.mitra.pjp2u.index') }}">
                <i class="material-icons-outlined">arrow_right</i>PAX PJP2U
              </a>
            </li>
            @hasrole('Super Admin Jasa')
            <li>
              <a href="{{ route('jasa.utilitas.index') }}">
                <i class="material-icons-outlined">arrow_right</i>Utilitas
              </a>
            </li>
            @endhasrole
            <li>
              <a href="{{ route('jasa.monitoring-pelaporan.index') }}">
                <i class="material-icons-outlined">arrow_right</i>Monitoring Pelaporan
              </a>
            </li>
          </ul>
        </li>
        @endhasanyrole

        {{-- Verifikasi laporan mitra (Admin Jasa) --}}
        @hasrole('Admin Jasa')
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">fact_check</i></div>
            <div class="menu-title">Verifikasi</div>
          </a>
          <ul>
            <li>
              <a href="{{ route('jasa.mitra.penjualan.index') }}">
                <i class="material-icons-outlined">arrow_right</i>Konsesi
              </a>
            </li>
            <li>
              <a href="{{ route('jasa.mitra.pjp2u.index') }}">
                <i class="material-icons-outlined">arrow_right</i>PAX PJP2U
              </a>
            </li>
            <li>
              <a href="{{ route('jasa.utilitas.index') }}">
                <i class="material-icons-outlined">arrow_right</i>Laporan Utilitas
              </a>
            </li>
            <li>
              <a href="{{ route('jasa.monitoring-pelaporan.index') }}">
                <i class="material-icons-outlined">arrow_right</i>Monitoring Pelaporan
              </a>
            </li>
          </ul>
        </li>
        @endhasrole

        {{-- Verifikasi laporan mitra (Admin Konsesi) --}}
        @hasrole('Admin Konsesi')
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">fact_check</i></div>
            <div class="menu-title">Laporan Mitra</div>
          </a>
          <ul>
            <li>
              <a href="{{ route('jasa.mitra.penjualan.index') }}">
                <i class="material-icons-outlined">arrow_right</i>Konsesi
              </a>
            </li>
            <li>
              <a href="{{ route('jasa.mitra.pjp2u.index') }}">
                <i class="material-icons-outlined">arrow_right</i>PAX PJP2U
              </a>
            </li>
          </ul>
        </li>
        @endhasrole

        {{-- ════════════════════════════════════════════════════
             TAGIHAN & PENCAIRAN
             ════════════════════════════════════════════════════ --}}
        @hasanyrole('Super Admin|Super Admin Jasa|Operator BLU|PPK|PPSPM|Bendahara Pengeluaran|Bendahara Penerimaan|Koordinator Keuangan|Kepala Subbagian Keuangan dan Tata Usaha|KPA|PLT/PLH|Pejabat Pengadaan|Operator Perjaldin|PPABP|Admin Jasa|Admin Konsesi|Koordinator Jasa|Kepala Seksi Pelayanan dan Kerjasama|Admin Listrik|Admin Air')
        <li class="menu-label">Tagihan &amp; Pencairan</li>
        @endhasanyrole

        @hasanyrole('Super Admin|Operator BLU|PPK|PPSPM|Bendahara Pengeluaran|Bendahara Penerimaan|Koordinator Keuangan|Kepala Subbagian Keuangan dan Tata Usaha|KPA')
        <li>
          <a href="{{ route('proses-tagihan.index') }}">
            <div class="parent-icon"><i class="material-icons-outlined">account_tree</i></div>
            <div class="menu-title">Proses Tagihan</div>
          </a>
        </li>
        @endhasanyrole

        @hasanyrole('Super Admin|Pejabat Pengadaan')
        <li>
          <a href="{{ route('contracts.index') }}">
            <div class="parent-icon"><i class="material-icons-outlined">description</i>
            </div>
            <div class="menu-title">Manajemen Kontrak</div>
          </a>
        </li>
        @endhasanyrole

        @hasrole('Operator Perjaldin')
        <li>
          <a href="{{ route('perjaldins.index') }}">
            <div class="parent-icon"><i class="material-icons-outlined">flight_takeoff</i>
            </div>
            <div class="menu-title">Manajemen Perjaldin</div>
          </a>
        </li>
        @endhasrole

        @hasanyrole('Super Admin|PPABP')
        <li>
          <a href="{{ route('honorarium.index') }}">
            <div class="parent-icon"><i class="material-icons-outlined">payments</i></div>
            <div class="menu-title">Manajemen Honor</div>
          </a>
        </li>
        @endhasanyrole

        {{-- Tagihan jasa (Admin Jasa) --}}
        @hasrole('Admin Jasa')
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">receipt_long</i></div>
            <div class="menu-title">Tagihan</div>
          </a>
          <ul>
            @unlessrole('Super Admin Jasa')
              <li>
                <a href="{{ route('tagihan-jasa.create') }}">
                  <i class="material-icons-outlined">arrow_right</i>Buat Tagihan
                </a>
              </li>
            @endunlessrole
            <li>
              <a href="{{ route('admin-jasa.tagihan.log-bulanan') }}">
                <i class="material-icons-outlined">arrow_right</i>Log Tagihan Bulanan
              </a>
            </li>
            <li>
              <a href="{{ route('admin-jasa.tagihan.jatuh-tempo') }}">
                <i class="material-icons-outlined">arrow_right</i>Jatuh Tempo
              </a>
            </li>
            <li>
              <a href="{{ route('admin-jasa.panduan') }}">
                <i class="material-icons-outlined">arrow_right</i>Panduan Admin Jasa
              </a>
            </li>
          </ul>
        </li>
        @endhasrole

        {{-- Tagihan jasa (Super Admin / Super Admin Jasa) --}}
        @hasanyrole('Super Admin|Super Admin Jasa')
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">receipt_long</i></div>
            <div class="menu-title">Tagihan Jasa</div>
          </a>
          <ul>
            <li>
              <a href="{{ route('admin-jasa.tagihan.log-bulanan') }}">
                <i class="material-icons-outlined">arrow_right</i>Log Tagihan Bulanan
              </a>
            </li>
            <li>
              <a href="{{ route('admin-jasa.tagihan.jatuh-tempo') }}">
                <i class="material-icons-outlined">arrow_right</i>Jatuh Tempo
              </a>
            </li>
            <li>
              <a href="{{ route('nomor-tagihan-jasa.index') }}">
                <i class="material-icons-outlined">arrow_right</i>Nomor Tagihan
              </a>
            </li>
          </ul>
        </li>
        @endhasanyrole

        {{-- Tagihan jasa (Koordinator Jasa) --}}
        @hasrole('Koordinator Jasa')
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">receipt_long</i></div>
            <div class="menu-title">Tagihan Jasa</div>
          </a>
          <ul>
            <li>
              <a href="{{ route('admin-jasa.tagihan.log-bulanan') }}">
                <i class="material-icons-outlined">arrow_right</i>Log Tagihan Bulanan
              </a>
            </li>
            <li>
              <a href="{{ route('admin-jasa.tagihan.jatuh-tempo') }}">
                <i class="material-icons-outlined">arrow_right</i>Jatuh Tempo
              </a>
            </li>
          </ul>
        </li>
        @endhasrole

        @hasrole('Admin Konsesi')
        <li>
          <a href="{{ route('tagihan-jasa.index', ['tipe' => 'KONSESI']) }}">
            <div class="parent-icon"><i class="material-icons-outlined">receipt_long</i></div>
            <div class="menu-title">Tagihan Konsesi</div>
          </a>
        </li>
        @endhasrole

        {{-- Log Tagihan Bulanan untuk Kasi PK, KPA (read-only).
             KPA juga memperoleh akses ke Jatuh Tempo. --}}
        @hasanyrole('Kepala Seksi Pelayanan dan Kerjasama|KPA|PLT/PLH')
        @unlessrole('Super Admin|Super Admin Jasa|Koordinator Jasa|Admin Jasa')
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">receipt_long</i></div>
            <div class="menu-title">Riwayat Tagihan Jasa</div>
          </a>
          <ul>
            <li>
              <a href="{{ route('admin-jasa.tagihan.log-bulanan') }}">
                <i class="material-icons-outlined">arrow_right</i>Log Tagihan Bulanan
              </a>
            </li>
            @hasanyrole('KPA|PLT/PLH')
            <li>
              <a href="{{ route('admin-jasa.tagihan.jatuh-tempo') }}">
                <i class="material-icons-outlined">arrow_right</i>Jatuh Tempo
              </a>
            </li>
            @endhasanyrole
          </ul>
        </li>
        @endunlessrole
        @endhasanyrole

        @hasanyrole('Admin Listrik|Admin Air')
        <li>
          <a href="{{ route('utilitas.dashboard') }}">
            <div class="parent-icon"><i class="material-icons-outlined">speed</i></div>
            <div class="menu-title">Catat Meter Utilitas</div>
          </a>
        </li>
        @endhasanyrole

        {{-- ════════════════════════════════════════════════════
             KELOLA LAYANAN JASA
             ════════════════════════════════════════════════════ --}}
        @hasanyrole('Super Admin|Super Admin Jasa|Admin Jasa')
        <li class="menu-label">Layanan Jasa</li>
        @endhasanyrole

        @hasanyrole('Super Admin|Super Admin Jasa')
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">settings</i></div>
            <div class="menu-title">Kelola Jasa</div>
          </a>
          <ul>
            <li>
              <a href="{{ route('jasa.mitra.index') }}">
                <i class="material-icons-outlined">arrow_right</i>Mitra Jasa
              </a>
            </li>
            <li>
              <a href="{{ route('jasa.admin.index') }}">
                <i class="material-icons-outlined">arrow_right</i>Admin Jasa
              </a>
            </li>
            <li>
              <a href="{{ route('master-layanan-jasa.index') }}">
                <i class="material-icons-outlined">arrow_right</i>Layanan Jasa
              </a>
            </li>
            @hasrole('Super Admin')
            <li>
              <a href="{{ route('jasa.integrasi.index') }}">
                <i class="material-icons-outlined">arrow_right</i>Integrasi API
              </a>
            </li>
            @endhasrole
          </ul>
        </li>
        @endhasanyrole

        @hasrole('Admin Jasa')
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">settings</i></div>
            <div class="menu-title">Kelola</div>
          </a>
          <ul>
            <li>
              <a href="{{ route('master-layanan-jasa.index') }}">
                <i class="material-icons-outlined">arrow_right</i>Layanan Dikelola
              </a>
            </li>
            <li>
              <a href="{{ route('admin-jasa.mitra') }}">
                <i class="material-icons-outlined">arrow_right</i>Mitra Jasa
              </a>
            </li>
            @hasrole('Super Admin')
            <li>
              <a href="{{ route('jasa.integrasi.index') }}">
                <i class="material-icons-outlined">arrow_right</i>Integrasi API
              </a>
            </li>
            @endhasrole
          </ul>
        </li>
        @endhasrole

        {{-- ════════════════════════════════════════════════════
             PEMBUKUAN & LAPORAN
             ════════════════════════════════════════════════════ --}}
        @hasanyrole('Super Admin|Super Admin Jasa|Bendahara Pengeluaran|Bendahara Penerimaan')
        <li class="menu-label">Pembukuan &amp; Laporan</li>
        @endhasanyrole

        @hasanyrole('Bendahara Pengeluaran|Bendahara Penerimaan|Super Admin')
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">summarize</i></div>
            <div class="menu-title">Pembukuan</div>
          </a>
          <ul>
            <li><a href="{{ route('pembukuan.bku.index') }}"><i class="material-icons-outlined">arrow_right</i>Buku Kas Umum</a></li>
            <li><a href="{{ route('pembukuan.bank.index') }}"><i class="material-icons-outlined">arrow_right</i>Buku Pembantu Bank</a></li>
            <li><a href="{{ route('pembukuan.bendahara.index') }}"><i class="material-icons-outlined">arrow_right</i>Buku Pembantu Bendahara</a></li>
            <li><a href="{{ route('pembukuan.bunga.index') }}"><i class="material-icons-outlined">arrow_right</i>Buku Pembantu Bunga Rekening</a></li>
            @hasrole('Bendahara Pengeluaran')
            <li><a href="{{ route('pembukuan.pajak.index') }}"><i class="material-icons-outlined">arrow_right</i>Buku Pembantu Pajak</a></li>
            @endhasrole
            {{-- Pengesahan Belanja khusus Bendahara Pengeluaran; Bendahara
                 Penerimaan memakai Buku Pengesahan Pendapatan. --}}
            @hasanyrole('Bendahara Pengeluaran|Super Admin')
            <li><a href="{{ route('pembukuan.pengesahan.index') }}"><i class="material-icons-outlined">arrow_right</i>Buku Pengesahan Belanja</a></li>
            @endhasanyrole
            @hasanyrole('Bendahara Penerimaan|Super Admin')
            <li><a href="{{ route('pembukuan.pengesahan-pendapatan.index') }}"><i class="material-icons-outlined">arrow_right</i>Buku Pengesahan Pendapatan</a></li>
            @endhasanyrole
            @hasrole('Bendahara Penerimaan')
            <li><a href="{{ route('pembukuan.piutang.index') }}"><i class="material-icons-outlined">arrow_right</i>Pengecekan Pembayaran (Piutang)</a></li>
            @endhasrole
          </ul>
        </li>
        @endhasanyrole

        @hasanyrole('Super Admin|Super Admin Jasa|Bendahara Penerimaan')
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">assessment</i></div>
            <div class="menu-title">Laporan</div>
          </a>
          <ul>
            <li>
              <a href="{{ route('super-admin-jasa.laporan.rekap-tagihan') }}">
                <i class="material-icons-outlined">arrow_right</i>Rekap Tagihan
              </a>
            </li>
            <li>
              <a href="{{ route('super-admin-jasa.laporan.rekap-layanan') }}">
                <i class="material-icons-outlined">arrow_right</i>Rekap per Layanan
              </a>
            </li>
            <li>
              <a href="{{ route('super-admin-jasa.laporan.rekap-terima-setor') }}">
                <i class="material-icons-outlined">arrow_right</i>Rekap Terima Setor
              </a>
            </li>
            <li>
              <a href="{{ route('super-admin-jasa.laporan.rekap-pembayaran') }}">
                <i class="material-icons-outlined">arrow_right</i>Rekap Pembayaran
              </a>
            </li>
            <li>
              <a href="{{ route('super-admin-jasa.laporan.rekap-piutang') }}">
                <i class="material-icons-outlined">arrow_right</i>Rekap Piutang
              </a>
            </li>
            <li>
              <a href="{{ route('super-admin-jasa.laporan.performa-mitra') }}">
                <i class="material-icons-outlined">arrow_right</i>Performa Pembayaran Mitra
              </a>
            </li>
          </ul>
        </li>
        @endhasanyrole

        {{-- ════════════════════════════════════════════════════
             MASTER & ADMINISTRASI
             ════════════════════════════════════════════════════ --}}
        @hasanyrole('Super Admin|KPA|PLT/PLH|Kepala Subbagian Keuangan dan Tata Usaha|Pejabat Pengadaan|Operator BLU|PPK|Koordinator Keuangan|Operator Perjaldin')
        <li class="menu-label">Master &amp; Administrasi</li>
        @endhasanyrole

        @hasanyrole('Super Admin|KPA|PLT/PLH|Kepala Subbagian Keuangan dan Tata Usaha|Pejabat Pengadaan|Operator BLU|PPK')
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">folder</i>
            </div>
            <div class="menu-title">Master Data</div>
          </a>
          <ul>
            @hasanyrole('Super Admin|Pejabat Pengadaan')
            <li><a href="{{ route('suppliers.index') }}"><i class="material-icons-outlined">arrow_right</i>Vendor</a>
            </li>
            @endhasanyrole
            @hasanyrole('Super Admin|KPA|PLT/PLH|Operator BLU|PPK|Kepala Subbagian Keuangan dan Tata Usaha')
            <li><a href="{{ route('dipas.index') }}"><i class="material-icons-outlined">arrow_right</i>DIPA</a>
            </li>
            <li><a href="{{ route('coas.index') }}"><i class="material-icons-outlined">arrow_right</i>COA</a>
            </li>
            @endhasanyrole
            @hasanyrole('Super Admin|KPA|PLT/PLH|Operator BLU|Kepala Subbagian Keuangan dan Tata Usaha')
            <li><a href="{{ route('master-pajak.index') }}"><i class="material-icons-outlined">arrow_right</i>Pajak</a>
            </li>
            @endhasanyrole
          </ul>
        </li>
        @endhasanyrole

        @hasrole('Operator Perjaldin')
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">folder</i></div>
            <div class="menu-title">Master Data</div>
          </a>
          <ul>
            <li>
              <a href="{{ route('master-uang-harian-perjaldin.index') }}">
                <i class="material-icons-outlined">arrow_right</i>Uang Harian
              </a>
            </li>
          </ul>
        </li>
        @endhasrole

        @hasanyrole('Super Admin|Pejabat Pengadaan')
        <li>
          <a href="{{ route('document-numbers.index') }}">
            <div class="parent-icon"><i class="material-icons-outlined">confirmation_number</i>
            </div>
            <div class="menu-title">Nomor Dokumen</div>
          </a>
        </li>
        @endhasanyrole

        @hasanyrole('Super Admin|Koordinator Keuangan')
        <li>
          <a href="{{ route('surat-numbers.index') }}">
            <div class="parent-icon"><i class="material-icons-outlined">tag</i>
            </div>
            <div class="menu-title">Nomor Surat</div>
          </a>
        </li>
        @endhasanyrole

        @hasrole('Super Admin')
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">admin_panel_settings</i></div>
            <div class="menu-title">Administrasi</div>
          </a>
          <ul>
            <li><a href="{{ route('admin.users.index') }}"><i class="material-icons-outlined">arrow_right</i>Manajemen User</a></li>
            <li><a href="{{ route('admin.roles.index') }}"><i class="material-icons-outlined">arrow_right</i>Manajemen Role</a></li>
            <li><a href="{{ route('admin.pegawai.index') }}"><i class="material-icons-outlined">arrow_right</i>Data Pegawai</a></li>
            <li><a href="{{ route('admin.notifikasi-wa.index') }}"><i class="material-icons-outlined">arrow_right</i>Notifikasi WhatsApp</a></li>
          </ul>
        </li>
        @endhasrole

      @endauth
    </ul>
    <!--end navigation-->
  </div>
</aside>
<!--end sidebar-->
