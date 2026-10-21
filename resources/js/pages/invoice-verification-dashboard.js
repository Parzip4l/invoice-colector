import ApexCharts from "apexcharts";

const analytics = window.invoiceDashboardAnalytics || {};

// Soft, modern, refined palette matching the pipeline status tones (NO GRADIENTS)
const palette = {
    red: "#e21a1a",      // LRT Red (Primary brand dominant)
    emerald: "#059669",  // Soft Emerald (Completed)
    amber: "#d97706",    // Soft Amber (In Review)
    rose: "#be123c",     // Soft Deep Crimson (Not Approved)
    violet: "#7c3aed",   // Soft Lavender/Violet (Received)
    slate: "#64748b",    // Soft Slate (Draft)
    muted: "#94a3b8",    // Soft Muted Text
};

const chartDefaults = {
    fontFamily: "inherit",
    toolbar: { show: false },
    zoom: { enabled: false },
};

function labels(items) {
    return (items || []).map((item) => item.label);
}

function values(items) {
    return (items || []).map((item) => Number(item.value || 0));
}

function rupiah(value) {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(Number(value || 0));
}

function renderChart(selector, options) {
    const target = document.querySelector(selector);

    if (!target) {
        return;
    }

    new ApexCharts(target, options).render();
}

function renderNoData(selector, message = "Belum ada data.") {
    const target = document.querySelector(selector);

    if (!target) {
        return;
    }

    target.classList.add("d-flex", "align-items-center", "justify-content-center", "text-muted");
    target.innerHTML = `<div class="small fw-medium">${message}</div>`;
}

// 1. Transaction Trend Chart (Clean Area Chart with Solid Tint, NO Gradient)
renderChart("#iv-transaction-trend", {
    chart: {
        ...chartDefaults,
        type: "area",
        height: 236,
    },
    series: [
        {
            name: "Transaksi",
            data: values(analytics.trend),
        },
    ],
    colors: [palette.red],
    dataLabels: { enabled: false },
    stroke: {
        curve: "smooth",
        width: 2.2,
    },
    fill: {
        type: "solid",
        opacity: 0.07,
    },
    markers: {
        size: 3.5,
        strokeWidth: 2,
        strokeColors: "#ffffff",
        colors: [palette.red],
    },
    grid: {
        borderColor: "rgba(100, 116, 139, .08)",
        strokeDashArray: 3,
    },
    xaxis: {
        categories: labels(analytics.trend),
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { style: { colors: palette.muted, fontSize: "11px" } },
    },
    yaxis: {
        labels: { style: { colors: palette.muted, fontSize: "11px" } },
    },
    tooltip: {
        theme: "light",
        y: {
            formatter: (value) => `${value} transaksi`,
        },
    },
});

// 2. Status Donut Chart (Harmonious Status Colors)
if (values(analytics.status_distribution).some((value) => value > 0)) {
    renderChart("#iv-status-donut", {
        chart: {
            ...chartDefaults,
            type: "donut",
            height: 236,
        },
        series: values(analytics.status_distribution),
        labels: labels(analytics.status_distribution),
        colors: [palette.slate, palette.red, palette.amber, palette.rose, palette.violet, palette.emerald],
        stroke: {
            width: 2.5,
            colors: ["#ffffff"],
        },
        plotOptions: {
            pie: {
                donut: {
                    size: "70%",
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: "Total Trx",
                            fontSize: "11.5px",
                            fontWeight: 600,
                            color: palette.muted,
                            formatter: (chart) => chart.globals.seriesTotals.reduce((total, value) => total + value, 0),
                        },
                        value: {
                            fontSize: "17px",
                            fontWeight: 700,
                            color: "#1e293b",
                        },
                    },
                },
            },
        },
        legend: {
            position: "bottom",
            fontSize: "11px",
            markers: { radius: 6 },
            itemMargin: { horizontal: 5, vertical: 2 },
        },
        dataLabels: { enabled: false },
    });
} else {
    renderNoData("#iv-status-donut", "Belum ada transaksi.");
}

// 3. Amount Chart Function
function renderAmountChart(selector, items, title, color, type = "bar") {
    renderChart(selector, {
        chart: {
            ...chartDefaults,
            type,
            height: 236,
        },
        series: [
            {
                name: title,
                data: values(items),
            },
        ],
        colors: [color],
        plotOptions: {
            bar: {
                borderRadius: 5,
                columnWidth: "40%",
            },
        },
        dataLabels: { enabled: false },
        stroke: type === "line" ? { curve: "smooth", width: 2.2 } : undefined,
        grid: {
            borderColor: "rgba(100, 116, 139, .08)",
            strokeDashArray: 3,
        },
        xaxis: {
            categories: labels(items),
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: {
                rotate: -20,
                style: { colors: palette.muted, fontSize: "11px" },
            },
        },
        yaxis: {
            labels: {
                style: { colors: palette.muted, fontSize: "11px" },
                formatter: (value) => {
                    if (value >= 1000000000) return `${Math.round(value / 1000000000)}M`;
                    if (value >= 1000000) return `${Math.round(value / 1000000)}jt`;
                    if (value >= 1000) return `${Math.round(value / 1000)}rb`;

                    return `${value}`;
                },
            },
        },
        tooltip: {
            y: {
                formatter: (value) => rupiah(value),
            },
        },
    });
}

renderAmountChart("#iv-amount-weekly", analytics.amount_weekly, "Nominal Mingguan", palette.red);
renderAmountChart("#iv-amount-monthly", analytics.amount_monthly, "Nominal Bulanan", palette.red, "line");
renderAmountChart("#iv-amount-yearly", analytics.amount_yearly, "Nominal Tahunan", palette.emerald);

renderChart("#iv-top-vendors", {
    chart: {
        ...chartDefaults,
        type: "bar",
        height: 236,
    },
    series: [
        {
            name: "Transaksi",
            data: values(analytics.top_vendors),
        },
    ],
    colors: [palette.emerald],
    plotOptions: {
        bar: {
            horizontal: true,
            borderRadius: 5,
            barHeight: "50%",
        },
    },
    dataLabels: { enabled: false },
    grid: {
        borderColor: "rgba(100, 116, 139, .08)",
        strokeDashArray: 3,
    },
    xaxis: {
        labels: { style: { colors: palette.muted, fontSize: "11px" } },
    },
    yaxis: {
        categories: labels(analytics.top_vendors),
        labels: {
            maxWidth: 160,
            style: { colors: palette.muted, fontSize: "11px" },
        },
    },
    tooltip: {
        y: {
            formatter: (value) => `${value} transaksi`,
        },
    },
});
