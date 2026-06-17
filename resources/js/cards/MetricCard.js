export function MetricCard({
    title,
    value,
    icon = "",
    subtitle = ""
}) {
    return `
        <div class="metric-card">
            <div class="metric-card-header">
                <span>${icon}</span>
                <span>${title}</span>
            </div>

            <div class="metric-card-value">
                ${value}
            </div>

            <div class="metric-card-subtitle">
                ${subtitle}
            </div>
        </div>
    `;
}