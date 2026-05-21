@push('css')
<style>
    .jasa-form-hero {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(37, 99, 235, .16);
        border-radius: 22px;
        background: linear-gradient(120deg, #12355c 0%, #174f86 52%, #1d65a6 100%);
        box-shadow: 0 18px 48px rgba(18, 53, 92, .18);
    }

    .jasa-form-hero::before {
        content: "";
        position: absolute;
        width: 360px;
        height: 360px;
        right: 8%;
        top: -190px;
        border-radius: 999px;
        background: radial-gradient(circle, rgba(125, 211, 252, .28), rgba(59, 130, 246, .18) 48%, transparent 70%);
        pointer-events: none;
    }

    .jasa-form-hero,
    .jasa-form-hero h4,
    .jasa-form-hero p {
        color: #fff !important;
    }

    .jasa-form-hero p {
        opacity: .82;
    }

    .jasa-form-card {
        border: 1px solid rgba(37, 99, 235, .12);
        border-radius: 20px;
        background: #fff;
        box-shadow: 0 18px 46px rgba(15, 23, 42, .08);
        overflow: hidden;
    }

    .jasa-form-section {
        border-bottom: 1px solid #e2e8f0;
        padding: 22px 24px;
    }

    .jasa-form-section:last-child {
        border-bottom: 0;
    }

    .jasa-section-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 16px;
    }

    .jasa-section-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        flex: 0 0 38px;
        border-radius: 12px;
        background: #1d4ed8;
        color: #fff;
        box-shadow: 0 12px 24px rgba(37, 99, 235, .18);
    }

    .jasa-section-title h6 {
        margin: 0;
        color: #1e3a8a;
        font-weight: 900;
    }

    .jasa-section-title p {
        margin: 0;
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
    }

    .jasa-form-card .form-label {
        color: #334155;
        font-size: 13px;
        font-weight: 800;
        margin-bottom: 6px;
    }

    .jasa-form-card .form-control,
    .jasa-form-card .form-select,
    .jasa-form-card .select2-selection {
        border-color: #dbe3ef !important;
        border-radius: 13px !important;
        min-height: 42px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .03);
    }

    .jasa-form-card .form-control:focus,
    .jasa-form-card .form-select:focus {
        border-color: #2563eb !important;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .12) !important;
    }

    .jasa-helper-panel,
    .jasa-live-preview,
    .jasa-check-card {
        border: 1px solid #bfdbfe;
        border-radius: 16px;
        background: linear-gradient(135deg, #eff6ff 0%, #f8fbff 100%);
        color: #475569;
        padding: 14px;
        height: 100%;
    }

    .jasa-helper-panel strong,
    .jasa-live-preview strong {
        color: #1e3a8a;
    }

    .jasa-action-footer {
        background: #f8fafc;
        padding: 18px 24px;
    }

    @media (max-width: 768px) {
        .jasa-form-section,
        .jasa-action-footer {
            padding: 18px 16px;
        }
    }
</style>
@endpush
