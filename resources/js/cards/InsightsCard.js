export function InsightCard(insight) {
    return `
        <div class="insight-card">

            <h3>
                AI Insight
            </h3>

            <p>
                ${insight.summary}
            </p>

        </div>
    `;
}