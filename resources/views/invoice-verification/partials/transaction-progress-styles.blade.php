<style>
    .iv-transaction-progress {
        --iv-progress-color: #ffb600;
        --iv-progress-soft: #fff8e8;
        display: grid;
        grid-template-columns: 36px minmax(0, 1fr);
        gap: .55rem;
        width: min(100%, 380px);
        min-width: 320px;
        padding: .1rem 0;
    }

    .iv-transaction-progress.is-success {
        --iv-progress-color: #22a559;
        --iv-progress-soft: #ebf8f0;
    }

    .iv-transaction-progress.is-danger {
        --iv-progress-color: #e5484d;
        --iv-progress-soft: #fff0f0;
    }

    .iv-transaction-progress__icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 10px;
        color: var(--iv-progress-color);
        background: var(--iv-progress-soft);
        font-size: 1.08rem;
    }

    .iv-transaction-progress__content {
        min-width: 0;
    }

    .iv-transaction-progress__heading {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: .5rem;
    }

    .iv-transaction-progress__title {
        overflow: hidden;
        color: #343a40;
        font-size: .78rem;
        font-weight: 700;
        line-height: 1.25;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .iv-transaction-progress__stage {
        flex: 0 0 auto;
        color: var(--iv-progress-color);
        font-size: .67rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .iv-transaction-progress__description {
        overflow: hidden;
        margin: .08rem 0 .32rem;
        color: #8a8a8a;
        font-size: .67rem;
        line-height: 1.25;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .iv-transaction-progress__track {
        position: relative;
        overflow: hidden;
        height: 5px;
        border-radius: 999px;
        background: #eef0f3;
    }

    .iv-transaction-progress__bar {
        position: absolute;
        inset: 0 auto 0 0;
        border-radius: inherit;
        background: var(--iv-progress-color);
    }
</style>
