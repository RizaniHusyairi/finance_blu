 <!--start header-->
 <header class="top-header">
    <nav class="navbar navbar-expand align-items-center gap-4 justify-content-between">
      <div class="btn-toggle">
        <a href="javascript:;"><i class="material-icons-outlined">menu</i></a>
      </div>
      <div class="search-bar flex-grow-1 d-none">
        <div class="position-relative">
          <input class="form-control rounded-5 px-5 search-control d-lg-block d-none" type="text" placeholder="Search">
          <span class="material-icons-outlined position-absolute d-lg-block d-none ms-3 translate-middle-y start-0 top-50">search</span>
          <span class="material-icons-outlined position-absolute me-3 translate-middle-y end-0 top-50 search-close">close</span>
          <div class="search-popup p-3">
            <div class="card rounded-4 overflow-hidden">
              <div class="card-header d-lg-none">
                <div class="position-relative">
                  <input class="form-control rounded-5 px-5 mobile-search-control" type="text" placeholder="Search">
                  <span class="material-icons-outlined position-absolute ms-3 translate-middle-y start-0 top-50">search</span>
                  <span class="material-icons-outlined position-absolute me-3 translate-middle-y end-0 top-50 mobile-search-close">close</span>
                 </div>
              </div>
              <div class="card-body search-content">
                <p class="search-title">Recent Searches</p>
                <div class="d-flex align-items-start flex-wrap gap-2 kewords-wrapper">
                  <a href="javascript:;" class="kewords"><span>Angular Template</span><i
                      class="material-icons-outlined fs-6">search</i></a>
                  <a href="javascript:;" class="kewords"><span>Dashboard</span><i
                      class="material-icons-outlined fs-6">search</i></a>
                  <a href="javascript:;" class="kewords"><span>Admin Template</span><i
                      class="material-icons-outlined fs-6">search</i></a>
                  <a href="javascript:;" class="kewords"><span>Bootstrap 5 Admin</span><i
                      class="material-icons-outlined fs-6">search</i></a>
                  <a href="javascript:;" class="kewords"><span>Html eCommerce</span><i
                      class="material-icons-outlined fs-6">search</i></a>
                  <a href="javascript:;" class="kewords"><span>Sass</span><i
                      class="material-icons-outlined fs-6">search</i></a>
                  <a href="javascript:;" class="kewords"><span>laravel 9</span><i
                      class="material-icons-outlined fs-6">search</i></a>
                </div>
                <hr>
                <p class="search-title">Tutorials</p>
                <div class="search-list d-flex flex-column gap-2">
                  <div class="search-list-item d-flex align-items-center gap-3">
                    <div class="list-icon">
                      <i class="material-icons-outlined fs-5">play_circle</i>
                    </div>
                    <div class="">
                      <h5 class="mb-0 search-list-title ">Wordpress Tutorials</h5>
                    </div>
                  </div>
                  <div class="search-list-item d-flex align-items-center gap-3">
                    <div class="list-icon">
                      <i class="material-icons-outlined fs-5">shopping_basket</i>
                    </div>
                    <div class="">
                      <h5 class="mb-0 search-list-title">eCommerce Website Tutorials</h5>
                    </div>
                  </div>
  
                  <div class="search-list-item d-flex align-items-center gap-3">
                    <div class="list-icon">
                      <i class="material-icons-outlined fs-5">laptop</i>
                    </div>
                    <div class="">
                      <h5 class="mb-0 search-list-title">Responsive Design</h5>
                    </div>
                  </div>
                </div>
  
                <hr>
                <p class="search-title">Members</p>
  
                <div class="search-list d-flex flex-column gap-2">
                  <div class="search-list-item d-flex align-items-center gap-3">
                    <div class="memmber-img">
                      <img src="https://placehold.co/110x110/png" width="32" height="32" class="rounded-circle" alt="">
                    </div>
                    <div class="">
                      <h5 class="mb-0 search-list-title ">Andrew Stark</h5>
                    </div>
                  </div>
  
                  <div class="search-list-item d-flex align-items-center gap-3">
                    <div class="memmber-img">
                      <img src="https://placehold.co/110x110/png" width="32" height="32" class="rounded-circle" alt="">
                    </div>
                    <div class="">
                      <h5 class="mb-0 search-list-title ">Snetro Jhonia</h5>
                    </div>
                  </div>
  
                  <div class="search-list-item d-flex align-items-center gap-3">
                    <div class="memmber-img">
                      <img src="https://placehold.co/110x110/png" width="32" height="32" class="rounded-circle" alt="">
                    </div>
                    <div class="">
                      <h5 class="mb-0 search-list-title">Michle Clark</h5>
                    </div>
                  </div>
  
                </div>
              </div>
              <div class="card-footer text-center bg-transparent">
                <a href="javascript:;" class="btn w-100">See All Search Results</a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <ul class="navbar-nav gap-1 nav-right-links align-items-center">

        {{-- Pusat Panduan — akses cepat dari mana saja --}}
        @unlessrole('Mitra|Mitra Jasa')
        <li class="nav-item">
          <a class="nav-link position-relative" href="{{ route('panduan.index') }}" title="Panduan penggunaan">
            <i class="material-icons-outlined">help_outline</i>
          </a>
        </li>
        @endunlessrole
        

        
       
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle dropdown-toggle-nocaret position-relative" data-bs-auto-close="outside"
            data-bs-toggle="dropdown" href="javascript:;"><i class="material-icons-outlined">notifications</i>
            <span class="badge-notify" id="notificationCounter" style="display:none;">0</span>
          </a>
          <div class="dropdown-menu dropdown-notify dropdown-menu-end shadow">
            <div class="px-3 py-1 d-flex align-items-center justify-content-between border-bottom">
              <h5 class="notiy-title mb-0">Notifications</h5>
              <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle dropdown-toggle-nocaret option" type="button"
                  data-bs-toggle="dropdown" aria-expanded="false">
                  <span class="material-icons-outlined">
                    more_vert
                  </span>
                </button>
                <div class="dropdown-menu dropdown-option dropdown-menu-end shadow">
                  <div><a class="dropdown-item d-flex align-items-center gap-2 py-2" href="javascript:;" onclick="markNotificationsAsRead()"><i
                        class="material-icons-outlined fs-6">done_all</i>Mark all as read</a></div>
                </div>
              </div>
            </div>
            <div class="notify-list" id="notificationDropdownList">
              <!-- Notifikasi akan diload via AJAX di app-scripts -->
              <div class="text-center py-4 text-muted"><small>Memuat notifikasi...</small></div>
            </div>
          </div>
        </li>
        
        <li class="nav-item dropdown">
          <style>
            /* ══ Dropdown profil user — animasi buka + micro-interactions ══ */
            @keyframes pfMenuIn {
              0%   { opacity: 0; transform: scale(.82) translateY(-10px); }
              65%  { opacity: 1; transform: scale(1.02) translateY(2px); }
              100% { opacity: 1; transform: scale(1) translateY(0); }
            }
            @keyframes pfItemIn { from { opacity: 0; transform: translateX(16px); } to { opacity: 1; transform: none; } }
            @keyframes pfAvatarPop { 0% { transform: scale(.5); opacity: 0; } 70% { transform: scale(1.08); } 100% { transform: scale(1); opacity: 1; } }
            @keyframes pfRing { 0% { box-shadow: 0 0 0 0 rgba(255,255,255,.55); } 100% { box-shadow: 0 0 0 14px rgba(255,255,255,0); } }
            @keyframes pfAurora { 0%,100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
            @keyframes pfShine { 0%, 55% { left: -70%; } 100% { left: 140%; } }
            @keyframes pfShake { 20%, 60% { transform: rotate(-12deg); } 40%, 80% { transform: rotate(10deg); } }

            .pf-toggle { transition: transform .22s cubic-bezier(.34,1.56,.64,1), box-shadow .22s ease; }
            .pf-toggle:hover { transform: translateY(-2px) scale(1.05); box-shadow: 0 8px 20px -8px rgba(79,70,229,.5); }
            .pf-toggle:active { transform: scale(.94); }
            .pf-toggle.show { box-shadow: 0 0 0 3px rgba(99,102,241,.35); }

            .pf-menu { transform-origin: top right; }
            .pf-menu.show { animation: pfMenuIn .38s cubic-bezier(.34,1.56,.64,1) both; }

            .pf-head { background: linear-gradient(135deg, #0d6efd, #6610f2, #7c3aed, #0d6efd);
              background-size: 300% 300%; animation: pfAurora 8s ease infinite; position: relative; overflow: hidden; }
            .pf-head::after { content: ''; position: absolute; top: 0; bottom: 0; width: 45%; left: -70%;
              background: linear-gradient(100deg, transparent, rgba(255,255,255,.22), transparent);
              transform: skewX(-18deg); animation: pfShine 3.2s ease-in-out .3s infinite; }
            .pf-menu.show .pf-avatar { animation: pfAvatarPop .45s cubic-bezier(.34,1.56,.64,1) .05s both,
              pfRing 1.6s ease-out .5s 2; }

            .pf-menu.show .pf-item { animation: pfItemIn .35s cubic-bezier(.22,1,.36,1) both; }
            .pf-menu.show .pf-item:nth-child(1) { animation-delay: .12s; }
            .pf-menu.show .pf-item:nth-child(2) { animation-delay: .19s; }
            .pf-menu.show .pf-item:nth-child(3) { animation-delay: .26s; }
            .pf-menu.show .pf-item:nth-child(4) { animation-delay: .3s; }

            .pf-item { transition: background .2s ease, transform .2s ease; }
            .pf-item:hover { background: linear-gradient(90deg, rgba(99,102,241,.1), transparent); transform: translateX(4px); }
            .pf-item .pf-ic { transition: transform .25s cubic-bezier(.34,1.56,.64,1); }
            .pf-item:hover .pf-ic { transform: scale(1.15) rotate(-8deg); }
            .pf-item.pf-danger:hover { background: linear-gradient(90deg, rgba(225,29,72,.1), transparent); }
            .pf-item.pf-danger:hover .pf-ic i { animation: pfShake .45s ease; }

            @media (prefers-reduced-motion: reduce) {
              .pf-menu.show, .pf-menu.show .pf-item, .pf-menu.show .pf-avatar,
              .pf-head, .pf-head::after, .pf-item .pf-ic, .pf-item.pf-danger:hover .pf-ic i { animation: none !important; }
              .pf-toggle, .pf-item { transition: none; }
            }
          </style>
          <a href="javascript:void(0);" class="dropdown-toggle dropdown-toggle-nocaret p-1 d-flex align-items-center rounded-pill border pf-toggle" data-bs-toggle="dropdown">
             <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=random&color=fff" class="rounded-circle" width="38" height="38" alt="">
          </a>
          <div class="dropdown-menu dropdown-user dropdown-menu-end shadow border-0 rounded-4 z-1000 p-0 mt-2 pf-menu" style="min-width: 260px; overflow: hidden;">
            <div class="px-4 py-4 text-center position-relative pf-head" style="color: white;">
                <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=fff&color=0d6efd" class="rounded-circle shadow-sm border border-3 border-white mb-2 pf-avatar" width="75" height="75" alt="" style="position: relative; z-index: 1;">
                <h6 class="mb-0 fw-bold text-truncate text-white" style="position: relative; z-index: 1;">{{ Auth::user()->name }}</h6>
                <small class="text-white-50 text-truncate d-block" style="font-size: 0.8rem; position: relative; z-index: 1;">{{ Auth::user()->email ?? 'Administrator' }}</small>
            </div>

            <div class="p-2">
                <a class="dropdown-item d-flex align-items-center gap-3 py-2 rounded-2 pf-item" href="{{ route('profile.index') }}">
                    <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-circle d-flex align-items-center justify-content-center pf-ic">
                        <i class="material-icons-outlined" style="font-size: 1.2rem;">person_outline</i>
                    </div>
                    <span>My Profile</span>
                </a>
                <a class="dropdown-item d-flex align-items-center gap-3 py-2 rounded-2 pf-item" href="javascript:;">
                    <div class="bg-secondary bg-opacity-10 text-secondary p-2 rounded-circle d-flex align-items-center justify-content-center pf-ic">
                        <i class="material-icons-outlined" style="font-size: 1.2rem;">settings</i>
                    </div>
                    <span>Settings</span>
                </a>

                <hr class="dropdown-divider my-2 pf-item">

                <a class="dropdown-item d-flex align-items-center gap-3 py-2 rounded-2 text-danger pf-item pf-danger" href="javascript:void(0);" onclick="document.getElementById('logout-form').submit()">
                    <div class="bg-danger bg-opacity-10 text-danger p-2 rounded-circle d-flex align-items-center justify-content-center pf-ic">
                        <i class="material-icons-outlined" style="font-size: 1.2rem;">logout</i>
                    </div>
                    <span class="fw-semibold">Sign Out</span>
                </a>
                <form action="{{ route('logout') }}" method="POST" id="logout-form" class="d-none">
                    @csrf
                </form>
            </div>
          </div>
        </li>
      </ul>

    </nav>
  </header>
  <!--end top header-->