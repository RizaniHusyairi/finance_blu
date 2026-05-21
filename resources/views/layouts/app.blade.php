<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="blue-theme">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | Laravel 11 & Bootstrap 5 Admin Dashboard Template</title>
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ URL::asset('build/images/favicon-32x32.png') }}" type="image/png">

    @include('layouts.theme-head')

    @include('layouts.head-css')
    <style>
        .main-content .table-responsive {
            border-radius: inherit;
        }

        .main-content .table:not(.table-sm) > :not(caption) > * > th,
        .main-content .table:not(.table-sm) > :not(caption) > * > td {
            padding: 1.05rem 1.25rem !important;
            line-height: 1.55;
            vertical-align: middle;
        }

        .main-content .table.table-sm > :not(caption) > * > th,
        .main-content .table.table-sm > :not(caption) > * > td {
            padding: .8rem 1rem !important;
            line-height: 1.45;
            vertical-align: middle;
        }

        .main-content .table thead th {
            white-space: nowrap;
        }

        .main-content .table tbody td {
            word-break: normal;
            overflow-wrap: anywhere;
        }

        .main-content .table .btn,
        .main-content .table a.btn,
        .main-content .table button.btn {
            margin-block: .12rem;
        }

        .main-content table.min-w-full th,
        .main-content table.min-w-full td {
            padding: 1rem 1.25rem !important;
            line-height: 1.5;
            vertical-align: middle;
        }

        .jasa-icon-btn {
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            min-width: 34px;
            padding: 0 !important;
            border-radius: 10px !important;
            font-size: 15px;
            font-weight: 800;
        }

        .jasa-icon-btn.btn-sm {
            width: 32px;
            height: 32px;
            min-width: 32px;
            font-size: 14px;
        }

        .jasa-icon-btn i {
            margin: 0 !important;
            line-height: 1;
        }
    </style>
</head>

<body>

    @include('layouts.topbar')

    @include('layouts.sidebar')
    
    <!--start main wrapper-->
    <main class="main-wrapper">
        <div class="main-content">
            @yield('content')
        </div>
    </main>

    <!--start overlay-->
    <div class="overlay btn-toggle"></div>
    <!--end overlay-->

    @include('layouts.extra')

    @include('layouts.app-scripts')
</body>

</html>
