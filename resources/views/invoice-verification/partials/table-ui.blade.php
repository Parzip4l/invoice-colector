@once
<style>
    .iv-table-card {
        border: 0;
        box-shadow: 0 .125rem .5rem rgba(15, 23, 42, .06);
    }

    .iv-table-toolbar {
        display: grid;
        grid-template-columns: minmax(260px, 1fr) repeat(var(--iv-filter-count, 2), minmax(170px, auto)) auto auto;
        gap: .75rem;
        align-items: end;
    }

    .iv-table-toolbar .form-label {
        font-size: .75rem;
        margin-bottom: .25rem;
        color: #64748b;
    }

    .iv-table {
        min-width: var(--iv-table-min-width, 1080px);
    }

    .iv-table th {
        color: #64748b;
        font-size: .76rem;
        letter-spacing: .02em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .iv-table td {
        vertical-align: middle;
        padding-top: .62rem;
        padding-bottom: .62rem;
        line-height: 1.25;
    }

    .iv-sort-link {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        color: inherit;
        text-decoration: none;
    }

    .iv-sort-link:hover {
        color: var(--bs-primary);
    }

    .iv-cell-truncate {
        max-width: var(--iv-cell-width, 280px);
    }

    .iv-actions {
        display: inline-flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: .4rem;
    }

    .iv-table-card .pagination {
        margin-bottom: 0;
        flex-wrap: wrap;
        gap: .25rem;
    }

    .iv-table-card .page-link {
        min-width: 2.25rem;
        border-radius: .5rem;
        text-align: center;
    }

    @media (max-width: 1199.98px) {
        .iv-table-toolbar {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 767.98px) {
        .iv-table-toolbar {
            grid-template-columns: 1fr;
        }

        .iv-actions {
            justify-content: flex-start;
        }
    }
</style>
@endonce
