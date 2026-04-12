<!--start sidebar-->
<aside class="sidebar-wrapper" data-simplebar="true">
  <div class="sidebar-header">
    <div class="logo-icon">
      <img src="{{ URL::asset('logo/Logo-BLU-Speed.png') }}" class="logo-img" alt="">
    </div>
    <div class="logo-name flex-grow-1">
      <h5 class="mb-0">SIKUT BLU </h5>
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
        @hasanyrole('Super Admin|KPA|Kepala Subbagian Keuangan dan Tata Usaha|Kepala Seksi Pelayanan dan Kerjasama|Pejabat Pengadaan|Operator BLU|PPABP')
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
        {{-- 1. Verifikasi SPMK --}}
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">draw</i></div>
            <div class="menu-title">Verifikasi SPMK</div>
          </a>
          <ul>
            <li><a href="{{ route('contracts.verifikasi') }}"><i class="material-icons-outlined">arrow_right</i>Draft Kontrak & SPMK</a></li>
          </ul>
        </li>

        {{-- 2. Verifikasi Tagihan --}}
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">fact_check</i></div>
            <div class="menu-title">Verifikasi Tagihan</div>
          </a>
          <ul>
            <li><a href="{{ route('ppk.tagihan.kontrak.index') }}"><i class="material-icons-outlined">arrow_right</i>Kontrak</a></li>
            <li><a href="{{ route('verifikasi-ppk.index') }}"><i class="material-icons-outlined">arrow_right</i>Perjaldin</a></li>
            <li><a href="{{ route('honorarium.ppk.pending') }}"><i class="material-icons-outlined">arrow_right</i>Honorarium</a></li>
          </ul>
        </li>

        {{-- 3. Verifikasi SPP --}}
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">history_edu</i></div>
            <div class="menu-title">Verifikasi SPP</div>
          </a>
          <ul>
            <li><a href="{{ route('verifikasi-ppk.spp.index') }}"><i class="material-icons-outlined">arrow_right</i>Kontrak</a></li>
            {{-- TODO: Pisahkan route SPP Perjaldin & Honor jika controller sudah siap --}}
            <li><a href="{{ route('verifikasi-ppk.spp.index') }}"><i class="material-icons-outlined">arrow_right</i>Perjaldin</a></li>
            <li><a href="{{ route('verifikasi-ppk.spp.index') }}"><i class="material-icons-outlined">arrow_right</i>Honor</a></li>
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
            <li><a href="#"><i class="material-icons-outlined">arrow_right</i>Perjaldin</a></li>
            <li><a href="#"><i class="material-icons-outlined">arrow_right</i>Honor</a></li>
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
            <li><a href="#"><i class="material-icons-outlined">arrow_right</i>Perjaldin</a></li>
            <li><a href="#"><i class="material-icons-outlined">arrow_right</i>Honor</a></li>
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
              <a href="{{ route('verifikasi-kasubag.spp.index') }}"><i class="material-icons-outlined">arrow_right</i>Perjaldin</a>
            </li>
            <li>
              <a href="{{ route('verifikasi-kasubag.spp.index') }}"><i class="material-icons-outlined">arrow_right</i>Honor</a>
            </li>
          </ul>
        </li>
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
              <a href="{{ route('verifikasi-kasubag.spm.index') }}"><i class="material-icons-outlined">arrow_right</i>Perjaldin</a>
            </li>
            <li>
              <a href="#"><i class="material-icons-outlined">arrow_right</i>Honor</a>
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
              <a href="{{ route('verifikasi-kasubag.npi.index') }}"><i class="material-icons-outlined">arrow_right</i>Perjaldin</a>
            </li>
            <li>
              <a href="{{ route('verifikasi-kasubag.npi.index') }}"><i class="material-icons-outlined">arrow_right</i>Honor</a>
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
              <a href="#"><i class="material-icons-outlined">arrow_right</i>Perjaldin</a>
            </li>
            <li>
              <a href="#"><i class="material-icons-outlined">arrow_right</i>Honor</a>
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
            <li><a href="{{ route('spms.index') }}"><i class="material-icons-outlined">arrow_right</i>SPM Perjaldin</a></li>
            <li><a href="{{ route('spms.index') }}"><i class="material-icons-outlined">arrow_right</i>SPM Honor</a></li>
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
            <div class="menu-title">Verifikasi (PPSPM)</div>
          </a>
          <ul>
            <li><a href="{{ route('verifikasi-ppspm.spm.index') }}"><i class="material-icons-outlined">arrow_right</i>SPM Perjaldin</a></li>
            <li><a href="#"><i class="material-icons-outlined">arrow_right</i>SPM Honor</a></li>
            <li><a href="{{ route('verifikasi-ppspm.spm.kontrak.index') }}"><i class="material-icons-outlined">arrow_right</i>SPM Kontrak</a></li>
          </ul>
        </li>
        @endhasrole
        @hasrole('Bendahara Pengeluaran')
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">receipt_long</i></div>
            <div class="menu-title">Pembuatan NPI</div>
          </a>
          <ul>
            <!-- TODO: Sementara menggunakan route yang sama (npis.index), perlu dipisah per jenis dokumen jika controller sudah siap -->
            <li><a href="{{ route('npis.kontrak.index') }}"><i class="material-icons-outlined">arrow_right</i>Kontrak</a></li>
            <li><a href="{{ route('npis.index') }}"><i class="material-icons-outlined">arrow_right</i>Perjaldin</a></li>
            <li><a href="{{ route('npis.index') }}"><i class="material-icons-outlined">arrow_right</i>Honor</a></li>
          </ul>
        </li>
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">account_balance</i></div>
            <div class="menu-title">Pencatatan SP2D</div>
          </a>
          <ul>
            <li><a href="{{ route('sp2ds.kontrak.index') }}"><i class="material-icons-outlined">arrow_right</i>Kontrak</a></li>
            <li><a href="{{ route('sp2ds.index') }}"><i class="material-icons-outlined">arrow_right</i>Perjaldin</a></li>
            <li><a href="{{ route('sp2ds.index') }}"><i class="material-icons-outlined">arrow_right</i>Honor</a></li>
          </ul>
        </li>
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">summarize</i></div>
            <div class="menu-title">Laporan</div>
          </a>
          <ul>
            <li><a href="{{ route('reports.bku') }}"><i class="material-icons-outlined">arrow_right</i>Laporan BKU</a></li>
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
            <li><a href="#"><i class="material-icons-outlined">arrow_right</i>Perjaldin</a></li>
            <li><a href="#"><i class="material-icons-outlined">arrow_right</i>Honor</a></li>
          </ul>
        </li>
        @endhasrole
        @hasanyrole('Super Admin|KPA|Kepala Subbagian Keuangan dan Tata Usaha|Kepala Seksi Pelayanan dan Kerjasama|PPK|Bendahara Penerimaan')
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
