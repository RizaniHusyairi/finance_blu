<style>
    .sa-report-page {
        color: #0f172a;
    }
    .sa-report-hero {
        position: relative;
        overflow: hidden;
        border-radius: 18px;
        padding: 18px 20px;
        background: linear-gradient(135deg, #0f2f57, #1d4ed8 62%, #38bdf8);
        box-shadow: 0 18px 42px rgba(15, 47, 87, .18);
    }
    .sa-report-hero::after {
        content: "";
        position: absolute;
        inset: auto -80px -120px auto;
        width: 260px;
        height: 260px;
        border-radius: 999px;
        background: rgba(251, 191, 36, .22);
    }
    .sa-report-hero > * {
        position: relative;
        z-index: 1;
    }
    .sa-report-hero h4 {
        color: #fff;
    }
    .sa-report-hero p {
        color: rgba(255, 255, 255, .78) !important;
        font-weight: 600;
    }
    .sa-report-hero i {
        color: #fbbf24 !important;
    }
    .sa-report-page .card.border-0.shadow-sm {
        border: 1px solid rgba(15, 23, 42, .06) !important;
        border-radius: 18px !important;
        box-shadow: 0 14px 32px rgba(15, 23, 42, .08) !important;
    }
    .sa-report-page .card-header {
        padding: 18px 20px 12px;
        border-bottom: 1px solid rgba(15, 23, 42, .06) !important;
    }
    .sa-report-page .card-header h6 {
        color: #0f2f57;
        font-weight: 800;
    }
    .sa-report-page .card-body {
        padding: 18px 20px;
    }
    .sa-report-page .small.text-uppercase.text-muted.fw-bold {
        letter-spacing: .04em;
        color: #64748b !important;
    }
    .sa-report-page .fs-3,
    .sa-report-page .fs-4 {
        letter-spacing: 0;
    }
    .sa-report-page .table {
        --bs-table-hover-bg: #f8fafc;
    }
    .sa-report-page .table thead.table-light th {
        background: #f8fafc;
        color: #475569;
        border-bottom: 1px solid #e2e8f0;
        font-weight: 800;
        letter-spacing: .04em;
    }
    .sa-report-page .table td {
        border-color: #eef2f7;
        color: #334155;
    }
    .sa-report-page .table tbody tr:hover td {
        color: #0f172a;
    }
    .sa-report-page .badge {
        border-radius: 999px;
        padding: .42rem .65rem;
        font-weight: 800;
    }
    .sa-report-filter .form-select,
    .sa-report-filter .form-control {
        border-color: #e2e8f0;
        border-radius: 12px;
        font-weight: 700;
    }
    .sa-report-filter .form-select:focus,
    .sa-report-filter .form-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .12);
    }
    .sa-report-months {
        padding: 12px;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #f8fafc;
    }
    .sa-report-months .btn {
        border-radius: 999px;
    }
</style>
