   <!--start sidebar-->
   <aside class="sidebar-wrapper" data-simplebar="true">
    <div class="sidebar-header">
      <div class="logo-icon">
        <img src="{{ URL::asset('build/images/logo-icon.png') }}" class="logo-img" alt="">
      </div>
      <div class="logo-name flex-grow-1">
        <h5 class="mb-0">Maxton</h5>
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
              <li><a href="{{ route('dashboard') }}"><i class="material-icons-outlined">arrow_right</i>Dashboard Internal</a>
              </li>
              @endunlessrole
              @role('Mitra')
              <li><a href="{{ route('mitra.dashboard') }}"><i class="material-icons-outlined">arrow_right</i>Portal Mitra</a>
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
              <li><a href="{{ route('employees.index') }}"><i class="material-icons-outlined">arrow_right</i>Pegawai & Pejabat</a>
              </li>
              @endhasanyrole
              @hasanyrole('Super Admin|Pejabat Pengadaan')
              <li><a href="{{ route('suppliers.index') }}"><i class="material-icons-outlined">arrow_right</i>Supplier / Mitra</a>
              </li>
              @endhasanyrole
              @hasanyrole('Super Admin|KPA|Operator BLU|Kepala Subbagian Keuangan dan Tata Usaha|Kepala Seksi Pelayanan dan Kerjasama')
              <li><a href="{{ route('budgets.index') }}"><i class="material-icons-outlined">arrow_right</i>Pagu Anggaran</a>
              </li>
              @endhasanyrole
            </ul>
          </li>
          @endhasanyrole
          @hasanyrole('Super Admin|Pejabat Pengadaan|PPK')
          <li>
            <a href="{{ route('contracts.index') }}">
              <div class="parent-icon"><i class="material-icons-outlined">description</i>
              </div>
              <div class="menu-title">Manajemen Kontrak</div>
            </a>
          </li>
          @endhasanyrole
          @hasanyrole('Super Admin|Operator BLU|PPABP|Operator Perjaldin|PPK|PPSPM|Bendahara Pengeluaran|Bendahara Penerimaan')
          <li>
            <a href="javascript:;" class="has-arrow">
              <div class="parent-icon"><i class="material-icons-outlined">payments</i>
              </div>
              <div class="menu-title">Tagihan & Bayar</div>
            </a>
            <ul>
              <li><a href="{{ route('transactions.index') }}"><i class="material-icons-outlined">arrow_right</i>Daftar Transaksi</a>
              </li>
              @hasanyrole('Super Admin|Operator BLU|PPABP|Operator Perjaldin')
              <li><a href="{{ route('transactions.create') }}"><i class="material-icons-outlined">arrow_right</i>Input Tagihan</a>
              </li>
              @endhasanyrole
            </ul>
          </li>
          @endhasanyrole
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