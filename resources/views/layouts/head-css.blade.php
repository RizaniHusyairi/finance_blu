<!-- loader-->
<link href="{{ URL::asset('build/css/pace.min.css') }}" rel="stylesheet">
<script src="{{ URL::asset('build/js/pace.min.js') }}"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<!--plugins-->
<link href="{{ URL::asset('build/plugins/perfect-scrollbar/css/perfect-scrollbar.css') }}" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="{{ URL::asset('build/plugins/metismenu/metisMenu.min.css') }}">
<link rel="stylesheet" type="text/css" href="{{ URL::asset('build/plugins/metismenu/mm-vertical.css') }}">
<link rel="stylesheet" type="text/css" href="{{ URL::asset('build/plugins/simplebar/css/simplebar.css') }}">
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
<!--bootstrap css-->
<link href="{{ URL::asset('build/css/bootstrap.min.css') }}" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Material+Icons+Outlined" rel="stylesheet">

@stack('css')

<!--main css-->
<link href="{{ URL::asset('build/css/bootstrap-extended.css') }}" rel="stylesheet">
<link href="{{ URL::asset('build/css/main.css') }}" rel="stylesheet">
<link href="{{ URL::asset('build/css/dark-theme.css') }}" rel="stylesheet">
<link href="{{ URL::asset('build/css/blue-theme.css') }}" rel="stylesheet">
<link href="{{ URL::asset('build/css/semi-dark.css') }}" rel="stylesheet">
<link href="{{ URL::asset('build/css/bordered-theme.css') }}" rel="stylesheet">
<link href="{{ URL::asset('build/css/responsive.css') }}" rel="stylesheet">

{{-- ============================================================
     Desain global "COA Select" (searchable dropdown)
     Diletakkan paling akhir agar menang dari CSS template.
     ============================================================ --}}
