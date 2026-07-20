import ApexCharts from "apexcharts";

const analytics = window.invoiceDashboardAnalytics || {};

const palette = {
    primary: "#e21a1a",
    success: "#16a34a",
    warning: "#c07f20",
    danger: "#ef4444",
    info: "#06b6d4",
    muted: "#64748b",
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

renderChart("#iv-transaction-trend", {
    chart: {
        ...chartDefaults,
        type: "area",
        height: 288,
    },
    series: [
        {
            name: "Transaksi",
            data: values(analytics.trend),
        },
    ],
    colors: [palette.primary],
    dataLabels: { enabled: false },
    stroke: {
        curve: "smooth",
        width: 3,
    },
    fill: {
        type: "gradient",
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.28,
            opacityTo: 0.04,
            stops: [0, 92, 100],
        },
    },
    markers: {
        size: 4,
        strokeWidth: 3,
        strokeColors: "#ffffff",
        colors: [palette.primary],
    },
    grid: {
        borderColor: "rgba(100, 116, 139, .16)",
        strokeDashArray: 4,
    },
    xaxis: {
        categories: labels(analytics.trend),
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { style: { colors: palette.muted } },
    },
    yaxis: {
        labels: { style: { colors: palette.muted } },
    },
    tooltip: {
        theme: "light",
        y: {
            formatter: (value) => `${value} transaksi`,
        },
    },
});

renderChart("#iv-status-donut", {
    chart: {
        ...chartDefaults,
        type: "donut",
        height: 288,
    },
    series: values(analytics.status_distribution),
    labels: labels(analytics.status_distribution),
    colors: [palette.primary, palette.warning, palette.info, palette.success, palette.danger, "#8b5cf6"],
    stroke: {
        width: 3,
        colors: ["#ffffff"],
    },
    plotOptions: {
        pie: {
            donut: {
                size: "68%",
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: "Total",
                        formatter: (chart) => chart.globals.seriesTotals.reduce((total, value) => total + value, 0),
                    },
                },
            },
        },
    },
    legend: {
        position: "bottom",
        fontSize: "12px",
        markers: { radius: 8 },
    },
    dataLabels: { enabled: false },
});

function renderAmountChart(selector, items, title, color, type = "bar") {
    renderChart(selector, {
        chart: {
            ...chartDefaults,
            type,
            height: 290,
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
                borderRadius: 7,
                columnWidth: "48%",
            },
        },
        dataLabels: { enabled: false },
        stroke: type === "line" ? { curve: "smooth", width: 3 } : undefined,
        grid: {
            borderColor: "rgba(100, 116, 139, .16)",
            strokeDashArray: 4,
        },
        xaxis: {
            categories: labels(items),
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: {
                rotate: -20,
                style: { colors: palette.muted },
            },
        },
        yaxis: {
            labels: {
                style: { colors: palette.muted },
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

renderAmountChart("#iv-amount-weekly", analytics.amount_weekly, "Nominal Mingguan", palette.warning);
renderAmountChart("#iv-amount-monthly", analytics.amount_monthly, "Nominal Bulanan", palette.primary, "line");
renderAmountChart("#iv-amount-yearly", analytics.amount_yearly, "Nominal Tahunan", palette.success);

renderChart("#iv-top-vendors", {
    chart: {
        ...chartDefaults,
        type: "bar",
        height: 290,
    },
    series: [
        {
            name: "Transaksi",
            data: values(analytics.top_vendors),
        },
    ],
    colors: [palette.success],
    plotOptions: {
        bar: {
            horizontal: true,
            borderRadius: 7,
            barHeight: "52%",
        },
    },
    dataLabels: { enabled: false },
    grid: {
        borderColor: "rgba(100, 116, 139, .16)",
        strokeDashArray: 4,
    },
    xaxis: {
        labels: { style: { colors: palette.muted } },
    },
    yaxis: {
        categories: labels(analytics.top_vendors),
        labels: {
            maxWidth: 160,
            style: { colors: palette.muted },
        },
    },
    tooltip: {
        y: {
            formatter: (value) => `${value} transaksi`,
        },
    },
});
