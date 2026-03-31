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
            @hasanyrole('Super Admin|Operator BLU|PPABP')
            <li><a href="{{ route('employees.index') }}"><i class="material-icons-outlined">arrow_right</i>Pegawai &
                Pejabat</a>
            </li>
            @endhasanyrole
            @hasanyrole('Super Admin|Pejabat Pengadaan')
            <li><a href="{{ route('suppliers.index') }}"><i class="material-icons-outlined">arrow_right</i>Supplier /
                Mitra</a>
            </li>
            @endhasanyrole
            @hasanyrole('Super Admin|KPA|Operator BLU|Kepala Subbagian Keuangan dan Tata Usaha|Kepala Seksi Pelayanan dan Kerjasama')
            <li><a href="{{ route('budgets.index') }}"><i class="material-icons-outlined">arrow_right</i>Pagu Anggaran</a>
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
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">draw</i></div>
            <div class="menu-title">Verifikasi Perikatan</div>
          </a>
          <ul>
            <li>
                <a href="{{ route('contracts.verifikasi') }}">
                  <i class="material-icons-outlined">arrow_right</i>Draft Kontrak & SPMK
                </a>
            </li>
          </ul>
        </li>

        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">fact_check</i></div>
            <div class="menu-title">Verifikasi Tagihan</div>
          </a>
          <ul>
            <li>
                <a href="{{ route('ppk.tagihan.kontrak.index') }}">
                  <i class="material-icons-outlined">arrow_right</i>Kontrak (BAST)
                </a>
            </li>
            <li>
                <a href="{{ route('verifikasi-ppk.index') }}">
                  <i class="material-icons-outlined">arrow_right</i>Perjaldin
                </a>
            </li>
            <li>
                <a href="{{ route('honorarium.ppk.pending') }}">
                  <i class="material-icons-outlined">arrow_right</i>Honorarium
                </a>
            </li>
          </ul>
        </li>

        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">history_edu</i></div>
            <div class="menu-title">Verifikasi Pencairan</div>
          </a>
          <ul>
            <li>
                <a href="{{ route('verifikasi-ppk.spp.index') }}">
                  <i class="material-icons-outlined">arrow_right</i>SPP
                </a>
            </li>
            <li>
                <a href="{{ route('verifikasi-ppk.npi.index') }}">
                  <i class="material-icons-outlined">arrow_right</i>NPI
                </a>
            </li>
            <li>
                <a href="#">
                  <i class="material-icons-outlined">arrow_right</i>SP2D
                </a>
            </li>
          </ul>
        </li>

        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">monitoring</i></div>
            <div class="menu-title">Monitoring & Laporan</div>
          </a>
          <ul>
            <li>
                <a href="#">
                  <i class="material-icons-outlined">arrow_right</i>Pengawasan DIPA
                </a>
            </li>
            <li>
                <a href="#">
                  <i class="material-icons-outlined">arrow_right</i>Arsip Kontrak
                </a>
            </li>
            <li>
                <a href="{{ route('perjaldin-blu.history') }}">
                  <i class="material-icons-outlined">arrow_right</i>Arsip Perjaldin
                </a>
            </li>
            <li>
                <a href="#">
                  <i class="material-icons-outlined">arrow_right</i>Arsip Honorarium
                </a>
            </li>
            <li>
                <a href="#">
                  <i class="material-icons-outlined">arrow_right</i>Pelacakan SP2D
                </a>
            </li>
          </ul>
        </li>
        @endhasrole
        @hasrole('Kepala Subbagian Keuangan dan Tata Usaha')
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">verified</i></div>
            <div class="menu-title">Verifikasi</div>
          </a>
          <ul>
            <li><a href="{{ route('verifikasi-kasubag.index') }}"><i class="material-icons-outlined">arrow_right</i>Perjaldin</a></li>
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
          </ul>
        </li>
        @endhasrole
        @hasrole('Bendahara Pengeluaran')
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">account_balance</i></div>
            <div class="menu-title">Bendahara Pengeluaran</div>
          </a>
          <ul>
            <li><a href="{{ route('npis.index') }}"><i class="material-icons-outlined">arrow_right</i>Pembuatan NPI</a></li>
            <li><a href="{{ route('sp2ds.index') }}"><i class="material-icons-outlined">arrow_right</i>Pencatatan SP2D & BKU</a></li>
          </ul>
        </li>
        @endhasrole
        @hasrole('Bendahara Penerimaan')
        <li>
          <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class="material-icons-outlined">how_to_reg</i></div>
            <div class="menu-title">Bendahara Penerimaan</div>
          </a>
          <ul>
            <li><a href="{{ route('verifikasi-bendahara-penerimaan.npi.index') }}"><i class="material-icons-outlined">arrow_right</i>TTD NPI</a></li>
          </ul>
        </li>
        @endhasrole
        @hasanyrole('Super Admin|KPA|Kepala Subbagian Keuangan dan Tata Usaha|Kepala Seksi Pelayanan dan Kerjasama|PPK|Bendahara Pengeluaran|Bendahara Penerimaan')
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