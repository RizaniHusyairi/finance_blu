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
            @unlessrole('Mitra')
            <li><a href="{{ route('dashboard') }}"><i class="material-icons-outlined">arrow_right</i>Dashboard
                Internal</a>
            </li>
            @endunlessrole
            @role('Mitra')
            <li><a href="{{ route('mitra.dashboard') }}"><i class="material-icons-outlined">arrow_right</i>Portal
                Mitra</a>
            </li>
            @endrole
          </ul>
        </li>
        @hasanyrole('Super Admin|KPA|Kepala Subbagian Keuangan dan Tata Usaha|Kepala Seksi Pelayanan dan Kerjasama|Pejabat Pengadaan|Operator BLU')
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">folder</i>
            </div>
            <div class="menu-title">Master Data</div>
          </a>
          <ul>
            
           
            @hasanyrole('Super Admin|Pejabat Pengadaan')
            <li><a href="{{ route('suppliers.index') }}"><i class="material-icons-outlined">arrow_right</i>Supplier /
                Mitra</a>
            </li>
            @endhasanyrole
            @hasanyrole('Super Admin|KPA|Operator BLU|Kepala Subbagian Keuangan dan Tata Usaha|Kepala Seksi Pelayanan dan Kerjasama')
            <li><a href="{{ route('dipas.index') }}"><i class="material-icons-outlined">arrow_right</i>DIPA</a>
            </li>
            <li><a href="{{ route('coas.index') }}"><i class="material-icons-outlined">arrow_right</i>COA</a>
            </li>
            <li><a href="{{ route('master-pajak.index') }}"><i class="material-icons-outlined">arrow_right</i>Pajak</a>
            </li>
            @endhasanyrole
          </ul>
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
        <li>
          <a href="{{ route('document-numbers.index') }}">
            <div class="parent-icon"><i class="material-icons-outlined">confirmation_number</i>
            </div>
            <div class="menu-title">Nomor Dokumen</div>
          </a>
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
        @hasrole('PPK')
        {{-- 1. Verifikasi SPMK (khusus PPK) --}}
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">draw</i></div>
            <div class="menu-title">Verifikasi SPMK</div>
          </a>
          <ul>
            <li><a href="{{ route('contracts.verifikasi') }}"><i class="material-icons-outlined">arrow_right</i>Draft Kontrak & SPMK</a></li>
          </ul>
        </li>
        @endhasrole

        {{-- Verifikasi Tagihan — seragam untuk SEMUA verifikator (PPK, PPSPM, Koor.Keu, Bendahara×2, Kasubbag) --}}
        @hasanyrole('PPK|PPSPM|Koordinator Keuangan|Bendahara Pengeluaran|Bendahara Penerimaan|Kepala Subbagian Keuangan dan Tata Usaha|Koordinator Jasa')
        @php
            // Resolusi route Perjaldin & Honorarium berdasarkan role user yang login.
            // Setiap role punya namespace route-nya sendiri; kalau tidak ada → null (link disabled).
            $u = auth()->user();
            $perjaldinRoute = null; $honorariumRoute = null;
            if ($u?->hasRole('PPK')) {
                $perjaldinRoute  = Route::has('verifikasi-ppk.perjaldin.index')  ? 'verifikasi-ppk.perjaldin.index'  : null;
                $honorariumRoute = Route::has('verifikasi-ppk.honorarium.index') ? 'verifikasi-ppk.honorarium.index' : null;
            } elseif ($u?->hasRole('PPSPM')) {
                $perjaldinRoute  = Route::has('verifikasi-ppspm.perjaldin.index') ? 'verifikasi-ppspm.perjaldin.index' : null;
                $honorariumRoute = Route::has('verifikasi-ppspm.honorarium.index')? 'verifikasi-ppspm.honorarium.index': null;
            } elseif ($u?->hasRole('Bendahara Pengeluaran')) {
                $perjaldinRoute  = Route::has('verifikasi-bendahara.perjaldin.index') ? 'verifikasi-bendahara.perjaldin.index' : null;
                $honorariumRoute = Route::has('verifikasi-bendahara.honorarium.index')? 'verifikasi-bendahara.honorarium.index': null;
            } elseif ($u?->hasRole('Bendahara Penerimaan')) {
                $perjaldinRoute  = Route::has('verifikasi-bendahara-penerimaan.perjaldin.index') ? 'verifikasi-bendahara-penerimaan.perjaldin.index' : null;
                $honorariumRoute = Route::has('verifikasi-bendahara-penerimaan.honorarium.index')? 'verifikasi-bendahara-penerimaan.honorarium.index': null;
            } elseif ($u?->hasRole('Kepala Subbagian Keuangan dan Tata Usaha')) {
                $perjaldinRoute  = Route::has('verifikasi-kasubag.index') ? 'verifikasi-kasubag.index' : null;
                $honorariumRoute = Route::has('verifikasi-kasubag.honorarium.index')? 'verifikasi-kasubag.honorarium.index': null;
            }
            // Koordinator Keuangan: belum ada route Perjaldin/Honor — biarkan null
        @endphp
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">fact_check</i></div>
            <div class="menu-title">Verifikasi Tagihan</div>
          </a>
          <ul>
            <li>
              <a href="{{ route('verifikasi-tagihan-kontrak.index') }}">
                <i class="material-icons-outlined">arrow_right</i>Kontrak
              </a>
            </li>
            <li>
              @if($perjaldinRoute)
                <a href="{{ route($perjaldinRoute) }}"><i class="material-icons-outlined">arrow_right</i>Perjaldin</a>
              @else
                <a href="javascript:;" class="text-muted" style="cursor: not-allowed;" title="Belum tersedia untuk role Anda">
                  <i class="material-icons-outlined">arrow_right</i>Perjaldin <small class="badge bg-secondary ms-1">soon</small>
                </a>
              @endif
            </li>
            <li>
              @if($honorariumRoute)
                <a href="{{ route($honorariumRoute) }}"><i class="material-icons-outlined">arrow_right</i>Honorarium</a>
              @else
                <a href="javascript:;" class="text-muted" style="cursor: not-allowed;" title="Belum tersedia untuk role Anda">
                  <i class="material-icons-outlined">arrow_right</i>Honorarium <small class="badge bg-secondary ms-1">soon</small>
                </a>
              @endif
            </li>
            <li>
              <a href="{{ route('verifikasi-tagihan-jasa.index') }}">
                <i class="material-icons-outlined">arrow_right</i>PNBP (Jasa)
              </a>
            </li>
          </ul>
        </li>
        @endhasanyrole

        @hasanyrole('Super Admin|Admin Jasa|Koordinator Keuangan|Kepala Seksi Pelayanan dan Kerjasama|Kepala Subbagian Keuangan dan Tata Usaha|KPA')
        <li>
          <a href="{{ route('tagihan-jasa.index') }}">
            <div class="parent-icon"><i class="material-icons-outlined">receipt_long</i></div>
            <div class="menu-title">Penagihan PNBP (Jasa)</div>
          </a>
        </li>
        @endhasanyrole

        @hasrole('PPK')
        {{-- placeholder agar @endhasrole di bawah tidak orphan --}}

        {{-- 3. Verifikasi SPP --}}
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">history_edu</i></div>
            <div class="menu-title">Verifikasi SPP</div>
          </a>
          <ul>
            <li><a href="{{ route('verifikasi-ppk.spp.index') }}"><i class="material-icons-outlined">arrow_right</i>Kontrak</a></li>
            <li><a href="{{ route('verifikasi-ppk.spp-perjaldin.index') }}"><i class="material-icons-outlined">arrow_right</i>Perjaldin</a></li>
            <li><a href="{{ route('verifikasi-spp.honor.index') }}"><i class="material-icons-outlined">arrow_right</i>Honor</a></li>
          </ul>
        </li>

        {{-- 4. Verifikasi NPI --}}
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">receipt_long</i></div>
            <div class="menu-title">Verifikasi NPI</div>
          </a>
          <ul>
            <li><a href="{{ route('verifikasi-ppk.npi.kontrak.index') }}"><i class="material-icons-outlined">arrow_right</i>Kontrak</a></li>
            {{-- TODO: Buat route NPI Perjaldin & Honor khusus PPK jika controller sudah siap --}}
            <li><a href="{{ route('verifikasi-npi.perjaldin.index') }}"><i class="material-icons-outlined">arrow_right</i>Perjaldin</a></li>
            <li><a href="{{ route('verifikasi-npi.honor.index') }}"><i class="material-icons-outlined">arrow_right</i>Honor</a></li>
          </ul>
        </li>

        {{-- 5. Verifikasi SP2D --}}
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">account_balance</i></div>
            <div class="menu-title">Verifikasi SP2D</div>
          </a>
          <ul>
            {{-- TODO: Buat route SP2D Kontrak/Perjaldin/Honor khusus PPK jika controller sudah siap --}}
            <li><a href="{{ route('verifikasi-ppk.sp2d.kontrak.index') }}"><i class="material-icons-outlined">arrow_right</i>Kontrak</a></li>
            <li><a href="{{ route('verifikasi-sp2d.perjaldin.index') }}"><i class="material-icons-outlined">arrow_right</i>Perjaldin</a></li>
            <li><a href="{{ route('verifikasi-sp2d.honor.index') }}"><i class="material-icons-outlined">arrow_right</i>Honor</a></li>
          </ul>
        </li>

        {{-- 6. Monitoring & Laporan --}}
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">article</i></div>
            <div class="menu-title">Monitoring & Laporan</div>
          </a>
          <ul>
            <li><a href="#"><i class="material-icons-outlined">arrow_right</i>Pengawasan DIPA</a></li>
            <li><a href="#"><i class="material-icons-outlined">arrow_right</i>Arsip Kontrak</a></li>
            <li><a href="{{ route('perjaldin-blu.history') }}"><i class="material-icons-outlined">arrow_right</i>Arsip Perjaldin</a></li>
            <li><a href="#"><i class="material-icons-outlined">arrow_right</i>Arsip Honorarium</a></li>
            <li><a href="#"><i class="material-icons-outlined">arrow_right</i>Pelacakan SP2D</a></li>
          </ul>
        </li>
        @endhasrole
        @hasrole('Kepala Subbagian Keuangan dan Tata Usaha')
        
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">history_edu</i></div>
            <div class="menu-title">Verifikasi SPP</div>
          </a>
          <ul>
            <li>
              <a href="{{ route('verifikasi-kasubag.spp.index') }}"><i class="material-icons-outlined">arrow_right</i>Kontrak</a>
            </li>
            <li>
              <a href="{{ route('verifikasi-kasubag.spp-perjaldin.index') }}"><i class="material-icons-outlined">arrow_right</i>Perjaldin</a>
            </li>
            <li>
              <a href="{{ route('verifikasi-spp.honor.index') }}"><i class="material-icons-outlined">arrow_right</i>Honor</a>
            </li>
          </ul>
        </li>
        @endhasrole

        @hasrole('Koordinator Keuangan')
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">history_edu</i></div>
            <div class="menu-title">Verifikasi SPP</div>
          </a>
          <ul>
            <li>
              <a href="{{ route('verifikasi-koordinator.spp.index') }}"><i class="material-icons-outlined">arrow_right</i>Kontrak</a>
            </li>
            <li>
              <a href="{{ route('verifikasi-koordinator.spp-perjaldin.index') }}"><i class="material-icons-outlined">arrow_right</i>Perjaldin</a>
            </li>
            <li>
              <a href="{{ route('verifikasi-spp.honor.index') }}"><i class="material-icons-outlined">arrow_right</i>Honor</a>
            </li>
          </ul>
        </li>
        @endhasrole

        @hasrole('Kepala Subbagian Keuangan dan Tata Usaha')
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">fact_check</i></div>
            <div class="menu-title">Verifikasi SPM</div>
          </a>
          <ul>
            <li>
              <a href="{{ route('verifikasi-kasubag.spm.kontrak.index') }}"><i class="material-icons-outlined">arrow_right</i>Kontrak</a>
            </li>
            <li>
              <a href="{{ route('verifikasi-kasubag.spm-perjaldin.index') }}"><i class="material-icons-outlined">arrow_right</i>Perjaldin</a>
            </li>
            <li>
              <a href="{{ route('verifikasi-spm.honor.index') }}"><i class="material-icons-outlined">arrow_right</i>Honor</a>
            </li>
          </ul>
        </li>
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">receipt_long</i></div>
            <div class="menu-title">Verifikasi NPI</div>
          </a>
          <ul>
            <li>
              <a href="{{ route('verifikasi-kasubag.npi.kontrak.index') }}"><i class="material-icons-outlined">arrow_right</i>Kontrak</a>
            </li>
            <li>
              <a href="{{ route('verifikasi-npi.perjaldin.index') }}"><i class="material-icons-outlined">arrow_right</i>Perjaldin</a>
            </li>
            <li>
              <a href="{{ route('verifikasi-npi.honor.index') }}"><i class="material-icons-outlined">arrow_right</i>Honor</a>
            </li>
          </ul>
        </li>
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">account_balance</i></div>
            <div class="menu-title">Verifikasi SP2D</div>
          </a>
          <ul>
            <li>
              <a href="{{ route('verifikasi-kasubag.sp2d.kontrak.index') }}"><i class="material-icons-outlined">arrow_right</i>Kontrak</a>
            </li>
            <li>
              <a href="{{ route('verifikasi-sp2d.perjaldin.index') }}"><i class="material-icons-outlined">arrow_right</i>Perjaldin</a>
            </li>
            <li>
              <a href="{{ route('verifikasi-sp2d.honor.index') }}"><i class="material-icons-outlined">arrow_right</i>Honor</a>
            </li>
          </ul>
        </li>
        @endhasrole
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
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">history</i></div>
            <div class="menu-title">Riwayat Dokumen</div>
          </a>
          <ul>
            {{-- TODO: Buat route riwayat dokumen jika controller sudah siap --}}
            <li><a href="#"><i class="material-icons-outlined">arrow_right</i>SPP</a></li>
            <li><a href="#"><i class="material-icons-outlined">arrow_right</i>SPM</a></li>
          </ul>
        </li>
        @endhasanyrole
        @hasrole('PPSPM')
        
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">verified_user</i></div>
            <div class="menu-title">Verifikasi SPM</div>
          </a>
          <ul>
            <li><a href="{{ route('verifikasi-ppspm.spm-perjaldin.index') }}"><i class="material-icons-outlined">arrow_right</i>SPM Perjaldin</a></li>
            <li><a href="{{ route('verifikasi-spm.honor.index') }}"><i class="material-icons-outlined">arrow_right</i>SPM Honor</a></li>
            <li><a href="{{ route('verifikasi-ppspm.spm.kontrak.index') }}"><i class="material-icons-outlined">arrow_right</i>SPM Kontrak</a></li>
          </ul>
        </li>
        @endhasrole
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
          </ul>
        </li>
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
            <li><a href="{{ route('pembukuan.pajak.index') }}"><i class="material-icons-outlined">arrow_right</i>Buku Pembantu Pajak</a></li>
            <li><a href="{{ route('pembukuan.pengesahan.index') }}"><i class="material-icons-outlined">arrow_right</i>Buku Pengesahan Belanja</a></li>
          </ul>
        </li>
        @endhasrole
        @hasrole('Bendahara Penerimaan')
        
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">how_to_reg</i></div>
            <div class="menu-title">Verifikasi NPI</div>
          </a>
          <ul>
            <li><a href="{{ route('verifikasi-bendahara-penerimaan.npi.kontrak.index') }}"><i class="material-icons-outlined">arrow_right</i>Kontrak</a></li>
            <li><a href="{{ route('verifikasi-npi.perjaldin.index') }}"><i class="material-icons-outlined">arrow_right</i>Perjaldin</a></li>
            <li><a href="{{ route('verifikasi-npi.honor.index') }}"><i class="material-icons-outlined">arrow_right</i>Honor</a></li>
          </ul>
        </li>
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
            <li><a href="{{ route('pembukuan.pengesahan.index') }}"><i class="material-icons-outlined">arrow_right</i>Buku Pengesahan Belanja</a></li>
            <li><a href="{{ route('pembukuan.piutang.index') }}"><i class="material-icons-outlined">arrow_right</i>Pengecekan Pembayaran (Piutang)</a></li>
          </ul>
        </li>
        @endhasrole
        @hasanyrole('Super Admin|KPA|Kepala Subbagian Keuangan dan Tata Usaha|Kepala Seksi Pelayanan dan Kerjasama|PPK')
        <li>
          <a href="{{ route('reports.bku') }}">
            <div class="parent-icon"><i class="material-icons-outlined">summarize</i>
            </div>
            <div class="menu-title">Laporan BKU</div>
          </a>
        </li>
        @endhasanyrole
      @endauth
      <!--end navigation-->
  </div>
</aside>
<!--end sidebar-->
