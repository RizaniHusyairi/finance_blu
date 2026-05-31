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
        <li class="menu-label">Menu Aplikasi</li>
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

        {{-- ===== Modul Administrasi (Super Admin) ===== --}}
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
            @hasanyrole('Super Admin|Operator BLU|Kepala Subbagian Keuangan dan Tata Usaha')
            <li><a href="{{ route('rekening-bank.index') }}"><i class="material-icons-outlined">arrow_right</i>Rekening Bank</a>
            </li>
            @endhasanyrole


          </ul>
        </li>
        @endhasanyrole
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
          </ul>
        </li>
        @endhasrole
        @hasanyrole('Admin Listrik|Admin Air')
        <li>
          <a href="{{ route('utilitas.dashboard') }}">
            <div class="parent-icon"><i class="material-icons-outlined">speed</i></div>
            <div class="menu-title">Catat Meter Utilitas</div>
          </a>
        </li>
        @endhasanyrole
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
        <li>
          <a href="{{ route('tagihan-jasa.index', ['tipe' => 'KONSESI']) }}">
            <div class="parent-icon"><i class="material-icons-outlined">receipt_long</i></div>
            <div class="menu-title">Tagihan Konsesi</div>
          </a>
        </li>
        @endhasrole
        @hasanyrole('Super Admin|Pejabat Pengadaan')
        <li>
          <a href="{{ route('contracts.index') }}">
            <div class="parent-icon"><i class="material-icons-outlined">description</i>
            </div>
            <div class="menu-title">Manajemen Kontrak</div>
          </a>
        </li>
        <li>
          <a href="{{ route('document-numbers.index') }}">
            <div class="parent-icon"><i class="material-icons-outlined">confirmation_number</i>
            </div>
            <div class="menu-title">Nomor Dokumen</div>
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
            <a href="javascript:;" class="has-arrow">
              <div class="parent-icon">
                <i class="material-icons-outlined">payments</i>
              </div>
              <div class="menu-title">Manajemen Honor</div>
            </a>
            <ul>
              <li>
                <a href="{{ route('honorarium.index') }}">
                  <i class="material-icons-outlined">arrow_right</i>Data Honorarium
                </a>
              </li>
            </ul>
          </li>
          @endhasanyrole
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

        {{-- Log Tagihan Bulanan untuk Kasi PK, KPA (read-only).
             KPA juga memperoleh akses ke Jatuh Tempo. --}}
        @hasanyrole('Kepala Seksi Pelayanan dan Kerjasama|KPA|PLT/PLH')
        @unlessrole('Super Admin|Super Admin Jasa|Koordinator Jasa|Admin Jasa')
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">receipt_long</i></div>
            <div class="menu-title">Riwayat</div>
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
          </ul>
        </li>
        @hasanyrole('Super Admin|Super Admin Jasa')
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">receipt_long</i></div>
            <div class="menu-title">Tagihan</div>
          </a>
          <ul>
            @hasrole('Super Admin')
              <li>
                <a href="{{ route('tagihan-jasa.create') }}">
                  <i class="material-icons-outlined">arrow_right</i>Buat Tagihan
                </a>
              </li>
            @endhasrole
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
        @hasanyrole('Super Admin|Super Admin Jasa|Bendahara Penerimaan|Koordinator Jasa|Kepala Seksi Pelayanan dan Kerjasama|')
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
        @endhasanyrole

        @hasrole('PPK')
        {{-- placeholder agar @endhasrole di bawah tidak orphan --}}

        @endhasrole

        @hasanyrole('PPK|Kepala Subbagian Keuangan dan Tata Usaha|Koordinator Keuangan')
        {{-- Verifikasi SPP (Terpadu 3 Role) --}}
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">history_edu</i></div>
            <div class="menu-title">Verifikasi SPP</div>
          </a>
          <ul>
            <li><a href="{{ route('verifikasi-spp.kontrak.index') }}"><i class="material-icons-outlined">arrow_right</i>Kontrak</a></li>
            <li><a href="{{ route('verifikasi-spp.perjaldin.index') }}"><i class="material-icons-outlined">arrow_right</i>Perjaldin</a></li>
            <li><a href="{{ route('verifikasi-spp.honor.index') }}"><i class="material-icons-outlined">arrow_right</i>Honor</a></li>
          </ul>
        </li>
        @endhasanyrole

        @php
            $sidebarUser = auth()->user();
            $addSidebarItem = function (&$items, string $label, string $route, ?string $badge = null) {
                if (! Route::has($route)) return;
                $key = $label;
                if (! isset($items[$key])) {
                    $items[$key] = ['label' => $label, 'route' => $route, 'badges' => []];
                }
                if ($badge && ! in_array($badge, $items[$key]['badges'], true)) {
                    $items[$key]['badges'][] = $badge;
                }
            };

            $spmVerifyItems = [];
            if ($sidebarUser?->hasRole('PPSPM')) {
                $addSidebarItem($spmVerifyItems, 'Kontrak', 'verifikasi-ppspm.spm.kontrak.index', 'PPSPM');
                $addSidebarItem($spmVerifyItems, 'Perjaldin', 'verifikasi-ppspm.spm-perjaldin.index', 'PPSPM');
            }
            if ($sidebarUser?->hasRole('Koordinator Keuangan')) {
                $addSidebarItem($spmVerifyItems, 'Kontrak', 'verifikasi-koordinator.spm.kontrak.index', 'Koord');
                $addSidebarItem($spmVerifyItems, 'Perjaldin', 'verifikasi-koordinator.spm-perjaldin.index', 'Koord');
            }
            if ($sidebarUser?->hasRole('Kepala Subbagian Keuangan dan Tata Usaha')) {
                $addSidebarItem($spmVerifyItems, 'Kontrak', 'verifikasi-kasubag.spm.kontrak.index', 'Kasubbag');
                $addSidebarItem($spmVerifyItems, 'Perjaldin', 'verifikasi-kasubag.spm-perjaldin.index', 'Kasubbag');
            }
            if ($sidebarUser?->hasAnyRole(['PPSPM', 'Koordinator Keuangan', 'Kepala Subbagian Keuangan dan Tata Usaha'])) {
                $addSidebarItem($spmVerifyItems, 'Honor', 'verifikasi-spm.honor.index');
            }

            $npiVerifyItems = [];
            if ($sidebarUser?->hasRole('PPK')) $addSidebarItem($npiVerifyItems, 'Kontrak', 'verifikasi-ppk.npi.kontrak.index', 'PPK');
            if ($sidebarUser?->hasRole('Kepala Subbagian Keuangan dan Tata Usaha')) $addSidebarItem($npiVerifyItems, 'Kontrak', 'verifikasi-kasubag.npi.kontrak.index', 'Kasubbag');
            if ($sidebarUser?->hasRole('Koordinator Keuangan')) $addSidebarItem($npiVerifyItems, 'Kontrak', 'verifikasi-koordinator.npi.kontrak.index', 'Koord');
            if ($sidebarUser?->hasRole('Bendahara Penerimaan')) $addSidebarItem($npiVerifyItems, 'Kontrak', 'verifikasi-bendahara-penerimaan.npi.kontrak.index', 'Ben. Terima');
            if ($sidebarUser?->hasAnyRole(['PPK', 'Kepala Subbagian Keuangan dan Tata Usaha', 'Koordinator Keuangan', 'Bendahara Penerimaan'])) {
                $addSidebarItem($npiVerifyItems, 'Perjaldin', 'verifikasi-npi.perjaldin.index');
                $addSidebarItem($npiVerifyItems, 'Honor', 'verifikasi-npi.honor.index');
            }

            $sp2dVerifyItems = [];
            if ($sidebarUser?->hasRole('PPK')) $addSidebarItem($sp2dVerifyItems, 'Kontrak', 'verifikasi-ppk.sp2d.kontrak.index', 'PPK');
            if ($sidebarUser?->hasRole('PPSPM')) $addSidebarItem($sp2dVerifyItems, 'Kontrak', 'verifikasi-ppspm.sp2d.kontrak.index', 'PPSPM');
            if ($sidebarUser?->hasRole('Koordinator Keuangan')) $addSidebarItem($sp2dVerifyItems, 'Kontrak', 'verifikasi-koordinator.sp2d.kontrak.index', 'Koord');
            if ($sidebarUser?->hasRole('Kepala Subbagian Keuangan dan Tata Usaha')) $addSidebarItem($sp2dVerifyItems, 'Kontrak', 'verifikasi-kasubag.sp2d.kontrak.index', 'Kasubbag');
            if ($sidebarUser?->hasAnyRole(['PPK', 'PPSPM', 'Koordinator Keuangan', 'Kepala Subbagian Keuangan dan Tata Usaha'])) {
                $addSidebarItem($sp2dVerifyItems, 'Perjaldin', 'verifikasi-sp2d.perjaldin.index');
                $addSidebarItem($sp2dVerifyItems, 'Honor', 'verifikasi-sp2d.honor.index');
            }
        @endphp

        @if(!empty($spmVerifyItems))
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">verified_user</i></div>
            <div class="menu-title">Verifikasi SPM</div>
          </a>
          <ul>
            @foreach($spmVerifyItems as $item)
            <li><a href="{{ route($item['route']) }}"><i class="material-icons-outlined">arrow_right</i>{{ $item['label'] }}</a></li>
            @endforeach
          </ul>
        </li>
        @endif

        @if(!empty($npiVerifyItems))
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">receipt_long</i></div>
            <div class="menu-title">Verifikasi NPI</div>
          </a>
          <ul>
            @foreach($npiVerifyItems as $item)
            <li><a href="{{ route($item['route']) }}"><i class="material-icons-outlined">arrow_right</i>{{ $item['label'] }}</a></li>
            @endforeach
          </ul>
        </li>
        @endif

        @if(!empty($sp2dVerifyItems))
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">account_balance</i></div>
            <div class="menu-title">Verifikasi SP2D</div>
          </a>
          <ul>
            @foreach($sp2dVerifyItems as $item)
            <li><a href="{{ route($item['route']) }}"><i class="material-icons-outlined">arrow_right</i>{{ $item['label'] }}</a></li>
            @endforeach
          </ul>
        </li>
        @endif
        @hasanyrole('Super Admin|Operator BLU')
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">print</i></div>
            <div class="menu-title">Pembuatan SPP</div>
          </a>
          <ul>
            <li><a href="{{ route('spps.perjaldin.index') }}"><i class="material-icons-outlined">arrow_right</i>SPP Perjaldin</a></li>
            <li><a href="{{ route('spps.honor.index') }}"><i class="material-icons-outlined">arrow_right</i>SPP Honor</a></li>
            <li><a href="{{ route('spps.kontrak.index') }}"><i class="material-icons-outlined">arrow_right</i>SPP Kontrak</a></li>
          </ul>
        </li>
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">note_add</i></div>
            <div class="menu-title">Pembuatan SPM</div>
          </a>
          <ul>
            <li><a href="{{ route('spms.perjaldin.index') }}"><i class="material-icons-outlined">arrow_right</i>SPM Perjaldin</a></li>
            <li><a href="{{ route('spms.honor.index') }}"><i class="material-icons-outlined">arrow_right</i>SPM Honor</a></li>
            <li><a href="{{ route('spms.kontrak.index') }}"><i class="material-icons-outlined">arrow_right</i>SPM Kontrak</a></li>
          </ul>
        </li>
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">article</i></div>
            <div class="menu-title">Monitoring Pencairan</div>
          </a>
          <ul>
            {{-- TODO: Buat route monitoring jika controller sudah siap --}}
            <li><a href="#"><i class="material-icons-outlined">arrow_right</i>Kontrak</a></li>
            <li><a href="#"><i class="material-icons-outlined">arrow_right</i>Perjaldin</a></li>
            <li><a href="#"><i class="material-icons-outlined">arrow_right</i>Honor</a></li>
          </ul>
        </li>
        @endhasanyrole
        @hasrole('Bendahara Pengeluaran')

        {{-- Verifikasi Tagihan Perjaldin --}}

        {{-- Pembuatan NPI --}}
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">receipt_long</i></div>
            <div class="menu-title">Pembuatan NPI</div>
          </a>
          <ul>
            <li><a href="{{ route('npis.kontrak.index') }}"><i class="material-icons-outlined">arrow_right</i>Kontrak</a></li>
            <li><a href="{{ route('npis.perjaldin.index') }}"><i class="material-icons-outlined">arrow_right</i>Perjaldin</a></li>
            <li><a href="{{ route('npis.honor.index') }}"><i class="material-icons-outlined">arrow_right</i>Honor</a></li>
          </ul>
        </li>
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">account_balance</i></div>
            <div class="menu-title">Pencatatan SP2D</div>
          </a>
          <ul>
            <li><a href="{{ route('sp2ds.kontrak.index') }}"><i class="material-icons-outlined">arrow_right</i>Kontrak</a></li>
            <li><a href="{{ route('sp2ds.perjaldin.index') }}"><i class="material-icons-outlined">arrow_right</i>Perjaldin</a></li>
            <li><a href="{{ route('sp2ds.honor.index') }}"><i class="material-icons-outlined">arrow_right</i>Honor</a></li>
          </ul>
        </li>
        {{-- Penyetoran Pajak --}}
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">receipt</i></div>
            <div class="menu-title">Penyetoran Pajak</div>
          </a>
          <ul>
            <li><a href="{{ route('pajak-potongan.kontrak.index') }}"><i class="material-icons-outlined">arrow_right</i>Kontrak</a></li>
            <li><a href="{{ route('pajak-potongan.honor.index') }}"><i class="material-icons-outlined">arrow_right</i>Honor</a></li>
          </ul>
        </li>
        {{-- Penyetoran Pajak END --}}
        @endhasrole

        <!-- <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">how_to_reg</i></div>
            <div class="menu-title">Verifikasi NPI</div>
          </a>
          <ul>
            <li><a href="{{ route('verifikasi-bendahara-penerimaan.npi.kontrak.index') }}"><i class="material-icons-outlined">arrow_right</i>Kontrak</a></li>
            <li><a href="{{ route('verifikasi-npi.perjaldin.index') }}"><i class="material-icons-outlined">arrow_right</i>Perjaldin</a></li>
            <li><a href="{{ route('verifikasi-npi.honor.index') }}"><i class="material-icons-outlined">arrow_right</i>Honor</a></li>
          </ul>
        </li> -->
        @hasanyrole('Bendahara Pengeluaran|Bendahara Penerimaan')
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">summarize</i></div>
            <div class="menu-title">Pembukuan</div>
          </a>
          <ul>
            <li><a href="{{ route('rekening-bank.index') }}"><i class="material-icons-outlined">arrow_right</i>Rekening Bank</a></li>
            <li><a href="{{ route('pembukuan.bku.index') }}"><i class="material-icons-outlined">arrow_right</i>Buku Kas Umum</a></li>
            <li><a href="{{ route('pembukuan.bank.index') }}"><i class="material-icons-outlined">arrow_right</i>Buku Pembantu Bank</a></li>
            <li><a href="{{ route('pembukuan.bendahara.index') }}"><i class="material-icons-outlined">arrow_right</i>Buku Pembantu Bendahara</a></li>
            <li><a href="{{ route('pembukuan.bunga.index') }}"><i class="material-icons-outlined">arrow_right</i>Buku Pembantu Bunga Rekening</a></li>
            @hasrole('Bendahara Pengeluaran')
            <li><a href="{{ route('pembukuan.pajak.index') }}"><i class="material-icons-outlined">arrow_right</i>Buku Pembantu Pajak</a></li>
            @endhasrole
            <li><a href="{{ route('pembukuan.pengesahan.index') }}"><i class="material-icons-outlined">arrow_right</i>Buku Pengesahan Belanja</a></li>
            @hasrole('Bendahara Penerimaan')
            <li><a href="{{ route('pembukuan.piutang.index') }}"><i class="material-icons-outlined">arrow_right</i>Pengecekan Pembayaran (Piutang)</a></li>
            @endhasrole
          </ul>
        </li>
        @endhasanyrole

      @endauth
      <!--end navigation-->
  </div>
</aside>
<!--end sidebar-->
