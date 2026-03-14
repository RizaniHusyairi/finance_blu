   @if(Request::is('widgets-*') || Request::is('app-*') || Request::is('component-*') || Request::is('icons-*') || Request::is('form-*') || Request::is('table-*') || Request::is('auth-*') || Request::is('pages-*') || Request::is('ecommerce-*') || Request::is('charts-*') || Request::is('map-*') || Request::is('cards') || Request::is('user-profile') || Request::is('timeline') || Request::is('faq') || Request::is('pricing-table'))
        @include('layouts.sidebar-template')
   @else
        @include('layouts.sidebar-app')
   @endif