<style>
    :root {
        --coa-accent: #4f46e5;
        --coa-accent-2: #06b6d4;
        --coa-ink: #0f172a;
        --coa-muted: #94a3b8;
        --coa-line: #e6eaf2;
        --coa-soft: #eef2ff;
    }

    /* ============ Kotak pilihan ============ */
    .coa-s2.select2-container { line-height: 1; }

    .coa-s2.select2-container--bootstrap-5 .select2-selection,
    .coa-s2.select2-container .select2-selection {
        min-height: 50px !important;
        height: auto !important;
        padding: 0 46px 0 16px !important;
        border: 1.5px solid var(--coa-line) !important;
        border-radius: 14px !important;
        background: #ffffff !important;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .05), inset 0 1px 0 rgba(255, 255, 255, .6) !important;
        display: flex !important;
        align-items: center !important;
        outline: none !important;
        transition: border-color .2s ease, box-shadow .2s ease, background .2s ease !important;
    }

    .coa-s2.select2-container--bootstrap-5 .select2-selection__rendered,
    .coa-s2 .select2-selection__rendered {
        padding: 0 !important;
        margin: 0 !important;
        color: var(--coa-ink) !important;
        font-weight: 650 !important;
        font-size: 14px !important;
        line-height: 1.4 !important;
    }

    .coa-s2 .select2-selection__placeholder {
        color: var(--coa-muted) !important;
        font-weight: 500 !important;
    }

    /* Hover */
    .coa-s2.select2-container--bootstrap-5 .select2-selection:hover {
        border-color: #c7cfe0 !important;
        box-shadow: 0 6px 18px rgba(79, 70, 229, .10) !important;
    }

    /* Fokus & terbuka */
    .coa-s2.select2-container--bootstrap-5.select2-container--focus .select2-selection,
    .coa-s2.select2-container--bootstrap-5.select2-container--open .select2-selection {
        border-color: var(--coa-accent) !important;
        background: linear-gradient(180deg, #ffffff, #fbfbff) !important;
        box-shadow: 0 0 0 4px rgba(79, 70, 229, .16), 0 8px 22px rgba(79, 70, 229, .12) !important;
    }

    /* ============ Tombol panah (chevron) ============ */
    .coa-s2 .select2-selection__arrow {
        position: absolute !important;
        top: 0 !important;
        right: 0 !important;
        width: 44px !important;
        height: 100% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        background: transparent !important;
    }

    .coa-s2 .select2-selection__arrow b { display: none !important; }

    .coa-s2 .select2-selection__arrow::after {
        content: "";
        width: 22px;
        height: 22px;
        border-radius: 7px;
        background: var(--coa-soft) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' fill='none' stroke='%234f46e5' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m3 5 4 4 4-4'/%3E%3C/svg%3E") no-repeat center;
        transition: transform .25s ease, background-color .2s ease;
    }

    .coa-s2.select2-container--open .select2-selection__arrow::after {
        transform: rotate(180deg);
        background-color: rgba(79, 70, 229, .18);
    }

    /* ============ Panel dropdown ============ */
    .coa-s2-drop.select2-dropdown {
        border: 1px solid var(--coa-line) !important;
        border-radius: 16px !important;
        overflow: hidden !important;
        background: #ffffff !important;
        box-shadow: 0 24px 50px -12px rgba(15, 23, 42, .28), 0 0 0 1px rgba(79, 70, 229, .06) !important;
        animation: coaDropIn .18s cubic-bezier(.2, .8, .2, 1);
    }

    .coa-s2-drop.select2-dropdown--above { transform-origin: bottom center; }

    @keyframes coaDropIn {
        from { opacity: 0; transform: translateY(-8px) scale(.985); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    /* ============ Kotak pencarian ============ */
    .coa-s2-drop .select2-search--dropdown {
        padding: 14px 14px 10px !important;
        background: #ffffff;
    }

    .coa-s2-drop .select2-search--dropdown .select2-search__field {
        border: 1.5px solid var(--coa-line) !important;
        border-radius: 12px !important;
        padding: 11px 14px 11px 40px !important;
        font-size: 14px !important;
        color: var(--coa-ink) !important;
        outline: none !important;
        background:
            #f8fafc url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' fill='none' stroke='%2394a3b8' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='8' cy='8' r='6'/%3E%3Cpath d='m17 17-4.5-4.5'/%3E%3C/svg%3E")
            no-repeat 13px center;
        transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
    }

    .coa-s2-drop .select2-search--dropdown .select2-search__field::placeholder {
        color: var(--coa-muted);
    }

    .coa-s2-drop .select2-search--dropdown .select2-search__field:focus {
        border-color: var(--coa-accent) !important;
        background-color: #fff;
        box-shadow: 0 0 0 4px rgba(79, 70, 229, .14) !important;
    }

    /* ============ Daftar hasil ============ */
    .coa-s2-drop .select2-results__options {
        max-height: 320px !important;
        padding: 6px 8px 10px !important;
    }

    .coa-s2-drop .select2-results__options::-webkit-scrollbar { width: 10px; }
    .coa-s2-drop .select2-results__options::-webkit-scrollbar-track { background: transparent; }
    .coa-s2-drop .select2-results__options::-webkit-scrollbar-thumb {
        background: #d4dae6;
        border-radius: 10px;
        border: 3px solid #fff;
    }
    .coa-s2-drop .select2-results__options::-webkit-scrollbar-thumb:hover { background: #b6bfd0; }

    /* Header optgroup */
    .coa-s2-drop .select2-results__group {
        display: flex !important;
        align-items: center;
        gap: 8px;
        margin: 8px 4px 4px !important;
        padding: 6px 10px !important;
        color: var(--coa-accent) !important;
        font-size: 10.5px !important;
        font-weight: 800 !important;
        letter-spacing: .08em !important;
        text-transform: uppercase !important;
        background: transparent !important;
    }

    .coa-s2-drop .select2-results__group::before {
        content: "";
        width: 14px;
        height: 3px;
        border-radius: 3px;
        background: linear-gradient(90deg, var(--coa-accent), var(--coa-accent-2));
    }

    /* Opsi */
    .coa-s2-drop .select2-results__option {
        position: relative !important;
        padding: 11px 14px 11px 38px !important;
        margin: 2px 4px !important;
        border-radius: 11px !important;
        color: #334155 !important;
        font-size: 13.5px !important;
        line-height: 1.45 !important;
        transition: background .14s ease, color .14s ease, padding-left .14s ease !important;
    }

    .coa-s2-drop .select2-results__option .select2-results__group { margin: 0 !important; }

    /* Titik bulat indikator (default) */
    .coa-s2-drop .select2-results__option::before {
        content: "";
        position: absolute;
        left: 16px;
        top: 50%;
        width: 7px;
        height: 7px;
        transform: translateY(-50%);
        border-radius: 50%;
        background: #d4dae6;
        transition: all .14s ease;
    }

    /* Hover / highlighted */
    .coa-s2-drop .select2-results__option--highlighted[aria-selected],
    .coa-s2-drop .select2-results__option--highlighted {
        background: linear-gradient(135deg, rgba(79, 70, 229, .10), rgba(6, 182, 212, .08)) !important;
        color: var(--coa-accent) !important;
    }

    .coa-s2-drop .select2-results__option--highlighted::before {
        background: var(--coa-accent);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, .16);
    }

    /* Terpilih */
    .coa-s2-drop .select2-results__option[aria-selected="true"] {
        background: var(--coa-soft) !important;
        color: var(--coa-accent) !important;
        font-weight: 750 !important;
    }

    .coa-s2-drop .select2-results__option[aria-selected="true"]::before {
        width: 15px;
        height: 15px;
        left: 14px;
        background: var(--coa-accent) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' fill='none' stroke='%23ffffff' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m3 8 3 3 7-7'/%3E%3C/svg%3E") no-repeat center / 11px;
        box-shadow: none;
    }

    /* Pesan kosong / mencari */
    .coa-s2-drop .select2-results__message {
        padding: 18px 14px !important;
        color: var(--coa-muted) !important;
        font-size: 13px !important;
        text-align: center !important;
    }

    @media (prefers-reduced-motion: reduce) {
        .coa-s2-drop.select2-dropdown { animation: none; }
        .coa-s2 .select2-selection__arrow::after { transition: none; }
    }
</style>
