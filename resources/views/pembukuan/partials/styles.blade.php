@push('css')
<style>
    .book-hero {
        background: linear-gradient(135deg, #f8fbff, #eef4ff);
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 1.25rem;
    }

    .book-card {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1rem;
        box-shadow: 0 0.35rem 1rem rgba(15, 23, 42, 0.04);
        overflow: hidden;
        background: #fff;
    }

    .book-card .card-header {
        background: rgba(248, 250, 252, 0.9);
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
    }

    .book-summary {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1rem;
        padding: 1rem 1.1rem;
        background: #fff;
        height: 100%;
    }

    .book-summary .label {
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #64748b;
        font-weight: 700;
        margin-bottom: .35rem;
    }

    .book-summary .value {
        font-size: 1.35rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.15;
    }

    .book-filter {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1rem;
        background: #fff;
        padding: 1rem 1.25rem;
        margin-bottom: 1.25rem;
    }

    .book-table th {
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748b;
        white-space: nowrap;
    }

    .book-table td {
        vertical-align: middle;
        font-size: .9rem;
    }

    .book-meta {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: .9rem;
    }

    .book-meta-item {
        background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: .9rem;
        padding: .9rem 1rem;
    }

    .book-meta-item .meta-label {
        font-size: .72rem;
        text-transform: uppercase;
        color: #64748b;
        letter-spacing: .05em;
        font-weight: 700;
        margin-bottom: .3rem;
    }

    .book-meta-item .meta-value {
        font-weight: 700;
        color: #0f172a;
        line-height: 1.35;
    }

    .book-empty {
        text-align: center;
        color: #64748b;
        padding: 3rem 1rem;
    }

    .book-empty i {
        font-size: 2.4rem;
        opacity: .3;
        display: block;
        margin-bottom: .75rem;
    }

    .book-timeline {
        position: relative;
        padding-left: 1.4rem;
    }

    .book-timeline::before {
        content: "";
        position: absolute;
        left: .4rem;
        top: .25rem;
        bottom: .25rem;
        width: 2px;
        background: #e2e8f0;
    }

    .book-timeline-item {
        position: relative;
        padding-bottom: 1rem;
    }

    .book-timeline-item:last-child {
        padding-bottom: 0;
    }

    .book-timeline-dot {
        position: absolute;
        left: -1.15rem;
        top: .32rem;
        width: .7rem;
        height: .7rem;
        border-radius: 999px;
        background: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }
</style>
@endpush
