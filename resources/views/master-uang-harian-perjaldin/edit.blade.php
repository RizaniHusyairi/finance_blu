@extends('layouts.app')
@section('title')
    Edit Data Uang Harian
@endsection
@push('css')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap');

        :root {
            --primary-gradient: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            --card-shadow: 0 16px 40px rgba(15, 23, 42, 0.03), 0 1px 3px rgba(15, 23, 42, 0.01);
            --border-glass: rgba(226, 232, 240, 0.8);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
        }

        /* Entry Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .delay-1 { animation-delay: 0.05s; }
        .delay-2 { animation-delay: 0.1s; }

        /* Page Title Header */
        .page-header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .page-title-wrapper {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .page-title-badge {
            font-size: 1.25rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            letter-spacing: -0.02em;
            display: inline-flex;
            align-items: center;
            gap: .75rem;
        }

        .page-title-badge::before {
            content: '';
            width: 4px;
            height: 24px;
            border-radius: 4px;
            background: linear-gradient(180deg, #2563eb, #0d9488);
        }

        .page-subtitle {
            font-size: 0.8rem;
            color: #64748b;
            font-weight: 600;
            margin-left: 1.05rem;
        }

        /* Back Button style */
        .btn-back-premium {
            background: #fff;
            border: 1.5px solid #cbd5e1;
            color: #475569 !important;
            font-weight: 700;
            font-size: 0.88rem;
            padding: .6rem 1.3rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.02);
            text-decoration: none;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .btn-back-premium:hover {
            border-color: #94a3b8;
            color: #0f172a !important;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
            transform: translateX(-3px);
        }

        .btn-back-premium i {
            transition: transform 0.25s ease;
        }

        .btn-back-premium:hover i {
            transform: translateX(-2px);
        }

        /* Card Style */
        .page-card {
            background: #fff;
            border: 1px solid var(--border-glass);
            border-radius: 1.5rem;
            box-shadow: var(--card-shadow);
            padding: 2.25rem;
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
            animation-delay: 0.15s;
        }

        .form-section-title {
            font-size: 0.95rem;
            font-weight: 800;
            letter-spacing: 0.03em;
            color: #475569;
            text-transform: uppercase;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-section-title i {
            color: #2563eb;
        }

        /* Modern Form Styling overrides */
        .form-group-premium {
            margin-bottom: 1.75rem;
        }

        .label-premium {
            font-weight: 700;
            font-size: 0.85rem;
            color: #334155;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .label-premium i {
            font-size: 0.95rem;
        }

        .input-premium {
            border-radius: 0.85rem !important;
            border: 1.5px solid #e2e8f0;
            padding: 0.7rem 1.1rem;
            font-size: 0.88rem;
            font-weight: 600;
            color: #0f172a;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            background-color: #fff;
        }

        .input-premium:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.08);
            outline: none;
        }

        /* Currency input groups decorations */
        .input-group-premium {
            position: relative;
            display: flex;
            flex-wrap: wrap;
            align-items: stretch;
            width: 100%;
        }

        .input-group-premium .input-group-text-premium {
            border-top-left-radius: 0.85rem !important;
            border-bottom-left-radius: 0.85rem !important;
            border: 1.5px solid #e2e8f0;
            border-right: none;
            background: #f8fafc;
            padding: 0.7rem 1.1rem;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            font-size: 0.88rem;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.25s ease;
        }

        .input-group-premium .input-premium {
            border-top-left-radius: 0 !important;
            border-bottom-left-radius: 0 !important;
            flex: 1 1 auto;
            width: 1%;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            letter-spacing: -0.01em;
        }

        /* Focus states for input groups */
        .input-group-premium:focus-within .input-group-text-premium {
            border-color: #2563eb;
            color: #2563eb;
            background: rgba(37, 99, 235, 0.03);
        }

        .input-group-premium:focus-within .input-premium {
            border-color: #2563eb;
        }

        /* Custom decoration themes */
        .input-group-teal:focus-within .input-group-text-premium {
            border-color: #0d9488;
            color: #0d9488;
            background: rgba(13, 148, 136, 0.03);
        }
        .input-group-teal:focus-within .input-premium {
            border-color: #0d9488;
            box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.08);
        }

        .input-group-blue:focus-within .input-group-text-premium {
            border-color: #2563eb;
            color: #2563eb;
            background: rgba(37, 99, 235, 0.03);
        }
        .input-group-blue:focus-within .input-premium {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.08);
        }

        .input-group-amber:focus-within .input-group-text-premium {
            border-color: #d97706;
            color: #d97706;
            background: rgba(245, 158, 11, 0.03);
        }
        .input-group-amber:focus-within .input-premium {
            border-color: #d97706;
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.08);
        }

        /* Subtexts */
        .form-desc-text {
            font-size: 0.78rem;
            color: #64748b;
            font-weight: 550;
            margin-top: 0.35rem;
            display: block;
        }

        /* Premium Buttons style */
        .btn-save-premium {
            background: var(--primary-gradient);
            border: none;
            color: #fff !important;
            font-weight: 700;
            font-size: 0.88rem;
            padding: .7rem 1.6rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.15);
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .btn-save-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25);
            color: #fff !important;
        }

        .btn-cancel-premium {
            background: #fff;
            border: 1.5px solid #cbd5e1;
            color: #475569 !important;
            font-weight: 700;
            font-size: 0.88rem;
            padding: .7rem 1.6rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.02);
            text-decoration: none;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .btn-cancel-premium:hover {
            border-color: #94a3b8;
            color: #0f172a !important;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
            transform: translateY(-2px);
        }
    </style>
@endpush
@section('content')
    <x-page-title title="Master Data" subtitle="Edit Uang Harian" />

    {{-- Header Section --}}
    <div class="page-header-container">
        <div class="page-title-wrapper animate-fade-in-up delay-1">
            <h5 class="page-title-badge">Edit Uang Harian</h5>
            <span class="page-subtitle">Ubah detail besaran uang harian untuk daerah/provinsi yang sudah terdaftar</span>
        </div>
        <a href="{{ route('master-uang-harian-perjaldin.index') }}" class="btn-back-premium animate-fade-in-up delay-1">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar
        </a>
    </div>

    {{-- Main Form Card --}}
    <div class="page-card delay-2">
        <div class="form-section-title animate-fade-in-up delay-1">
            <i class="bi bi-pencil-square"></i> Form Perubahan Uang Harian
        </div>
        <form action="{{ route('master-uang-harian-perjaldin.update', $data->id) }}" method="POST">
            @method('PUT')
            @include('master-uang-harian-perjaldin._form')
        </form>
    </div>
@endsection
