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
          <a href="javascript:void(0);" class="dropdown-toggle dropdown-toggle-nocaret p-1 d-flex align-items-center rounded-pill border" data-bs-toggle="dropdown" style="transition: all 0.2s;">
             <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=random&color=fff" class="rounded-circle" width="38" height="38" alt="">
          </a>
          <div class="dropdown-menu dropdown-user dropdown-menu-end shadow border-0 rounded-4 z-1000 p-0 mt-2" style="min-width: 260px; overflow: hidden; animation: fadeIn 0.3s;">
            <div class="px-4 py-4 text-center position-relative" style="background: linear-gradient(135deg, #0d6efd, #6610f2); color: white;">
                <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=fff&color=0d6efd" class="rounded-circle shadow-sm border border-3 border-white mb-2" width="75" height="75" alt="">
                <h6 class="mb-0 fw-bold text-truncate text-white">{{ Auth::user()->name }}</h6>
                <small class="text-white-50 text-truncate d-block" style="font-size: 0.8rem;">{{ Auth::user()->email ?? 'Administrator' }}</small>
            </div>
            
            <div class="p-2">
                <a class="dropdown-item d-flex align-items-center gap-3 py-2 rounded-2" href="{{ route('profile.index') }}" style="transition: 0.2s;">
                    <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-circle d-flex align-items-center justify-content-center">
                        <i class="material-icons-outlined" style="font-size: 1.2rem;">person_outline</i>
                    </div>
                    <span>My Profile</span>
                </a>
                <a class="dropdown-item d-flex align-items-center gap-3 py-2 rounded-2" href="javascript:;" style="transition: 0.2s;">
                    <div class="bg-secondary bg-opacity-10 text-secondary p-2 rounded-circle d-flex align-items-center justify-content-center">
                        <i class="material-icons-outlined" style="font-size: 1.2rem;">settings</i>
                    </div>
                    <span>Settings</span>
                </a>
                
                <hr class="dropdown-divider my-2">
                
                <a class="dropdown-item d-flex align-items-center gap-3 py-2 rounded-2 text-danger" href="javascript:void(0);" onclick="document.getElementById('logout-form').submit()" style="transition: 0.2s;">
                    <div class="bg-danger bg-opacity-10 text-danger p-2 rounded-circle d-flex align-items-center justify-content-center">
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